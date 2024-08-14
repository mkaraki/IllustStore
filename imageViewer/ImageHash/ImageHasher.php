<?php
/*
 * This source code contains following codes:
 *
 * - JohannesBuchner/imagehash (BSG 2-Clause "Simplified" License)
 * Copyright (c) 2013-2022, Johannes Buchner
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 *     Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 *     Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * - jenssegers/imagehash (MIT License)
 * MIT License
 *
 * Copyright (c) 2020 Jens Segers
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace mkaraki\ImageHash;

class ImageHasher
{
    private function average(array $ary): float
    {
        return (float)array_sum($ary) / (float)count($ary);
    }

    public function average_hash(
        \Imagick $image,
        int $hash_size = 8,
        int $image_resample_filter = \imagick::FILTER_LANCZOS,
        float $image_resample_blur = 1.0
    ): ImageHash
    {
        $proc_img = clone $image;
        $proc_img->transformImageColorspace(\imagick::COLORSPACE_GRAY);
        $proc_img->resizeImage($hash_size, $hash_size, $image_resample_filter, $image_resample_blur);

        $img_ary = $proc_img->exportImagePixels(
            0, 0,
            $hash_size, $hash_size,
            "R", \Imagick::PIXEL_CHAR
        );

        $avg = $this->average($img_ary);

        $hash_bits = [];
        for ($i = 0; $i < count($img_ary); $i++) {
            $hash_bits[] = $img_ary[$i] > $avg ? 1 : 0;
        }

        return new ImageHash($hash_bits);
    }

    public function difference_hash(
        \Imagick $image,
        int $hash_size = 8,
        int $image_resample_filter = \imagick::FILTER_LANCZOS,
        float $image_resample_blur = 1.0
    ): ImageHash
    {
        $width = $hash_size + 1;
        $height = $hash_size;

        $proc_img = clone $image;
        $proc_img->transformImageColorspace(\imagick::COLORSPACE_GRAY);
        $proc_img->resizeImage($width, $height, $image_resample_filter, $image_resample_blur);

        $img_ary = $proc_img->exportImagePixels(
            0, 0,
            $width, $height,
            "R", \Imagick::PIXEL_CHAR
        );

        $hash_bits = [];
        for ($y = 0; $y < $height; $y++) {
            $left = $img_ary[$y * $width];

            for ($x = 1; $x < $width; $x++) {
                $right = $img_ary[($y * $width) + $x];

                $hash_bits[] = $left < $right ? 1 : 0;

                $left = $right;
            }
        }

        return new ImageHash($hash_bits);
    }

    // Impl of type II of https://docs.scipy.org/doc/scipy/reference/generated/scipy.fftpack.dct.html
    protected function calculateDCT(array $x): array
    {
        $N = count($x);

        $y = [];

        for ($k = 0.0; $k < $N; $k++) {
            $sum = 0;
            for ($n = 0.0; $n < $N; $n++) {
                $sum +=
                    ((float)$x[$n]) *
                    cos(
                        (pi() * $k * ((2.0 * $n) + 1.0))
                            /
                        (2.0 * $N)
                    );
            }
            $y[$k] = 2 * $sum;
        }

        return $y;
    }

    protected function median(array $pixels): float
    {
        sort($pixels, SORT_NUMERIC);

        if (count($pixels) % 2 === 0) {
            return (
                ((float)(
                    $pixels[count($pixels) / 2 - 1] +
                    $pixels[count($pixels) / 2]
                )) / 2.0);
        }

        return $pixels[count($pixels) / 2];
    }

    public function perceptual_hash(
        \Imagick $image,
        int $hash_size = 8,
        int $highfreq_factor = 4,
        int $image_resample_filter = \imagick::FILTER_LANCZOS, // Should FILTER_LANCZOS but that takes too much time.
        float $image_resample_blur = 1.0
    ): ImageHash
    {
        $img_size = $hash_size * $highfreq_factor;

        $proc_img = clone $image;
        $proc_img->transformImageColorspace(\imagick::COLORSPACE_GRAY);
        $proc_img->resizeImage($img_size, $img_size, $image_resample_filter, $image_resample_blur);

        $img_ary = $proc_img->exportImagePixels(
            0, 0,
            $img_size, $img_size,
            "R", \Imagick::PIXEL_CHAR
        );

        $rows = [];
        $matrix = [];

        for ($y = 0; $y < $img_size; $y++) {
            $row = [];
            for ($x = 0; $x < $img_size; $x++) {
                $row[$x] = $img_ary[($y * $img_size) + $x];
            }
            $rows[$y] = $this->calculateDCT($row);
        }

        for ($x = 0; $x < $img_size; $x++) {
            $col = [];
            for ($y = 0; $y < $img_size; $y++) {
                $col[$y] = $rows[$y][$x];
            }
            $matrix[$x] = $this->calculateDCT($col);
        }

        $pixels = [];
        for ($y = 0; $y < $hash_size; $y ++)
        {
            for ($x = 0; $x < $hash_size; $x ++)
            {
                $pixels[] = $matrix[$y][$x];
            }
        }

        $cmp = $this->median($pixels);

        $bits = [];
        foreach ($pixels as $pixel) {
            $bits[] = $pixel > $cmp ? 1 : 0;
        }

        return new ImageHash($bits);
    }
}

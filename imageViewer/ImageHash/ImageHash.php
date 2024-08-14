<?php

namespace mkaraki\ImageHash;

class ImageHash
{
    private string $bits;

    public function __construct(array $bit_array) {
        $this->bits = implode('', $bit_array);
    }

    public function hex(): string {
        $hex = '';
        for ($i = 0; $i < strlen($this->bits); $i += 4) {
            $binstr = substr($this->bits, $i, 4);
            $hex .= dechex(bindec($binstr));
        }
        return $hex;
    }
}
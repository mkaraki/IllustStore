import unittest
import os

import numpy
from PIL import Image

from webp_utils import WebPUtils
from PIL import ImageChops


class WebpUtilsTestCase(unittest.TestCase):
    def test_compress_and_decompress(self):
        rnd_img = numpy.random.rand(1920, 1920, 4) * 255

        with open('test.png', 'wb') as f:
            im = Image.fromarray(rnd_img.astype('uint8')).convert('RGBA')
            im.save(f)

        try:
            # For type conversion
            rnd_img = Image.open('test.png')

            WebPUtils.convert_to_lossless_webp('test.png', 'test.webp')
        except:
            self.fail('Exception threw in encode webp')
        finally:
            if os.path.exists('test.png'):
                os.remove('test.png')

        try:
            assert_img = Image.open('test.webp')

            img_diff = ImageChops.difference(rnd_img, assert_img)
            self.assertFalse(img_diff.getbbox(), 'Image not equal')
        except:
            self.fail('Exception threw in decode output webp')
        finally:
            if os.path.exists('test.webp'):
                os.remove('test.webp')


if __name__ == '__main__':
    unittest.main()

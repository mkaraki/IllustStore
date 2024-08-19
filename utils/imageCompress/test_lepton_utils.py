import os
import unittest
import numpy
from PIL import Image
from PIL import ImageChops

from lepton_utils import LeptonConvert

class LeptonConvertTestCase(unittest.TestCase):
    def test_compress_and_decompress_with_files(self):
        lu = LeptonConvert()

        rnd_img = numpy.random.rand(1920, 1920, 3) * 255

        with open('test.jpg', 'wb') as f:
            im = Image.fromarray(rnd_img.astype('uint8')).convert('RGB')
            im.save(f)

        try:
            # Due to jpeg is not lossless.
            rnd_img = Image.open('test.jpg')

            res = lu.convert_to_lepton('test.jpg', 'test.lep')
            self.assertEqual(res, 0, 'Jpeg to lepton conversion returns not 0 (OK)')
        except:
            self.fail('Exception threw')
        finally:
            if os.path.exists('test.jpg'):
                os.remove('test.jpg')

        try:
            res = lu.convert_to_jpeg('test.lep', 'test.jpg')
            if not res:
                self.fail('Convert to jpeg from lepton failed.')
        except:
            self.fail('Exception threw in decode lepton')
        finally:
            if os.path.exists('test.lep'):
                os.remove('test.lep')

        if os.path.exists('test.jpg'):
            try:
                actual_img = Image.open('test.jpg')

                img_diff = ImageChops.difference(rnd_img, actual_img)
                self.assertFalse(img_diff.getbbox(), 'Image not equal')
            except:
                self.fail('Exception threw in decoded jpeg file')
            finally:
                os.remove('test.jpg')
        else:
            self.fail('Decompressed image not found')


if __name__ == '__main__':
    unittest.main()

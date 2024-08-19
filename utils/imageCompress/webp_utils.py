import os

import webp
from PIL import Image


class WebPUtils:

    def __init__(self):
        pass

    @staticmethod
    def verify_webp_and_other(webp_file, other_file):
        webp_data = webp.load_image(webp_file, 'RGBA')
        other_data = Image.open(other_file).convert('RGBA')

        return webp_data == other_data

    @staticmethod
    def select_webp_or_other(webp_file, other_file):
        webp_size = os.path.getsize(webp_file)
        other_size = os.path.getsize(other_file)

        if webp_size < other_size:
            return 'webp'
        else:
            return 'other'

    @staticmethod
    def convert_to_lossless_webp(image_path, output_path):
        orig_img = Image.open(image_path)

        webp.save_image(
            orig_img,
            output_path,
            lossless=True,
            method=6,
        )

    @staticmethod
    def try_convert_webp(image_path, output_path):
        try:
            WebPUtils.convert_to_lossless_webp(image_path, output_path)

            verify = WebPUtils.verify_webp_and_other(image_path, output_path)

            if not verify:
                os.remove(image_path)
                return False

            sel = WebPUtils.select_webp_or_other(image_path, output_path)

            if sel != 'webp':
                os.remove(image_path)
                return False

            return True

        except:
            if os.path.exists(output_path):
                os.remove(output_path)
            return False

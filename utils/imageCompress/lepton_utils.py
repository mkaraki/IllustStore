from ctypes import *
import os
import io

from PIL import Image

class LeptonConvert:

    def __init__(self):
        self.lib = cdll.LoadLibrary("liblepton_jpeg.so")
        self.lib.WrapperDecompressImage.argtypes = [POINTER(c_uint8), c_uint64, POINTER(c_uint8), c_uint64, c_int32, POINTER(c_uint64)]
        self.lib.WrapperDecompressImage.restype = c_int32

        self.lib.WrapperCompressImage.argtypes = [POINTER(c_uint8), c_uint64, POINTER(c_uint8), c_uint64, c_int32, POINTER(c_uint64)]
        self.lib.WrapperCompressImage.restype = c_int32

    def _save_uint8_array_to_path(self, file_path, ary, ary_len):
        try:
            with open(file_path, "wb") as f:
                for i in range(ary_len):
                    f.write(bytes([ary[i]]))
        except Exception as e:
            print(f"Failed to save file at {file_path}: {e}")

    def _load_lepton_from_uint8_array(self, raw_data, raw_data_size) -> bytes|None:
        res_size = c_uint64(0)
        img = (c_uint8 * (raw_data_size * 3))()

        ret = self.lib.WrapperDecompressImage(raw_data, raw_data_size, img, len(img), 2, byref(res_size))
        if ret != 0:
            return None

        img = img[:res_size.value]

        return bytes(img)

    def load_lepton_from_path(self, file_path) -> bytes|None:
        raw_size = os.path.getsize(file_path)
        raw_data = (c_uint8 * raw_size)()

        with open(file_path, "rb") as f:
            for i in range(raw_size):
                raw_data[i] = ord(f.read(1))

        jpeg_data = self._load_lepton_from_uint8_array(raw_data, raw_size)

        return jpeg_data

    def compare_lepton_and_other(self, lepton_file, other_file):
        lepton_data = self.load_lepton_from_path(lepton_file)
        lepton_data = Image.Open(io.BytesIO(lepton_data)).convert('RGB')
        other_data = Image.Open(other_file).convert('RGB')

        return lepton_data == other_data
    
    def select_lepton_or_other(self, lepton_file, other_file):
        lepton_size = os.path.getsize(lepton_file)
        other_size = os.path.getsize(other_file)

        if lepton_size < other_size:
            return 'lepton'
        else:
            return 'other'

    def convert_to_lepton(self, image_path, output_path) -> int:
        if not os.path.exists(image_path):
            return False
        raw_data_size= os.path.getsize(image_path)
        raw_data = (c_uint8 * (raw_data_size))()

        with open(image_path, "rb") as f:
            for i in range(raw_data_size):
                raw_data[i] = ord(f.read(1))

        img = (c_uint8 * (raw_data_size * 2))()

        res_size = c_uint64(0)

        ret = self.lib.WrapperCompressImage(raw_data, raw_data_size, img, len(img), 2, byref(res_size))

        if ret != 0:
            return ret

        self._save_uint8_array_to_path(output_path, img, res_size.value)

        return 0

    def convert_to_jpeg(self, image_path, output_path) -> bool:
        jpg_raw = self.load_lepton_from_path(image_path)

        if jpg_raw is None:
            return False

        self._save_uint8_array_to_path(output_path, jpg_raw, len(jpg_raw))

        return True

class LeptonUtils:

    def __init__(self):
        pass

    @staticmethod
    def verify_lepton_and_other(lepton_file, other_file):
        lc = LeptonConvert()
        lepton_data = Image.open(io.BytesIO(lc.load_lepton_from_path(lepton_file))).convert('RGB')
        jpeg_data = Image.open(other_file).convert('RGB')

        return lepton_data == jpeg_data

    @staticmethod
    def select_lepton_or_other(lepton_file, other_file):
        lepton_size = os.path.getsize(lepton_file)
        other_size = os.path.getsize(other_file)

        if lepton_size < other_size:
            return 'lepton'
        else:
            return 'other'

    @staticmethod
    def convert_to_lepton(lepton_path, output_path):
        lc = LeptonConvert()
        lc.convert_to_lepton(lepton_path, output_path)

    @staticmethod
    def try_convert_lepton(lepton_path, output_path):
        try:
            LeptonUtils.convert_to_lepton(lepton_path, output_path)

            verify = LeptonUtils.verify_lepton_and_other(lepton_path, output_path)

            if not verify:
                os.remove(lepton_path)
                return

            sel = LeptonUtils.select_lepton_or_other(lepton_path, output_path)

            if sel != 'lepton':
                os.remove(lepton_path)
                return False

            return True

        except:
            if os.path.exists(lepton_path):
                os.remove(lepton_path)
            return False
from ctypes import *
import os

class LeptonConvert:

    def __init__(self):
        self.lib = cdll.LoadLibrary("liblepton_jpeg.so")
        self.lib.WrapperDecompressImage.argtypes = [POINTER(c_uint8), c_uint64, POINTER(c_uint8), c_uint64, c_int32, POINTER(c_uint64)]
        self.lib.WrapperDecompressImage.restype = None

    def save_uint8_array_to_path(self, file_path, ary, ary_len):
        try:
            with open(file_path, "wb") as f:
                for i in range(ary_len):
                    f.write(bytes([ary[i]]))
        except Exception as e:
            print(f"Failed to save file at {file_path}: {e}")

    def load_lepton_from_uint8_array(self, raw_data, raw_data_size):
        res_size = c_uint64(0)
        img = (c_uint8 * (raw_data_size * 2))()

        self.lib.WrapperDecompressImage(raw_data, raw_data_size, img, len(img), 2, byref(res_size))

        img = img[:res_size.value]

        return img
    
    def load_lepton_from_path(self, file_path):
        raw_size = os.path.getsize(file_path)
        raw_data = (c_uint8 * raw_size)()

        with open(file_path, "rb") as f:
            f.readinto(raw_data)
        
        jpeg_data = self.load_lepton_from_uint8_array(raw_data, raw_size)

        return jpeg_data

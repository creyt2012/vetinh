import ctypes
import os
import numpy as np
from pydantic import BaseModel

# Define the C++ struct in Python
class SatelliteMetrics(ctypes.Structure):
    _fields_ = [("mean_brightness", ctypes.c_float),
                ("cloud_coverage_pct", ctypes.c_float),
                ("estimated_temperature_k", ctypes.c_float),
                ("pressure_proxy_hpa", ctypes.c_float)]

class HPCBridge:
    def __init__(self):
        # Load the compiled C++ shared library
        lib_path = os.path.join(os.path.dirname(__file__), "cpp_extensions", "src", "libimage_processor.so")
        if not os.path.exists(lib_path):
            # Fallback for MacOS / Windows extensions
            lib_path = lib_path.replace(".so", ".dylib")
            if not os.path.exists(lib_path):
                raise FileNotFoundError(f"HPC C++ Library not found at {lib_path}")

        self.lib = ctypes.CDLL(lib_path)
        
        # Define argument and return types for the C++ function
        self.lib.process_imagery_hpc.argtypes = [
            ctypes.POINTER(ctypes.c_uint8),  # img_data
            ctypes.c_int,                    # width
            ctypes.c_int,                    # height
            ctypes.c_int                     # channels
        ]
        self.lib.process_imagery_hpc.restype = SatelliteMetrics

    def analyze_image(self, image_array: np.ndarray) -> dict:
        """
        Pass a NumPy array directly to C++ memory for zero-copy HPC Processing
        """
        if not image_array.flags['C_CONTIGUOUS']:
            image_array = np.ascontiguousarray(image_array)

        height, width = image_array.shape[:2]
        channels = image_array.shape[2] if len(image_array.shape) > 2 else 1

        # Get C pointer to the numpy array data
        data_ptr = image_array.ctypes.data_as(ctypes.POINTER(ctypes.c_uint8))

        # Call C++ function
        metrics = self.lib.process_imagery_hpc(data_ptr, width, height, channels)

        # Return as standard Python dict
        return {
            "mean_brightness": float(metrics.mean_brightness),
            "cloud_coverage_pct": float(metrics.cloud_coverage_pct),
            "temperature_c": round(float(metrics.estimated_temperature_k - 273.15), 1),
            "pressure_hpa": round(float(metrics.pressure_proxy_hpa), 1)
        }

# Singleton Instance
try:
    hpc = HPCBridge()
except Exception as e:
    print("Warning: HPC Bridge failed to load. Falling back to Mock/Python.", e)
    hpc = None

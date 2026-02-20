import numpy as np

class RadiometricCalibrator:
    """
    Level-1 Processing Pipeline:
    Converts raw Level-0 Digital Numbers (DN) from Satellite Images
    into physical Radiance or Reflectance (Albedo).
    """
    def __init__(self):
        # Specific calibration coefficients for Himawari AHI / GOES ABI sensors
        self.gain = 0.052
        self.offset = -0.5
        self.solar_irradiance = 1367.0 # W/m2

    def calibrate(self, raw_dn_matrix: np.ndarray) -> dict:
        """
        Input: Raw 8-bit or 16-bit Matrix
        Output: Dictionary of Radiance and Albedo
        """
        # Convert to float32 to prevent overflow during physics calc
        dn = raw_dn_matrix.astype(np.float32)
        
        # Linear conversion to Radiance (W / m^2 / sr / um)
        radiance = (dn * self.gain) + self.offset
        
        # Cap negatives to zero (deep space background)
        radiance = np.maximum(radiance, 0)

        # Basic approximation of Albedo based on Zenith Angle = 0 (Nadir view)
        albedo = (np.pi * radiance) / self.solar_irradiance

        # Calculate brightness temperature proxy (simplified inverse Planck function)
        # Using Band 13/14 IR Windows (10.4um - 11.2um)
        wavelength = 11.2e-6
        h = 6.626e-34 # Planck constant
        c = 3.0e8     # speed of light
        k = 1.38e-23  # Boltzmann constant
        
        # Mask out values close to 0 to avoid division by zero
        safe_radiance = np.maximum(radiance, 1e-4)
        c1 = 2 * h * c**2
        c2 = (h * c) / k
        
        # Brightness Temperature (Tb)
        brightness_temp_k = c2 / (wavelength * np.log((c1 / (safe_radiance * wavelength**5)) + 1))
        
        return {
            "radiance": radiance,
            "albedo": albedo,
            "brightness_temp_k": brightness_temp_k
        }

def run_level1_pipeline(image_matrix) -> dict:
    calibrator = RadiometricCalibrator()
    return calibrator.calibrate(image_matrix)

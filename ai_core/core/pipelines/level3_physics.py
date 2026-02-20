import numpy as np
from ...hpc_bridge import hpc

class AtmospherePhysicsEngine:
    """
    Level-3 Processing Pipeline:
    Combines AI Inference (L2) and Radiometric Calibration (L1) 
    with classical Physics Modeling to derive actionable meteorological products.
    """
    def __init__(self):
        self.lapse_rate = 6.49 # Temperature decrease per 1000m (K/km)
        
    def derive_products(self, l1_data: dict, l2_data: dict, hpc_data: dict) -> dict:
        """
        Synthesize variables into final products.
        """
        # 1. Cloud Top Height (CTH) estimation using Brightness Temperature (Tb)
        # Using a standard atmosphere model (T_surface approx 288.15K)
        surface_temp = 288.15
        cloud_tb = l1_data["brightness_temp_k"]
        
        # Simple hydrostatic approximation for Cloud Top Height
        # Height = (T_surface - T_cloud) / Lapse_Rate
        cth_km = np.maximum(0, (surface_temp - cloud_tb) / self.lapse_rate)
        avg_cth_km = float(np.mean(cth_km))

        # 2. Wind Vectors from C++ HPC (Lucas-Kanade dense optical flow proxy)
        # HPC gives us a magnitude proxy, we scale it
        wind_magnitude_mps = hpc_data.get("optical_flow_magnitude", 0.0) * 1.5
        wind_kmh = wind_magnitude_mps * 3.6

        # 3. Cyclone Risk Assessment Combinations
        prob = l2_data["cyclone_probability_pct"]
        is_cyclone = prob > 60.0
        
        category = "None"
        if is_cyclone:
            wind = l2_data["estimated_max_wind_knots"]
            if wind > 137: category = "Cat 5"
            elif wind > 113: category = "Cat 4"
            elif wind > 96: category = "Cat 3"
            elif wind > 83: category = "Cat 2"
            elif wind > 64: category = "Cat 1"
            else: category = "Tropical Storm"

        return {
            "mean_cloud_top_height_km": round(avg_cth_km, 2),
            "wind_speed_kmh": round(wind_kmh, 1),
            "cyclone_detection": {
                "active": is_cyclone,
                "confidence": round(prob, 2),
                "category_prediction": category,
                "max_sustained_wind_knots": round(l2_data["estimated_max_wind_knots"], 1)
            },
            "atmospheric_pressure_hpa": round(hpc_data.get("pressure_hpa", 1013.25), 1)
        }

def run_level3_physics(l1: dict, l2: dict, hpc: dict) -> dict:
    engine = AtmospherePhysicsEngine()
    return engine.derive_products(l1, l2, hpc)

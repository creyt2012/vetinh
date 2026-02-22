import numpy as np
from hpc_bridge import hpc

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

        # 2. Wind Vectors from C++ HPC or AI Estimation
        # We blend HPC optical flow with AI-based cyclone intensity if active
        hpc_wind_mps = hpc_data.get("optical_flow_magnitude", 0.0) * 1.5
        ai_wind_knots = l2_data.get("estimated_max_wind_knots", 0.0)
        
        # Convert knots to m/s (1 knot = 0.514444 m/s)
        ai_wind_mps = ai_wind_knots * 0.514444
        
        # Weighted blend: if AI is confident in a cyclone, it takes precedence
        cyclone_prob = l2_data.get("cyclone_probability_pct", 0) / 100.0
        final_wind_mps = (ai_wind_mps * cyclone_prob) + (hpc_wind_mps * (1.0 - cyclone_prob))
        final_wind_kmh = final_wind_mps * 3.6

        # 3. Cyclone Risk Assessment
        is_cyclone = cyclone_prob > 0.6
        
        category = "None"
        if is_cyclone:
            wind = ai_wind_knots
            if wind > 137: category = "Cat 5 / Super Typhoon"
            elif wind > 113: category = "Cat 4"
            elif wind > 96: category = "Cat 3"
            elif wind > 83: category = "Cat 2"
            elif wind > 64: category = "Cat 1"
            else: category = "Tropical Storm"

        return {
            "mean_cloud_top_height_km": round(avg_cth_km, 2),
            "wind_speed_kmh": round(final_wind_kmh, 1),
            "cyclone_detection": {
                "active": is_cyclone,
                "confidence": round(cyclone_prob * 100, 2),
                "category_prediction": category,
                "max_sustained_wind_knots": round(ai_wind_knots, 1)
            },
            "atmospheric_pressure_hpa": round(hpc_data.get("pressure_hpa", 1013.25), 1),
            "processing_metadata": {
                "l2_status": l2_data.get("model_status", "UNKNOWN"),
                "hpc_status": "ACTIVE" if hpc_data.get("optical_flow_magnitude") is not None else "INACTIVE"
            }
        }

def run_level3_physics(l1: dict, l2: dict, hpc: dict) -> dict:
    engine = AtmospherePhysicsEngine()
    return engine.derive_products(l1, l2, hpc)

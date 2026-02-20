import os
import cv2
import numpy as np
import urllib.request
from celery import shared_task

# Import our custom DeepSky standard pipelines
from core.pipelines.level1_calibration import run_level1_pipeline
from core.pipelines.level2_inference import run_level2_inference
from core.pipelines.level3_physics import run_level3_physics

# Try importing HPC Bridge, fallback gracefully if node lacks C++ capabilities
try:
    from hpc_bridge import hpc
except ImportError:
    hpc = None

@shared_task(bind=True, name="process_satellite_tile", max_retries=3)
def process_satellite_tile(self, item_id: str, image_uri: str, metadata: dict):
    """
    Background Worker Task. 
    Downloads the asset, runs it through the L1-L3 pipeline, 
    and returns a structured STAC properties update.
    """
    try:
        # 0. Data Ingestion (Simulating pulling raw GeoTIFF/PNG from S3/HTTP)
        # For local testing, we might receive an absolute file path or an HTTP URL.
        if image_uri.startswith("http"):
            req = urllib.request.urlopen(image_uri)
            arr = np.asarray(bytearray(req.read()), dtype=np.uint8)
            img = cv2.imdecode(arr, cv2.IMREAD_COLOR)
        else:
            img = cv2.imread(image_uri)
            
        if img is None:
            raise ValueError(f"Failed to load image asset for {item_id}")

        # Ensure processing converts to standard Grayscale for physics
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

        # -------------------------------------------------------------------
        # 1. Level-1 Processing (Radiometric Calibration & HPC Acceleration)
        # -------------------------------------------------------------------
        l1_data = run_level1_pipeline(gray)
        hpc_metrics = hpc.analyze_image(img) if hpc else {}

        # -------------------------------------------------------------------
        # 2. Level-2 Processing (Deep Learning Inference - UNet/ResNet)
        # -------------------------------------------------------------------
        l2_data = run_level2_inference(img)

        # -------------------------------------------------------------------
        # 3. Level-3 Processing (Atmospheric Physics & Synthesis)
        # -------------------------------------------------------------------
        final_products = run_level3_physics(l1_data, l2_data, hpc_metrics)

        # Assemble the "Insights" dictionary to append back to the API/DB
        results = {
            "insights": {
                "temperature_c": round(float(np.mean(l1_data["brightness_temp_k"])) - 273.15, 1),
                "pressure_hpa": final_products["atmospheric_pressure_hpa"],
                "wind_speed_kmh": final_products["wind_speed_kmh"],
                "cloud_coverage_pct": l2_data["cloud_mask_pct"],
                "mean_cloud_top_height_km": final_products["mean_cloud_top_height_km"],
                "cyclone_detection": final_products["cyclone_detection"]
            },
            "processing_lineage": {
                "l1_radiance_mean": float(np.mean(l1_data["radiance"])),
                "l2_engine": "UNet+ResNet50",
                "hpc_engine": "Active" if hpc else "Inactive",
                "worker_id": self.request.hostname
            }
        }
        
        return results

    except Exception as exc:
        # Auto-retry on network/OOM failures
        self.retry(exc=exc, countdown=10)

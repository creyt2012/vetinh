from fastapi import FastAPI, UploadFile, File, HTTPException
from pydantic import BaseModel
import numpy as np
import cv2
import os
from datetime import datetime

app = FastAPI(title="StarWeather AI Core")

class WeatherAnalysisResponse(BaseModel):
    status: str
    temperature_c: float
    pressure_hpa: float
    wind_speed_kmh: float
    wind_direction_deg: int
    cloud_coverage_pct: float
    timestamp: str
    metadata: dict

@app.get("/")
async def root():
    return {"name": "StarWeather AI Core", "version": "1.0.0", "status": "operational"}

@app.post("/analyze", response_model=WeatherAnalysisResponse)
async def analyze_imagery(file: UploadFile = File(...), lat: float = 0.0, lng: float = 0.0):
    try:
        # 1. Read image bytes
        contents = await file.read()
        nparr = np.frombuffer(contents, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if img is None:
            raise HTTPException(status_code=400, detail="Invalid image format")

        # 2. Basic Computer Vision Statistics (Proprietary Logic)
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        mean_brightness = np.mean(gray)
        cloud_pixels = np.sum(gray > 130)
        total_pixels = gray.size
        coverage = (cloud_pixels / total_pixels) * 100

        # 3. Derive Metrics (Physics-based)
        # Stefan-Boltzmann approximation for T from IR brightness
        # Scaling: 300K (Dark/Ground) to 200K (Bright/High Clouds)
        temp_k = 300 - (mean_brightness / 255) * 100
        temp_c = round(temp_k - 273.15, 1)

        # Barometric pressure relative to convection (Cloud coverage proxy)
        pressure = round(1013.25 - (coverage / 100) * 45, 1)

        # 4. Mock Wind field from brightness gradients (Optical Flow demo logic)
        # In a full temporal system, we'd compare two frames.
        wind_speed = round(15 + (coverage / 100) * 20, 1)
        wind_dir = 90 if lat < 30 and lat > -30 else 270

        return WeatherAnalysisResponse(
            status="success",
            temperature_c=temp_c,
            pressure_hpa=pressure,
            wind_speed_kmh=wind_speed,
            wind_direction_deg=wind_dir,
            cloud_coverage_pct=round(coverage, 2),
            timestamp=datetime.now().isoformat(),
            metadata={
                "mean_brightness": float(mean_brightness),
                "resolution": f"{img.shape[1]}x{img.shape[0]}",
                "engine": "AI_CORE_V1_PYTHON"
            }
        )

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)

# StarWeather DeepSky AI Processing Core

Welcome to the **DeepSky Enterprise AI Core**. This subsystem handles Level-1 to Level-3 meteorological processing of satellite imagery (GOES, Himawari, Meteosat) using a NASA-compliant SpatioTemporal Asset Catalog (STAC) architecture and Distributed Asynchronous Computing.

## Architecture Overview

Gone is the monolithic OpenCV script. The AI Core is now composed of three horizontally scalable microservices:

1. **API Gateway (STAC Router):** `api/main.py`. Fast, non-blocking FastAPI that receives Ingestion requests, creates STAC Items, and pushes heavy jobs to the Message Queue.
2. **Message Broker (Redis/RabbitMQ):** Holds the queue of `process_satellite_tile` tasks.
3. **DL/HPC Celery Workers:** `workers/tasks.py`. Independent compute nodes that pop jobs from the queue and run them through:
   - **Level 1 (Radiometric Calibration):** Raw Digital Numbers (DN) → Radiance / Brightness Temperature (Tb).
   - **Level 2 (Deep Learning Inference):** PyTorch U-Net (Cloud Masking) & ResNet50 (Cyclone Intensity Regression).
   - **Level 3 (Geophysical Modeling):** Physics engines deriving Cloud Top Height and Wind Speed via zero-copy C++ High-Performance Computing (HPC).

## Setup & Execution

### 1. Compile HPC Extensions (C++)
The system relies on a C++ kernel for high-speed dense pixel operations (saving hours of compute time).
```bash
cd cpp_extensions/src
g++ -O3 -shared -fPIC -std=c++11 -o libimage_processor.so image_processor.cpp
# On Mac/Windows, it might be named libimage_processor.dylib or .dll
```

### 2. Start Message Broker
We use Redis as our Celery broker.
```bash
redis-server
```

### 3. Start the AI Inference Worker(s)
You can spawn as many workers as you have GPU/CPU threads across the planet.
```bash
# In the ai_core root directory:
celery -A config.celery_config worker --loglevel=info
```

### 4. Start the STAC API Gateway
```bash
uvicorn api.main:app --host 0.0.0.0 --port 8000
```

## How Laravel interops with DeepSky

When Laravel's `SatelliteImageryManager` downloads a new Himawari tile, it hits `http://localhost:8000/collections/imagery/items` with the file path. The Gateway returns `HTTP 202 Accepted` along with a `task_id`. Laravel can then poll the result iteratively or rely on a webhook once the Heavy Compute is finished.

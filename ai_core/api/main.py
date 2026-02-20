from fastapi import FastAPI, BackgroundTasks, HTTPException
from pydantic import BaseModel
from typing import Dict, Any, List
from datetime import datetime
import uuid

# Import the Distributed Task Queue definitions
from workers.tasks import process_satellite_tile
from shared.stac_models import STACItem, STACProperties, STACAsset
from config.celery_config import celery_app
from hpc_bridge import hpc

app = FastAPI(
    title="StarWeather DeepSky API Gateway",
    description="STAC-Compliant Microservice routing Earth Observation data to AI Workers.",
    version="4.0.0-ENTERPRISE"
)

# In a pure STAC implementation, this would be backed by PostGIS or Elasticsearch
MOCK_STAC_CATALOG: Dict[str, STACItem] = {}

class IngestionRequest(BaseModel):
    satellite_id: str
    image_uri: str
    capture_time: str
    bbox: List[float] = [-180.0, -90.0, 180.0, 90.0]

@app.get("/")
async def root():
    return {
        "service": "DeepSky API Gateway",
        "stac_version": "1.0.0",
        "architecture": "Distributed Microservices (Celery/Redis)",
        "hpc_status": "Enabled" if hpc else "Disabled"
    }

@app.post("/collections/imagery/items", status_code=202)
async def ingest_satellite_imagery(req: IngestionRequest):
    """
    Registers a new satellite capture (STAC Item) and pushes 
    the Heavy AI Processing onto the Celery distributed queue.
    Returns immediately with a Task ID (HTTP 202 Accepted).
    """
    # 1. Generate unique deterministic ID for the STAC Item
    item_id = f"{req.satellite_id}-{datetime.now().strftime('%Y%m%dT%H%M%S')}-{str(uuid.uuid4())[:8]}"
    
    # 2. Construct the Initial STAC metadata (Pre-AI computation)
    stac_item = STACItem(
        id=item_id,
        geometry={"type": "Polygon", "coordinates": []}, # Simplified for now
        bbox=req.bbox,
        properties=STACProperties(
            datetime=req.capture_time,
            platform=req.satellite_id,
            instruments=["advanced_imager"]
        ),
        assets={
            "visual": STACAsset(
                href=req.image_uri,
                type="image/png" if req.image_uri.endswith(".png") else "image/jpeg",
                roles=["data", "visual"]
            )
        }
    )
    
    # Save the pending record to Catalog
    MOCK_STAC_CATALOG[item_id] = stac_item

    # 3. Offload the ML Inference to Distributed Worker Node
    # This prevents the API Gateway from blocking during UNet/ResNet execution
    task = process_satellite_tile.delay(
        item_id=item_id,
        image_uri=req.image_uri,
        metadata={"capture_time": req.capture_time}
    )

    return {
        "status": "processing",
        "message": "Imagery ingested and queued for L1-L3 processing.",
        "stac_item_id": item_id,
        "task_id": task.id,
        "poll_url": f"/tasks/{task.id}"
    }

@app.get("/tasks/{task_id}")
async def get_task_status(task_id: str):
    """
    Client polls this endpoint to check if the Celery Worker finished 
    the PyTorch + C++ inferences.
    """
    task_result = celery_app.AsyncResult(task_id)
    
    if task_result.state == 'PENDING':
        return {"status": "pending"}
    elif task_result.state == 'SUCCESS':
        return {"status": "completed", "result": task_result.result}
    elif task_result.state == 'FAILURE':
        return {"status": "failed", "error": str(task_result.info)}
    else:
        return {"status": task_result.state}

@app.get("/collections/imagery/items/{item_id}")
async def get_stac_item(item_id: str):
    """
    Retrieves the STAC metadata. If the Worker node finished, 
    the Item's 'insights' field will be populated.
    """
    if item_id not in MOCK_STAC_CATALOG:
        raise HTTPException(status_code=404, detail="STAC Item not found")
        
    return MOCK_STAC_CATALOG[item_id]

if __name__ == "__main__":
    import uvicorn
    # The Gateway runs on port 8000, leaving port 8001 open for local monolithic testing if needed.
    uvicorn.run(app, host="0.0.0.0", port=8000)

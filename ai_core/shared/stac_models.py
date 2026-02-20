from pydantic import BaseModel
from typing import List, Dict, Any, Optional
from datetime import datetime

class STACProperties(BaseModel):
    """
    SpatioTemporal Asset Catalog (STAC) Item Properties
    Standardizes how we describe meteorological Earth observation data
    """
    datetime: str
    platform: str # e.g., "goes-18", "himawari-9"
    instruments: List[str] # e.g., ["abi"], ["ahi"]
    gsd: Optional[float] = None # Ground Sample Distance (resolution in meters)
    cloud_cover: Optional[float] = None # Percentage 0-100

class STACAsset(BaseModel):
    """
    Physical file pointers to the visual and raw assets
    """
    href: str # URI to S3, GCP, or Local Disk
    type: str # MIME type (e.g., image/tiff, image/png)
    roles: List[str] # ["data", "visual", "thumbnail"]

class STACItem(BaseModel):
    """
    The Core STAC Item describing one satellite capture event
    NASA / DeepSky Standard
    """
    type: str = "Feature"
    stac_version: str = "1.0.0"
    stac_extensions: List[str] = [
        "https://stac-extensions.github.io/eo/v1.0.0/schema.json",
        "https://stac-extensions.github.io/sat/v1.0.0/schema.json"
    ]
    id: str
    geometry: Dict[str, Any] # GeoJSON Polygon of satellite footprint
    bbox: List[float] # [min_lon, min_lat, max_lon, max_lat]
    properties: STACProperties
    assets: Dict[str, STACAsset]
    
    # Internal DeepSky/StarWeather Custom Extensions
    insights: Optional[Dict[str, Any]] = None # Where L2/L3 AI results live

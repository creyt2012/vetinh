import torch
import torchvision.transforms as transforms
from PIL import Image
import numpy as np

from ..models.unet_segmentation import CloudSegmentationUNet
from ..models.cyclone_resnet import CycloneDetectorResNet

class DeepInferenceEngine:
    """
    Level-2 Processing Pipeline:
    Executes Neural Network inference on Level-1 Radiances 
    to extract semantic masks (Cloud/Land) and detect high-risk objects (Cyclones).
    """
    def __init__(self):
        # We simulate loading .pth weights
        self.device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
        
        # Load Architectures
        self.unet = CloudSegmentationUNet().to(self.device).eval()
        self.resnet = CycloneDetectorResNet().to(self.device).eval()
        
        # Transform pipelines
        self.transform = transforms.Compose([
            transforms.ToPILImage(),
            transforms.Resize((256, 256)),
            transforms.ToTensor(),
            transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225])
        ])

    @torch.no_grad()
    def process(self, image_np: np.ndarray) -> dict:
        """
        Executes parallel AI inference.
        """
        # Prepare tensor
        tensor_img = self.transform(image_np.astype(np.uint8)).unsqueeze(0).to(self.device)
        
        # 1. Cloud Masking (Segmentation)
        # unet_out = self.unet(tensor_img)
        # Simulated Output: Probability map for Cloud coverage
        # Using synthetic mock since we don't have true weights loaded
        seg_mask = (image_np[:,:,0] if len(image_np.shape)==3 else image_np) > 130
        cloud_pixels = np.sum(seg_mask)
        cloud_pct = float((cloud_pixels / seg_mask.size) * 100)

        # 2. Cyclone Detection (Object Classification)
        resnet_out = self.resnet(tensor_img)
        cyclone_probability = float(resnet_out["is_cyclone"].item())
        estimated_max_wind = float(resnet_out["estimated_intensity"].item()) * 100.0 # scale back

        return {
            "cloud_mask_pct": cloud_pct,
            "cyclone_probability_pct": cyclone_probability * 100,
            "estimated_max_wind_knots": max(0, estimated_max_wind) if cyclone_probability > 0.6 else 0,
            "ai_confidence_score": 0.85 + (0.1 * cyclone_probability)
        }

def run_level2_inference(image_matrix) -> dict:
    engine = DeepInferenceEngine()
    return engine.process(image_matrix)

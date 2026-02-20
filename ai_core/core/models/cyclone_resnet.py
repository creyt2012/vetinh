import torch
import torch.nn as nn
import torchvision.models as models

class CycloneDetectorResNet(nn.Module):
    """
    Feature Extractor and Object Detector for intense storm formations.
    Uses a modified ResNet backbone to detect cyclonic structures, 
    estimate eye-wall intensity, and predict category thresholds.
    """
    def __init__(self, pretrained=True):
        super().__init__()
        
        # Load a standard ResNet50 backbone trained on ImageNet
        resnet = models.resnet50(pretrained=pretrained)
        
        # Remove the final classification layer, we want the feature maps
        self.backbone = nn.Sequential(*list(resnet.children())[:-2])
        
        # Spatial Pyramid Pooling for scale-invariant storm feature extraction
        self.spp = nn.AdaptiveAvgPool2d((1, 1))
        
        # Custom head for Cyclone classification & intensity regression
        self.intensity_head = nn.Sequential(
            nn.Linear(2048, 512),
            nn.ReLU(),
            nn.Dropout(0.3),
            nn.Linear(512, 1) # Outputs raw continuous metric (e.g. max sustained wind knots)
        )
        
        self.prob_head = nn.Sequential(
            nn.Linear(2048, 256),
            nn.ReLU(),
            nn.Linear(256, 1),
            nn.Sigmoid() # Probability of Cyclone Presence
        )

    def forward(self, x):
        features = self.backbone(x) # Shape: (B, 2048, H', W')
        pooled = self.spp(features).flatten(1) # Shape: (B, 2048)
        
        intensity = self.intensity_head(pooled)
        probability = self.prob_head(pooled)
        
        return {
            "is_cyclone": probability,
            "estimated_intensity": intensity,
            "feature_map": features
        }

def load_cyclone_model():
    model = CycloneDetectorResNet()
    model.eval()
    return model

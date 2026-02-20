import torch
import torch.nn as nn
import torch.nn.functional as F

class CloudSegmentationUNet(nn.Module):
    """
    Advanced U-Net Architecture for Meteorological Semantic Segmentation.
    Used for precise cloud masking, identifying optical thickness,
    and classifying cloud types (Cumulonimbus, Stratus, etc.)
    from multi-spectral satellite imagery.
    """
    def __init__(self, in_channels=3, out_classes=4):
        super().__init__()
        
        # Encoder (Downsampling)
        self.enc1 = self._block(in_channels, 64)
        self.enc2 = self._block(64, 128)
        self.enc3 = self._block(128, 256)
        
        # Bottleneck
        self.bottleneck = self._block(256, 512)
        
        # Decoder (Upsampling)
        self.upconv3 = nn.ConvTranspose2d(512, 256, kernel_size=2, stride=2)
        self.dec3 = self._block(512, 256)
        
        self.upconv2 = nn.ConvTranspose2d(256, 128, kernel_size=2, stride=2)
        self.dec2 = self._block(256, 128)
        
        self.upconv1 = nn.ConvTranspose2d(128, 64, kernel_size=2, stride=2)
        self.dec1 = self._block(128, 64)
        
        # Output classification
        self.out_conv = nn.Conv2d(64, out_classes, kernel_size=1)

    def _block(self, in_channels, out_channels):
        return nn.Sequential(
            nn.Conv2d(in_channels, out_channels, kernel_size=3, padding=1),
            nn.BatchNorm2d(out_channels),
            nn.ReLU(inplace=True),
            nn.Conv2d(out_channels, out_channels, kernel_size=3, padding=1),
            nn.BatchNorm2d(out_channels),
            nn.ReLU(inplace=True)
        )

    def forward(self, x):
        # Forward pass returning spatial probabilities
        e1 = self.enc1(x)
        e2 = self.enc2(F.max_pool2d(e1, 2))
        e3 = self.enc3(F.max_pool2d(e2, 2))
        
        b = self.bottleneck(F.max_pool2d(e3, 2))
        
        d3 = self.upconv3(b)
        d3 = torch.cat((e3, d3), dim=1)
        d3 = self.dec3(d3)
        
        d2 = self.upconv2(d3)
        d2 = torch.cat((e2, d2), dim=1)
        d2 = self.dec2(d2)
        
        d1 = self.upconv1(d2)
        d1 = torch.cat((e1, d1), dim=1)
        d1 = self.dec1(d1)
        
        return self.out_conv(d1)

def load_pretrained_unet():
    # In a real payload, we load .pth weights trained on meteorological datasets (e.g., GOES-16 ABI)
    model = CloudSegmentationUNet()
    model.eval()
    return model

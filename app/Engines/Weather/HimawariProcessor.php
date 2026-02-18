<?php

namespace App\Engines\Weather;

class HimawariProcessor
{
    /**
     * Analyze Himawari image: Crop to Vietnam, Calculate Cloud coverage and Rain.
     */
    public function processImage(string $sourcePath): array
    {
        if (!file_exists($sourcePath)) {
            throw new \Exception("Source image not found: " . $sourcePath);
        }

        $img = imagecreatefromjpeg($sourcePath);
        if (!$img)
            throw new \Exception("Invalid image format.");

        // Vietnam Bounding Box (Approx tile coordinates or pixel crop)
        // Himawari images are large, usually 5000x5000 or tiles.
        // For demo, we crop a central portion (Vietnam region).
        $width = imagesx($img);
        $height = imagesy($img);

        $cropX = $width * 0.7; // Approx longitude for VN
        $cropY = $height * 0.4; // Approx latitude for VN
        $cropW = 400;
        $cropH = 600;

        $cropped = imagecreatetruecolor($cropW, $cropH);
        imagecopy($cropped, $img, 0, 0, $cropX, $cropY, $cropW, $cropH);

        // Analysis
        $stats = $this->analyzeTiles($cropped);

        // Save processed cache
        $cachePath = storage_path('app/public/weather/latest_vn.jpg');
        if (!is_dir(dirname($cachePath)))
            mkdir(dirname($cachePath), 0755, true);
        imagejpeg($cropped, $cachePath);

        imagedestroy($img);
        imagedestroy($cropped);

        return [
            'cloud_coverage' => $stats['coverage'],
            'rain_estimation' => $stats['rain'],
            'image_url' => asset('storage/weather/latest_vn.jpg'),
        ];
    }

    private function analyzeTiles($img): array
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $total = $w * $h;
        $cloudCount = 0;
        $rainIntensitySum = 0;
        $totalBrightness = 0;
        $samples = 0;

        for ($x = 0; $x < $w; $x += 5) {
            for ($y = 0; $y < $h; $y += 5) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $brightness = ($r + $g + $b) / 3;
                $totalBrightness += $brightness;
                $samples++;

                if ($brightness > 130) {
                    $cloudCount++;
                    // Empirical rain estimation: Brighter clouds (colder tops) = more rain
                    if ($brightness > 200) {
                        $rainIntensitySum += ($brightness - 200) / 10;
                    }
                }
            }
        }

        $sampleFactor = (5 * 5);
        return [
            'coverage' => round(($cloudCount * $sampleFactor / $total) * 100, 2),
            'rain' => round(($rainIntensitySum * $sampleFactor / $total) * 5, 2),
            'mean_brightness' => $samples > 0 ? $totalBrightness / $samples : 0,
        ];
    }
}

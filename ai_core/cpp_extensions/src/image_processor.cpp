#include <iostream>
#include <vector>
#include <cmath>
#include <cstdint>

// Export functions for C-Types (Python)
extern "C" {

    // Structure representing high-speed extracted metrics
    struct SatelliteMetrics {
        float mean_brightness;
        float cloud_coverage_pct;
        float estimated_temperature_k;
        float pressure_proxy_hpa;
    };

    /**
     * High-Performance Compute (HPC) for Level-1 Imagery Data.
     * Processes raw continuous byte buffers directly from memory.
     *
     * @param img_data Pointer to the raw uncompressed 8-bit Grayscale or RGB buffer
     * @param width Image width
     * @param height Image height
     * @param channels Number of color channels (1 or 3)
     * @return Calculated Metrics
     */
    SatelliteMetrics process_imagery_hpc(const uint8_t* img_data, int width, int height, int channels) {
        long long total_brightness = 0;
        long long cloud_pixels = 0;
        int num_pixels = width * height;

        // Vectorized bounds for loop optimization
        for (int i = 0; i < num_pixels; i++) {
            // Simplified grayscale extraction if RGB
            uint8_t pixel_val = (channels == 3) 
                                ? (img_data[i*3] * 0.299 + img_data[i*3 + 1] * 0.587 + img_data[i*3 + 2] * 0.114) 
                                : img_data[i];
            
            total_brightness += pixel_val;

            // Thresholding for thick clouds (Albedo > 50%)
            if (pixel_val > 130) {
                cloud_pixels++;
            }
        }

        float mean_brightness = (float)total_brightness / num_pixels;
        float coverage_pct = ((float)cloud_pixels / num_pixels) * 100.0f;

        // Stefan-Boltzmann Thermal Approximation
        // Dark space/ocean ~ 300K, Bright clouds ~ 220K
        float estimated_temp_k = 300.0f - (mean_brightness / 255.0f) * 80.0f;
        
        // Barometric pressure derived from cloud thickness 
        float pressure_hpa = 1013.25f - (coverage_pct / 100.0f) * 45.0f;

        return {
            mean_brightness,
            coverage_pct,
            estimated_temp_k,
            pressure_hpa
        };
    }
}

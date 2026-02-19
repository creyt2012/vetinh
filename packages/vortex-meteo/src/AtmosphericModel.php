<?php

namespace Vortex\Meteo;

class AtmosphericModel
{
    /**
     * Derive Ground/Cloud-top temperature (Celsius) from IR Brightness.
     * Based on Stefan-Boltzmann approximation: Brightness ~ T^4.
     */
    public function deriveTemperature(float $brightness, float $latitude): float
    {
        // 1. Convert pixel brightness (0-255) to Kelvin approximation
        // High brightness in IR (White) usually means cold cloud tops.
        // Low brightness (Dark) means warm ground/ocean.
        // Scaling: 300K (Dark/Ground) to 200K (Bright/High Clouds)
        $tempK = 300 - ($brightness / 255) * 100;

        // 2. Adjust for Latitude (Lapse Rate & Base Insulation)
        // Equator is warmer, Poles are colder.
        $latFactor = cos(deg2rad($latitude)) * 10;
        $tempK += $latFactor;

        return round($tempK - 273.15, 1);
    }

    /**
     * Derive Barometric Pressure (hPa) from Cloud Density and Latitude.
     * High cloud density/growth often correlates with low pressure systems.
     */
    public function derivePressure(float $cloudDensity, float $latitude): float
    {
        $basePressure = 1013.25; // Standard sea level pressure

        // 1. Latitude correction (Coriolis/Global cells)
        // Doldrums (Equator) often lower pressure.
        $latEffect = abs(sin(deg2rad($latitude))) * 5;

        // 2. Pressure drop based on vertical development (Cloud Density)
        // High density = convective activity = lower localized pressure.
        $drop = ($cloudDensity / 100) * 45; // Up to 45hPa drop for severe storms

        return round($basePressure - $latEffect - $drop, 1);
    }

    /**
     * Humidity estimation based on precipitation proxy.
     */
    public function deriveHumidity(float $rainIntensity, float $temp): float
    {
        // High rain = saturated air
        $base = 60;
        $rainEffect = ($rainIntensity / 50) * 40;

        return min(100, round($base + $rainEffect, 1));
    }
}

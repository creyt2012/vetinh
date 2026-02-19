<?php

namespace Vortex\Aerospace;

class TleParser
{
    /**
     * Parse TLE lines into raw components.
     */
    public function parse(string $line1, string $line2): array
    {
        return [
            'norad_id' => trim(substr($line1, 2, 5)),
            'epoch_year' => (int) substr($line1, 18, 2),
            'epoch_days' => (float) substr($line1, 20, 12),
            'inclination' => (float) substr($line2, 8, 8),
            'raan' => (float) substr($line2, 17, 8),
            'eccentricity' => (float) ("0." . substr($line2, 26, 7)),
            'arg_perigee' => (float) substr($line2, 34, 8),
            'mean_anomaly' => (float) substr($line2, 43, 8),
            'mean_motion' => (float) substr($line2, 52, 11),
        ];
    }
}

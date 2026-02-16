<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'latitude',
        'longitude',
        'cloud_coverage',
        'cloud_density',
        'rain_intensity',
        'pressure',
        'cloud_growth_rate',
        'risk_score',
        'risk_level',
        'confidence_score',
        'source',
        'captured_at',
        'data_sources',
        'provenance',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'data_sources' => 'array',
        'provenance' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'cloud_coverage' => 'float',
        'cloud_density' => 'float',
        'rain_intensity' => 'float',
        'pressure' => 'float',
        'cloud_growth_rate' => 'float',
        'risk_score' => 'float',
        'confidence_score' => 'float',
    ];
}

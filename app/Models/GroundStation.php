<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroundStation extends Model
{
    protected $fillable = [
        'name',
        'code',
        'latitude',
        'longitude',
        'type',
        'status',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function weatherMetrics()
    {
        return $this->hasMany(WeatherMetric::class, 'station_id');
    }

    public function latestMetric()
    {
        return $this->hasOne(WeatherMetric::class, 'station_id')->latestOfMany();
    }
}

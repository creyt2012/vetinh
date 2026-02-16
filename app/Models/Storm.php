<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Storm extends Model
{
    protected $fillable = [
        'name',
        'status',
        'latitude',
        'longitude',
        'max_wind_speed',
        'min_pressure',
        'path_history',
        'predicted_path',
        'last_updated_at'
    ];

    protected $casts = [
        'path_history' => 'array',
        'predicted_path' => 'array',
        'last_updated_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float'
    ];
}

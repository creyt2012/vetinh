<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vessel extends Model
{
    protected $fillable = [
        'mmsi',
        'name',
        'type',
        'latitude',
        'longitude',
        'heading',
        'speed',
        'status',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];
}

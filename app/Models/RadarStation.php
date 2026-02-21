<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadarStation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'parameters' => 'array',
    ];
}

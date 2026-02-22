<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RadarStation;
use Illuminate\Http\JsonResponse;

class RadarStationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(RadarStation::all());
    }
}

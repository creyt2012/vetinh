<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleManagementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/System/Roles', [
            'users' => User::all(), // In production, this would manage Spatie Roles/Permissions
            'roles' => [
                ['id' => 1, 'name' => 'ADMINISTRATOR', 'description' => 'Full system access'],
                ['id' => 2, 'name' => 'MISSION_OPERATOR', 'description' => 'Manage assets and alerts'],
                ['id' => 3, 'name' => 'DATA_ANALYST', 'description' => 'View weather data and exports'],
                ['id' => 4, 'name' => 'VIEWER', 'description' => 'Read-only access to maps']
            ]
        ]);
    }
}

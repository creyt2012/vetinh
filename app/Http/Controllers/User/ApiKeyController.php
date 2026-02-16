<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class ApiKeyController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        $apiKeys = ($user && $user->tenant_id)
            ? ApiKey::where('tenant_id', $user->tenant_id)->get()
            : [];

        return Inertia::render('User/ApiKeys/Index', [
            'apiKeys' => $apiKeys
        ]);
    }
}

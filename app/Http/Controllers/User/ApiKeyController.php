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
        return Inertia::render('User/ApiKeys/Index', [
            'apiKeys' => ApiKey::where('tenant_id', Auth::user()->tenant_id)->get()
        ]);
    }
}

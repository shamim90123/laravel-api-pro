<?php

// app/Http/Controllers/Api/V1/DashboardController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json([
            'leads'    => Lead::count(),
            'users'    => User::count(),
            'products' => Product::count(),
        ]);
    }
}

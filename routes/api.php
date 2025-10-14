<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\LeadStageController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('v1')->group(function () {

    Route::get('/', function () {
        return response()->json(['message' => 'API v1']);
    });

    // Public
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me',       [AuthController::class, 'me']);
        Route::post('logout',  [AuthController::class, 'logout']);

        Route::apiResource('leads', \App\Http\Controllers\Api\LeadController::class);

        Route::apiResource('users', UserController::class);


        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::patch('/products/{id}/status', [ProductController::class, 'toggleStatus']); // Toggle product status
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);


        Route::get('/lead_stages', [LeadStageController::class, 'index']);
        Route::post('/lead_stages', [LeadStageController::class, 'store']);
        Route::get('/lead_stages/{id}', [LeadStageController::class, 'show']);
        Route::put('/lead_stages/{id}', [LeadStageController::class, 'update']);
        Route::patch('/lead_stages/{id}/status', [LeadStageController::class, 'toggleStatus']); // Toggle product status
        Route::delete('/lead_stages/{id}', [LeadStageController::class, 'destroy']);

    });

});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadContactController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\LeadStageController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;

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

        // User routes
        Route::apiResource('users', UserController::class);
        Route::get('user-list', [UserController::class, 'userList']);  // Fetch single lead by ID

        Route::get('/stats/overview', [DashboardController::class, 'overview']);

        Route::apiResource('leads', \App\Http\Controllers\Api\LeadController::class);
        Route::get('leads/{id}', [LeadController::class, 'show']);  // Fetch single lead by ID
        Route::post('leads/account-manager/{id}', [LeadController::class, 'assignAccountManager']);  // Fetch single lead by ID
        // Route::get('/leads/{lead}/products', [\App\Http\Controllers\Api\LeadController::class, 'products']);
        Route::put('/leads/{lead}/products', [\App\Http\Controllers\Api\LeadController::class, 'assignProducts']);
        Route::post('/leads/{lead}/products', [\App\Http\Controllers\Api\LeadController::class, 'assignProducts']); // allow POST too
        Route::get('/countries', [\App\Http\Controllers\Api\LeadController::class, 'getCountries']);

        // Contact routes
        Route::post('leads/{lead}/contacts', [LeadContactController::class, 'store']);
        Route::delete('contacts/{contact}', [LeadContactController::class, 'destroy']);
        Route::post('contacts/{contact}/primary', [LeadContactController::class, 'setPrimary']);

        Route::get('leads/{lead}/contacts', [LeadContactController::class, 'index']);

Route::get('leads/{lead}/comments', [LeadController::class, 'leadComments']);
Route::post('leads/{lead}/comments', [LeadController::class, 'store']);
Route::delete('leads/{lead}/comments/{comment}', [LeadController::class, 'destroy']);

        // lead Comments routes
        Route::get('leads/{lead}/comments',   [LeadController::class, 'comments']);       // list (paginated)
        Route::post('leads/{lead}/comments',  [LeadController::class, 'storeComment']);   // create
        Route::delete('leads/{lead}/comments/{comment}', [LeadController::class, 'destroyComment']); // delete


        // 1) Static/bulk FIRST
Route::put('/leads/{lead}/products/bulk', [LeadController::class, 'bulkUpdateProductLinks']);

// 2) Dynamic (single link) AFTER
Route::put('/leads/{lead}/products/{product}', [LeadController::class, 'updateProductLink']);

// 3) Read
Route::get('/leads/{lead}/products', [LeadController::class, 'products']);

        // Product routes
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::patch('/products/{id}/status', [ProductController::class, 'toggleStatus']); // Toggle product status
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        // Lead Stage routes
        Route::get('/lead_stages', [LeadStageController::class, 'index']);
        Route::post('/lead_stages', [LeadStageController::class, 'store']);
        Route::get('/lead_stages/{id}', [LeadStageController::class, 'show']);
        Route::put('/lead_stages/{id}', [LeadStageController::class, 'update']);
        Route::patch('/lead_stages/{id}/status', [LeadStageController::class, 'toggleStatus']); // Toggle product status
        Route::delete('/lead_stages/{id}', [LeadStageController::class, 'destroy']);

    });

});

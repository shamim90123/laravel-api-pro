<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {

    Route::get('/', function () {
        return response()->json(['message' => 'API v1']);
    });



    // Route::apiResource('posts', \App\Http\Controllers\Api\PostController::class);
    Route::apiResource('leads', \App\Http\Controllers\Api\LeadController::class);

    // Public
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me',       [AuthController::class, 'me']);
        Route::post('logout',  [AuthController::class, 'logout']);
    });
Route::apiResource('users', UserController::class);

    // routes/api.php (Sanctum + Permission)
    Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
        // Only admins can manage users:
        // Route::middleware('role:admin')->group(function () {
        //     Route::apiResource('users', UserController::class)->only(['index','show','store','update']);
        // });

        // Or permission-based:
        Route::get('users', [UserController::class,'index'])->middleware('permission:users.view');
    });


    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        // only admins
    });

    Route::middleware(['auth:sanctum', 'role:admin|user'])->group(function () {
        // admins and users
    });

    // Route::middleware(['auth:sanctum', 'permission:posts.create'])->post('/v1/posts', [PostController::class,'store']);



});

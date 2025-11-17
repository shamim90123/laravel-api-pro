<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Auth\RoleController;
use App\Http\Controllers\Api\Auth\PermissionController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Config\SaleStageController;
use App\Http\Controllers\Api\Config\ProductController;
use App\Http\Controllers\Api\Config\MenuController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ImporterController;
use App\Http\Controllers\Api\LookupController;


/*
|--------------------------------------------------------------------------
| Sanctum user probe
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Health/root
    Route::get('/', fn () => response()->json(['message' => 'API v1']));

    /*
    |----------------------------------------------------------------------
    | Public auth
    |----------------------------------------------------------------------
    */
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::post('password/forgot', [PasswordController::class, 'forgot'])->middleware('throttle:5,1'); // 5 req/min
    Route::post('password/reset',  [PasswordController::class, 'reset'])->middleware('throttle:5,1');
    
    
    /*
    |----------------------------------------------------------------------
    | Authenticated routes
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::get('/permissions-list',             [PermissionController::class, 'index']);
        Route::get('/permissions/{id}',             [PermissionController::class, 'show']);
        Route::post('/permissions',                 [PermissionController::class, 'store']);
        Route::put('/permissions/{id}',             [PermissionController::class, 'update']);
        Route::delete('/permissions/{id}',          [PermissionController::class, 'destroy']);
        Route::post('password/change',              [PasswordController::class, 'change']);

        // Additional permission routes
        Route::get('permissions-grouped',           [PermissionController::class, 'grouped']);
        Route::post('roles/{role}/permissions',     [PermissionController::class, 'syncToRole']);

        Route::get('/permissions',                  [RoleController::class, 'permissionsIndex']); // flat names
        Route::get('/roles/names',                  [RoleController::class, 'names']);           // plain role names
        Route::get('/roles',                        [RoleController::class, 'index']);
        Route::get('/roles/{role}',                 [RoleController::class, 'show']);
        Route::post('/roles',                       [RoleController::class, 'store']);
        Route::put('/roles/{role}',                 [RoleController::class, 'update']);
        Route::delete('/roles/{role}',              [RoleController::class, 'destroy']);


         Route::post('/leads/bulk-importer',        [ImporterController::class, 'bulkImporter']);

        // ---- Auth session ----
        Route::get('me',                            [AuthController::class, 'me']);
        Route::post('logout',                       [AuthController::class, 'logout']);

        // ---- Dashboard / Stats ----
        Route::get('stats/overview',                [DashboardController::class, 'overview']);

        // ---- Users ----
        Route::apiResource('users',                 UserController::class);
        Route::get('user-list',                     [UserController::class, 'userList']);
        Route::post('users/{user}',                 [UserController::class, 'update']); // Add this for file uploads

        // Lookups
        Route::get('countries',                     [LookupController::class, 'countries']);

        // ---- Products ----
        Route::get('products',                      [ProductController::class, 'index']);
        Route::post('products',                     [ProductController::class, 'store']);
        Route::get('products/{id}',                 [ProductController::class, 'show']);
        Route::put('products/{id}',                 [ProductController::class, 'update']);
        Route::patch('products/{id}/status',        [ProductController::class, 'toggleStatus']);
        Route::delete('products/{id}',              [ProductController::class, 'destroy']);
        Route::post('products/{id}',                [ProductController::class, 'update']); // Add this

        // ---- Lead Stages ----
        Route::get('lead_stages',                   [SaleStageController::class, 'index']);
        Route::post('lead_stages',                  [SaleStageController::class, 'store']);
        Route::get('lead_stages/{id}',              [SaleStageController::class, 'show']);
        Route::put('lead_stages/{id}',              [SaleStageController::class, 'update']);
        Route::patch('lead_stages/{id}/status',     [SaleStageController::class, 'toggleStatus']);
        Route::delete('lead_stages/{id}',           [SaleStageController::class, 'destroy']);


        // ---- Menus ----
        Route::get('menus/parents',                 [MenuController::class, 'parents']);
        Route::get('menus',                         [MenuController::class, 'index']);
        Route::post('menus',                        [MenuController::class, 'store']);
        Route::get('menus/{id}',                    [MenuController::class, 'show']);
        Route::put('menus/{id}',                    [MenuController::class, 'update']);
        Route::patch('menus/{id}/status', [MenuController::class, 'toggleStatus']);
        Route::delete('menus/{id}',                 [MenuController::class, 'destroy']);

    });
});

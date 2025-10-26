<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Leads\LeadController;
use App\Http\Controllers\Api\Leads\LeadCommentController;
use App\Http\Controllers\Api\Leads\LeadProductController;
use App\Http\Controllers\Api\Leads\LeadContactController;
use App\Http\Controllers\Api\SaleStageController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
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

    /*
    |----------------------------------------------------------------------
    | Authenticated routes
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // ---- Auth session ----
        Route::get('me',      [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        // ---- Dashboard / Stats ----
        Route::get('stats/overview', [DashboardController::class, 'overview']);

        // ---- Users ----
        Route::apiResource('users', UserController::class);
        Route::get('user-list', [UserController::class, 'userList']);

        Route::apiResource('leads', LeadController::class);
        Route::post('leads/account-manager/{lead}', [LeadController::class, 'assignAccountManager']);
        Route::post('/leads/bulk-importer',    [LeadController::class, 'bulkImporter']);
        Route::post('/leads/bulk-comment-importer',    [LeadController::class, 'bulkCommentImporter']);
        Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus']);


        // Contacts
        Route::get('leads/{lead}/contacts',             [LeadContactController::class, 'index']);
        Route::post('leads/{lead}/contacts',            [LeadContactController::class, 'store_update']);
        Route::delete('contacts/{contact}',             [LeadContactController::class, 'destroy']);
        Route::post('contacts/{contact}/primary',       [LeadContactController::class, 'setPrimary']);


        // Comments
        Route::get('leads/{lead}/comments',              [LeadCommentController::class, 'index']);
        Route::post('leads/{lead}/comments',             [LeadCommentController::class, 'store']);
        Route::delete('leads/{lead}/comments/{comment}', [LeadCommentController::class, 'destroy']);

        // Lead ↔ Products
        Route::get('leads/{lead}/products',            [LeadProductController::class, 'index']);
        Route::put('leads/{lead}/products',            [LeadProductController::class, 'assign']);
        Route::post('leads/{lead}/products',           [LeadProductController::class, 'assign']); // legacy
        Route::put('leads/{lead}/products/bulk',       [LeadProductController::class, 'bulkUpdate']);
        // Route::put('leads/{lead}/products/{product}',  [LeadProductController::class, 'updateSingle']);

        // Lookups
        Route::get('countries', [LookupController::class, 'countries']);

        // ---- Products ----
        Route::get('products',                 [ProductController::class, 'index']);
        Route::post('products',                [ProductController::class, 'store']);
        Route::get('products/{id}',            [ProductController::class, 'show']);
        Route::put('products/{id}',            [ProductController::class, 'update']);
        Route::patch('products/{id}/status',   [ProductController::class, 'toggleStatus']);
        Route::delete('products/{id}',         [ProductController::class, 'destroy']);


        // ---- Lead Stages ----
        Route::get('lead_stages',                 [SaleStageController::class, 'index']);
        Route::post('lead_stages',                [SaleStageController::class, 'store']);
        Route::get('lead_stages/{id}',            [SaleStageController::class, 'show']);
        Route::put('lead_stages/{id}',            [SaleStageController::class, 'update']);
        Route::patch('lead_stages/{id}/status',   [SaleStageController::class, 'toggleStatus']);
        Route::delete('lead_stages/{id}',         [SaleStageController::class, 'destroy']);
    });
});

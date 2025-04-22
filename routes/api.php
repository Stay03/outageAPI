<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OutageController;
use App\Http\Controllers\LocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// V1 API Routes
Route::prefix('v1')->group(function () {
    // Auth routes (public)
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        
        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
        });
    });
    
    // Protected routes with rate limiting
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // Outage routes
        Route::apiResource('outages', OutageController::class);
        
        // Location routes
        Route::apiResource('locations', LocationController::class);
        
        // Analytics routes will be added here later
    });
});

// Fallback for undefined API routes
Route::fallback(function() {
    return response()->json([
        'message' => 'Endpoint not found. If error persists, contact info@ourapp.com'
    ], 404);
});
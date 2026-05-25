<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\QuoteController;
use App\Http\Controllers\API\ClaimController;
use App\Http\Controllers\API\ActivityLogController;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES (Public + Protected)
|--------------------------------------------------------------------------
|*/
Route::prefix('auth')->middleware('throttle:api')->group(function () {

    // Public
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    // Protected
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        // Admin Only: Register new users
        Route::post('register', [AuthController::class, 'register'])->middleware('role:Admin');
        Route::patch('users/{id}/status', [AuthController::class, 'updateStatus'])->middleware('role:Admin');

        // Any logged-in user
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
    });
});

/*
|--------------------------------------------------------------------------
| PROTECTED API ROUTES
|--------------------------------------------------------------------------
|*/
Route::middleware(['auth:sanctum', 'throttle:api','active'])->group(function () {

    // Quote Routes
    Route::prefix('quotes')->group(function () {
        Route::get('/', [QuoteController::class, 'index'])->middleware('role:Admin,Agent,Customer');
        Route::post('/', [QuoteController::class, 'store'])->middleware('role:Admin,Agent');
        Route::get('/{id}', [QuoteController::class, 'show'])->middleware('role:Admin,Agent,Customer');
        Route::put('/{id}', [QuoteController::class, 'update'])->middleware('role:Admin,Agent');
        Route::delete('/{id}', [QuoteController::class, 'destroy'])->middleware('role:Admin');
    });

    // Claim Routes
    Route::prefix('claims')->group(function () {
        Route::get('/', [ClaimController::class, 'index'])->middleware('role:Admin,Agent,Customer');
        Route::post('/', [ClaimController::class, 'store'])->middleware('role:Admin,Agent,Customer');
        Route::get('/{id}', [ClaimController::class, 'show'])->middleware('role:Admin,Agent,Customer');
        Route::put('/{id}', [ClaimController::class, 'update'])->middleware('role:Admin,Agent');
        Route::patch('/{id}/status', [ClaimController::class, 'updateStatus'])->middleware('role:Admin,Agent');
    });

    // Activity Logs (Admin Only)
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->middleware('role:Admin');
});


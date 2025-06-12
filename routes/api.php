<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductTypeController;
use App\Http\Controllers\Api\ServiceActionController;
use App\Http\Controllers\Api\ServiceOfferingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SettingController;

// Public authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']); // Get authenticated user (used by ProfileController::show as well)

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // Dashboard
    Route::get('/dashboard-summary', [DashboardController::class, 'summary']);

    // Core CRUD
    Route::apiResource('customers', CustomerController::class);

    Route::post('/orders/quote-item', [OrderController::class, 'quoteOrderItem']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/payment', [OrderController::class, 'recordPayment']);
    Route::get('service-offerings/all-for-select', [ServiceOfferingController::class, 'allForSelect']);
    Route::get('product-types/all-for-select', [ProductTypeController::class, 'allForSelect']); // Add this if needed for dropdowns

    Route::apiResource('orders', OrderController::class);

    // Service Management Admin Routes
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('product-types', ProductTypeController::class);
    Route::apiResource('service-actions', ServiceActionController::class);
    Route::apiResource('service-offerings', ServiceOfferingController::class);
    // Add other service-related resources if needed (e.g., PricingRules)

    // Application Settings (Admin only - ensure proper authorization)
    Route::get('/settings', [SettingController::class, 'index'])->middleware('can:view_app_settings'); // Example middleware
    Route::put('/settings', [SettingController::class, 'update'])->middleware('can:update_app_settings'); // Example middleware

});

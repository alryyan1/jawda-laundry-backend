<?php

use App\Http\Controllers\Api\ExpenseController;
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
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;

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
// Inside Route::middleware('auth:sanctum')->group(function () { ...
  Route::get('/product-types/{productType}/available-service-actions', [App\Http\Controllers\Api\ProductTypeController::class, 'availableServiceActions']);
  Route::apiResource('admin/users', UserController::class); // Note the 'admin/' prefix
  Route::apiResource('admin/roles', RoleController::class);
  Route::get('admin/permissions', [PermissionController::class, 'index']); // Usually only 
  Route::post('/product-types/{productType}/create-all-service-offerings', [App\Http\Controllers\Api\ProductTypeController::class, 'createAllOfferings']);
  Route::get('/orders/{order}/invoice/download', [OrderController::class, 'downloadInvoice']);
  Route::get('/expenses/categories', [ExpenseController::class, 'getCategories']);
    Route::apiResource('expenses', ExpenseController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('purchases', PurchaseController::class);
    // Add a route for getting all suppliers for a dropdown
    Route::get('/suppliers-list', [SupplierController::class, 'all']);
  // });
});


Route::delete('/product-types/{productType}/image', [ProductTypeController::class, 'deleteImage']);
Route::apiResource('product-types', ProductTypeController::class);

Route::get('/dashboard/orders-trend', [App\Http\Controllers\Api\DashboardController::class, 'ordersTrend']);
Route::get('/dashboard/revenue-breakdown', [App\Http\Controllers\Api\DashboardController::class, 'revenueBreakdown']);

// Order Creation Related Endpoints

// Data Endpoints for Form Dropdowns and Logic
Route::get('/service-offerings/all-for-select', [ServiceOfferingController::class, 'allForSelect']);
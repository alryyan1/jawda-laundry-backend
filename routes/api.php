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
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WhatsappTemplateController;

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

  // Admin Management
  Route::apiResource('admin/users', UserController::class);
  Route::apiResource('admin/roles', RoleController::class);
  Route::get('admin/permissions', [PermissionController::class, 'index']);
  
  // Product Type Management
  Route::get('/product-types/{productType}/available-service-actions', [ProductTypeController::class, 'availableServiceActions']);
  Route::post('/product-types/{productType}/create-all-service-offerings', [ProductTypeController::class, 'createAllOfferings']);
  
  // Order Management
  Route::get('/orders/{order}/invoice/download', [OrderController::class, 'downloadInvoice']);
  
  // Expense Management
  Route::get('/expenses/categories', [ExpenseController::class, 'getCategories']);
  Route::apiResource('expenses', ExpenseController::class);
  
  // Supplier Management
  Route::apiResource('suppliers', SupplierController::class);
  Route::get('/suppliers-list', [SupplierController::class, 'all']);
  
  // Purchase Management
  Route::apiResource('purchases', PurchaseController::class);
  
  // Expense Categories
  Route::apiResource('expense-categories', ExpenseCategoryController::class);
  
  // Payment Management
  Route::apiResource('orders.payments', PaymentController::class)->except(['show', 'update']);
  
  // Application Settings
  Route::get('/settings', [SettingController::class, 'index']);
  Route::put('/settings', [SettingController::class, 'update']);
  Route::post('/settings/whatsapp/send-test', [SettingController::class, 'sendTestWhatsapp']);
    Route::post('/orders/{order}/send-whatsapp-invoice', [OrderController::class, 'sendWhatsappInvoice'])
    ->name('orders.invoice.whatsapp');
    Route::get('/whatsapp-templates', [WhatsappTemplateController::class, 'index']);
    Route::post('/whatsapp-templates', [WhatsappTemplateController::class, 'store']);
    Route::put('/whatsapp-templates/{whatsappTemplate}', [WhatsappTemplateController::class, 'update']);
  // });
});


Route::delete('/product-types/{productType}/image', [ProductTypeController::class, 'deleteImage']);
Route::apiResource('product-types', ProductTypeController::class);

Route::get('/dashboard/orders-trend', [App\Http\Controllers\Api\DashboardController::class, 'ordersTrend']);
Route::get('/dashboard/revenue-breakdown', [App\Http\Controllers\Api\DashboardController::class, 'revenueBreakdown']);

// Order Creation Related Endpoints

// Data Endpoints for Form Dropdowns and Logic
Route::get('/service-offerings/all-for-select', [ServiceOfferingController::class, 'allForSelect']);


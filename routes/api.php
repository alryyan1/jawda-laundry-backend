<?php

use App\Http\Controllers\Api\CustomerLedgerController;
use App\Http\Controllers\Api\ExpenseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerTypeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductTypeController;
use App\Http\Controllers\Api\ServiceActionController;
use App\Http\Controllers\Api\ServiceOfferingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PredefinedSizeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReportController;
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
   // and POST /product-types/{product_type}/predefined-sizes
   Route::apiResource('product-types.predefined-sizes', PredefinedSizeController::class)
   ->only(['index', 'store']);

// This defines DELETE /product-types/{product_type}/predefined-sizes/{predefined_size}
// Note: Laravel automatically singularizes the resource name for the parameter.
Route::delete(
  '/product-types/{product_type}/predefined-sizes/{predefined_size}',
  [PredefinedSizeController::class, 'destroy']
)->name('product-types.predefined-sizes.destroy');
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
  Route::get('/expenses/categories', [ExpenseCategoryController::class, 'index']);
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
  Route::post('/orders/{order}/send-whatsapp-message', [OrderController::class, 'sendWhatsappMessage'])
    ->name('orders.whatsapp.message');
    Route::get('/whatsapp-templates', [WhatsappTemplateController::class, 'index']);
    Route::post('/whatsapp-templates', [WhatsappTemplateController::class, 'store']);
    Route::put('/whatsapp-templates/{whatsappTemplate}', [WhatsappTemplateController::class, 'update']);
    Route::apiResource('customer-types', CustomerTypeController::class);
    // This defines GET /product-types/{product_type}/predefined-sizes
 
    // Product Type Management
    Route::delete('/product-types/{productType}/image', [ProductTypeController::class, 'deleteImage']);
    
    // Dashboard endpoints
    Route::get('/dashboard/orders-trend', [DashboardController::class, 'ordersTrend']);
    Route::get('/dashboard/revenue-breakdown', [DashboardController::class, 'revenueBreakdown']);
    Route::get('admin/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('admin/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/reports/cost-summary', [ReportController::class, 'costSummary']);
    Route::get('/reports/orders/export-csv', [OrderController::class, 'exportCsv']);
    Route::get('/reports/overdue-pickups', [ReportController::class, 'overduePickupOrders']);
    Route::get('/customers/{customer}/ledger', [CustomerLedgerController::class, 'show']);
    Route::get('/dashboard/today-summary', [App\Http\Controllers\Api\DashboardController::class, 'todaySummary']);
    Route::patch('/order-items/{id}/status', [\App\Http\Controllers\Api\OrderItemController::class, 'updateStatus']);
    Route::get('/reports/sales-summary', [ReportController::class, 'salesSummary']);


  });
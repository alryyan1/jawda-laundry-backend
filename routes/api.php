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
use App\Http\Controllers\Api\RestaurantTableController;
use App\Http\Controllers\Api\DiningTableController;
use App\Http\Controllers\Api\TableReservationController;
use App\Http\Controllers\Api\NavigationController;
use App\Http\Controllers\Api\CustomerPriceListController;
use App\Http\Controllers\Api\CustomerPricingRuleController;
use App\Http\Controllers\Api\SettingsController;

// Public authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('service-offerings/all-for-select', [ServiceOfferingController::class, 'allForSelect']);
Route::get('product-types/all-for-select', [ProductTypeController::class, 'allForSelect']); // Add this if needed for dropdowns

// Temporary public routes for testing
Route::get('/dining-tables', [DiningTableController::class, 'index']);
Route::get('/dining-tables/statistics', [DiningTableController::class, 'statistics']);
Route::get('/table-reservations/today', [TableReservationController::class, 'todayReservations']);

// Public download routes (no authentication required)
Route::get('/orders/{order}/invoice/download', [OrderController::class, 'downloadInvoice']);
Route::get('/orders/{order}/pos-invoice-pdf', [OrderController::class, 'downloadPosInvoice']);
Route::get('/orders/{order}/pos-invoice-height', [OrderController::class, 'getPosInvoiceHeight']);

// Public report PDF routes (no authentication required)
Route::get('/reports/orders/pdf', [ReportController::class, 'exportOrdersReportPdf']);
Route::get('/reports/orders/pdf/view', [ReportController::class, 'viewOrdersReportPdf']);

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
  Route::get('/orders/statistics', [OrderController::class, 'statistics']);
  Route::post('/orders/{order}/mark-complete', [OrderController::class, 'markOrderComplete']);


  Route::apiResource('orders', OrderController::class);
  Route::post('/orders/{order}/cancel', [OrderController::class, 'cancelOrder']);
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
  Route::apiResource('product-types', ProductTypeController::class);
  Route::apiResource('service-actions', ServiceActionController::class);
  Route::apiResource('service-offerings', ServiceOfferingController::class);
  Route::put('/product-types/{productTypeId}/first-offering-price', [ServiceOfferingController::class, 'updateFirstOfferingPrice']);
  // Add other service-related resources if needed (e.g., PricingRules)

  // Admin Management
  Route::apiResource('admin/users', UserController::class);
  Route::apiResource('admin/roles', RoleController::class);
  Route::get('admin/permissions', [PermissionController::class, 'index']);
  
  // Product Type Management
  Route::get('/product-types/{productType}/available-service-actions', [ProductTypeController::class, 'availableServiceActions']);
  Route::post('/product-types/{productType}/create-all-service-offerings', [ProductTypeController::class, 'createAllOfferings']);
  
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
  

  
  Route::post('/orders/{order}/send-whatsapp-invoice', [OrderController::class, 'sendWhatsappInvoice'])
    ->name('orders.invoice.whatsapp');
  Route::post('/orders/{order}/send-whatsapp-message', [OrderController::class, 'sendWhatsappMessage'])
    ->name('orders.whatsapp.message');
    Route::get('/whatsapp-templates', [WhatsappTemplateController::class, 'index']);
    Route::post('/whatsapp-templates', [WhatsappTemplateController::class, 'store']);
    Route::put('/whatsapp-templates/{whatsappTemplate}', [WhatsappTemplateController::class, 'update']);
    Route::apiResource('customer-types', CustomerTypeController::class);
    
    // Restaurant Table Management
    Route::apiResource('restaurant-tables', RestaurantTableController::class);
    Route::get('/restaurant-tables/available', [RestaurantTableController::class, 'available']);
    Route::patch('/restaurant-tables/{restaurantTable}/status', [RestaurantTableController::class, 'updateStatus']);
    
    // Dining Table Management (moved to public for testing)
    Route::patch('/dining-tables/{diningTable}/status', [DiningTableController::class, 'updateStatus']);
    
    // Table Reservation Management
    Route::apiResource('table-reservations', TableReservationController::class);
    Route::post('/table-reservations/{tableReservation}/assign-order', [TableReservationController::class, 'assignOrder']);
    
    // This defines GET /product-types/{product_type}/predefined-sizes
 
    // Product Type Management
    Route::delete('/product-types/{productType}/image', [ProductTypeController::class, 'deleteImage']);
    
    // Dashboard endpoints
    Route::get('/dashboard/orders-trend', [DashboardController::class, 'ordersTrend']);
    Route::get('/dashboard/order-items-trend', [DashboardController::class, 'orderItemsTrend']);
    Route::get('/dashboard/revenue-breakdown', [DashboardController::class, 'revenueBreakdown']);

    Route::get('/reports/cost-summary', [ReportController::class, 'costSummary']);
    Route::get('/reports/orders/export-csv', [OrderController::class, 'exportCsv']);
    Route::get('/reports/overdue-pickups', [ReportController::class, 'overduePickupOrders']);
    Route::get('/customers/{customer}/ledger', [CustomerLedgerController::class, 'show']);
    Route::get('/dashboard/today-summary', [App\Http\Controllers\Api\DashboardController::class, 'todaySummary']);
    Route::patch('/order-items/{id}/status', [\App\Http\Controllers\Api\OrderItemController::class, 'updateStatus']);
    Route::patch('/order-items/{id}/picked-up-quantity', [\App\Http\Controllers\Api\OrderItemController::class, 'updatePickedUpQuantity']);
    Route::delete('/order-items/{id}', [\App\Http\Controllers\Api\OrderItemController::class, 'destroy']);
    Route::get('/reports/sales-summary', [ReportController::class, 'salesSummary']);
    Route::get('/reports/daily-revenue', [ReportController::class, 'dailyRevenueReport']);
    Route::get('/reports/daily-costs', [ReportController::class, 'dailyCostsReport']);
    
    // Navigation Management Routes
    Route::get('/navigation/user', [NavigationController::class, 'getUserNavigation']);
    Route::apiResource('navigation', NavigationController::class)->except(['show']);
    Route::put('/navigation/order', [NavigationController::class, 'updateOrder']);
    Route::get('/users/{user}/navigation-permissions', [NavigationController::class, 'getUserPermissions']);
    Route::put('/users/{user}/navigation-permissions', [NavigationController::class, 'updateUserPermissions']);



  // Customer Price List Management
  Route::get('/customers/{customer}/price-list', [CustomerPriceListController::class, 'show']);
  Route::put('/customers/{customer}/price-list', [CustomerPriceListController::class, 'update']);
  Route::delete('/customers/{customer}/price-list', [CustomerPriceListController::class, 'destroy']);
  Route::get('/customers/{customer}/price-list/summary', [CustomerPriceListController::class, 'summary']);
  Route::get('/customers/{customer}/price-list/export', [CustomerPriceListController::class, 'export']);

  // Customer Product Type Management
                // Customer Pricing Rules
              Route::get('/customers/{customer}/pricing-rules', [CustomerPricingRuleController::class, 'index']);
              Route::get('/customers/{customer}/pricing-rules/all', [CustomerPricingRuleController::class, 'getAllForCustomer']);
              Route::post('/customers/{customer}/pricing-rules', [CustomerPricingRuleController::class, 'store']);
              Route::put('/customers/{customer}/pricing-rules/{pricingRule}', [CustomerPricingRuleController::class, 'update']);
              Route::delete('/customers/{customer}/pricing-rules/{pricingRule}', [CustomerPricingRuleController::class, 'destroy']);
              Route::get('/customers/{customer}/pricing-rules/available-service-offerings', [CustomerPricingRuleController::class, 'getAvailableServiceOfferings']);
Route::post('/customers/{customer}/pricing-rules/import-all', [CustomerPricingRuleController::class, 'importAllServiceOfferings']);
Route::get('/customers/{customer}/pricing-rules/products', [CustomerPricingRuleController::class, 'getCustomerProductsWithPricingRules']);


  });

// Reports routes (protected)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/reports/orders', [ReportController::class, 'getOrdersReport']);
});


Route::apiResource('product-categories', ProductCategoryController::class);
  // Application Settings
  Route::get('/settings', [SettingController::class, 'index']);
  Route::put('/settings', [SettingController::class, 'update']);
  Route::post('/settings/logo/upload', [SettingController::class, 'uploadLogo']);
  Route::delete('/settings/logo', [SettingController::class, 'deleteLogo']);
  Route::post('/settings/whatsapp/send-test', [SettingController::class, 'sendTestWhatsapp']);
  Route::get('admin/settings', [SettingController::class, 'index'])->name('settings.index');
  Route::put('admin/settings', [SettingController::class, 'update'])->name('settings.update');

  // New Database Settings API
  Route::get('/settings/public', [SettingsController::class, 'public']);
  Route::get('/settings/group/{group}', [SettingsController::class, 'getByGroup']);
  Route::get('/settings/key/{key}', [SettingsController::class, 'show']);
  Route::put('/settings/bulk', [SettingsController::class, 'update']);
  Route::put('/settings/single/{key}', [SettingsController::class, 'updateSingle']);
  Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache']);

  // Specific settings endpoints
  Route::get('/settings/company/info', [SettingsController::class, 'companyInfo']);
  Route::get('/settings/app/branding', [SettingsController::class, 'appBranding']);
  Route::get('/settings/pos/config', [SettingsController::class, 'posConfig']);
  Route::get('/settings/whatsapp/config', [SettingsController::class, 'whatsappConfig']);
  Route::get('/settings/theme/config', [SettingsController::class, 'themeConfig']);


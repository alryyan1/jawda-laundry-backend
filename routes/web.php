<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Public download routes (no authentication required)
Route::get('/orders/{order}/invoice/download', [OrderController::class, 'downloadInvoice'])
     ->name('orders.invoice.download');
Route::get('/orders/{order}/pos-invoice-pdf', [OrderController::class, 'downloadPosInvoice'])
     ->name('orders.invoice.pos.pdf');

Route::get('/reports/orders/pdf/view', [ReportController::class, 'viewOrdersReportPdf']);
Route::get('/orders/pdf/download', [ReportController::class, 'exportOrdersReportPdf'])
     ->name('orders.pdf.download');
Route::get('/orders/excel/download', [OrderController::class, 'exportCsv'])
     ->name('orders.excel.download');

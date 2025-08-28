<?php
// Composer autoload
require __DIR__ . '/vendor/autoload.php';
// Bootstrap Laravel
$app = require __DIR__ . '/bootstrap/app.php';
/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;

try {
    // Fetch a sample set of orders
    $orders = Order::with([
        'customer',
        'items.serviceOffering.productType.category',
        'items.serviceOffering.serviceAction',
        'payments'
    ])->orderBy('id', 'desc')->limit(50)->get();

    $export = new \App\Excel\OrdersExcelExport();
    $export->setOrders($orders);
    $export->setFilters([]);
    $export->setSettings([
        'company_name' => \app_setting('company_name', config('app.name')),
        'company_address' => \app_setting('company_address'),
        'currency_symbol' => \app_setting('currency_symbol', 'OMR'),
    ]);

    $content = $export->generate();
    $path = __DIR__ . '/storage/app/orders_report_test.xlsx';
    file_put_contents($path, $content);
    echo "OK: $path\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}



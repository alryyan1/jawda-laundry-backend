<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Expense;

try {
    $expenses = Expense::with(['user:id,name', 'category:id,name'])
        ->orderBy('id', 'desc')
        ->limit(100)
        ->get();

    $pdf = new \App\Pdf\ExpensesListPdf();
    $pdf->setExpenses($expenses);
    $pdf->setFilters([]);
    $pdf->setSettings([
        'company_name' => \app_setting('company_name', config('app.name')),
        'company_address' => \app_setting('company_address'),
        'currency_symbol' => \app_setting('currency_symbol', 'OMR'),
    ]);

    $content = $pdf->generate();
    $path = __DIR__ . '/storage/app/expenses_report_test.pdf';
    file_put_contents($path, $content);
    echo "OK: $path\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}



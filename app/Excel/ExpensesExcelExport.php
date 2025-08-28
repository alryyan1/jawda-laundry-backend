<?php

namespace App\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExpensesExcelExport
{
    private $expenses;
    private $filters = [];
    private $settings = [];

    public function setExpenses($expenses)
    {
        $this->expenses = $expenses;
    }

    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    public function generate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $row = 1;
        $companyName = $this->settings['company_name'] ?? 'Company';
        $sheet->setCellValue('A' . $row, $companyName . ' - Expenses Report');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row += 2;

        // Filters
        if (!empty($this->filters)) {
            $filtersText = [];
            if (!empty($this->filters['date_from'])) $filtersText[] = 'From: ' . $this->filters['date_from'];
            if (!empty($this->filters['date_to'])) $filtersText[] = 'To: ' . $this->filters['date_to'];
            if (!empty($this->filters['search'])) $filtersText[] = 'Search: ' . $this->filters['search'];
            if (!empty($this->filters['expense_category_id'])) $filtersText[] = 'Category ID: ' . $this->filters['expense_category_id'];
            $sheet->setCellValue('A' . $row, 'Filters: ' . implode(' | ', $filtersText));
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $row++;
        }

        $row++;

        // Table headers
        $headers = ['ID', 'Name', 'Category', 'Amount', 'Payment Method', 'Expense Date', 'Recorded By', 'Description'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $row, $h);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;

        $totalAmount = 0;

        foreach ($this->expenses as $expense) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $expense->id);
            $sheet->setCellValue($col++ . $row, $expense->name);
            $categoryName = $expense->category_name ?? ($expense->category->name ?? null) ?? '-';
            $sheet->setCellValue($col++ . $row, $categoryName);
            $sheet->setCellValue($col++ . $row, number_format((float)$expense->amount, 3));
            $sheet->setCellValue($col++ . $row, $expense->payment_method ?? '-');
            $sheet->setCellValue($col++ . $row, $expense->expense_date ?? '-');
            $sheet->setCellValue($col++ . $row, $expense->user->name ?? '-');
            $sheet->setCellValue($col++ . $row, $expense->description ?? '');
            $totalAmount += (float)$expense->amount;
            $row++;
        }

        // Totals
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('D' . $row, number_format($totalAmount, 3) . ' ' . ($this->settings['currency_symbol'] ?? ''));
        $sheet->getStyle('D' . $row)->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', 'H') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return (string) ob_get_clean();
    }
}



<?php

namespace App\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OrdersPaymentsExcelExport
{
    protected $orders;
    protected $filters;
    protected $settings;

    public function setOrders($orders)
    {
        $this->orders = $orders;
        return $this;
    }

    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
        return $this;
    }

    public function generate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Orders Payments');

        // Header title
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', ($this->settings['company_name'] ?? 'Restaurant Service') . ' - Orders Payments Report');
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ECF0F1'],
            ],
        ]);

        // Filters row
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'Filters: ' . $this->getFilterText());
        $sheet->getStyle('A2:F2')->applyFromArray([
            'font' => ['size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Column headers
        $headers = ['ID', 'Daily Number', 'Paid Amount', 'Payment Method', 'Order Item Names', 'Sum'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }
        $sheet->getStyle('A4:F4')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D6DBDF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'ABB2B9'],
                ],
            ],
        ]);

        // Data rows
        $row = 5;
        $totalPaid = 0.0;
        $ordersTotalSum = 0.0;

        foreach ($this->orders as $order) {
            // Prepare items string
            $itemNames = '';
            if ($order->items) {
                $names = [];
                foreach ($order->items as $item) {
                    $names[] = ($item->serviceOffering->productType->name ?? 'N/A');
                }
                $itemNames = implode(', ', $names);
            }

            $orderTotalAmount = (float) ($order->total_amount ?? 0);
            $ordersTotalSum += $orderTotalAmount;

            $hasPayments = false;
            if ($order->payments && count($order->payments) > 0) {
                foreach ($order->payments as $payment) {
                    // Consider only actual payments when type field exists
                    if (isset($payment->type) && $payment->type !== 'payment') {
                        continue;
                    }
                    $hasPayments = true;
                    $paid = (float) ($payment->amount ?? 0);
                    $totalPaid += $paid;
                    $sheet->setCellValueExplicit('A' . $row, (string) $order->id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue('B' . $row, $order->daily_order_number ?? '');
                    $sheet->setCellValue('C' . $row, $paid);
                    $sheet->setCellValue('D' . $row, $payment->method ?? 'unknown');
                    $sheet->setCellValue('E' . $row, $itemNames);
                    $sheet->setCellValue('F' . $row, $orderTotalAmount);
                    $row++;
                }
            }

            // If no payments, still list the order with zero paid amount
            if (!$hasPayments) {
                $sheet->setCellValueExplicit('A' . $row, (string) $order->id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('B' . $row, $order->daily_order_number ?? '');
                $sheet->setCellValue('C' . $row, 0);
                $sheet->setCellValue('D' . $row, '');
                $sheet->setCellValue('E' . $row, $itemNames);
                $sheet->setCellValue('F' . $row, $orderTotalAmount);
                $row++;
            }
        }

        // Totals row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('C' . $row, $totalPaid);
        $sheet->setCellValue('F' . $row, $ordersTotalSum);
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8F9F9'],
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '2C3E50']],
                'bottom' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '2C3E50']],
            ],
        ]);

        // Borders and alignment for data area
        $sheet->getStyle('A4:F' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDC3C7'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Autosize columns
        foreach (range('A', 'F') as $colChar) {
            $sheet->getColumnDimension($colChar)->setAutoSize(true);
        }

        // Output
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    private function getFilterText(): string
    {
        $parts = [];
        if (!empty($this->filters['shift_id'])) {
            $parts[] = 'Shift: #' . $this->filters['shift_id'];
        } elseif (!empty($this->filters['date_from']) && !empty($this->filters['date_to'])) {
            $parts[] = 'Date: ' . $this->filters['date_from'] . ' to ' . $this->filters['date_to'];
        }
        if (!empty($this->filters['status'])) {
            $parts[] = 'Status: ' . ucfirst($this->filters['status']);
        }
        if (!empty($this->filters['search'])) {
            $parts[] = 'Search: ' . $this->filters['search'];
        }
        if (!empty($this->filters['order_id'])) {
            $parts[] = 'Order ID: ' . $this->filters['order_id'];
        }
        return $parts ? implode(', ', $parts) : 'All Orders';
    }
}



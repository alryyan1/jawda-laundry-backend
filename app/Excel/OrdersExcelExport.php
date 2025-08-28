<?php

namespace App\Excel;

use App\Models\Order;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class OrdersExcelExport
{
    protected $orders;
    protected $filters;
    protected $settings;
    protected $spreadsheet;
    protected $worksheet;

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
        $this->spreadsheet = new Spreadsheet();
        $this->worksheet = $this->spreadsheet->getActiveSheet();
        
        // Set document properties
        $this->setDocumentProperties();
        
        // Create summary sheet
        $this->createSummarySheet();
        
        // Create orders sheet
        $this->createOrdersSheet();
        
        // Create detailed items sheet
        $this->createDetailedItemsSheet();
        
        // Create charts sheet
        $this->createChartsSheet();
        
        // Generate the Excel file
        $writer = new Xlsx($this->spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    private function setDocumentProperties()
    {
        $this->spreadsheet->getProperties()
                            ->setCreator($this->settings['company_name'] ?? 'Restaurant System')
                ->setLastModifiedBy($this->settings['company_name'] ?? 'Restaurant System')
            ->setTitle('Orders Report')
            ->setSubject('Orders Export Report')
            ->setDescription('Professional orders export report with detailed analysis')
                            ->setKeywords('orders, restaurant, report, export')
            ->setCategory('Reports');
    }

    private function createSummarySheet()
    {
        $this->worksheet->setTitle('Summary');
        
        // Company header
        $this->worksheet->mergeCells('A1:H1');
        $this->worksheet->setCellValue('A1', $this->settings['company_name'] ?? 'Restaurant Service');
        $this->applyHeaderStyle('A1:H1');
        
        $this->worksheet->mergeCells('A2:H2');
        $this->worksheet->setCellValue('A2', 'Orders Report - Executive Summary');
        $this->applySubHeaderStyle('A2:H2');
        
        // Filters info
        $this->worksheet->mergeCells('A3:H3');
        $this->worksheet->setCellValue('A3', 'Filters: ' . $this->getFilterText());
        $this->worksheet->getStyle('A3:H3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '2C3E50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        
        // Report metadata
        $this->worksheet->setCellValue('A5', 'Generated:');
        $this->worksheet->setCellValue('B5', date('F j, Y \a\t g:i A'));
        $this->worksheet->setCellValue('A6', 'Report Period:');
        $this->worksheet->setCellValue('B6', $this->getFilterText());
        
        // Summary statistics
        $this->createSummaryStatistics();
        
        // Apply borders to summary cards
        $this->applySummaryCardBorders();
        
        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $this->worksheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function createSummaryStatistics()
    {
        $totalOrders = $this->orders->count();
        $totalAmount = $this->orders->sum('total_amount');
        $totalPaid = $this->orders->sum('paid_amount');
        $totalDue = $totalAmount - $totalPaid;
        $averageOrderValue = $totalOrders > 0 ? $totalAmount / $totalOrders : 0;
        
        // Status breakdown
        $statusBreakdown = $this->orders->groupBy('status')->map->count();
        
        // Create summary cards
        $this->createSummaryCard('A8', 'Total Orders', $totalOrders, 'orders');
        $this->createSummaryCard('C8', 'Total Revenue', number_format($totalAmount, 3) . ' ' . ($this->settings['currency_symbol'] ?? 'OMR'), 'revenue');
        $this->createSummaryCard('E8', 'Total Paid', number_format($totalPaid, 3) . ' ' . ($this->settings['currency_symbol'] ?? 'OMR'), 'paid');
        $this->createSummaryCard('A10', 'Outstanding', number_format($totalDue, 3) . ' ' . ($this->settings['currency_symbol'] ?? 'OMR'), 'due');
        $this->createSummaryCard('C10', 'Average Order', number_format($averageOrderValue, 3) . ' ' . ($this->settings['currency_symbol'] ?? 'OMR'), 'avg');
        
        // Status breakdown table
        $this->createStatusBreakdownTable($statusBreakdown, 'A14');
    }

    private function createSummaryCard($cell, $title, $value, $type)
    {
        $this->worksheet->setCellValue($cell, $title);
        $this->worksheet->setCellValue($cell . '1', $value);
        
        // Style the card
        $this->worksheet->getStyle($cell)->getFont()->setBold(true)->setSize(10);
        $this->worksheet->getStyle($cell . '1')->getFont()->setBold(true)->setSize(12);
        $this->worksheet->getStyle($cell . '1')->getFont()->setColor($this->getCardColor($type));
        
        // Apply borders to the card
        $this->worksheet->getStyle($cell . ':' . $cell . '1')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDC3C7'],
                ],
            ],
        ]);
    }

    private function createStatusBreakdownTable($statusBreakdown, $startCell)
    {
        $this->worksheet->setCellValue($startCell, 'Status Breakdown');
        $this->worksheet->getStyle($startCell)->getFont()->setBold(true)->setSize(12);
        
        $row = $this->worksheet->getCell($startCell)->getRow() + 2;
        $col = $this->worksheet->getCell($startCell)->getColumn();
        
        // Headers
        $this->worksheet->setCellValue($col . $row, 'Status');
        $this->worksheet->setCellValue(chr(ord($col) + 1) . $row, 'Count');
        $this->worksheet->setCellValue(chr(ord($col) + 2) . $row, 'Percentage');
        
        $this->applyTableHeaderStyle($col . $row . ':' . chr(ord($col) + 2) . $row);
        
        $row++;
        $totalOrders = $this->orders->count();
        
        foreach ($statusBreakdown as $status => $count) {
            $percentage = $totalOrders > 0 ? ($count / $totalOrders) * 100 : 0;
            
            $this->worksheet->setCellValue($col . $row, ucfirst($status));
            $this->worksheet->setCellValue(chr(ord($col) + 1) . $row, $count);
            $this->worksheet->setCellValue(chr(ord($col) + 2) . $row, number_format($percentage, 1) . '%');
            
            $row++;
        }
    }

    private function createOrdersSheet()
    {
        $ordersSheet = $this->spreadsheet->createSheet();
        $ordersSheet->setTitle('Orders');
        
        // Company header
        $ordersSheet->mergeCells('A1:N1');
        $ordersSheet->setCellValue('A1', $this->settings['company_name'] ?? 'Restaurant Service');
        $this->applyHeaderStyle('A1:N1', $ordersSheet);
        
        // Report title
        $ordersSheet->mergeCells('A2:N2');
        $ordersSheet->setCellValue('A2', 'Orders Report');
        $this->applySubHeaderStyle('A2:N2', $ordersSheet);
        
        // Filters info
        $ordersSheet->mergeCells('A3:N3');
        $ordersSheet->setCellValue('A3', 'Filters: ' . $this->getFilterText());
        $ordersSheet->getStyle('A3:N3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '2C3E50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        
        // Generated date
        $ordersSheet->mergeCells('A4:N4');
        $ordersSheet->setCellValue('A4', 'Generated: ' . date('F j, Y \a\t g:i A'));
        $ordersSheet->getStyle('A4:N4')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '7F8C8D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        
        // Headers (starting at row 6)
        $headers = [
            'ID', 'Order Number', 'Customer Name', 'Customer Phone', 'Status',
            'Order Date', 'Due Date', 'Pickup Date', 'Total Amount', 'Amount Paid', 'Amount Due',
            'Category Sequences', 'Notes'
        ];
        
        $col = 'A';
        foreach ($headers as $header) {
            $ordersSheet->setCellValue($col . '6', $header);
            $col++;
        }
        
        // Apply header styling
        $this->applyTableHeaderStyle('A6:' . chr(ord($col) - 1) . '6', $ordersSheet);
        
        // Data rows
        $row = 7;
        foreach ($this->orders as $order) {
            $ordersSheet->setCellValue('A' . $row, $order->id);
            $ordersSheet->setCellValue('B' . $row, $order->id);
            $ordersSheet->setCellValue('C' . $row, $order->customer ? $order->customer->name : 'N/A');
            $ordersSheet->setCellValue('D' . $row, $order->customer ? $order->customer->phone : 'N/A');
            $ordersSheet->setCellValue('E' . $row, ucfirst($order->status));
            $ordersSheet->setCellValue('F' . $row, $order->order_date ? date('Y-m-d H:i', strtotime($order->order_date)) : '');
            $ordersSheet->setCellValue('G' . $row, $order->due_date ? date('Y-m-d', strtotime($order->due_date)) : '');
            $ordersSheet->setCellValue('H' . $row, $order->pickup_date ? date('Y-m-d H:i', strtotime($order->pickup_date)) : '');
            $ordersSheet->setCellValue('I' . $row, $order->total_amount);
            $ordersSheet->setCellValue('J' . $row, $order->paid_amount);
            $ordersSheet->setCellValue('K' . $row, $order->amount_due);
            $ordersSheet->setCellValue('L' . $row, $order->category_sequences_string ?? '');
            $ordersSheet->setCellValue('M' . $row, $order->notes ?? '');
            
            $row++;
        }
        
        // Add totals row
        $totalRow = $row;
        $ordersSheet->setCellValue('A' . $totalRow, 'TOTAL');
        $ordersSheet->mergeCells('A' . $totalRow . ':H' . $totalRow);
        $ordersSheet->setCellValue('I' . $totalRow, '=SUM(I7:I' . ($row - 1) . ')');
        $ordersSheet->setCellValue('J' . $totalRow, '=SUM(J7:J' . ($row - 1) . ')');
        $ordersSheet->setCellValue('K' . $totalRow, '=SUM(K7:K' . ($row - 1) . ')');
        
        // Style totals row
        $ordersSheet->getStyle('A' . $totalRow . ':N' . $totalRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ECF0F1'],
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '2C3E50']],
                'bottom' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '2C3E50']],
            ],
        ]);
        
        // Format currency columns
        $ordersSheet->getStyle('I7:K' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.000');
        $ordersSheet->getStyle('I' . $totalRow . ':K' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.000');
        
        // Apply borders to all data cells
        $ordersSheet->getStyle('A6:N' . ($row - 1))->applyFromArray([
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
        
        // Center align specific columns
        $ordersSheet->getStyle('C7:C' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Customer name
        $ordersSheet->getStyle('L7:L' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Category sequences
        $ordersSheet->getStyle('M7:M' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Notes
        
        // Auto-size columns
        foreach (range('A', 'N') as $col) {
            $ordersSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add filters
        $ordersSheet->setAutoFilter('A6:N6');
    }

    private function createDetailedItemsSheet()
    {
        $itemsSheet = $this->spreadsheet->createSheet();
        $itemsSheet->setTitle('Order Items');
        
        // Company header
        $itemsSheet->mergeCells('A1:K1');
        $itemsSheet->setCellValue('A1', $this->settings['company_name'] ?? 'Restaurant Service');
        $this->applyHeaderStyle('A1:K1', $itemsSheet);
        
        // Report title
        $itemsSheet->mergeCells('A2:K2');
        $itemsSheet->setCellValue('A2', 'Order Items Report');
        $this->applySubHeaderStyle('A2:K2', $itemsSheet);
        
        // Filters info
        $itemsSheet->mergeCells('A3:K3');
        $itemsSheet->setCellValue('A3', 'Filters: ' . $this->getFilterText());
        $itemsSheet->getStyle('A3:K3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '2C3E50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        
        // Generated date
        $itemsSheet->mergeCells('A4:K4');
        $itemsSheet->setCellValue('A4', 'Generated: ' . date('F j, Y \a\t g:i A'));
        $itemsSheet->getStyle('A4:K4')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '7F8C8D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        
        // Headers (starting at row 6)
        $headers = [
            'Order ID', 'Customer', 'Product Type', 'Service', 'Quantity', 'Dimensions',
            'Price/Unit', 'Subtotal', 'Status', 'Picked Up Qty'
        ];
        
        $col = 'A';
        foreach ($headers as $header) {
            $itemsSheet->setCellValue($col . '6', $header);
            $col++;
        }
        
        // Apply header styling
        $this->applyTableHeaderStyle('A6:' . chr(ord($col) - 1) . '6', $itemsSheet);
        
        // Data rows
        $row = 7;
        foreach ($this->orders as $order) {
            foreach ($order->items as $item) {
                $productType = $item->serviceOffering && $item->serviceOffering->productType 
                    ? $item->serviceOffering->productType->name 
                    : 'N/A';
                $serviceName = $item->serviceOffering && $item->serviceOffering->serviceAction
                    ? $item->serviceOffering->serviceAction->name
                    : 'N/A';
                $dimensions = '';
                if ($item->length_meters && $item->width_meters) {
                    $dimensions = $item->length_meters . 'm x ' . $item->width_meters . 'm';
                }
                
                $itemsSheet->setCellValue('A' . $row, $order->id);
                $itemsSheet->setCellValue('B' . $row, $order->customer ? $order->customer->name : 'N/A');
                $itemsSheet->setCellValue('C' . $row, $productType);
                $itemsSheet->setCellValue('D' . $row, $serviceName);
                $itemsSheet->setCellValue('E' . $row, $item->quantity);
                $itemsSheet->setCellValue('F' . $row, $dimensions);
                $itemsSheet->setCellValue('G' . $row, $item->calculated_price_per_unit_item);
                $itemsSheet->setCellValue('H' . $row, $item->sub_total);
                $itemsSheet->setCellValue('I' . $row, ucfirst($item->status ?? 'pending'));
                $itemsSheet->setCellValue('J' . $row, $item->picked_up_quantity ?? 0);
                
                $row++;
            }
        }
        
        // Add totals row
        $totalRow = $row;
        $itemsSheet->setCellValue('A' . $totalRow, 'TOTAL');
        $itemsSheet->mergeCells('A' . $totalRow . ':G' . $totalRow);
        $itemsSheet->setCellValue('H' . $totalRow, '=SUM(H7:H' . ($row - 1) . ')');
        
        // Style totals row
        $itemsSheet->getStyle('A' . $totalRow . ':K' . $totalRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ECF0F1'],
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '2C3E50']],
                'bottom' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '2C3E50']],
            ],
        ]);
        
        // Format currency columns
        $itemsSheet->getStyle('G7:H' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.000');
        $itemsSheet->getStyle('H' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.000');
        
        // Apply borders to all data cells
        $itemsSheet->getStyle('A6:K' . ($row - 1))->applyFromArray([
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
        
        // Left align specific columns
        $itemsSheet->getStyle('B7:B' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Customer
        $itemsSheet->getStyle('C7:C' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Product Type
        $itemsSheet->getStyle('D7:D' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Service
        
        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $itemsSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add filters
        $itemsSheet->setAutoFilter('A6:K6');
    }

    private function createChartsSheet()
    {
        $chartsSheet = $this->spreadsheet->createSheet();
        $chartsSheet->setTitle('Charts');
        
        // Create status pie chart
        $this->createStatusPieChart($chartsSheet);
        
        // Create revenue trend chart
        $this->createRevenueTrendChart($chartsSheet);
    }

    private function createStatusPieChart($sheet)
    {
        $statusBreakdown = $this->orders->groupBy('status')->map->count();
        
        // Data for chart
        $dataRow = 2;
        foreach ($statusBreakdown as $status => $count) {
            $sheet->setCellValue('A' . $dataRow, ucfirst($status));
            $sheet->setCellValue('B' . $dataRow, $count);
            $dataRow++;
        }
        
        // Create chart
        $dataSeriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Charts!$A$2:$A$' . ($dataRow - 1), null, count($statusBreakdown)),
        ];
        $dataSeriesValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Charts!$B$2:$B$' . ($dataRow - 1), null, count($statusBreakdown)),
        ];
        
        $series = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            [],
            $dataSeriesValues
        );
        
        $plot = new PlotArea(null, [$series]);
        $legend = new Legend();
        $title = new Title('Order Status Distribution');
        
        $chart = new Chart(
            'status_chart',
            $title,
            $legend,
            $plot
        );
        
        $chart->setTopLeftPosition('D2');
        $chart->setBottomRightPosition('K15');
        
        $sheet->addChart($chart);
    }

    private function createRevenueTrendChart($sheet)
    {
        // Group orders by date
        $dailyRevenue = $this->orders->groupBy(function($order) {
            return date('Y-m-d', strtotime($order->order_date));
        })->map(function($orders) {
            return $orders->sum('total_amount');
        })->sortKeys();
        
        // Data for chart
        $dataRow = 2;
        foreach ($dailyRevenue as $date => $revenue) {
            $sheet->setCellValue('A' . $dataRow, $date);
            $sheet->setCellValue('B' . $dataRow, $revenue);
            $dataRow++;
        }
        
        if ($dataRow > 2) {
            // Create chart
            $dataSeriesLabels = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Charts!$A$2:$A$' . ($dataRow - 1), null, count($dailyRevenue)),
            ];
            $dataSeriesValues = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Charts!$B$2:$B$' . ($dataRow - 1), null, count($dailyRevenue)),
            ];
            
            $series = new DataSeries(
                DataSeries::TYPE_LINECHART,
                null,
                range(0, count($dataSeriesValues) - 1),
                $dataSeriesLabels,
                [],
                $dataSeriesValues
            );
            
            $plot = new PlotArea(null, [$series]);
            $legend = new Legend();
            $title = new Title('Daily Revenue Trend');
            
            $chart = new Chart(
                'revenue_chart',
                $title,
                $legend,
                $plot
            );
            
            $chart->setTopLeftPosition('D17');
            $chart->setBottomRightPosition('K30');
            
            $sheet->addChart($chart);
        }
    }

    private function applyHeaderStyle($range, $worksheet = null)
    {
        $worksheet = $worksheet ?: $this->worksheet;
        $worksheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2980B9'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function applySubHeaderStyle($range, $worksheet = null)
    {
        $worksheet = $worksheet ?: $this->worksheet;
        $worksheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '2C3E50'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function applyTableHeaderStyle($range, $worksheet = null)
    {
        $worksheet = $worksheet ?: $this->worksheet;
        $worksheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '34495E'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '2C3E50'],
                ],
            ],
        ]);
    }

    private function getCardColor($type)
    {
        $colors = [
            'orders' => '2ECC71', // Green
            'revenue' => '3498DB', // Blue
            'paid' => '9B59B6',   // Purple
            'due' => 'E74C3C',    // Red
            'avg' => 'F1C40F',    // Yellow
        ];
        
        return new \PhpOffice\PhpSpreadsheet\Style\Color($colors[$type] ?? '95A5A6');
    }

    private function applySummaryCardBorders()
    {
        // Apply borders to summary cards
        $this->worksheet->getStyle('A8:B9')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDC3C7'],
                ],
            ],
        ]);
        
        $this->worksheet->getStyle('C8:D9')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDC3C7'],
                ],
            ],
        ]);
        
        $this->worksheet->getStyle('E8:F9')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDC3C7'],
                ],
            ],
        ]);
        
        $this->worksheet->getStyle('A10:B11')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDC3C7'],
                ],
            ],
        ]);
        
        $this->worksheet->getStyle('C10:D11')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDC3C7'],
                ],
            ],
        ]);
    }

    private function getFilterText()
    {
        $filters = [];
        
        if (!empty($this->filters['date_from']) && !empty($this->filters['date_to'])) {
            $filters[] = 'Date: ' . $this->filters['date_from'] . ' to ' . $this->filters['date_to'];
        }
        
        if (!empty($this->filters['status'])) {
            $filters[] = 'Status: ' . ucfirst($this->filters['status']);
        }
        
        if (!empty($this->filters['search'])) {
            $filters[] = 'Search: ' . $this->filters['search'];
        }
        
        if (!empty($this->filters['order_id'])) {
            $filters[] = 'Order ID: ' . $this->filters['order_id'];
        }
        
        return implode(', ', $filters) ?: 'All Orders';
    }
}

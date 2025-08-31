<?php

namespace App\Pdf;

use App\Models\Order;
use TCPDF;
use Exception;

class OrdersListPdf extends TCPDF
{
    protected $orders;
    protected $filters;
    protected $settings;
    protected $font = 'arial';
    protected $companyName;
    protected $companyAddress;
    protected $currencySymbol;

    public function setOrders($orders)
    {
        $this->orders = $orders;
    }

    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
        $this->companyName = $settings['company_name'] ?? 'Restaurant Service';
        $this->companyAddress = $settings['company_address'] ?? '';
        $this->currencySymbol = $settings['currency_symbol'] ?? 'OMR';
    }

    public function Header()
    {
        // Simple header without background
        $this->SetFont($this->font, 'B', 16);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 8, $this->companyName, 0, 1, 'C');
        
        if ($this->companyAddress) {
            $this->SetFont($this->font, '', 10);
            $this->Cell(0, 6, $this->companyAddress, 0, 1, 'C');
        }
        
        $this->SetFont($this->font, 'B', 12);
        $this->Cell(0, 6, 'Orders List Report', 0, 1, 'C');
        
        // Add filters display in clean way
        $this->addCleanFiltersDisplay();
        
        // Add some space after header
        $this->Ln(10);
    }

    public function Footer()
    {
        $this->SetY(-20);
        
        // Calculate footer width (page width minus left and right margins)
        $pageWidth = $this->GetPageWidth();
        $leftMargin = $this->lMargin;
        $rightMargin = $this->rMargin;
        $footerWidth = $pageWidth - $leftMargin - $rightMargin;
        
        // Footer line
        $this->SetDrawColor(200, 200, 200);
        $this->Line($leftMargin, $this->GetY() - 5, $pageWidth - $rightMargin, $this->GetY() - 5);
        
        // Footer content
        $this->SetFont($this->font, '', 9);
        $this->SetTextColor(128, 128, 128);
        
        // Calculate column widths for footer
        $colWidth = $footerWidth / 3;
        
        // Left side - Company info
        $this->Cell($colWidth, 8, $this->companyName . ' - Orders Report', 0, 0, 'L');
        
        // Center - Page info
        $this->Cell($colWidth, 8, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
        
        // Right side - Date
        $this->Cell($colWidth, 8, 'Generated: ' . date('M j, Y'), 0, 1, 'R');
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
        
        return implode(', ', $filters);
    }

    public function generate()
    {
        // Set A4 portrait orientation
        $this->AddPage('P');
        $this->SetFont($this->font, '', 11);

        // Show new orders table with heading first
        $this->generateOrdersTable();
        
        // Add summary on new page
        $this->generateSummary();

        return $this->Output('', 'S');
    }

    private function generateSummary()
    {
        // Add new page for summary
        $this->AddPage('P');
        
        // Calculate available width
        $pageWidth = $this->GetPageWidth();
        $leftMargin = $this->lMargin;
        $rightMargin = $this->rMargin;
        $topMargin = $this->tMargin;
        $availableWidth = $pageWidth - $leftMargin - $rightMargin;
        
        // Calculate proper top margin for summary (same as other pages)
        $headerHeight = 35;
        $currentY = $this->GetY();
        $requiredY = $topMargin + $headerHeight + 10; // 10mm spacing after header
        
        if ($currentY < $requiredY) {
            $this->SetY($requiredY);
        }
        
        // Summary section with light background
        $this->SetFillColor(248, 249, 250); // Light gray background
        $this->Rect($leftMargin, $this->GetY(), $availableWidth, 60, 'F');
        
        // Summary title
        $this->SetFont($this->font, 'B', 16);
        $this->SetTextColor(52, 73, 94);
        $this->Cell(0, 10, 'Orders Summary', 0, 1, 'C');
        
        $this->Ln(5);
        
        // Calculate summary data for orders
        $ordersCount = $this->orders->count();
        $ordersAmount = $this->orders->sum('total_amount');
        $ordersPaid = $this->orders->sum('paid_amount');
        $ordersDue = $ordersAmount - $ordersPaid;
        $ordersAverage = $ordersCount > 0 ? $ordersAmount / $ordersCount : 0;
        
        // Create summary table
        $this->SetFont($this->font, 'B', 12);
        $this->SetTextColor(0, 0, 0);
        
        // Table header
        $this->Cell($availableWidth * 0.5, 8, 'Metric', 1, 0, 'C', true);
        $this->Cell($availableWidth * 0.5, 8, 'Value', 1, 1, 'C', true);
        
        // Table data
        $this->SetFont($this->font, '', 10);
        
        $this->Cell($availableWidth * 0.5, 8, 'Total Orders', 1, 0, 'L');
        $this->Cell($availableWidth * 0.5, 8, $ordersCount, 1, 1, 'C');
        
        $this->Cell($availableWidth * 0.5, 8, 'Total Amount', 1, 0, 'L');
        $this->Cell($availableWidth * 0.5, 8, number_format($ordersAmount, 3) . ' ' . $this->currencySymbol, 1, 1, 'C');
        
        $this->Cell($availableWidth * 0.5, 8, 'Total Paid', 1, 0, 'L');
        $this->Cell($availableWidth * 0.5, 8, number_format($ordersPaid, 3) . ' ' . $this->currencySymbol, 1, 1, 'C');
        
        $this->Cell($availableWidth * 0.5, 8, 'Outstanding', 1, 0, 'L');
        $this->Cell($availableWidth * 0.5, 8, number_format($ordersDue, 3) . ' ' . $this->currencySymbol, 1, 1, 'C');
        
        $this->Cell($availableWidth * 0.5, 8, 'Average Order', 1, 0, 'L');
        $this->Cell($availableWidth * 0.5, 8, number_format($ordersAverage, 3) . ' ' . $this->currencySymbol, 1, 1, 'C');
        
        $this->Ln(5);
    }
    


    private function generateOrdersTable()
    {
        // Calculate available width (page width minus left and right margins)
        $pageWidth = $this->GetPageWidth();
        $leftMargin = $this->lMargin;
        $rightMargin = $this->rMargin;
        $topMargin = $this->tMargin;
        $availableWidth = $pageWidth - $leftMargin - $rightMargin;
        
        // Calculate proper top margin for orders table
        // Header takes about 35mm (company name + address + report title + filters)
        // Add some spacing after header
        $headerHeight = 35;
        $currentY = $this->GetY();
        $requiredY = $topMargin + $headerHeight + 10; // 10mm spacing after header
        
        if ($currentY < $requiredY) {
            $this->SetY($requiredY);
        }
        
        // Table heading as side heading with underline
        $this->SetFont($this->font, 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 8, 'New Orders', 0, 1, 'L');
        
        // Underline
        $this->SetDrawColor(0, 0, 0);
        $this->Line($this->lMargin, $this->GetY(), $this->GetPageWidth() - $this->rMargin, $this->GetY());
        
        $this->Ln(5);
        
        // Simple table header without colors
        $this->SetFont($this->font, 'B', 11);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        
        // Calculate column widths based on available space
        $colWidths = [
            'id' => $availableWidth * 0.15,      // 15% of available width
            'date' => $availableWidth * 0.25,     // 25% of available width
            'items' => $availableWidth * 0.20,    // 20% of available width
            'total' => $availableWidth * 0.20,    // 20% of available width
            'paid' => $availableWidth * 0.20      // 20% of available width
        ];

        
        $this->Cell($colWidths['id'], 5, 'ID', 'TB', 0, 'C', false);
        $this->Cell($colWidths['date'], 5, 'Date', 'TB', 0, 'C', false);
        $this->Cell($colWidths['items'], 5, 'Items', 'TB', 0, 'C', false);
        $this->Cell($colWidths['total'], 5, 'Total', 'TB', 0, 'C', false);
        $this->Cell($colWidths['paid'], 5, 'Paid', 'TB', 1, 'C', false);

        // Table body with enhanced styling
        $this->SetFont($this->font, '', 10);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        $fill = false;
        
        foreach ($this->orders as $order) {
            // Check if we need a new page
            if ($this->GetY() > 180) {
                $this->AddPage('P');
                // Repeat header on new page
                $this->SetFont($this->font, 'B', 11);
                $this->SetTextColor(0, 0, 0);
                $this->SetDrawColor(0, 0, 0);
                
                $this->Cell($colWidths['id'], 5, 'ID', 0, 0, 'C', false);
                $this->Cell($colWidths['date'], 5, 'Date', 0, 0, 'C', false);
                $this->Cell($colWidths['items'], 5, 'Items', 0, 0, 'C', false);
                $this->Cell($colWidths['total'], 5, 'Total', 0, 0, 'C', false);
                $this->Cell($colWidths['paid'], 5, 'Paid', 0, 1, 'C', false);
                
                $this->SetFont($this->font, '', 10);
                $this->SetTextColor(0, 0, 0);
                $this->SetDrawColor(0, 0, 0);
                $fill = false;
            }

            // Set alternating row colors with lighter sky blue and skip styling
            if ($fill) {
                $this->SetFillColor(167, 230, 245); // 50% lighter sky blue for alternating rows
            } else {
                $this->SetFillColor(255, 255, 255); // White for other rows (no styling)
            }
            
            $orderDate = $order->order_date ? date('m/d/Y', strtotime($order->order_date)) : '-';
            $totalItems = $order->items ? $order->items->sum('quantity') : 0;
            $amountDue = $order->total_amount - $order->paid_amount;
            
            // Get category sequences
            $sequences = '';
            if ($order->category_sequences && is_array($order->category_sequences)) {
                $sequences = implode(', ', $order->category_sequences);
            }
            
            $this->Cell($colWidths['id'], 5, $order->id, 0, 0, 'C', $fill);
            $this->Cell($colWidths['date'], 5, $orderDate, 0, 0, 'C', $fill);
            $this->Cell($colWidths['items'], 5, $totalItems, 0, 0, 'C', $fill);
            
            
            $this->Cell($colWidths['total'], 5, number_format($order->total_amount, 3), 0, 0, 'C', $fill);
            $this->Cell($colWidths['paid'], 5, number_format($order->paid_amount, 3), 0, 1, 'C', $fill);
            
            $fill = !$fill;
        }
        
        // Add totals row
        $this->addTotalsRow($colWidths);
    }
    
    private function addTotalsRow($colWidths)
    {
        // Calculate totals
        $totalItems = $this->orders->sum(function($order) {
            return $order->items ? $order->items->sum('quantity') : 0;
        });
        $totalAmount = $this->orders->sum('total_amount');
        $totalPaid = $this->orders->sum('paid_amount');
        $totalDue = $totalAmount - $totalPaid;
        
        // Totals row styling
        $this->SetFont($this->font, 'B', 10);
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(220, 220, 220); // Light gray for totals row
        $this->SetDrawColor(0, 0, 0);
        
        $this->Cell($colWidths['id'], 5, 'TOTAL', 0, 0, 'C', true);
        $this->Cell($colWidths['date'], 5, '', 0, 0, 'C', true);
        $this->Cell($colWidths['items'], 5, $totalItems, 0, 0, 'C', true);
        $this->Cell($colWidths['total'], 5, number_format($totalAmount, 3), 0, 0, 'C', true);
        $this->Cell($colWidths['paid'], 5, number_format($totalPaid, 3), 0, 1, 'C', true);
    }
    

    


    private function generateDetailedItems()
    {
        $this->Ln(15);
        
        // Section header with background
        $this->SetFillColor(52, 73, 94);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont($this->font, 'B', 14);
        $this->Cell(0, 12, '  Detailed Order Items', 0, 1, 'L', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->Ln(5);
        
        foreach ($this->orders as $order) {
            // Check if we need a new page
            if ($this->GetY() > 150) {
                $this->AddPage('L');
            }
            
            // Order header with professional styling
            $this->SetFont($this->font, 'B', 11);
            $this->SetFillColor(41, 128, 185);
            $this->SetTextColor(255, 255, 255);
            $this->SetDrawColor(41, 128, 185);
            $this->Cell(0, 8, '  Order ID: ' . $order->id . ' - ' . ($order->customer ? $order->customer->name : 'N/A'), 1, 1, 'L', true);
            
            if ($order->items && $order->items->count() > 0) {
                $this->SetFont($this->font, 'B', 9);
                $this->SetFillColor(248, 249, 250);
                $this->SetTextColor(52, 73, 94);
                $this->SetDrawColor(200, 200, 200);
                
                $this->Cell(50, 8, 'Product Type', 1, 0, 'C', true);
                $this->Cell(40, 8, 'Service', 1, 0, 'C', true);
                $this->Cell(20, 8, 'Qty', 1, 0, 'C', true);
                $this->Cell(35, 8, 'Dimensions', 1, 0, 'C', true);
                $this->Cell(35, 8, 'Price/Unit', 1, 0, 'C', true);
                $this->Cell(35, 8, 'Subtotal', 1, 1, 'C', true);
                
                $this->SetFont($this->font, '', 8);
                $this->SetTextColor(52, 73, 94);
                $fill = false;
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
                    
                    // Professional alternating row colors
                    $this->SetFillColor($fill ? 248 : 255, $fill ? 249 : 255, $fill ? 250 : 255);
                    
                    $this->Cell(50, 7, $this->truncateText($productType, 22), 1, 0, 'L', $fill);
                    $this->Cell(40, 7, $this->truncateText($serviceName, 18), 1, 0, 'L', $fill);
                    $this->Cell(20, 7, $item->quantity, 1, 0, 'C', $fill);
                    $this->Cell(35, 7, $dimensions, 1, 0, 'C', $fill);
                    $this->Cell(35, 7, number_format($item->calculated_price_per_unit_item, 3), 1, 0, 'R', $fill);
                    $this->Cell(35, 7, number_format($item->sub_total, 3), 1, 1, 'R', $fill);
                    
                    $fill = !$fill;
                }
            } else {
                $this->SetFont($this->font, '', 8);
                $this->SetTextColor(128, 128, 128);
                $this->SetFillColor(248, 249, 250);
                $this->Cell(0, 8, 'No items found', 1, 1, 'C', true);
            }
            
            $this->Ln(8);
        }
    }

    private function getStatusBadge($status)
    {
        $statusColors = [
            'pending' => [255, 193, 7],    // Yellow
            'processing' => [23, 162, 184], // Cyan
            'completed' => [40, 167, 69],   // Green
            'cancelled' => [220, 53, 69],   // Red
            'delivered' => [102, 16, 242],  // Purple
        ];
        
        $color = $statusColors[strtolower($status)] ?? [108, 117, 125]; // Default gray
        
        // For now, just return the status text
        // In a more advanced implementation, you could create colored badges
        return ucfirst($status);
    }
    
    private function truncateText($text, $maxLength)
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
    
    private function addCleanFiltersDisplay()
    {
        $filterText = [];
        
        if (!empty($this->filters['date_from']) && !empty($this->filters['date_to'])) {
            $filterText[] = 'Date: ' . $this->filters['date_from'] . ' to ' . $this->filters['date_to'];
        }
        
        if (!empty($this->filters['status'])) {
            $filterText[] = 'Status: ' . ucfirst($this->filters['status']);
        }
        
        if (!empty($this->filters['search'])) {
            $filterText[] = 'Search: ' . $this->filters['search'];
        }
        
        if (!empty($this->filters['order_id'])) {
            $filterText[] = 'Order ID: ' . $this->filters['order_id'];
        }
        
        if (!empty($this->filters['customer_id'])) {
            $filterText[] = 'Customer ID: ' . $this->filters['customer_id'];
        }
        
        if (!empty($this->filters['product_type_id'])) {
            $filterText[] = 'Product Type: ' . $this->filters['product_type_id'];
        }
        
        if (!empty($this->filters['category_sequence_search'])) {
            $filterText[] = 'Category Seq: ' . $this->filters['category_sequence_search'];
        }
        
        if (!empty($filterText)) {
            $this->SetFont($this->font, '', 9);
            $this->SetTextColor(128, 128, 128);
            $this->Cell(0, 4, 'Filters: ' . implode(' | ', $filterText), 0, 1, 'C');
        }
    }
    

}

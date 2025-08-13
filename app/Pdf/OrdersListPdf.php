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
        $this->companyName = $settings['company_name'] ?? 'Laundry Service';
        $this->companyAddress = $settings['company_address'] ?? '';
        $this->currencySymbol = $settings['currency_symbol'] ?? 'OMR';
    }

    public function Header()
    {
        // Professional header with background
        $this->SetFillColor(41, 128, 185);
        $this->Rect(0, 0, $this->GetPageWidth(), 35, 'F');
        
        // Company header
        $this->SetFont($this->font, 'B', 20);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 12, $this->companyName, 0, 1, 'C');
        
        $this->SetFont($this->font, '', 11);
        if ($this->companyAddress) {
            $this->Cell(0, 7, $this->companyAddress, 0, 1, 'C');
        }
        
        // Report title with subtitle
        $this->SetFont($this->font, 'B', 16);
        $this->Cell(0, 8, 'Orders List Report', 0, 1, 'C');
        
        // Reset text color for content
        $this->SetTextColor(0, 0, 0);
        
        // Report metadata section
        $this->Ln(8);
        $this->SetFillColor(248, 249, 250);
        $this->Rect(10, 40, $this->GetPageWidth() - 20, 20, 'F');
        
        $this->SetFont($this->font, 'B', 10);
        $this->SetTextColor(52, 73, 94);
        $this->Cell(30, 6, 'Generated:', 0, 0, 'L');
        $this->SetFont($this->font, '', 10);
        $this->Cell(60, 6, date('F j, Y \a\t g:i A'), 0, 0, 'L');
        
        $this->SetFont($this->font, 'B', 10);
        $this->Cell(30, 6, 'Report Type:', 0, 0, 'L');
        $this->SetFont($this->font, '', 10);
        $this->Cell(60, 6, 'Orders Summary & Details', 0, 1, 'L');
        
        // Show active filters
        $filterText = $this->getFilterText();
        if ($filterText) {
            $this->SetFont($this->font, 'B', 10);
            $this->Cell(30, 6, 'Filters:', 0, 0, 'L');
            $this->SetFont($this->font, '', 10);
            $this->Cell(0, 6, $filterText, 0, 1, 'L');
        }
        
        $this->Ln(8);
    }

    public function Footer()
    {
        $this->SetY(-20);
        
        // Footer line
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY() - 5, $this->GetPageWidth() - 10, $this->GetY() - 5);
        
        // Footer content
        $this->SetFont($this->font, '', 9);
        $this->SetTextColor(128, 128, 128);
        
        // Left side - Company info
        $this->Cell(100, 8, $this->companyName . ' - Orders Report', 0, 0, 'L');
        
        // Center - Page info
        $this->Cell(100, 8, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
        
        // Right side - Date
        $this->Cell(100, 8, 'Generated: ' . date('M j, Y'), 0, 1, 'R');
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
        // Set landscape orientation
        $this->AddPage('L');
        $this->SetFont($this->font, '', 11);

        // Only show orders table
        $this->generateOrdersTable();

        return $this->Output('', 'S');
    }

    private function generateSummary()
    {
        // Section header with background
        $this->SetFillColor(52, 73, 94);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont($this->font, 'B', 14);
        $this->Cell(0, 12, '  Executive Summary', 0, 1, 'L', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->Ln(5);
        
        $totalOrders = $this->orders->count();
        $totalAmount = $this->orders->sum('total_amount');
        $totalPaid = $this->orders->sum('paid_amount');
        $totalDue = $totalAmount - $totalPaid;
        $averageOrderValue = $totalOrders > 0 ? $totalAmount / $totalOrders : 0;

        // Create professional summary cards
        $this->createSummaryCard('Total Orders', $totalOrders, 'orders', 20);
        $this->createSummaryCard('Total Revenue', number_format($totalAmount, 3) . ' ' . $this->currencySymbol, 'revenue', 80);
        $this->createSummaryCard('Total Paid', number_format($totalPaid, 3) . ' ' . $this->currencySymbol, 'paid', 140);
        $this->createSummaryCard('Outstanding', number_format($totalDue, 3) . ' ' . $this->currencySymbol, 'due', 200);
        $this->createSummaryCard('Average Order', number_format($averageOrderValue, 3) . ' ' . $this->currencySymbol, 'avg', 260);
        
        $this->Ln(15);
    }
    
    private function createSummaryCard($title, $value, $type, $x)
    {
        $cardWidth = 55;
        $cardHeight = 25;
        
        // Card background
        $this->SetFillColor(248, 249, 250);
        $this->SetDrawColor(200, 200, 200);
        $this->Rect($x, $this->GetY(), $cardWidth, $cardHeight, 'DF');
        
        // Title
        $this->SetFont($this->font, 'B', 8);
        $this->SetTextColor(52, 73, 94);
        $this->SetXY($x + 2, $this->GetY() + 2);
        $this->Cell($cardWidth - 4, 6, $title, 0, 0, 'C');
        
        // Value
        $this->SetFont($this->font, 'B', 10);
        $this->SetTextColor(41, 128, 185);
        $this->SetXY($x + 2, $this->GetY() + 8);
        $this->Cell($cardWidth - 4, 8, $value, 0, 0, 'C');
        
        // Icon or indicator
        $this->SetFillColor($this->getCardColor($type));
        $this->SetXY($x + 2, $this->GetY() + 16);
        $this->Cell(3, 3, '', 0, 0, 'L', true);
    }
    
    private function getCardColor($type)
    {
        switch ($type) {
            case 'orders': return [46, 204, 113]; // Green
            case 'revenue': return [52, 152, 219]; // Blue
            case 'paid': return [155, 89, 182]; // Purple
            case 'due': return [231, 76, 60]; // Red
            case 'avg': return [241, 196, 15]; // Yellow
            default: return [149, 165, 166]; // Gray
        }
    }

    private function generateOrdersTable()
    {
        // Calculate available width (page width minus left and right margins)
        $pageWidth = $this->GetPageWidth();
        $leftMargin = $this->lMargin;
        $rightMargin = $this->rMargin;
        $availableWidth = $pageWidth - $leftMargin - $rightMargin;
        
        // Simple table header without colors
        $this->SetFont($this->font, 'B', 9);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        
        // Calculate column widths based on available space
        $colWidths = [
            'id' => $availableWidth * 0.08,      // 8% of available width
            'customer' => $availableWidth * 0.20, // 20% of available width
            'date' => $availableWidth * 0.12,     // 12% of available width
            'items' => $availableWidth * 0.08,    // 8% of available width
            'total' => $availableWidth * 0.15,    // 15% of available width
            'paid' => $availableWidth * 0.15,     // 15% of available width
            'due' => $availableWidth * 0.12,      // 12% of available width
            'sequences' => $availableWidth * 0.10  // 10% of available width
        ];
        
        $this->Cell($colWidths['id'], 10, 'ID', 1, 0, 'C', false);
        $this->Cell($colWidths['customer'], 10, 'Customer', 1, 0, 'C', false);
        $this->Cell($colWidths['date'], 10, 'Date', 1, 0, 'C', false);
        $this->Cell($colWidths['items'], 10, 'Items', 1, 0, 'C', false);
        $this->Cell($colWidths['total'], 10, 'Total', 1, 0, 'C', false);
        $this->Cell($colWidths['paid'], 10, 'Paid', 1, 0, 'C', false);
        $this->Cell($colWidths['due'], 10, 'Due', 1, 0, 'C', false);
        $this->Cell($colWidths['sequences'], 10, 'Sequences', 1, 1, 'C', false);

        // Table body with simple styling
        $this->SetFont($this->font, '', 8);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        
        foreach ($this->orders as $order) {
            // Check if we need a new page
            if ($this->GetY() > 180) {
                $this->AddPage('L');
                // Repeat header on new page
                $this->SetFont($this->font, 'B', 9);
                $this->SetTextColor(0, 0, 0);
                $this->SetDrawColor(0, 0, 0);
                
                $this->Cell($colWidths['id'], 10, 'ID', 1, 0, 'C', false);
                $this->Cell($colWidths['customer'], 10, 'Customer', 1, 0, 'C', false);
                $this->Cell($colWidths['date'], 10, 'Date', 1, 0, 'C', false);
                $this->Cell($colWidths['items'], 10, 'Items', 1, 0, 'C', false);
                $this->Cell($colWidths['total'], 10, 'Total', 1, 0, 'C', false);
                $this->Cell($colWidths['paid'], 10, 'Paid', 1, 0, 'C', false);
                $this->Cell($colWidths['due'], 10, 'Due', 1, 0, 'C', false);
                $this->Cell($colWidths['sequences'], 10, 'Sequences', 1, 1, 'C', false);
                
                $this->SetFont($this->font, '', 8);
                $this->SetTextColor(0, 0, 0);
                $this->SetDrawColor(0, 0, 0);
            }

            $customerName = $order->customer ? $order->customer->name : 'N/A';
            $orderDate = $order->order_date ? date('m/d/Y', strtotime($order->order_date)) : '-';
            $totalItems = $order->items ? $order->items->sum('quantity') : 0;
            $amountDue = $order->total_amount - $order->paid_amount;
            
            // Get category sequences
            $sequences = '';
            if ($order->category_sequences && is_array($order->category_sequences)) {
                $sequences = implode(', ', $order->category_sequences);
            }
            
            $this->Cell($colWidths['id'], 8, $order->id, 1, 0, 'C', false);
            $this->Cell($colWidths['customer'], 8, $this->truncateText($customerName, 30), 1, 0, 'C', false);
            $this->Cell($colWidths['date'], 8, $orderDate, 1, 0, 'C', false);
            $this->Cell($colWidths['items'], 8, $totalItems, 1, 0, 'C', false);
            $this->Cell($colWidths['total'], 8, number_format($order->total_amount, 3), 1, 0, 'C', false);
            $this->Cell($colWidths['paid'], 8, number_format($order->paid_amount, 3), 1, 0, 'C', false);
            $this->Cell($colWidths['due'], 8, number_format($amountDue, 3), 1, 0, 'C', false);
            $this->Cell($colWidths['sequences'], 8, $this->truncateText($sequences, 30), 1, 1, 'C', false);
        }
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
}

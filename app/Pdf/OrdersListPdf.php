<?php

namespace App\Pdf;

use App\Models\Order;
use Exception;

class OrdersListPdf extends BasePdf
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
        // Simple header without background
        $this->SetFont($this->font, 'B', 16);
        $this->Cell(0, 8, $this->companyName, 0, 1, 'C');
        
        $this->SetFont($this->font, '', 10);
        if ($this->companyAddress) {
            $this->Cell(0, 6, $this->companyAddress, 0, 1, 'C');
        }
        
        // Report title
        $this->SetFont($this->font, 'B', 14);
        $this->Cell(0, 8, 'Orders Management Report', 0, 1, 'C');
        
        // Report info
        $this->SetFont($this->font, '', 9);
        $this->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        
        // Show active filters
        $filterText = $this->getFilterText();
        if ($filterText) {
            $this->Cell(0, 5, 'Filters: ' . $filterText, 0, 1, 'C');
        }
        
        $this->Ln(3);
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

        // Orders table first
        $this->generateOrdersTable();

        return $this->Output('', 'S');
    }



    private function generateOrdersTable()
    {
        // Simple section header
        $this->SetFont($this->font, 'B', 12);
        $this->Cell(0, 8, 'Orders List', 0, 1, 'L');
        
        // Simple table header
        $this->SetFont($this->font, 'B', 9);
        $this->SetFillColor(240, 240, 240);
        
        $this->Cell(20, 10, 'ID', 1, 0, 'C', true);
        $this->Cell(40, 10, 'Customer', 1, 0, 'C', true);
        $this->Cell(30, 10, 'Date', 1, 0, 'C', true);
        $this->Cell(25, 10, 'Status', 1, 0, 'C', true);
        $this->Cell(20, 10, 'Items', 1, 0, 'C', true);
        $this->Cell(35, 10, 'Total', 1, 0, 'C', true);
        $this->Cell(35, 10, 'Paid', 1, 0, 'C', true);
        $this->Cell(35, 10, 'Due', 1, 0, 'C', true);
        $this->Cell(45, 10, 'Sequences', 1, 1, 'C', true);

        // Table body
        $this->SetFont($this->font, '', 8);
        $fill = false;
        
        foreach ($this->orders as $order) {
            // Check if we need a new page
            if ($this->GetY() > 180) {
                $this->AddPage('L');
                // Repeat header on new page
                $this->SetFont($this->font, 'B', 9);
                $this->SetFillColor(240, 240, 240);
                
                $this->Cell(20, 10, 'ID', 1, 0, 'C', true);
                $this->Cell(40, 10, 'Customer', 1, 0, 'C', true);
                $this->Cell(30, 10, 'Date', 1, 0, 'C', true);
                $this->Cell(25, 10, 'Status', 1, 0, 'C', true);
                $this->Cell(20, 10, 'Items', 1, 0, 'C', true);
                $this->Cell(35, 10, 'Total', 1, 0, 'C', true);
                $this->Cell(35, 10, 'Paid', 1, 0, 'C', true);
                $this->Cell(35, 10, 'Due', 1, 0, 'C', true);
                $this->Cell(45, 10, 'Sequences', 1, 1, 'C', true);
                
                $this->SetFont($this->font, '', 8);
                $fill = false;
            }

            // Simple alternating row colors
            $this->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            
            $customerName = $order->customer ? $order->customer->name : 'N/A';
            $orderDate = $order->order_date ? date('m/d/Y', strtotime($order->order_date)) : '-';
            $totalItems = $order->items ? $order->items->sum('quantity') : 0;
            $amountDue = $order->total_amount - $order->paid_amount;
            
            // Get category sequences
            $sequences = '';
            if ($order->category_sequences && is_array($order->category_sequences)) {
                $sequences = implode(', ', $order->category_sequences);
            }
            
            $this->Cell(20, 8, $order->id, 1, 0, 'C', $fill);
            $this->Cell(40, 8, $this->truncateText($customerName, 20), 1, 0, 'L', $fill);
            $this->Cell(30, 8, $orderDate, 1, 0, 'C', $fill);
            $this->Cell(25, 8, $this->getStatusBadge($order->status), 1, 0, 'C', $fill);
            $this->Cell(20, 8, $totalItems, 1, 0, 'C', $fill);
            $this->Cell(35, 8, number_format($order->total_amount, 3), 1, 0, 'R', $fill);
            $this->Cell(35, 8, number_format($order->paid_amount, 3), 1, 0, 'R', $fill);
            $this->Cell(35, 8, number_format($amountDue, 3), 1, 0, 'R', $fill);
            $this->Cell(45, 8, $this->truncateText($sequences, 18), 1, 1, 'C', $fill);
            
            $fill = !$fill;
        }
        
        // Add detailed items section for each order
        $this->generateDetailedItems();
    }

    private function generateDetailedItems()
    {
        $this->Ln(5);
        
        // Simple section header
        $this->SetFont($this->font, 'B', 12);
        $this->Cell(0, 8, 'Order Items Details', 0, 1, 'L');
        
        foreach ($this->orders as $order) {
            // Check if we need a new page
            if ($this->GetY() > 150) {
                $this->AddPage('L');
            }
            
            // Simple order header
            $this->SetFont($this->font, 'B', 10);
            $this->SetFillColor(240, 240, 240);
            $this->Cell(0, 6, 'Order ID: ' . $order->id . ' - ' . ($order->customer ? $order->customer->name : 'N/A'), 1, 1, 'L', true);
            
            if ($order->items && $order->items->count() > 0) {
                $this->SetFont($this->font, 'B', 8);
                $this->SetFillColor(240, 240, 240);
                
                $this->Cell(50, 6, 'Product Type', 1, 0, 'C', true);
                $this->Cell(40, 6, 'Service', 1, 0, 'C', true);
                $this->Cell(20, 6, 'Qty', 1, 0, 'C', true);
                $this->Cell(35, 6, 'Dimensions', 1, 0, 'C', true);
                $this->Cell(35, 6, 'Price/Unit', 1, 0, 'C', true);
                $this->Cell(35, 6, 'Subtotal', 1, 1, 'C', true);
                
                $this->SetFont($this->font, '', 8);
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
                    
                    // Simple alternating row colors
                    $this->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                    
                    $this->Cell(50, 5, $this->truncateText($productType, 22), 1, 0, 'L', $fill);
                    $this->Cell(40, 5, $this->truncateText($serviceName, 18), 1, 0, 'L', $fill);
                    $this->Cell(20, 5, $item->quantity, 1, 0, 'C', $fill);
                    $this->Cell(35, 5, $dimensions, 1, 0, 'C', $fill);
                    $this->Cell(35, 5, number_format($item->calculated_price_per_unit_item, 3), 1, 0, 'R', $fill);
                    $this->Cell(35, 5, number_format($item->sub_total, 3), 1, 1, 'R', $fill);
                    
                    $fill = !$fill;
                }
            } else {
                $this->SetFont($this->font, '', 8);
                $this->Cell(0, 6, 'No items found', 1, 1, 'C');
            }
            
            $this->Ln(3);
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

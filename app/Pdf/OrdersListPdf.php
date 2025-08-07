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
        // Company header
        $this->SetFont($this->font, 'B', 18);
        $this->Cell(0, 10, $this->companyName, 0, 1, 'C');
        
        $this->SetFont($this->font, '', 10);
        if ($this->companyAddress) {
            $this->Cell(0, 6, $this->companyAddress, 0, 1, 'C');
        }
        
        $this->Ln(5);
        
        // Report title
        $this->SetFont($this->font, 'B', 16);
        $this->Cell(0, 10, 'Orders List Report', 0, 1, 'C');
        
        // Filters and date info
        $this->SetFont($this->font, '', 9);
        $this->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        
        // Show active filters
        $filterText = $this->getFilterText();
        if ($filterText) {
            $this->Cell(0, 5, 'Filters: ' . $filterText, 0, 1, 'C');
        }
        
        $this->Ln(5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont($this->font, 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
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
        $this->AddPage();
        $this->SetFont($this->font, '', 11);

        // Orders table first
        $this->generateOrdersTable();
        
        $this->Ln(15);

        // Summary section at the bottom
        $this->generateSummary();

        return $this->Output('', 'S');
    }

    private function generateSummary()
    {
        $this->SetFont($this->font, 'B', 14);
        $this->Cell(0, 10, 'Summary', 0, 1, 'L');
        $this->SetFont($this->font, '', 11);
        
        $totalOrders = $this->orders->count();
        $totalAmount = $this->orders->sum('total_amount');
        $totalPaid = $this->orders->sum('paid_amount');
        $totalDue = $totalAmount - $totalPaid;
        $averageOrderValue = $totalOrders > 0 ? $totalAmount / $totalOrders : 0;

        // Create a summary table
        $this->SetFillColor(245, 245, 245);
        $this->SetFont($this->font, 'B', 10);
        
        $this->Cell(60, 10, 'Metric', 1, 0, 'C', true);
        $this->Cell(50, 10, 'Value', 1, 1, 'C', true);
        
        $this->SetFont($this->font, '', 10);
        $this->Cell(60, 8, 'Total Orders', 1, 0, 'L');
        $this->Cell(50, 8, $totalOrders, 1, 1, 'R');
        
        $this->Cell(60, 8, 'Total Amount', 1, 0, 'L');
        $this->Cell(50, 8, number_format($totalAmount, 3) . ' ' . $this->currencySymbol, 1, 1, 'R');
        
        $this->Cell(60, 8, 'Total Paid', 1, 0, 'L');
        $this->Cell(50, 8, number_format($totalPaid, 3) . ' ' . $this->currencySymbol, 1, 1, 'R');
        
        $this->Cell(60, 8, 'Total Due', 1, 0, 'L');
        $this->Cell(50, 8, number_format($totalDue, 3) . ' ' . $this->currencySymbol, 1, 1, 'R');
        
        $this->Cell(60, 8, 'Average Order', 1, 0, 'L');
        $this->Cell(50, 8, number_format($averageOrderValue, 3) . ' ' . $this->currencySymbol, 1, 1, 'R');
    }

    private function generateOrdersTable()
    {
        $this->SetFont($this->font, 'B', 14);
        $this->Cell(0, 10, 'Orders Details', 0, 1, 'L');
        
        // Table header
        $this->SetFont($this->font, 'B', 9);
        $this->SetFillColor(70, 130, 180);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(18, 9, 'ID', 1, 0, 'C', true);
        $this->Cell(30, 9, 'Customer', 1, 0, 'C', true);
        $this->Cell(25, 9, 'Date', 1, 0, 'C', true);
        $this->Cell(20, 9, 'Status', 1, 0, 'C', true);
        $this->Cell(18, 9, 'Items', 1, 0, 'C', true);
        $this->Cell(30, 9, 'Total', 1, 0, 'C', true);
        $this->Cell(30, 9, 'Paid', 1, 0, 'C', true);
        $this->Cell(30, 9, 'Due', 1, 0, 'C', true);
        $this->Cell(35, 9, 'Sequences', 1, 1, 'C', true);

        // Table body
        $this->SetFont($this->font, '', 8);
        $this->SetTextColor(0, 0, 0);
        $fill = false;
        
        foreach ($this->orders as $order) {
            // Check if we need a new page
            if ($this->GetY() > 250) {
                $this->AddPage();
                // Repeat header on new page
                $this->SetFont($this->font, 'B', 9);
                $this->SetFillColor(70, 130, 180);
                $this->SetTextColor(255, 255, 255);
                
                $this->Cell(18, 9, 'ID', 1, 0, 'C', true);
                $this->Cell(30, 9, 'Customer', 1, 0, 'C', true);
                $this->Cell(25, 9, 'Date', 1, 0, 'C', true);
                $this->Cell(20, 9, 'Status', 1, 0, 'C', true);
                $this->Cell(18, 9, 'Items', 1, 0, 'C', true);
                $this->Cell(30, 9, 'Total', 1, 0, 'C', true);
                $this->Cell(30, 9, 'Paid', 1, 0, 'C', true);
                $this->Cell(30, 9, 'Due', 1, 0, 'C', true);
                $this->Cell(35, 9, 'Sequences', 1, 1, 'C', true);
                
                $this->SetFont($this->font, '', 8);
                $this->SetTextColor(0, 0, 0);
                $fill = false;
            }

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
            
            $this->Cell(18, 7, $order->id, 1, 0, 'C', $fill);
            $this->Cell(30, 7, $this->truncateText($customerName, 15), 1, 0, 'L', $fill);
            $this->Cell(25, 7, $orderDate, 1, 0, 'C', $fill);
            $this->Cell(20, 7, ucfirst($order->status), 1, 0, 'C', $fill);
            $this->Cell(18, 7, $totalItems, 1, 0, 'C', $fill);
            $this->Cell(30, 7, number_format($order->total_amount, 3), 1, 0, 'R', $fill);
            $this->Cell(30, 7, number_format($order->paid_amount, 3), 1, 0, 'R', $fill);
            $this->Cell(30, 7, number_format($amountDue, 3), 1, 0, 'R', $fill);
            $this->Cell(35, 7, $this->truncateText($sequences, 12), 1, 1, 'C', $fill);
            
            $fill = !$fill;
        }
        
        // Add detailed items section for each order
        $this->generateDetailedItems();
    }

    private function generateDetailedItems()
    {
        $this->Ln(10);
        $this->SetFont($this->font, 'B', 14);
        $this->Cell(0, 10, 'Order Items Details', 0, 1, 'L');
        
        foreach ($this->orders as $order) {
            // Check if we need a new page
            if ($this->GetY() > 220) {
                $this->AddPage();
            }
            
            $this->SetFont($this->font, 'B', 11);
            $this->SetFillColor(240, 240, 240);
            $this->Cell(0, 7, 'Order ID: ' . $order->id . ' - ' . ($order->customer ? $order->customer->name : 'N/A'), 1, 1, 'L', true);
            
            if ($order->items && $order->items->count() > 0) {
                $this->SetFont($this->font, 'B', 9);
                $this->SetFillColor(220, 220, 220);
                
                $this->Cell(45, 7, 'Product Type', 1, 0, 'C', true);
                $this->Cell(35, 7, 'Service', 1, 0, 'C', true);
                $this->Cell(18, 7, 'Qty', 1, 0, 'C', true);
                $this->Cell(30, 7, 'Dimensions', 1, 0, 'C', true);
                $this->Cell(30, 7, 'Price/Unit', 1, 0, 'C', true);
                $this->Cell(30, 7, 'Subtotal', 1, 1, 'C', true);
                
                $this->SetFont($this->font, '', 8);
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
                    
                    $this->Cell(45, 6, $this->truncateText($productType, 18), 1, 0, 'L');
                    $this->Cell(35, 6, $this->truncateText($serviceName, 15), 1, 0, 'L');
                    $this->Cell(18, 6, $item->quantity, 1, 0, 'C');
                    $this->Cell(30, 6, $dimensions, 1, 0, 'C');
                    $this->Cell(30, 6, number_format($item->calculated_price_per_unit_item, 3), 1, 0, 'R');
                    $this->Cell(30, 6, number_format($item->sub_total, 3), 1, 1, 'R');
                }
            } else {
                $this->SetFont($this->font, '', 8);
                $this->Cell(0, 7, 'No items found', 1, 1, 'C');
            }
            
            $this->Ln(5);
        }
    }

    private function truncateText($text, $maxLength)
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
}

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
        $this->SetFont($this->font, '', 10);

        // Summary section
        $this->generateSummary();
        
        $this->Ln(10);

        // Orders table
        $this->generateOrdersTable();

        return $this->Output('', 'S');
    }

    private function generateSummary()
    {
        $this->SetFont($this->font, 'B', 12);
        $this->Cell(0, 8, 'Summary', 0, 1, 'L');
        $this->SetFont($this->font, '', 10);
        
        $totalOrders = $this->orders->count();
        $totalAmount = $this->orders->sum('total_amount');
        $totalPaid = $this->orders->sum('paid_amount');
        $totalDue = $totalAmount - $totalPaid;
        $averageOrderValue = $totalOrders > 0 ? $totalAmount / $totalOrders : 0;

        // Create a summary table
        $this->SetFillColor(245, 245, 245);
        $this->SetFont($this->font, 'B', 9);
        
        $this->Cell(50, 8, 'Metric', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Value', 1, 1, 'C', true);
        
        $this->SetFont($this->font, '', 9);
        $this->Cell(50, 6, 'Total Orders', 1, 0, 'L');
        $this->Cell(40, 6, $totalOrders, 1, 1, 'R');
        
        $this->Cell(50, 6, 'Total Amount', 1, 0, 'L');
        $this->Cell(40, 6, number_format($totalAmount, 3) . ' ' . $this->currencySymbol, 1, 1, 'R');
        
        $this->Cell(50, 6, 'Total Paid', 1, 0, 'L');
        $this->Cell(40, 6, number_format($totalPaid, 3) . ' ' . $this->currencySymbol, 1, 1, 'R');
        
        $this->Cell(50, 6, 'Total Due', 1, 0, 'L');
        $this->Cell(40, 6, number_format($totalDue, 3) . ' ' . $this->currencySymbol, 1, 1, 'R');
        
        $this->Cell(50, 6, 'Average Order', 1, 0, 'L');
        $this->Cell(40, 6, number_format($averageOrderValue, 3) . ' ' . $this->currencySymbol, 1, 1, 'R');
    }

    private function generateOrdersTable()
    {
        $this->SetFont($this->font, 'B', 12);
        $this->Cell(0, 8, 'Orders Details', 0, 1, 'L');
        
        // Table header
        $this->SetFont($this->font, 'B', 8);
        $this->SetFillColor(70, 130, 180);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(15, 8, 'ID', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Order #', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Customer', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Date', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Status', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Items', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Total', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Paid', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Due', 1, 1, 'C', true);

        // Table body
        $this->SetFont($this->font, '', 7);
        $this->SetTextColor(0, 0, 0);
        $fill = false;
        
        foreach ($this->orders as $order) {
            // Check if we need a new page
            if ($this->GetY() > 250) {
                $this->AddPage();
                // Repeat header on new page
                $this->SetFont($this->font, 'B', 8);
                $this->SetFillColor(70, 130, 180);
                $this->SetTextColor(255, 255, 255);
                
                $this->Cell(15, 8, 'ID', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Order #', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Customer', 1, 0, 'C', true);
                $this->Cell(20, 8, 'Date', 1, 0, 'C', true);
                $this->Cell(20, 8, 'Status', 1, 0, 'C', true);
                $this->Cell(15, 8, 'Items', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Total', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Paid', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Due', 1, 1, 'C', true);
                
                $this->SetFont($this->font, '', 7);
                $this->SetTextColor(0, 0, 0);
                $fill = false;
            }

            $this->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            
            $customerName = $order->customer ? $order->customer->name : 'N/A';
            $orderDate = $order->order_date ? date('m/d/Y', strtotime($order->order_date)) : '-';
            $totalItems = $order->items ? $order->items->sum('quantity') : 0;
            $amountDue = $order->total_amount - $order->paid_amount;
            
            $this->Cell(15, 6, $order->id, 1, 0, 'C', $fill);
            $this->Cell(25, 6, $order->order_number, 1, 0, 'L', $fill);
            $this->Cell(25, 6, $this->truncateText($customerName, 12), 1, 0, 'L', $fill);
            $this->Cell(20, 6, $orderDate, 1, 0, 'C', $fill);
            $this->Cell(20, 6, ucfirst($order->status), 1, 0, 'C', $fill);
            $this->Cell(15, 6, $totalItems, 1, 0, 'C', $fill);
            $this->Cell(25, 6, number_format($order->total_amount, 3), 1, 0, 'R', $fill);
            $this->Cell(25, 6, number_format($order->paid_amount, 3), 1, 0, 'R', $fill);
            $this->Cell(25, 6, number_format($amountDue, 3), 1, 1, 'R', $fill);
            
            $fill = !$fill;
        }
        
        // Add detailed items section for each order
        $this->generateDetailedItems();
    }

    private function generateDetailedItems()
    {
        $this->Ln(10);
        $this->SetFont($this->font, 'B', 12);
        $this->Cell(0, 8, 'Order Items Details', 0, 1, 'L');
        
        foreach ($this->orders as $order) {
            // Check if we need a new page
            if ($this->GetY() > 220) {
                $this->AddPage();
            }
            
            $this->SetFont($this->font, 'B', 10);
            $this->SetFillColor(240, 240, 240);
            $this->Cell(0, 6, 'Order #' . $order->order_number . ' - ' . ($order->customer ? $order->customer->name : 'N/A'), 1, 1, 'L', true);
            
            if ($order->items && $order->items->count() > 0) {
                $this->SetFont($this->font, 'B', 8);
                $this->SetFillColor(220, 220, 220);
                
                $this->Cell(40, 6, 'Product Type', 1, 0, 'C', true);
                $this->Cell(30, 6, 'Service', 1, 0, 'C', true);
                $this->Cell(15, 6, 'Qty', 1, 0, 'C', true);
                $this->Cell(25, 6, 'Dimensions', 1, 0, 'C', true);
                $this->Cell(25, 6, 'Price/Unit', 1, 0, 'C', true);
                $this->Cell(25, 6, 'Subtotal', 1, 1, 'C', true);
                
                $this->SetFont($this->font, '', 7);
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
                    
                    $this->Cell(40, 5, $this->truncateText($productType, 15), 1, 0, 'L');
                    $this->Cell(30, 5, $this->truncateText($serviceName, 12), 1, 0, 'L');
                    $this->Cell(15, 5, $item->quantity, 1, 0, 'C');
                    $this->Cell(25, 5, $dimensions, 1, 0, 'C');
                    $this->Cell(25, 5, number_format($item->calculated_price_per_unit_item, 3), 1, 0, 'R');
                    $this->Cell(25, 5, number_format($item->sub_total, 3), 1, 1, 'R');
                }
            } else {
                $this->SetFont($this->font, '', 8);
                $this->Cell(0, 6, 'No items found', 1, 1, 'C');
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

<?php

namespace App\Pdf;

use App\Models\Order;
use Exception;

class OrdersReportPdf extends BasePdf
{
    protected $orders;
    protected $dateFrom;
    protected $dateTo;
    protected $settings;
    protected $font = 'arial';

    public function setOrders($orders)
    {
        $this->orders = $orders;
    }

    public function setDateRange($dateFrom, $dateTo)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    public function Header()
    {
        $this->SetFont($this->font, 'B', 16);
        $this->Cell(0, 10, 'Orders Report', 0, 1, 'C');
        
        $this->SetFont($this->font, '', 10);
        $this->Cell(0, 6, 'Date Range: ' . $this->dateFrom . ' to ' . $this->dateTo, 0, 1, 'C');
        $this->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Ln(5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont($this->font, 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }

    public function generate()
    {
        $this->AddPage();
        $this->SetFont($this->font, '', 10);

        // Summary section
        $this->SetFont($this->font, 'B', 12);
        $this->Cell(0, 8, 'Summary', 0, 1, 'L');
        $this->SetFont($this->font, '', 10);
        
        $totalOrders = $this->orders->count();
        $totalAmount = $this->orders->sum('paid_amount');
        $averageOrderValue = $totalOrders > 0 ? $totalAmount / $totalOrders : 0;

        $this->Cell(40, 6, 'Total Orders:', 0, 0);
        $this->Cell(0, 6, $totalOrders, 0, 1);
        
        $this->Cell(40, 6, 'Total Amount:', 0, 0);
        $this->Cell(0, 6, number_format($totalAmount, 3), 0, 1);
        
        $this->Cell(40, 6, 'Average Order:', 0, 0);
        $this->Cell(0, 6, number_format($averageOrderValue, 3), 0, 1);
        
        $this->Ln(10);

        // Table header
        $this->SetFont($this->font, 'B', 9);
        $this->SetFillColor(240, 240, 240);
        
        $this->Cell(25, 8, 'Order #', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Date/Time', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Amount', 1, 0, 'C', true);
        $this->Cell(25, 8, 'User', 1, 0, 'C', true);
        $this->Cell(60, 8, 'Items', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Status', 1, 1, 'C', true);

        // Table body
        $this->SetFont($this->font, '', 8);
        foreach ($this->orders as $order) {
            // Check if we need a new page
            if ($this->GetY() > 250) {
                $this->AddPage();
                // Repeat header on new page
                $this->SetFont($this->font, 'B', 9);
                $this->SetFillColor(240, 240, 240);
                $this->Cell(25, 8, 'Order #', 1, 0, 'C', true);
                $this->Cell(30, 8, 'Date/Time', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Amount', 1, 0, 'C', true);
                $this->Cell(25, 8, 'User', 1, 0, 'C', true);
                $this->Cell(60, 8, 'Items', 1, 0, 'C', true);
                $this->Cell(20, 8, 'Status', 1, 1, 'C', true);
                $this->SetFont($this->font, '', 8);
            }

            $orderNumber = $order->order_number;
            $orderDate = $order->order_date ? date('m/d/Y H:i', strtotime($order->order_date)) : '-';
            $amount = number_format($order->paid_amount, 3);
            $user = $order->user ? $order->user->name : '-';
            $status = ucfirst($order->status);

            // Prepare items text
            $itemsText = '';
            if ($order->items) {
                $itemNames = [];
                foreach ($order->items as $item) {
                    $itemName = $item->serviceOffering ? $item->serviceOffering->display_name : 'Unknown Item';
                    $itemText = $itemName . ' x' . $item->quantity;
                    if ($item->length_meters && $item->width_meters) {
                        $itemText .= ' (' . $item->length_meters . 'm x ' . $item->width_meters . 'm)';
                    }
                    $itemNames[] = $itemText;
                }
                $itemsText = implode(', ', $itemNames);
            }

            // Use MultiCell for items column to handle long text
            $this->Cell(25, 6, $orderNumber, 0, 0, 'L');
            $this->Cell(30, 6, $orderDate, 0, 0, 'L');
            $this->Cell(25, 6, $amount, 0, 0, 'R');
            $this->Cell(25, 6, $user, 0, 0, 'L');
            
            // Handle items column with MultiCell
            $this->MultiCell(60, 6, $itemsText, 0, 'L', false, 0);
            $this->Cell(20, 6, $status, 0, 1, 'C');
        }

        return $this->Output('', 'S');
    }
} 
<?php

namespace App\Pdf;

use App\Models\Order;
use TCPDF;

class PosInvoicePdf extends TCPDF
{
    protected Order $order;
    protected array $settings;
    protected $font = 'arial';
    protected $currencySymbol = '$';

    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
        $this->currencySymbol = $settings['general_default_currency_symbol'] ?? '$';
    }

    // We can define a very simple or no header/footer for POS receipts
    public function Header() {}
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont($this->font, 'I', 8);
        $this->Cell(0, 10, 'Thank you for your business!', 0, false, 'C');
    }

    /**
     * The main method to generate the PDF content using Cell()
     */
    public function generate()
    {
        $this->AddPage();
        $this->SetFont($this->font, '', 10);

        // --- Company Header ---
        $this->SetFont($this->font, 'B', 14);
        $this->Cell(0, 6, $this->settings['general_company_name'] ?? 'LaundryPro', 0, 1, 'C');
        $this->SetFont($this->font, '', 8);
        $this->MultiCell(0, 4, $this->settings['general_company_address'] ?? '123 Clean St, Fresh City', 0, 'C');
        $this->Cell(0, 4, $this->settings['general_company_phone'] ?? '555-123-4567', 0, 1, 'C');
        $this->Ln(4);

        // --- Divider ---
        $this->drawDashedLine();

        // --- Order Details ---
        $this->SetFont($this->font, '', 9);
        $this->Cell(20, 5, 'Order #:');
        $this->Cell(0, 5, $this->order->id, 0, 1, 'R');
        $this->Cell(20, 5, 'Date:');
        $this->Cell(0, 5, $this->order->order_date->format('M d, Y h:i A'), 0, 1, 'R');
        $this->Cell(20, 5, 'Customer:');
        $this->Cell(0, 5, $this->order->customer->name, 0, 1, 'R');
        $this->Cell(20, 5, 'Cashier:');
        $this->Cell(0, 5, $this->order->user->name ?? 'N/A', 0, 1, 'R');
        $this->Ln(2);

        $this->drawDashedLine();

        // --- Items Table Header ---
        $this->SetFont($this->font, 'B', 9);
        $this->Cell(38, 6, 'Item');
        $this->Cell(8, 6, 'Qty', 0, 0, 'C');
        $this->Cell(12, 6, 'Price', 0, 0, 'R');
        $this->Cell(14, 6, 'Total', 0, 1, 'R');
        $this->drawDashedLine();
        $this->Ln(1);

        // --- Items Table Body ---
        $this->SetFont($this->font, '', 9);
        foreach ($this->order->items as $item) {
            // Use MultiCell for the item name to allow wrapping
            $this->MultiCell(38, 4, $item->serviceOffering->display_name, 1, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
            $currentY = $this->GetY();
            $this->SetY($currentY - 4); // Move back up to align other cells

            $this->SetX(44); // Position for Qty
            $this->Cell(8, 4, $item->quantity, 1, 0, 'C');
            $this->SetX(52); // Position for Price
            $this->Cell(12, 4, number_format($item->calculated_price_per_unit_item, 2), 1, 0, 'R');
            $this->SetX(64); // Position for Total
            $this->Cell(14, 4, number_format($item->sub_total, 2), 1, 1, 'R');
        }

        $this->Ln(1);
        $this->drawDashedLine();

        // --- Summary Section ---
        $this->SetFont($this->font, '', 10);
        $this->Cell(48, 6, 'Subtotal:', 0, 0, 'R');
        $this->Cell(24, 6, number_format($this->order->total_amount, 2), 0, 1, 'R');
        
        // Add Tax/Discount here if needed

        $this->SetFont($this->font, 'B', 12);
        $this->Cell(48, 8, 'TOTAL:', 0, 0, 'R');
        $this->Cell(24, 8, $this->currencySymbol . number_format($this->order->total_amount, 2), 0, 1, 'R');
        
        $this->SetFont($this->font, '', 10);
        $this->Cell(48, 6, 'Amount Paid:', 0, 0, 'R');
        $this->Cell(24, 6, number_format($this->order->paid_amount, 2), 0, 1, 'R');
        
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(48, 6, 'Amount Due:', 0, 0, 'R');
        $this->Cell(24, 6, number_format($this->order->amount_due, 2), 0, 1, 'R');
        
        $this->Ln(5);

        // --- Notes Section ---
        if ($this->order->notes) {
            $this->SetFont('helvetica', 'I', 8);
            $this->MultiCell(0, 4, "Notes: " . $this->order->notes, 0, 'L');
        }

        // --- Barcode ---
        $style = [
            'position' => '', 'align' => 'C', 'stretch' => false,
            'fitwidth' => true, 'cellfitalign' => '', 'border' => false,
            'hpadding' => 'auto', 'vpadding' => 'auto', 'fgcolor' => [0,0,0],
            'bgcolor' => false, 'text' => true, 'font' => 'helvetica',
            'fontsize' => 8, 'stretchtext' => 4
        ];
        // $this->write1DBarcode(strval($this->order->id), 'C128', '', '', '', 15, 0.4, $style, 'N');
    }
    
    private function drawDashedLine()
    {
        $this->SetLineStyle(['width' => 0.1, 'dash' => '2,2', 'color' => [0, 0, 0]]);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 72, $this->GetY()); // 72mm width for 80mm paper with margins
        $this->Ln(1);
    }
}
<?php

namespace App\Pdf;

use App\Models\Order;
use TCPDF; // <-- Use the base TCPDF class directly

class InvoicePdf extends TCPDF
{
    protected Order $order;
    protected $companyName;
    protected $companyAddress;

    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    public function setCompanyDetails(string $name = null, string $address = null)
    {
        $this->companyName = $name ?? config('app_settings.company_name', config('app.name'));
        $this->companyAddress = $address ?? config('app_settings.company_address', '');
    }

    // Page header
    public function Header()
    {
        // Set font
        $this->SetFont('helvetica', 'B', 18);
        // Title
        $this->Cell(0, 15, 'INVOICE', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(5);

        // Company Details
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 10, $this->companyName, 0, false, 'C');
        $this->Ln(4);
        $this->Cell(0, 10, $this->companyAddress, 0, false, 'C');
        $this->Ln(10);

        // Draw a line under the header
        $this->Line(15, 35, 195, 35);
    }

    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Thank you for your business!', 0, false, 'L', 0, '', 0, false, 'T', 'M');
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }

    /**
     * Generates the main content of the invoice using Cell() method.
     */
    public function generate()
    {
        $this->AddPage();

        // --- Customer and Order Info Section ---
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(95, 7, 'Billed To:');
        $this->Cell(95, 7, 'Invoice Details:', 0, 1, 'R'); // 1 means new line
        $this->SetFont('helvetica', '', 10);

        $customerAddress = $this->order->customer->address ?? '';
        $customerLines = explode("\n", $customerAddress);

        // We need to calculate the max height for this block
        $maxLines = max(3 + count($customerLines), 3);
        $blockHeight = $maxLines * 5;

        // Use MultiCell for potentially multi-line addresses
        $this->MultiCell(95, $blockHeight,
            $this->order->customer->name . "\n" .
            $this->order->customer->phone . "\n" .
            $customerAddress,
            0, 'L'
        );

        // The Y position for the right column starts where the left column started
        $this->SetY($this->GetY() - $blockHeight);

        $this->MultiCell(95, 5, 'Invoice #: ' . $this->order->order_number, 0, 'R');
        $this->MultiCell(95, 5, 'Order Date: ' . ($this->order->order_date ? $this->order->order_date->format('M d, Y') : 'N/A'), 0, 'R');
        $this->MultiCell(95, 5, 'Due Date: ' . ($this->order->due_date ? $this->order->due_date->format('M d, Y') : 'N/A'), 0, 'R');

        $this->Ln(10); // Add some space

        // --- Items Table Header ---
        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(90, 7, 'Service / Item', 1, 0, 'L', true);
        $this->Cell(25, 7, 'Quantity', 1, 0, 'C', true);
        $this->Cell(30, 7, 'Unit Price', 1, 0, 'R', true);
        $this->Cell(35, 7, 'Subtotal', 1, 1, 'R', true);

        // --- Items Table Body ---
        $this->SetFont('helvetica', '', 9);
        $this->SetFillColor(255, 255, 255);
        $fill = false; // To alternate row colors
        foreach ($this->order->items as $item) {
            $description = $item->serviceOffering->display_name;
            if ($item->product_description_custom) {
                $description .= "\n  (" . $item->product_description_custom . ")";
            }
            if ($item->length_meters && $item->width_meters) {
                 $description .= "\n  " . $item->length_meters . "m x " . $item->width_meters . "m";
            }

            // Get the height of the MultiCell for the description
            $descriptionHeight = $this->getStringHeight(90, $description);
            $cellHeight = max(6, $descriptionHeight); // Minimum height of 6

            $this->MultiCell(90, $cellHeight, $description, 1, 'L', $fill, 0);
            $this->MultiCell(25, $cellHeight, $item->quantity, 1, 'C', $fill, 0, '', '', true, 0, false, true, $cellHeight, 'M');
            $this->MultiCell(30, $cellHeight, number_format($item->calculated_price_per_unit_item, 2), 1, 'R', $fill, 0, '', '', true, 0, false, true, $cellHeight, 'M');
            $this->MultiCell(35, $cellHeight, number_format($item->sub_total, 2), 1, 'R', $fill, 1, '', '', true, 0, false, true, $cellHeight, 'M');
            $fill = !$fill;
        }

        // --- Summary Section ---
        $this->Ln(5);
        $this->SetFont('helvetica', '', 10);
        $this->SetX(100); // Move to the right side of the page
        $this->Cell(45, 7, 'Subtotal', 0, 0, 'R');
        $this->Cell(45, 7, number_format($this->order->total_amount, 2), 0, 1, 'R');

        // Add Tax/Discount rows here if needed
        // $this->SetX(100);
        // $this->Cell(45, 7, 'Tax (10%)', 0, 0, 'R');
        // $this->Cell(45, 7, '... tax amount ...', 0, 1, 'R');

        $this->SetFont('helvetica', 'B', 11);
        $this->SetX(100);
        $this->Cell(45, 7, 'Total', 0, 0, 'R');
        $this->Cell(45, 7, number_format($this->order->total_amount, 2), 0, 1, 'R');

        $this->SetFont('helvetica', '', 10);
        $this->SetX(100);
        $this->Cell(45, 7, 'Amount Paid', 0, 0, 'R');
        $this->Cell(45, 7, number_format($this->order->paid_amount, 2), 0, 1, 'R');

        $this->SetFont('helvetica', 'B', 11);
        $this->SetX(100);
        $this->Cell(45, 7, 'Amount Due', 0, 0, 'R');
        $this->Cell(45, 7, number_format($this->order->amount_due, 2), 0, 1, 'R');

        // --- Notes Section ---
        if ($this->order->notes) {
            $this->Ln(10);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(0, 7, 'Notes:', 0, 1, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->MultiCell(0, 5, $this->order->notes, 0, 'L');
        }
    }
}
<?php

namespace App\Pdf;

use App\Models\Order;
use TCPDF;
use Exception;

class PosInvoicePdf extends TCPDF
{
    protected Order $order;
    protected array $settings;
    protected $font = 'arial';
    protected $currencySymbol = '$';
    protected $language = 'en'; // Default language
    protected $translations = [];

    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
        $this->currencySymbol = $settings['general_default_currency_symbol'] ?? '$';
        $this->language = $settings['language'] ?? 'en';
        $this->loadTranslations();
    }

    /**
     * Add logo to the PDF if logo URL is provided
     */
    private function addLogo()
    {
        $logoUrl = $this->settings['company_logo_url'] ?? null;
        
        if (!$logoUrl) {
            return false;
        }

        try {
            // Get the current Y position
            $currentY = $this->GetY();
            
            // Calculate logo dimensions (max width 20mm for POS receipt)
            $maxWidth = 20;
            $maxHeight = 15;
            
            // Get image dimensions
            $imageInfo = getimagesize($logoUrl);
            if (!$imageInfo) {
                return false;
            }
            
            $imageWidth = $imageInfo[0];
            $imageHeight = $imageInfo[1];
            
            // Calculate scaling to fit within max dimensions while maintaining aspect ratio
            $scaleX = $maxWidth / $imageWidth;
            $scaleY = $maxHeight / $imageHeight;
            $scale = min($scaleX, $scaleY);
            
            $scaledWidth = $imageWidth * $scale;
            $scaledHeight = $imageHeight * $scale;
            
            // Center the logo horizontally
            $x = ($this->GetPageWidth() - $scaledWidth) / 2;
            
            // Add the logo
            $this->Image($logoUrl, $x, $currentY, $scaledWidth, $scaledHeight);
            
            // Move Y position down to account for logo
            $this->SetY($currentY + $scaledHeight + 2);
            
            return true;
        } catch (Exception $e) {
            // Log error or handle gracefully
            return false;
        }
    }

    private function loadTranslations()
    {
        $this->translations = [
            'company_name' => [
                'en' => $this->settings['general_company_name'] ?? 'LaundryPro',
                'ar' => $this->settings['general_company_name_ar'] ?? 'لوندرى برو'
            ],
            'company_address' => [
                'en' => $this->settings['general_company_address'] ?? '123 Clean St, Fresh City',
                'ar' => $this->settings['general_company_address_ar'] ?? '١٢٣ شارع النظافة، المدينة النظيفة'
            ],
            'company_phone' => [
                'en' => $this->settings['general_company_phone'] ?? '555-123-4567',
                'ar' => $this->settings['general_company_phone_ar'] ?? '٥٥٥-١٢٣-٤٥٦٧'
            ],
            'order' => [
                'en' => 'Order #',
                'ar' => 'طلب رقم'
            ],
            'date' => [
                'en' => 'Date',
                'ar' => 'التاريخ'
            ],
            'customer' => [
                'en' => 'Customer',
                'ar' => 'العميل'
            ],
            'cashier' => [
                'en' => 'Cashier',
                'ar' => 'الكاشير'
            ],
            'item' => [
                'en' => 'Item',
                'ar' => 'العنصر'
            ],
            'quantity' => [
                'en' => 'Qty',
                'ar' => 'الكمية'
            ],
            'price' => [
                'en' => 'Price',
                'ar' => 'السعر'
            ],
            'total' => [
                'en' => 'Total',
                'ar' => 'المجموع'
            ],
            'subtotal' => [
                'en' => 'Subtotal',
                'ar' => 'المجموع الفرعي'
            ],
            'amount_paid' => [
                'en' => 'Amount Paid',
                'ar' => 'المبلغ المدفوع'
            ],
            'amount_due' => [
                'en' => 'Due',
                'ar' => ' المستحق'
            ],
            'notes' => [
                'en' => 'Notes',
                'ar' => 'ملاحظات'
            ],
            'category' => [
                'en' => 'Category',
                'ar' => 'الفئة'
            ],
            'thank_you' => [
                'en' => 'Thank you for your business!',
                //reverse the arabic text
                'ar' => 'معنا تعاملكم شكراً'
            ]
        ];
    }

    private function getBilingualText($key)
    {
        $en = $this->translations[$key]['en'] ?? $key;
        $ar = $this->translations[$key]['ar'] ?? $key;
        return $en . ' / ' . $ar;
    }

    /**
     * Group order items by category
     */
    private function groupItemsByCategory()
    {
        $groupedItems = [];
        
        foreach ($this->order->items as $item) {
            $categoryName = $item->serviceOffering->productType->category->name ?? 'Uncategorized';
            $categoryId = $item->serviceOffering->productType->category->id ?? 0;
            
            if (!isset($groupedItems[$categoryId])) {
                $groupedItems[$categoryId] = [
                    'name' => $categoryName,
                    'items' => []
                ];
            }
            
            $groupedItems[$categoryId]['items'][] = $item;
        }
        
        return $groupedItems;
    }

    // We can define a very simple or no header/footer for POS receipts
    public function Header() {}
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont($this->font, '', 8);
        $this->Cell(0, 10, $this->getBilingualText('thank_you'), 0, false, 'C');
    }

    /**
     * The main method to generate the PDF content using Cell()
     */
    public function generate()
    {
        $this->AddPage();
        $this->SetFont($this->font, '', 10);
        
        // Define padding variables
        $leftPadding = 0;
        $rightPadding = 0;

        // --- Company Header with Logo ---
        $logoAdded = $this->addLogo();
        
        // If logo was added, we don't need extra spacing
        if (!$logoAdded) {
            $this->Ln(2);
        }
        
        $this->SetFont($this->font, 'B', 14);
        $this->Cell(0, 6, $this->getBilingualText('company_name'), 0, 1, 'C');
        $this->SetFont($this->font, '', 8);
        $this->MultiCell(0, 4, $this->getBilingualText('company_address'), 0, 'C');
        $this->Cell(0, 4, $this->getBilingualText('company_phone'), 0, 1, 'C');
        $this->Ln(4);

        // --- Divider ---
        $this->SetLineStyle(['width' => 0.1, 'color' => [0, 0, 0]]);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 72, $this->GetY());
        $this->Ln(1);

        // --- Order Details ---
        $this->SetFont($this->font, '', 9);
        $this->Cell(20, 5, $this->getBilingualText('order'));
        $this->Cell(0, 5, $this->order->id, 0, 1, 'R');
        $this->Cell(20, 5, $this->getBilingualText('date'));
        $this->Cell(0, 5, $this->order->order_date->format('M d, Y h:i A'), 0, 1, 'R');
        $this->Cell(20, 5, $this->getBilingualText('customer'));
        $this->Cell(0, 5, $this->order->customer->name, 0, 1, 'R');
        $this->Cell(20, 5, $this->getBilingualText('cashier'));
        $this->Cell(0, 5, $this->order->user->name ?? 'N/A', 0, 1, 'R');
        $this->Ln(2);

        $this->SetLineStyle(['width' => 0.1, 'color' => [0, 0, 0]]);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 72, $this->GetY());
        $this->Ln(1);

        // --- Items Table Header ---
        $this->SetFont($this->font, 'B', 9);
        $this->Cell(35, 6, 'item', 0, 0, 'L');
        $this->Cell(8, 6, 'Qty', 0, 0, 'C'); // Only English
        $this->Cell(12, 6, 'Price', 0, 0, 'R');
        $this->Cell(14, 6, 'Total', 0, 1, 'R'); // Only English
        $this->SetLineStyle(['width' => 0.1, 'color' => [0, 0, 0]]);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 72, $this->GetY());
        $this->Ln(1);

        // --- Items Table Body (Grouped by Category) ---
        $this->SetFont($this->font, '', 9);
        $groupedItems = $this->groupItemsByCategory();
        
        foreach ($groupedItems as $categoryId => $categoryData) {
            // Category Header
            $this->SetFont($this->font, 'B', 9);
            $this->SetTextColor(100, 100, 100); // Gray color for category
            $this->Cell(0, 5, '--- ' . $categoryData['name'] . ' ---', 0, 1, 'C');
            $this->SetTextColor(0, 0, 0); // Reset to black
            $this->SetFont($this->font, '', 9);
            $this->Ln(1);
            
            // Items in this category
            foreach ($categoryData['items'] as $item) {
                // Use MultiCell for the item name to allow wrapping
                $this->MultiCell(35, 4, $item->serviceOffering->display_name, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
                $currentY = $this->GetY();
                $this->SetY($currentY - 4); // Move back up to align other cells

                $this->SetX(39); // Position for Qty
                $this->Cell(8, 4, $item->quantity, 0, 0, 'C');
                $this->SetX(47); // Position for Price
                $this->Cell(12, 4, number_format($item->calculated_price_per_unit_item, 2), 0, 0, 'R');
                $this->SetX(59); // Position for Total
                $this->Cell(14, 4, number_format($item->sub_total, 2), 0, 1, 'R');
            }
            
            // Add spacing between categories (except for the last category)
            if ($categoryId !== array_key_last($groupedItems)) {
                $this->Ln(2);
            }
        }

        $this->Ln(1);
        $this->SetLineStyle(['width' => 0.1, 'color' => [0, 0, 0]]);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 72, $this->GetY());
        $this->Ln(1);

        // --- Summary Section ---
        $this->SetFont($this->font, '', 10);
        $this->Cell(40, 6, $this->getBilingualText('subtotal') . ':', 0, 0, 'R');
        $this->Cell(20, 6, number_format($this->order->total_amount, 2), 0, 1, 'R');
        
        // Add Tax/Discount here if needed

        $this->SetFont($this->font, 'B', 12);
        $this->Cell(40, 8, $this->getBilingualText('total') . ':', 0, 0, 'R');
        $this->Cell(20, 8, $this->currencySymbol . number_format($this->order->total_amount, 2), 0, 1, 'R');
        
        $this->SetFont($this->font, '', 10);
        $this->Cell(40, 6, $this->getBilingualText('amount_paid') . ':', 0, 0, 'R');
        $this->Cell(20, 6, number_format($this->order->paid_amount, 2), 0, 1, 'R');
        
        $this->SetFont('arial', 'B', 10);
        $this->Cell(40, 6, $this->getBilingualText('amount_due') . ':', 0, 0, 'R');
        $this->Cell(20, 6, number_format($this->order->amount_due, 2), 0, 1, 'R');
        
        $this->Ln(5);

        // --- Notes Section ---
        if ($this->order->notes) {
            $this->SetFont('arial', 'I', 8);
            $this->MultiCell(0, 4, $this->getBilingualText('notes') . ": " . $this->order->notes, 0, 'L');
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
    
}
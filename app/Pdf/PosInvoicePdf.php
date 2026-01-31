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
            //increase the width of the image to 30
            $this->Image($logoUrl, 15, 0, 50, 35);

            // Move Y position down to account for logo
            $this->SetY(25);

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
                'en' => 'We work for the comfort of our customers',
                'ar' => 'نعـمل من أجل راحـــة عمالئـنا'
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

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont($this->font, '', 8);
        // $this->Cell(0, 5, 'we work for the comfort of our customers', 0, 1, 'C');
        // $this->Cell(0, 5, 'نعمل من أجل راحة عملائنا', 0, false, 'C');

    }

    /**
     * Calculate the total height needed for the receipt including category headers
     */
    public function calculateTotalHeight(): float
    {
        $totalHeight = 0;

        // Header section height
        $totalHeight += 20; // Company header with logo
        $totalHeight += 10; // Order details section
        $totalHeight += 8;  // Items table header
        $totalHeight += 2;  // Header line

        // Items section height
        $groupedItems = $this->groupItemsByCategory();
        foreach ($groupedItems as $categoryId => $categoryData) {
            $category = \App\Models\ProductCategory::find($categoryId);
            $hasSequence = $category && $category->sequence_enabled && $category->sequence_prefix;

            // Category division line (if sequence enabled)
            if ($hasSequence) {
                $totalHeight += 2; // Line height
            }

            // Category header with sequence
            $categoryHeaderHeight = $this->calculateCategoryHeaderHeight($categoryId, $categoryData['name'], $hasSequence);
            $totalHeight += $categoryHeaderHeight;
            $totalHeight += 1; // Spacing after category name

            // Items in this category
            foreach ($categoryData['items'] as $item) {
                // Calculate combined text height (product name + display name)
                $productName = $item->serviceOffering->productType->name ?? '';
                $displayName = $item->serviceOffering->display_name ?? '';
                $combinedText = $productName . ' - ' . $displayName;
                $itemNameHeight = $this->calculateTextHeight(35, $combinedText, 4);
                $totalHeight += max(4, $itemNameHeight); // Minimum 4mm height per item
            }

            // Spacing between categories (except for the last category)
            if ($categoryId !== array_key_last($groupedItems)) {
                $totalHeight += 2;
            }
        }

        // Summary section height
        $totalHeight += 2;  // Line before summary
        $totalHeight += 6;  // Subtotal
        $totalHeight += 8;  // Total
        $totalHeight += 6;  // Amount paid
        $totalHeight += 6;  // Amount due
        $totalHeight += 5;  // Spacing

        // Notes section height (if exists)
        if ($this->order->notes) {
            $notesHeight = $this->calculateTextHeight(72, $this->getBilingualText('notes') . ": " . $this->order->notes, 4);
            $totalHeight += $notesHeight;
        }

        // Footer height
        $totalHeight += 15; // Thank you message

        return $totalHeight;
    }

    /**
     * Get the remaining space on the current page
     */
    public function getRemainingPageHeight(): float
    {
        $pageHeight = $this->getPageHeight();
        $currentY = $this->GetY();
        $bottomMargin = 15; // Footer space

        return $pageHeight - $currentY - $bottomMargin;
    }

    /**
     * Check if there's enough space for the remaining content
     */
    public function hasEnoughSpace(float $requiredHeight): bool
    {
        return $this->getRemainingPageHeight() >= $requiredHeight;
    }

    /**
     * Calculate the height needed for just the items section (including category headers)
     */
    public function calculateItemsSectionHeight(): float
    {
        $totalHeight = 0;
        $groupedItems = $this->groupItemsByCategory();

        foreach ($groupedItems as $categoryId => $categoryData) {
            $category = \App\Models\ProductCategory::find($categoryId);
            $hasSequence = $category && $category->sequence_enabled && $category->sequence_prefix;

            // Category division line (if sequence enabled)
            if ($hasSequence) {
                $totalHeight += 2; // Line height
            }

            // Category header with sequence
            $categoryHeaderHeight = $this->calculateCategoryHeaderHeight($categoryId, $categoryData['name'], $hasSequence);
            $totalHeight += $categoryHeaderHeight;
            $totalHeight += 1; // Spacing after category name

            // Items in this category
            foreach ($categoryData['items'] as $item) {
                // Calculate combined text height (product name + display name)
                $productName = $item->serviceOffering->productType->name ?? '';
                $displayName = $item->serviceOffering->display_name ?? '';
                $combinedText = $productName . ' - ' . $displayName;
                $itemNameHeight = $this->calculateTextHeight(35, $combinedText, 4);
                $totalHeight += max(4, $itemNameHeight); // Minimum 4mm height per item
            }

            // Spacing between categories (except for the last category)
            if ($categoryId !== array_key_last($groupedItems)) {
                $totalHeight += 2;
            }
        }

        return $totalHeight;
    }

    /**
     * Get a detailed breakdown of the receipt height
     */
    public function getHeightBreakdown(): array
    {
        $breakdown = [
            'header' => 20,
            'order_details' => 10,
            'table_header' => 8,
            'header_line' => 2,
            'items_section' => $this->calculateItemsSectionHeight(),
            'summary_line' => 2,
            'subtotal' => 6,
            'total' => 8,
            'amount_paid' => 6,
            'amount_due' => 6,
            'spacing' => 5,
            'footer' => 15
        ];

        // Add notes height if exists
        if ($this->order->notes) {
            $breakdown['notes'] = $this->calculateTextHeight(72, $this->getBilingualText('notes') . ": " . $this->order->notes, 4);
        } else {
            $breakdown['notes'] = 0;
        }

        $breakdown['total_height'] = array_sum($breakdown);

        return $breakdown;
    }

    /**
     * Calculate the height needed for text that may wrap
     */
    private function calculateTextHeight(float $width, string $text, float $lineHeight): float
    {
        // Use TCPDF's getStringHeight method for more accurate calculation
        $this->SetFont($this->font, '', 9);
        $height = $this->getStringHeight($width, $text);
        return max($lineHeight, $height);
    }

    /**
     * Get the display name for a category including sequence if available
     */
    private function getCategoryDisplayName(int $categoryId, string $categoryName, bool $hasSequence): string
    {
        if ($hasSequence) {
            $sequence = $this->order->category_sequences[$categoryId] ?? '';
            if ($sequence) {
                return $sequence . ' - ' . $categoryName;
            }
        }
        return $categoryName;
    }

    /**
     * Get the sequence number for a category
     */
    private function getCategorySequence(int $categoryId): string
    {
        return $this->order->category_sequences[$categoryId] ?? '';
    }

    /**
     * Calculate the height needed for a category header including sequence
     */
    private function calculateCategoryHeaderHeight(int $categoryId, string $categoryName, bool $hasSequence): float
    {
        if ($hasSequence) {
            $sequence = $this->getCategorySequence($categoryId);
            if ($sequence) {
                // Height for sequence number (bigger font) + spacing + category name
                return 8 + 1 + 5; // 8mm for sequence, 1mm spacing, 5mm for category name
            }
        }
        // Regular category name height
        return $this->calculateTextHeight(72, $categoryName, 5);
    }

    /**
     * The main method to generate the PDF content using Cell()
     */
    public function generate()
    {
        $this->AddPage();

        // --- Constants ---
        $pageWidth = 72; // Printable width in mm (approx for 80mm paper)
        $lineColor = [100, 100, 100]; // Dark Gray
        $this->SetLineWidth(0.1);
        $this->SetDrawColorArray($lineColor);

        // --- Logo & Company Header ---
        $logoAdded = $this->addLogo();
        if (!$logoAdded) {
            $this->Ln(2);
        }

        // Company Info - Centered
        $this->SetFont($this->font, '', 9);
        $this->MultiCell(0, 4, $this->getBilingualText('company_address'), 0, 'C');
        $this->Cell(0, 4, $this->settings['general_company_phone'], 0, 1, 'C');
        $this->Ln(3);

        // --- Divider ---
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + $pageWidth, $this->GetY());
        $this->Ln(2);

        // --- Order Meta Data (Grid Layout) ---
        $this->SetFont($this->font, '', 9);

        // Helper for Key-Value Rows
        $printMetaRow = function ($label, $value) use ($pageWidth) {
            $this->SetFont($this->font, 'B', 9);
            $this->Cell(25, 5, $label . ':', 0, 0, 'L');
            $this->SetFont($this->font, '', 9);
            $this->Cell($pageWidth - 25, 5, $value, 0, 1, 'R');
        };

        $printMetaRow($this->getBilingualText('order'), '#' . $this->order->id);
        $printMetaRow($this->getBilingualText('date'), $this->order->order_date->format('d/m/Y h:i A'));
        $printMetaRow($this->getBilingualText('customer'), $this->order->customer->name);

        if ($this->order->user) {
            $printMetaRow($this->getBilingualText('cashier'), $this->order->user->name);
        }

        // --- Category Sequences (if any) ---
        if ($this->order->category_sequences && !empty($this->order->category_sequences)) {
            $this->Ln(1);
            $this->SetFont($this->font, 'B', 10);
            $this->MultiCell(0, 5, 'Seq: ' . $this->order->getCategorySequencesString(), 0, 'C');
        }

        $this->Ln(2);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + $pageWidth, $this->GetY());
        $this->Ln(2);

        // --- Items Table Header ---
        // Widths: Item(34), Qty(8), Price(13), Total(17) = 72mm
        $wItem = 34;
        $wQty = 8;
        $wPrice = 13;
        $wTotal = 17;

        $this->SetFont($this->font, 'B', 9);
        $this->Cell($wItem, 6, $this->getBilingualText('item'), 0, 0, 'L');
        $this->Cell($wQty, 6, 'Qty', 0, 0, 'C');
        $this->Cell($wPrice, 6, 'Price', 0, 0, 'R');
        $this->Cell($wTotal, 6, 'Total', 0, 1, 'R');

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + $pageWidth, $this->GetY());
        $this->Ln(1);

        // --- Items Body ---
        $this->SetFont($this->font, '', 9);
        $groupedItems = $this->groupItemsByCategory();

        foreach ($groupedItems as $categoryId => $categoryData) {
            $category = \App\Models\ProductCategory::find($categoryId);
            $hasSequence = $category && $category->sequence_enabled && $category->sequence_prefix;

            // Sequence Header (Only show sequence number prominently)
            if ($hasSequence) {
                $sequence = $this->getCategorySequence($categoryId);
                if ($sequence) {
                    $this->Ln(1);
                    $this->SetFont($this->font, 'B', 12);
                    $this->Cell(0, 6, $sequence, 0, 1, 'C');
                    $this->SetFont($this->font, '', 9);
                }
            }

            foreach ($categoryData['items'] as $item) {
                // Prepare Item Name
                $productName = $item->serviceOffering->productType->name ?? '';
                $displayName = $item->serviceOffering->display_name ?? '';
                $itemText = $productName . ($displayName ? ' / ' . $displayName : '');

                // Calculate Height required for this Item Name
                $nbLines = $this->getNumLines($itemText, $wItem);
                $lineHeight = 5;
                $rowHeight = $nbLines * $lineHeight;

                // Check for page break (simple check)
                if ($this->GetY() + $rowHeight > $this->getPageHeight() - 15) {
                    $this->AddPage();
                    // Re-print header if needed, but for POS usually unnecessary
                }

                $startX = $this->GetX();
                $startY = $this->GetY();

                // Print Name (MultiCell)
                $this->MultiCell($wItem, $lineHeight, $itemText, 0, 'L', false, 1);
                $endY = $this->GetY(); // Capture Y after name

                // Print Numbers (Single Line, aligned to top of row)
                // Reset to top of row
                $this->SetXY($startX + $wItem, $startY);

                $this->Cell($wQty, $lineHeight, $item->quantity, 0, 0, 'C');
                $this->Cell($wPrice, $lineHeight, number_format($item->calculated_price_per_unit_item, 3), 0, 0, 'R');
                $this->Cell($wTotal, $lineHeight, number_format($item->sub_total, 3), 0, 0, 'R');

                // Move to end of row (max Y)
                $this->SetY($endY);
                // Add tiny buffer if multiple lines
                if ($nbLines > 1) $this->Ln(1);
            }

            // tiny separation between categories if needed
            // if ($categoryId !== array_key_last($groupedItems)) {
            //    $this->Ln(1);
            // }
        }

        $this->Ln(2);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + $pageWidth, $this->GetY());
        $this->Ln(2);

        // --- Summary Section ---
        $printSummaryRow = function ($label, $value, $isBold = false, $fontSize = 9) use ($pageWidth) {
            $this->SetFont($this->font, $isBold ? 'B' : '', $fontSize);
            $this->Cell($pageWidth - 30, 5, $label . ':', 0, 0, 'R');
            $this->Cell(30, 5, $value, 0, 1, 'R');
        };

        $printSummaryRow($this->getBilingualText('subtotal'), number_format($this->order->calculated_total_amount, 3));

        $this->Ln(1);
        $printSummaryRow($this->getBilingualText('total'), $this->currencySymbol . ' ' . number_format($this->order->calculated_total_amount, 3), true, 12);
        $this->Ln(1);

        $printSummaryRow($this->getBilingualText('amount_paid'), number_format($this->order->paid_amount, 3));

        $dueAmount = $this->order->calculated_total_amount - $this->order->paid_amount;
        if ($dueAmount > 0) {
            $printSummaryRow($this->getBilingualText('amount_due'), number_format($dueAmount, 3), true, 10);
        }

        $this->Ln(4);

        // --- Footer Note ---
        if ($this->order->notes) {
            $this->SetFont($this->font, 'I', 8);
            $this->MultiCell(0, 4, $this->getBilingualText('notes') . ": " . $this->order->notes, 0, 'L');
            $this->Ln(2);
        }

        $this->SetFont($this->font, 'I', 8);
        $this->MultiCell(0, 4, $this->getBilingualText('thank_you'), 0, 'C');
        $this->Ln(2);
    }
}

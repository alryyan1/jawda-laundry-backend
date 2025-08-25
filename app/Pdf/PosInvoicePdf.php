<?php

namespace App\Pdf;

use App\Models\Order;
use App\Models\Setting;
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
        // Be tolerant to key names and fall back to DB directly (no helper)
        $this->currencySymbol = $settings['currency_symbol']
            ?? $settings['general_default_currency_symbol']
            ?? Setting::getValue('currency_symbol', 'OMR');
        $this->language = $settings['language'] ?? Setting::getValue('pdf_language', 'en');
        $this->loadTranslations();
    }

    /**
     * Add logo to the PDF if logo URL is provided
     */
    private function addLogo()
    {
        $logoUrl = $this->settings['company_logo_url'] ?? Setting::getValue('company_logo_url', null);
        
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
                'en' => $this->settings['general_company_name']
                    ?? $this->settings['company_name']
                    ?? Setting::getValue('company_name', 'RestaurantPro'),
                'ar' => $this->settings['general_company_name_ar']
                    ?? $this->settings['company_name_ar']
                    ?? Setting::getValue('company_name_ar', 'لوندرى برو')
            ],
            'company_address' => [
                'en' => $this->settings['general_company_address']
                    ?? $this->settings['company_address']
                    ?? Setting::getValue('company_address', '123 Clean St, Fresh City'),
                'ar' => $this->settings['general_company_address_ar']
                    ?? $this->settings['company_address_ar']
                    ?? Setting::getValue('company_address_ar', '١٢٣ شارع النظافة، المدينة النظيفة')
            ],
            'company_phone' => [
                'en' => $this->settings['general_company_phone']
                    ?? $this->settings['company_phone']
                    ?? Setting::getValue('company_phone', '555-123-4567'),
                'ar' => $this->settings['general_company_phone_ar']
                    ?? $this->settings['company_phone_ar']
                    ?? Setting::getValue('company_phone_ar', '٥٥٥-١٢٣-٤٥٦٧')
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
                'en' => '',
                'ar' => ''
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
        $this->Cell(0, 5, 'we work for the comfort of our customers', 0, 1, 'C');
        $this->Cell(0, 5, 'نعمل من أجل راحة عملائنا', 0, false, 'C');

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

        //  dd($this->settings);
        
        
        $this->SetFont($this->font, 'B', 14);
        $companyName = $this->settings['general_company_name']
            ?? Setting::getValue('company_name', config('app.name'));
        $this->Cell(0, 6, $companyName, 0, 1, 'C');
        $this->SetFont($this->font, '', 8);
        $this->MultiCell(0, 4, $this->getBilingualText('company_address'), 0, 'C');
        $phone1 = $this->settings['general_company_phone']
            ?? Setting::getValue('company_phone', '');
        $phone2 = $this->settings['general_company_phone_2']
            ?? Setting::getValue('company_phone_2', '');
        $phoneLine = trim($phone2 . (($phone2 && $phone1) ? ' - ' : '') . $phone1);
        if ($phoneLine !== '') {
            $this->Cell(0, 4, $phoneLine, 0, 1, 'C');
        }
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
        
        // Display category sequences if available
        if ($this->order->category_sequences && !empty($this->order->category_sequences)) {
            $this->Ln(1);
            $this->SetFont($this->font, 'B', 10);
            $this->Cell(0, 5, 'Category Sequences: ' . $this->order->getCategorySequencesString(), 0, 1, 'C');
            $this->SetFont($this->font, '', 9);
        }
        
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
            // Check if this category has sequence enabled
            $category = \App\Models\ProductCategory::find($categoryId);
            $hasSequence = $category && $category->sequence_enabled && $category->sequence_prefix;
            
            // Add division line for categories with sequences
            if ($hasSequence) {
                $this->SetLineStyle(['width' => 0.3, 'color' => [0, 0, 0]]);
                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 72, $this->GetY());
                $this->Ln(1);
            }
            
            // Category Header
            if ($hasSequence) {
                // Display sequence number on separate line with bigger font
                $sequence = $this->getCategorySequence($categoryId);
                if ($sequence) {
                    $this->SetFont($this->font, 'B', 14);
                    $this->SetTextColor(0, 0, 0); // Black color for sequence
                    $this->Cell(0, 8, $sequence, 0, 1, 'C');
                    $this->Ln(1);
                }
                
                // Display category name
                $this->SetFont($this->font, 'B', 11);
                // $this->SetTextColor(100, 100, 100); // Gray color for category name
                $this->Cell(0, 5, '--- ' . $categoryData['name'] . ' ---', 0, 1, 'C');
            } else {
                // Regular category without sequence
                $this->SetFont($this->font, 'B', 11);
                // $this->SetTextColor(100, 100, 100); // Gray color for regular categories
                $this->Cell(0, 5, '--- ' . $categoryData['name'] . ' ---', 0, 1, 'C');
            }
            
            $this->SetTextColor(0, 0, 0); // Reset to black
            $this->SetFont($this->font, '', 9);
            $this->Ln(1);
            
            // Items in this category
            foreach ($categoryData['items'] as $item) {
                // Create combined text: Product Name - Display Name
                $productName = $item->serviceOffering->productType->name ?? '';
                $displayName = $item->serviceOffering->display_name ?? '';
                $combinedText = $productName . ' - (' . $displayName.')';
                
                // Use MultiCell for the combined name to allow wrapping
                $this->MultiCell(35, 4, $combinedText, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
                $currentY = $this->GetY();
                $this->SetY($currentY - 4); // Move back up to align other cells

                $this->SetX(39); // Position for Qty
                $this->Cell(8, 4, $item->quantity, 0, 0, 'C');
                $this->SetX(47); // Position for Price
                $this->Cell(12, 4, number_format($item->calculated_price_per_unit_item, 3), 0, 0, 'R');
                $this->SetX(59); // Position for Total
                $this->Cell(14, 4, number_format($item->sub_total, 3), 0, 1, 'R');
                
                // Show item notes if setting is enabled and notes exist
                if (($this->settings['pos_show_item_notes_in_pdf'] ?? true) && !empty($item->notes)) {
                    $this->SetFont($this->font, 'I', 7);
                    $this->SetTextColor(100, 100, 100); // Gray color for notes
                    
                    // Sanitize and prepare notes text
                    $notesText = $item->notes;
                    
                    // Replace ❌ emoji with "excluded" text
                    $notesText = str_replace('❌', 'excluded:', $notesText);
                    
                    // Remove any problematic characters and ensure proper encoding
                    $notesText = trim($notesText);
                    $notesText = str_replace(["\r", "\n"], ' ', $notesText); // Replace newlines with spaces
                    $notesText = preg_replace('/[^\x20-\x7E]/', '', $notesText); // Keep only printable ASCII characters
                    $notesText = 'Notes: ' . $notesText;
                    
                    // Only add notes if we have valid text after sanitization
                    if (!empty(trim($notesText)) && $notesText !== 'Notes: ') {
                        try {
                            $this->MultiCell(69, 3, $notesText, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
                        } catch (Exception $e) {
                            // If MultiCell fails, try with a simpler approach
                            $this->Cell(69, 3, substr($notesText, 0, 50) . (strlen($notesText) > 50 ? '...' : ''), 0, 1, 'L');
                        }
                    }
                    
                    $this->SetTextColor(0, 0, 0); // Reset to black
                    $this->SetFont($this->font, '', 9);
                }
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
        $this->Cell(25, 6, number_format($this->order->calculated_total_amount, 3), 0, 1, 'R');
        
        // Add Tax/Discount here if needed

        $this->SetFont($this->font, 'B', 12);
        $this->Cell(40, 8, $this->getBilingualText('total') . ':', 0, 0, 'R');
        $this->Cell(25, 8, $this->currencySymbol . number_format($this->order->calculated_total_amount, 3), 0, 1, 'R');
        
        $this->SetFont($this->font, '', 10);
        $this->Cell(40, 6, $this->getBilingualText('amount_paid') . ':', 0, 0, 'R');
        $this->Cell(25, 6, number_format($this->order->paid_amount, 3), 0, 1, 'R');
        
        $this->SetFont('arial', 'B', 10);
        $this->Cell(40, 6, $this->getBilingualText('amount_due') . ':', 0, 0, 'R');
        $this->Cell(25, 6, number_format($this->order->calculated_total_amount - $this->order->paid_amount, 3), 0, 1, 'R');
        
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
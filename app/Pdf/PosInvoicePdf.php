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
            // Center the logo horizontally
            $logoWidth = 20;
            $logoHeight = 20;
            $x = ($this->GetPageWidth() - $logoWidth) / 2;

            // Add the logo centered
            $this->Image($logoUrl, $x, $this->GetY(), $logoWidth, $logoHeight);

            // Move Y position down to account for logo
            $this->SetY($this->GetY() + $logoHeight + 2);

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
            ],
            'order_invoice' => [
                'en' => 'Order Invoice',
                'ar' => 'فاتورة الطلب'
            ],
            'invoice_to' => [
                'en' => 'Invoice To',
                'ar' => 'فاتورة إلى'
            ],
            'service_name' => [
                'en' => 'Service Name',
                'ar' => 'اسم الخدمة'
            ],
            'rate' => [
                'en' => 'Rate',
                'ar' => 'السعر'
            ],
            'vat_tax' => [
                'en' => 'VAT: TAX',
                'ar' => 'ضريبة القيمة المضافة: الضريبة'
            ],
            'addon' => [
                'en' => 'Addon',
                'ar' => 'إضافة'
            ],
            'discount' => [
                'en' => 'Discount',
                'ar' => 'خصم'
            ],
            'tax' => [
                'en' => 'Tax',
                'ar' => 'الضريبة'
            ],
            'gross_total' => [
                'en' => 'Gross Total',
                'ar' => 'المجموع الإجمالي'
            ],
            'delivery_date' => [
                'en' => 'Delivery Date',
                'ar' => 'تاريخ التسليم'
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
        $pageWidth = 72; // Match the page width used in generate()
        $wServiceName = 34; // Service name column width

        // --- Header Section ---
        // Logo (if exists: 20mm + 2mm spacing, if not: 2mm)
        $logoUrl = $this->settings['company_logo_url'] ?? null;
        if ($logoUrl) {
            $totalHeight += 20; // Logo height
            $totalHeight += 2; // Spacing after logo
        } else {
            $totalHeight += 2; // Spacing if no logo
        }

        // Company Name (5mm + 1mm spacing)
        $totalHeight += 5;
        $totalHeight += 1;

        // Company Address (4mm)
        $totalHeight += 4;

        // Phone Number (4mm)
        $totalHeight += 4;

        // Separator dash (4mm)
        $totalHeight += 4;

        // Spacing (2mm)
        $totalHeight += 2;

        // Order Invoice Black Bar Header (8mm + 2mm spacing)
        $totalHeight += 8;
        $totalHeight += 2;

        // --- Order Details Section ---
        // Order No row (5mm)
        $totalHeight += 5;
        
        // Date row (5mm)
        $totalHeight += 5;
        
        // Delivery Date row (5mm)
        $totalHeight += 5;

        // Spacing after order details (2mm)
        $totalHeight += 2;

        // --- Invoice To Section ---
        $customerName = $this->order->customer->name ?? '';
        $customerPhone = $this->order->customer->phone ?? '';
        $invoiceToText = ($this->translations['invoice_to']['en'] ?? 'Invoice To') . ': ' . $customerName . ' ' . $customerPhone;
        $invoiceToHeight = $this->calculateTextHeight($pageWidth, $invoiceToText, 5);
        $totalHeight += $invoiceToHeight;

        // Spacing after Invoice To (2mm)
        $totalHeight += 2;

        // --- Items Table Header ---
        // Table header (6mm + 1mm spacing)
        $totalHeight += 6;
        $totalHeight += 1;

        // --- Items Section ---
        $groupedItems = $this->groupItemsByCategory();

        foreach ($groupedItems as $categoryId => $categoryData) {
            $category = \App\Models\ProductCategory::find($categoryId);
            $hasSequence = $category && $category->sequence_enabled && $category->sequence_prefix;

            // Sequence Header (if enabled: 1mm Ln + 6mm Cell)
            if ($hasSequence) {
                $sequence = $this->getCategorySequence($categoryId);
                if ($sequence) {
                    $totalHeight += 1; // Ln(1)
                    $totalHeight += 6; // Cell height
                }
            }

            // Items in this category
            $itemIndex = 0;
            foreach ($categoryData['items'] as $item) {
                // Dotted line separator (before each item except the first one in each category)
                if ($itemIndex > 0) {
                    $totalHeight += 1; // Ln(1) before dotted line
                    $totalHeight += 1; // Ln(1) after dotted line
                }

                // Prepare Service Name: "{productType name} - {display_name}"
                $productName = $item->serviceOffering->productType->name ?? '';
                $displayName = $item->serviceOffering->productType->name ?? '';
                $serviceName = $productName . ($displayName ? ' - ' . $displayName : '');

                // Prepare Description: "[{serviceAction name} {serviceAction description}]"
                $serviceAction = $item->serviceOffering->serviceAction ?? null;
                $serviceActionName = $serviceAction ? ($serviceAction->name ?? '') : '';
                $serviceActionDesc = $serviceAction ? ($serviceAction->description ?? '') : '';
                $description = '[' . $serviceActionName;
                if ($serviceActionDesc) {
                    $description .= ' ' . $serviceActionDesc;
                }
                $description .= ']';

                // Combine service name and description
                $itemText = $serviceName . "\n" . $description;

                // Calculate actual height for this item (using lineHeight = 4)
                $this->SetFont($this->font, '', 9);
                $itemHeight = $this->getStringHeight($wServiceName, $itemText);
                $totalHeight += max(8, $itemHeight); // Minimum 8mm (2 lines × 4mm)

                // Add spacing if multi-line (1mm)
                $nbLines = $this->getNumLines($itemText, $wServiceName);
                if ($nbLines > 2) {
                    $totalHeight += 1;
                }

                $itemIndex++;
            }
        }

        // --- Summary Section ---
        // Spacing before summary (2mm)
        $totalHeight += 2;

        // Line (no height, just draws line)
        // Spacing after line (2mm)
        $totalHeight += 2;

        // VAT: TAX label (4mm + 1mm spacing)
        $totalHeight += 4;
        $totalHeight += 1;

        // Sub Total row (5mm)
        $totalHeight += 5;

        // Addon row (5mm)
        $totalHeight += 5;

        // Discount row (5mm)
        $totalHeight += 5;

        // Tax (0%) row (5mm)
        $totalHeight += 5;

        // Gross Total row (5mm)
        $totalHeight += 5;

        // Paid Amount row (5mm)
        $totalHeight += 5;

        // Spacing after summary (4mm)
        $totalHeight += 4;

        // --- Footer Section ---
        // Notes section (if exists)
        if ($this->order->notes) {
            $notesText = $this->getBilingualText('notes') . ": " . $this->order->notes;
            $this->SetFont($this->font, 'I', 8);
            $notesHeight = $this->getStringHeight($pageWidth, $notesText);
            $totalHeight += $notesHeight;
            $totalHeight += 2; // Spacing after notes
        }

        // Thank you message (4mm + 2mm spacing)
        $this->SetFont($this->font, 'I', 8);
        $thankYouText = $this->getBilingualText('thank_you');
        $thankYouHeight = $this->getStringHeight($pageWidth, $thankYouText);
        $totalHeight += $thankYouHeight;
        $totalHeight += 2;

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

        // Company Name - Below logo
        $companyName = $this->settings['general_company_name'] ?? 'H2O';
        $this->SetFont($this->font, 'B', 10);
        $this->Cell(0, 5, $companyName, 0, 1, 'C');
        $this->Ln(1);

        // Company Address - Single line (Arabic address)
        $address = $this->settings['general_company_address_ar'] ?? $this->settings['general_company_address'] ?? '';
        $this->SetFont($this->font, '', 8);
        $this->Cell(0, 4, $address, 0, 1, 'C');

        // Phone Number
        $phone = $this->settings['general_company_phone'] ?? '';
        $this->Cell(0, 4, $phone, 0, 1, 'C');

        // Separator dash
        $this->Cell(0, 4, '-', 0, 1, 'C');
        $this->Ln(2);

        // --- Order Invoice Black Bar Header ---
        $this->SetFillColor(0, 0, 0); // Black background
        $this->SetTextColor(255, 255, 255); // White text
        $this->SetFont($this->font, 'B', 10);
        $this->Cell(0, 8, $this->translations['order_invoice']['en'] ?? 'Order Invoice', 0, 1, 'C', true);
        $this->SetTextColor(0, 0, 0); // Reset to black text
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

        // Order No format: ORD-{id}
        $orderNo = 'ORD-' . $this->order->id;
        $printMetaRow($this->translations['order']['en'] ?? 'Order #', $orderNo);
        
        // Order Date format: d/m/Y
        $orderDate = $this->order->order_date->format('d/m/Y');
        $printMetaRow($this->translations['date']['en'] ?? 'Date', $orderDate);
        
        // Delivery Date
        $deliveryDateText = $this->order->delivered_date 
            ? $this->order->delivered_date->format('d/m/Y')
            : $this->order->order_date->format('d/m/Y') . ' (Pending)';
        $printMetaRow($this->translations['delivery_date']['en'] ?? 'Delivery Date', $deliveryDateText);

        $this->Ln(2);

        // --- Invoice To Section ---
        $customerName = $this->order->customer->name ?? '';
        $customerPhone = $this->order->customer->phone ?? '';
        $invoiceToText = ($this->translations['invoice_to']['en'] ?? 'Invoice To') . ': ' . $customerName . ' ' . $customerPhone;
        $this->SetFont($this->font, '', 9);
        $this->Cell(0, 5, $invoiceToText, 0, 1, 'L');
        $this->Ln(2);

        // --- Items Table Header (Black Background with White Text) ---
        // Widths: Service Name(34), Rate(13), QTY(8), Total(17) = 72mm
        $wServiceName = 34;
        $wRate = 13;
        $wQty = 8;
        $wTotal = 17;

        $this->SetFillColor(0, 0, 0); // Black background
        $this->SetTextColor(255, 255, 255); // White text
        $this->SetFont($this->font, 'B', 9);
        $this->Cell($wServiceName, 6, $this->translations['service_name']['en'] ?? 'Service Name', 0, 0, 'L', true);
        $this->Cell($wRate, 6, $this->translations['rate']['en'] ?? 'Rate', 0, 0, 'C', true);
        $this->Cell($wQty, 6, 'QTY', 0, 0, 'C', true);
        $this->Cell($wTotal, 6, $this->translations['total']['en'] ?? 'Total', 0, 1, 'R', true);
        $this->SetTextColor(0, 0, 0); // Reset to black text
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

            $itemIndex = 0;
            foreach ($categoryData['items'] as $item) {
                // Add dotted line separator before each item (except the first one)
                if ($itemIndex > 0) {
                    $this->Ln(1);
                    $currentY = $this->GetY();
                    // Draw dotted line across the full width
                    $this->SetLineWidth(0.1);
                    $this->SetDrawColorArray([150, 150, 150]); // Light gray for dotted line
                    // Draw dotted line using small dashes
                    $dashLength = 1;
                    $gapLength = 1;
                    $x = $this->GetX();
                    $endX = $x + $pageWidth;
                    while ($x < $endX) {
                        $this->Line($x, $currentY, min($x + $dashLength, $endX), $currentY);
                        $x += $dashLength + $gapLength;
                    }
                    $this->SetDrawColorArray([100, 100, 100]); // Reset to original line color (dark gray)
                    $this->Ln(1);
                }

                // Prepare Service Name: "{productType name}"
                $productName = $item->serviceOffering->productType->name ?? '';
                $serviceName = $productName;

                // Prepare Description: "[{serviceAction name} {serviceAction description}]"
                $serviceAction = $item->serviceOffering->serviceAction ?? null;
                $serviceActionName = $serviceAction ? ($serviceAction->name ?? '') : '';
                $serviceActionDesc = $serviceAction ? ($serviceAction->description ?? '') : '';
                $description = '[' . $serviceActionName;
                if ($serviceActionDesc) {
                    $description .= ' ' . $serviceActionDesc;
                }
                $description .= ']';

                // Combine service name and description
                $itemText = $serviceName . "\n" . $description;

                // Calculate Height required for this Item
                $nbLines = $this->getNumLines($itemText, $wServiceName);
                $lineHeight = 4;
                $rowHeight = $nbLines * $lineHeight;

                // Check for page break (simple check)
                if ($this->GetY() + $rowHeight > $this->getPageHeight() - 15) {
                    $this->AddPage();
                    // Re-print header if needed, but for POS usually unnecessary
                }

                $startX = $this->GetX();
                $startY = $this->GetY();

                // Print Service Name and Description (MultiCell)
                $this->MultiCell($wServiceName, $lineHeight, $itemText, 0, 'L', false, 1);
                $endY = $this->GetY(); // Capture Y after name

                // Print Numbers (Single Line, aligned to top of row)
                // Reset to top of row
                $this->SetXY($startX + $wServiceName, $startY);

                // Format currency values
                $rate =  number_format($item->calculated_price_per_unit_item, 2);
                $total =  number_format($item->sub_total, 2);

                $this->Cell($wRate, $lineHeight, $rate, 0, 0, 'R');
                $this->Cell($wQty, $lineHeight, $item->quantity, 0, 0, 'C');
                $this->Cell($wTotal, $lineHeight, $total, 0, 0, 'R');

                // Move to end of row (max Y)
                $this->SetY($endY);
                // Add tiny buffer if multiple lines
                if ($nbLines > 1) $this->Ln(1);
                
                $itemIndex++;
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

        // Calculate totals: total items count and total quantity
        $totalItemsCount = $this->order->items->count();
        $totalQuantity = $this->order->items->sum('quantity');

        // Display Items and Quantity totals
        $this->SetFont($this->font, '', 8);
        $itemsLabelEn = ($this->translations['item']['en'] ?? 'Item') . 's';
        $itemsLabelAr = ($this->translations['item']['ar'] ?? 'العنصر');
        $itemsLabel = $itemsLabelEn . ' / ' . $itemsLabelAr;
        $quantityLabel = $this->getBilingualText('quantity');
        $printSummaryRow($itemsLabel, (string)$totalItemsCount, false, 8);
        $printSummaryRow($quantityLabel, (string)$totalQuantity, false, 8);
        $this->Ln(1);

        // Sub Total
        $subTotal = $this->currencySymbol . ' ' . number_format((float)$this->order->calculated_total_amount, 2);
        $printSummaryRow($this->getBilingualText('subtotal'), $subTotal);

        // Gross Total
        $grossTotal = $this->currencySymbol . ' ' . number_format((float)$this->order->calculated_total_amount, 2);
        $printSummaryRow($this->getBilingualText('gross_total'), $grossTotal, true, 10);

        // Paid Amount
        $paidAmount = $this->currencySymbol . ' ' . number_format((float)($this->order->paid_amount ?? 0), 2);
        $printSummaryRow($this->getBilingualText('amount_paid'), $paidAmount);

        $this->Ln(4);

        // --- Footer Note ---
        if ($this->order->notes) {
            $this->SetFont($this->font, 'I', 8);
            $this->MultiCell(0, 4, ($this->translations['notes']['en'] ?? 'Notes') . ": " . $this->order->notes, 0, 'L');
            $this->Ln(2);
        }

        $this->SetFont($this->font, 'I', 8);
        $this->MultiCell(0, 4, $this->translations['thank_you']['en'] ?? 'We work for the comfort of our customers', 0, 'C');
        $this->Ln(2);
    }
}

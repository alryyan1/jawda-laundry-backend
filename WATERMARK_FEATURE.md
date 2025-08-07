# PDF Watermark Feature

## Overview

The PDF watermark feature allows you to add a watermark to all generated PDF documents in the system. The watermark is controlled by a setting in the database and can be enabled or disabled globally.

## Features

- **Global Control**: Enable/disable watermark for all PDFs through a single setting
- **Customizable Text**: Default watermark text is "JAWDA LAUNDRY" but can be customized
- **Professional Appearance**: Semi-transparent, rotated watermark that doesn't interfere with content
- **Automatic Application**: Watermark is automatically added to all PDF pages when enabled

## Settings

### Database Setting

The watermark feature is controlled by the `show_watermark` setting in the `settings` table:

- **Key**: `show_watermark`
- **Type**: `boolean`
- **Group**: `pdf`
- **Default Value**: `false`
- **Description**: Enable watermark on all generated PDF documents

### Managing the Setting

You can manage this setting through:

1. **Database directly**:
   ```sql
   UPDATE settings SET value = 'true' WHERE `key` = 'show_watermark';
   ```

2. **Laravel Tinker**:
   ```php
   \App\Models\Setting::setValue('show_watermark', true);
   ```

3. **Admin Panel**: If you have a settings management interface

## Implementation Details

### Base PDF Class

All PDF classes now extend `App\Pdf\BasePdf` instead of `TCPDF` directly. This base class includes:

- Automatic watermark checking on page creation
- Watermark rendering with proper styling
- Methods to customize watermark behavior

### Watermark Properties

- **Text**: "JAWDA LAUNDRY" (default)
- **Font**: Arial Bold, 48pt
- **Color**: Light gray (RGB: 200, 200, 200)
- **Opacity**: 30%
- **Rotation**: 45 degrees
- **Position**: Centered on page

### Affected PDF Classes

The following PDF classes have been updated to support watermarks:

- `OrdersListPdf` - Orders list reports
- `InvoicePdf` - Customer invoices
- `PosInvoicePdf` - POS receipts
- `OrdersReportPdf` - Order reports
- `MyCustomTCPDF` - Custom PDF base class

## Usage Examples

### Basic Usage

```php
// Create a PDF - watermark will be applied automatically if setting is enabled
$pdf = new \App\Pdf\OrdersListPdf();
$pdf->AddPage();
// ... add content ...
$output = $pdf->Output('document.pdf', 'S');
```

### Custom Watermark Text

```php
$pdf = new \App\Pdf\OrdersListPdf();
$pdf->setWatermarkText('CONFIDENTIAL');
$pdf->AddPage();
// ... add content ...
```

### Manual Control

```php
$pdf = new \App\Pdf\OrdersListPdf();
$pdf->setShowWatermark(true); // Force enable watermark
$pdf->AddPage();
// ... add content ...
```

## Technical Implementation

### BasePdf Class Methods

- `addWatermark()`: Internal method that renders the watermark
- `setWatermarkText($text)`: Set custom watermark text
- `setShowWatermark($show)`: Manually enable/disable watermark
- `AddPage()`: Overridden to automatically add watermark

### Watermark Rendering Process

1. Check if watermark is enabled (setting or manual override)
2. Save current PDF state
3. Calculate page center position
4. Set watermark properties (font, color, opacity)
5. Apply rotation transformation
6. Draw watermark text
7. Restore PDF state

## Migration

The watermark feature was added via migration `2025_08_08_022018_add_show_watermark_to_settings_table.php` which:

- Adds the `show_watermark` setting to the database
- Sets default value to `false` (disabled)
- Groups it under 'pdf' settings

## Testing

To test the watermark feature:

1. Enable the setting:
   ```php
   \App\Models\Setting::setValue('show_watermark', true);
   ```

2. Generate any PDF document through the application

3. Check that the watermark appears on all pages

4. Disable the setting:
   ```php
   \App\Models\Setting::setValue('show_watermark', false);
   ```

5. Generate another PDF and verify no watermark appears

## Notes

- The watermark is applied to every page automatically
- Watermark text can be customized per PDF instance
- The feature respects the global setting by default
- Manual override is available for special cases
- Watermark is designed to be visible but not interfere with content readability

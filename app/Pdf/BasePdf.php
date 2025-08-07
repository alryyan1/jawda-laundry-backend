<?php

namespace App\Pdf;

use TCPDF;
use App\Models\Setting;

class BasePdf extends TCPDF
{
    protected $showWatermark = false;
    protected $watermarkText = 'JAWDA LAUNDRY';
    protected $font = 'arial';

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false)
    {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        
        // Check if watermark should be shown
        $this->showWatermark = Setting::getValue('show_watermark', false);
        
        // Set default font
        $this->SetFont($this->font, '', 10);
    }

    /**
     * Add watermark to the current page
     */
    protected function addWatermark()
    {
        if (!$this->showWatermark) {
            return;
        }

        // Save current state
        $this->startTransform();
        
        // Get page dimensions
        $pageWidth = $this->GetPageWidth();
        $pageHeight = $this->GetPageHeight();
        
        // Calculate center position
        $x = $pageWidth / 2;
        $y = $pageHeight / 2;
        
        // Set watermark properties
        $this->SetFont($this->font, 'B', 48);
        $this->SetTextColor(200, 200, 200); // Light gray
        $this->SetAlpha(0.3); // 30% opacity
        
        // Rotate text 45 degrees
        $this->StartTransform();
        $this->Rotate(45, $x, $y);
        
        // Calculate text width and center it
        $textWidth = $this->GetStringWidth($this->watermarkText);
        $textX = $x - ($textWidth / 2);
        $textY = $y;
        
        // Draw the watermark text
        $this->Text($textX, $textY, $this->watermarkText);
        
        // Restore transformations
        $this->StopTransform();
        $this->SetAlpha(1.0); // Reset opacity
        $this->SetTextColor(0, 0, 0); // Reset text color
        
        // Restore state
        $this->stopTransform();
    }

    /**
     * Override AddPage to add watermark
     */
    public function AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false)
    {
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
        
        // Add watermark after page is created
        $this->addWatermark();
    }

    /**
     * Set watermark text
     */
    public function setWatermarkText($text)
    {
        $this->watermarkText = $text;
    }

    /**
     * Enable or disable watermark
     */
    public function setShowWatermark($show)
    {
        $this->showWatermark = $show;
    }
}

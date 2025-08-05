<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallTcpdfFont extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tcpdf:install-font 
                            {font=arial : The font name to install (without extension)}
                            {--type=TrueTypeUnicode : Font type for TCPDF}
                            {--path=public : Path to the font file relative to project root}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install TCPDF fonts using tcpdf_addfont.php script';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fontName = $this->argument('font');
        $fontType = $this->option('type');
        $fontPath = $this->option('path');
        
        // Construct the full path to the font file
        $fontFile = base_path($fontPath . '/' . strtoupper($fontName) . '.TTF');
        
        // Check if font file exists
        if (!File::exists($fontFile)) {
            $this->error("Font file not found: {$fontFile}");
            $this->info("Available fonts in {$fontPath} directory:");
            $this->listAvailableFonts($fontPath);
            return 1;
        }
        
        $this->info("Installing font: {$fontName}");
        $this->info("Font file: {$fontFile}");
        $this->info("Font type: {$fontType}");
        
        // Find TCPDF installation directory
        $tcpdfPath = $this->findTcpdfPath();
        if (!$tcpdfPath) {
            $this->error("TCPDF installation not found. Please ensure TCPDF is properly installed.");
            return 1;
        }
        
        $tcpdfAddFontScript = $tcpdfPath . '/tools/tcpdf_addfont.php';
        
        if (!File::exists($tcpdfAddFontScript)) {
            $this->error("TCPDF addfont script not found: {$tcpdfAddFontScript}");
            return 1;
        }
        
        // Execute the TCPDF font installation command
        $command = "php {$tcpdfAddFontScript} -b -t {$fontType} -i {$fontFile}";
        
        $this->info("Executing command: {$command}");
        
        // Execute the command
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->info("Font installed successfully!");
            $this->info("Output:");
            foreach ($output as $line) {
                $this->line($line);
            }
        } else {
            $this->error("Failed to install font. Return code: {$returnCode}");
            $this->error("Output:");
            foreach ($output as $line) {
                $this->error($line);
            }
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Find TCPDF installation path
     */
    private function findTcpdfPath()
    {
        // Common TCPDF installation paths
        $possiblePaths = [
            base_path('vendor/tecnickcom/tcpdf'),
            base_path('vendor/tecnickcom/tcpdf/tcpdf'),
            base_path('vendor/tcpdf'),
            base_path('vendor/tcpdf/tcpdf'),
        ];
        
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * List available fonts in the specified directory
     */
    private function listAvailableFonts($directory)
    {
        $fullPath = base_path($directory);
        if (!File::exists($fullPath)) {
            $this->error("Directory not found: {$fullPath}");
            return;
        }
        
        $files = File::files($fullPath);
        $fontFiles = array_filter($files, function($file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, ['ttf', 'otf', 'pfb']);
        });
        
        if (empty($fontFiles)) {
            $this->warn("No font files found in {$directory}");
            return;
        }
        
        foreach ($fontFiles as $file) {
            $this->line("  - " . basename($file));
        }
    }
} 
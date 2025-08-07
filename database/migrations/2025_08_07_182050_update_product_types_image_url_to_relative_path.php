<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all product types with image_url
        $productTypes = DB::table('product_types')->whereNotNull('image_url')->get();
        
        foreach ($productTypes as $productType) {
            $currentUrl = $productType->image_url;
            
            // Convert absolute URL to relative path
            $relativePath = $this->convertToRelativePath($currentUrl);
            
            if ($relativePath) {
                DB::table('product_types')
                    ->where('id', $productType->id)
                    ->update(['image_url' => $relativePath]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all product types with image_url
        $productTypes = DB::table('product_types')->whereNotNull('image_url')->get();
        
        foreach ($productTypes as $productType) {
            $currentPath = $productType->image_url;
            
            // Convert relative path back to absolute URL
            $absoluteUrl = $this->convertToAbsoluteUrl($currentPath);
            
            if ($absoluteUrl) {
                DB::table('product_types')
                    ->where('id', $productType->id)
                    ->update(['image_url' => $absoluteUrl]);
            }
        }
    }

    /**
     * Convert absolute URL to relative path
     */
    private function convertToRelativePath(string $url): ?string
    {
        // Remove common URL prefixes
        $url = str_replace('http://localhost/storage/', '', $url);
        $url = str_replace('http://127.0.0.1/storage/', '', $url);
        $url = str_replace('https://localhost/storage/', '', $url);
        $url = str_replace('https://127.0.0.1/storage/', '', $url);
        
        // Remove any remaining domain parts
        if (preg_match('/^https?:\/\/[^\/]+\/storage\/(.+)$/', $url, $matches)) {
            $url = $matches[1];
        }
        
        // Ensure it starts with the correct path
        if (!str_starts_with($url, 'product_types/')) {
            $url = 'product_types/' . $url;
        }
        
        return $url;
    }

    /**
     * Convert relative path to absolute URL
     */
    private function convertToAbsoluteUrl(string $path): ?string
    {
        // Ensure it's a relative path
        if (str_starts_with($path, 'http')) {
            return $path; // Already absolute
        }
        
        // Add the storage prefix
        return 'storage/' . $path;
    }
};

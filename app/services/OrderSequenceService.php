<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductCategory;
use Illuminate\Support\Collection;

class OrderSequenceService
{
    /**
     * Generate category-specific sequences for an order
     * 
     * @param Order $order
     * @return array
     */
    public function generateOrderSequences(Order $order): array
    {
        $sequences = [];
        
        // Get all unique categories from order items
        $categories = $this->getOrderCategories($order);
        
        foreach ($categories as $category) {
            if ($category->sequence_enabled && $category->sequence_prefix) {
                $itemCount = $this->getCategoryItemCount($order, $category->id);
                $sequence = $this->generateSequenceForCategory($category, $itemCount);
                $sequences[$category->id] = $sequence;
            }
        }
        
        return $sequences;
    }
    
    /**
     * Get all unique categories from order items
     */
    private function getOrderCategories(Order $order): Collection
    {
        $categoryIds = $order->items()
            ->with('serviceOffering.productType.category')
            ->get()
            ->pluck('serviceOffering.productType.category.id')
            ->unique()
            ->filter();
            
        return ProductCategory::whereIn('id', $categoryIds)->get();
    }
    
    /**
     * Get the count of items for a specific category in the order
     */
    private function getCategoryItemCount(Order $order, int $categoryId): int
    {
        return $order->items()
            ->with('serviceOffering.productType')
            ->get()
            ->filter(function ($item) use ($categoryId) {
                return $item->serviceOffering->productType->product_category_id === $categoryId;
            })
            ->count();
    }
    
    /**
     * Generate sequence for a specific category
     */
    private function generateSequenceForCategory(ProductCategory $category, int $itemCount): string
    {
        // Increment the sequence for this category
        $category->incrementSequence();
        
        // Get the next sequence number
        $sequenceNumber = $category->getNextSequence();
        
        // Format: Z001-2 (sequence number - item count)
        return $sequenceNumber . '-' . $itemCount;
    }
    
    /**
     * Get the next sequence for a category without incrementing
     */
    public function getNextSequenceForCategory(ProductCategory $category): string
    {
        if (!$category->sequence_enabled || !$category->sequence_prefix) {
            return '';
        }
        
        return $category->getNextSequence();
    }
    
    /**
     * Reset sequence for a category (for testing or manual reset)
     */
    public function resetSequenceForCategory(ProductCategory $category, int $newSequence = 0): void
    {
        $category->current_sequence = $newSequence;
        $category->save();
    }
} 
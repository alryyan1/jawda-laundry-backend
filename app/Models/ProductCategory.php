<?php // app/Models/ProductCategory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'description', 
        'image_url',
        'sequence_prefix',
        'sequence_enabled',
        'current_sequence'
    ];

    protected $casts = [
        'sequence_enabled' => 'boolean',
        'current_sequence' => 'integer'
    ];

    public function productTypes()
    {
        return $this->hasMany(ProductType::class);
    }

    /**
     * Get the next sequence number for this category
     */
    public function getNextSequence(): string
    {
        if (!$this->sequence_enabled || !$this->sequence_prefix) {
            return '';
        }

        $nextNumber = ($this->current_sequence ?? 0) + 1;
        return $this->sequence_prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Increment the sequence number for this category
     */
    public function incrementSequence(): void
    {
        if ($this->sequence_enabled) {
            $this->current_sequence = ($this->current_sequence ?? 0) + 1;
            $this->save();
        }
    }
}

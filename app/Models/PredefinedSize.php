<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PredefinedSize extends Model {
    use HasFactory;
    protected $fillable = ['product_type_id', 'name', 'length_meters', 'width_meters'];
    public function productType() { return $this->belongsTo(ProductType::class); }
}
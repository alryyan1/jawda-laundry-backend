<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model {
    use HasFactory;
    protected $fillable = ['status', 'message_template', 'is_active', 'attach_invoice'];
    protected $casts = ['is_active' => 'boolean', 'attach_invoice' => 'boolean'];
}
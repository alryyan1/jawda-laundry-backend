<?php // app/Models/ServiceAction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceAction extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'base_duration_minutes'];
    public function serviceOfferings()
    {
        return $this->hasMany(ServiceOffering::class);
    }
}

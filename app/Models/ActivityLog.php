<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model {
    use HasFactory;
    protected $fillable = ['description', 'user_id', 'properties', 'loggable_id', 'loggable_type'];
    protected $casts = ['properties' => 'collection']; // Automatically casts JSON to a helpful Laravel Collection

    public function loggable(): MorphTo {
        return $this->morphTo();
    }
    public function user() {
        return $this->belongsTo(User::class)->withDefault(['name' => 'System']); // Show 'System' if user was deleted
    }
}
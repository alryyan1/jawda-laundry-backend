<?php
namespace App\Traits;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait LogsActivity {
    /**
     * The "boot" method of the trait.
     * This can be used to automatically log events like 'created', 'updated', 'deleted'.
     * This is an advanced (but very powerful) optional step.
     */
    // protected static function bootLogsActivity() {
    //     static::created(function ($model) {
    //         $modelName = class_basename($model);
    //         $model->logActivity("{$modelName} was created.");
    //     });
    //     static::deleted(function ($model) {
    //         $modelName = class_basename($model);
    //         $model->logActivity("{$modelName} was deleted.");
    //     });
    // }

    public function activities(): MorphMany {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest();
    }

    public function logActivity(string $description, array $properties = null): void {
        $this->activities()->create([
            'description' => $description,
            'user_id' => Auth::id(), // Automatically logs the currently authenticated user
            'properties' => $properties,
        ]);
    }
}
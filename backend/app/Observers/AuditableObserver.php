<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditableObserver
{
    public function created(Model $model): void
    {
        $this->logEvent('created', $model, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $dirty = $model->getDirty();
        if (empty($dirty)) return;

        $original = array_intersect_key($model->getOriginal(), $dirty);
        $this->logEvent('updated', $model, $original, $dirty);
    }

    public function deleted(Model $model): void
    {
        $this->logEvent('deleted', $model, $model->getOriginal(), null);
    }

    private function logEvent(string $action, Model $model, ?array $old, ?array $new): void
    {
        // Skip if no authenticated user (e.g., during migrations/seeds)
        if (!auth()->check()) return;

        $modelName = class_basename($model);
        $description = match($action) {
            'created' => "{$modelName} #{$model->id} created",
            'updated' => "{$modelName} #{$model->id} updated: " . implode(', ', array_keys($new ?? [])),
            'deleted' => "{$modelName} #{$model->id} deleted",
            default   => "{$modelName} #{$model->id} {$action}",
        };

        AuditLog::log($action, $description, get_class($model), $model->id, $old, $new);
    }
}

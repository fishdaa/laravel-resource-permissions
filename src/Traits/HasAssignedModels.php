<?php

namespace Fishdaa\LaravelResourcePermissions\Traits;

use Fishdaa\LaravelResourcePermissions\Models\ModelHasResourceAndPermission;
use Illuminate\Support\Collection;

trait HasAssignedModels
{
    /**
     * Get all models assigned to this resource (with permissions or roles).
     * Optionally filter to only specific models.
     *
     * @param  array|Collection|null  $models  Optional array of model instances to filter
     * @return Collection
     */
    public function getAssignedModels($models = null): Collection
    {
        return ModelHasResourceAndPermission::getModelsForResource($this, $models);
    }

    /**
     * Check if a specific model is assigned to this resource.
     *
     * @param  mixed  $model
     * @return bool
     */
    public function hasModelAssigned($model): bool
    {
        return ModelHasResourceAndPermission::isModelAssignedToResource($model, $this);
    }

    /**
     * Check if all specified models are assigned to this resource.
     *
     * @param  array|Collection  $models
     * @return bool
     */
    public function hasAllModelsAssigned($models): bool
    {
        $modelPairs = collect($models)->map(function ($model) {
            /** @var \Illuminate\Database\Eloquent\Model $model */
            return ['model_type' => get_class($model), 'model_id' => $model->id];
        });

        $assignedPairs = ModelHasResourceAndPermission::forResource($this)
            ->select('model_type', 'model_id')
            ->distinct()
            ->get()
            ->map(function ($record) {
                return ['model_type' => $record->model_type, 'model_id' => $record->model_id];
            });

        foreach ($modelPairs as $pair) {
            $found = $assignedPairs->contains(function ($assignedPair) use ($pair) {
                return $assignedPair['model_type'] === $pair['model_type'] 
                    && $assignedPair['model_id'] === $pair['model_id'];
            });
            
            if (!$found) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any of the specified models are assigned to this resource.
     *
     * @param  array|Collection  $models
     * @return bool
     */
    public function hasAnyModelAssigned($models): bool
    {
        $modelPairs = collect($models)->map(function ($model) {
            /** @var \Illuminate\Database\Eloquent\Model $model */
            return ['model_type' => get_class($model), 'model_id' => $model->id];
        });

        return ModelHasResourceAndPermission::forResource($this)
            ->where(function ($query) use ($modelPairs) {
                foreach ($modelPairs as $pair) {
                    $query->orWhere(function ($q) use ($pair) {
                        $q->where('model_type', $pair['model_type'])
                          ->where('model_id', $pair['model_id']);
                    });
                }
            })
            ->exists();
    }

}


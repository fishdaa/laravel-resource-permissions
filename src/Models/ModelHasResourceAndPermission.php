<?php

namespace Fishdaa\LaravelResourcePermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModelHasResourceAndPermission extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('resource-permissions.table_name', 'model_has_resource_and_permissions');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'resource_type',
        'resource_id',
        'permission_id',
        'role_id',
        'created_by',
    ];

    /**
     * Get the model that owns the resource permission.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the resource that the permission is for.
     */
    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the permission.
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Get the role.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user who created this permission assignment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'created_by');
    }

    /**
     * Scope a query to only include permissions for a specific model.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModel($query, $model)
    {
        return $query->where('model_type', get_class($model))
            ->where('model_id', $model->id);
    }

    /**
     * Scope a query to only include permissions for a specific resource.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $resource
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForResource($query, $resource)
    {
        return $query->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id);
    }

    /**
     * Scope a query to only include permissions for a specific permission.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|Permission  $permission
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPermission($query, $permission)
    {
        if ($permission instanceof Permission) {
            return $query->where('permission_id', $permission->id);
        }

        $permissionModel = Permission::where('name', $permission)->first();

        return $query->where('permission_id', $permissionModel ? $permissionModel->id : null);
    }

    /**
     * Scope a query to only include permissions for a specific role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|Role  $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRole($query, $role)
    {
        if ($role instanceof Role) {
            return $query->where('role_id', $role->id);
        }

        $roleModel = Role::where('name', $role)->first();

        return $query->where('role_id', $roleModel ? $roleModel->id : null);
    }

    /**
     * Scope a query to filter by permission name (joins with permissions table).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $permissionName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePermissionName($query, $permissionName)
    {
        $tableName = $this->getTable();

        return $query->join('permissions', "{$tableName}.permission_id", '=', 'permissions.id')
            ->where('permissions.name', $permissionName);
    }

    /**
     * Scope a query to filter by role name (joins with roles table).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $roleName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereRoleName($query, $roleName)
    {
        $tableName = $this->getTable();

        return $query->join('roles', "{$tableName}.role_id", '=', 'roles.id')
            ->where('roles.name', $roleName);
    }

    /**
     * Get a query builder scoped to a specific model and resource.
     *
     * @param  mixed  $model
     * @param  mixed  $resource
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function forModelAndResource($model, $resource)
    {
        return static::query()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id);
    }

    /**
     * Check if a model has a specific permission for a resource.
     *
     * @param  mixed  $model
     * @param  mixed  $resource
     * @param  string|\Spatie\Permission\Models\Permission  $permission
     * @return bool
     */
    public static function hasResourcePermission($model, $resource, $permission)
    {
        $tableName = (new static)->getTable();

        $query = static::query()
            ->where("{$tableName}.model_type", get_class($model))
            ->where("{$tableName}.model_id", $model->id)
            ->where("{$tableName}.resource_type", get_class($resource))
            ->where("{$tableName}.resource_id", $resource->id)
            ->join('permissions', "{$tableName}.permission_id", '=', 'permissions.id');

        if ($permission instanceof Permission) {
            $query->where('permissions.id', $permission->id);
        } else {
            $query->where('permissions.name', $permission);
        }

        return $query->exists();
    }

    /**
     * Check if a model has a specific role for a resource.
     *
     * @param  mixed  $model
     * @param  mixed  $resource
     * @param  string|\Spatie\Permission\Models\Role  $role
     * @return bool
     */
    public static function hasResourceRole($model, $resource, $role)
    {
        $tableName = (new static)->getTable();

        $query = static::query()
            ->where("{$tableName}.model_type", get_class($model))
            ->where("{$tableName}.model_id", $model->id)
            ->where("{$tableName}.resource_type", get_class($resource))
            ->where("{$tableName}.resource_id", $resource->id)
            ->join('roles', "{$tableName}.role_id", '=', 'roles.id');

        if ($role instanceof Role) {
            $query->where('roles.id', $role->id);
        } else {
            $query->where('roles.name', $role);
        }

        return $query->exists();
    }

    /**
     * Check if a model is assigned to a resource (has any permission or role).
     *
     * @param  mixed  $model  Model instance
     * @param  mixed  $resource
     * @return bool
     */
    public static function isModelAssignedToResource($model, $resource): bool
    {
        return static::query()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id)
            ->exists();
    }

    /**
     * Get all models assigned to a resource (with permissions or roles).
     * Optionally filter to only specific models.
     *
     * @param  mixed  $resource
     * @param  array|\Illuminate\Support\Collection|null  $models  Optional array of model instances to filter
     * @return \Illuminate\Support\Collection
     */
    public static function getModelsForResource($resource, $models = null)
    {
        $query = static::forResource($resource)->distinct();
        
        if ($models !== null) {
            $modelPairs = collect($models)->map(function ($model) {
                /** @var \Illuminate\Database\Eloquent\Model $model */
                return ['model_type' => get_class($model), 'model_id' => $model->id];
            });
            
            $query->where(function ($q) use ($modelPairs) {
                foreach ($modelPairs as $pair) {
                    $q->orWhere(function ($subQ) use ($pair) {
                        $subQ->where('model_type', $pair['model_type'])
                             ->where('model_id', $pair['model_id']);
                    });
                }
            });
        }
        
        $modelPairs = $query->select('model_type', 'model_id')->get();
        
        $results = collect();
        foreach ($modelPairs->groupBy('model_type') as $modelType => $pairs) {
            $modelIds = $pairs->pluck('model_id')->toArray();
            $models = $modelType::whereIn('id', $modelIds)->get();
            $results = $results->merge($models);
        }

        return $results;
    }

}

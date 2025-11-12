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
        'user_id',
        'resource_type',
        'resource_id',
        'permission_id',
        'role_id',
        'created_by',
    ];

    /**
     * Get the user that owns the resource permission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class));
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

        return $query->where('permission_id', $permissionModel?->id);
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

        return $query->where('role_id', $roleModel?->id);
    }
}

// Backward compatibility aliases
class_alias(
    ModelHasResourceAndPermission::class,
    \Fishdaa\LaravelResourcePermissions\Models\UserHasResourceAndPermission::class
);

class_alias(
    ModelHasResourceAndPermission::class,
    \Fishdaa\LaravelResourcePermissions\Models\UserResourcePermission::class
);


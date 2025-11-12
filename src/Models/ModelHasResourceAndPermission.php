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
     * Get a query builder scoped to a specific user and resource.
     *
     * @param  mixed  $user
     * @param  mixed  $resource
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function forUserAndResource($user, $resource)
    {
        return static::query()
            ->where('user_id', $user->id)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id);
    }

    /**
     * Check if a user has a specific permission for a resource.
     *
     * @param  mixed  $user
     * @param  mixed  $resource
     * @param  string|\Spatie\Permission\Models\Permission  $permission
     * @return bool
     */
    public static function hasResourcePermission($user, $resource, $permission)
    {
        $tableName = (new static)->getTable();

        $query = static::query()
            ->where("{$tableName}.user_id", $user->id)
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
     * Check if a user has a specific role for a resource.
     *
     * @param  mixed  $user
     * @param  mixed  $resource
     * @param  string|\Spatie\Permission\Models\Role  $role
     * @return bool
     */
    public static function hasResourceRole($user, $resource, $role)
    {
        $tableName = (new static)->getTable();

        $query = static::query()
            ->where("{$tableName}.user_id", $user->id)
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
     * Check if a user is assigned to a resource (has any permission or role).
     *
     * @param  mixed  $user  User model instance or user ID
     * @param  mixed  $resource
     * @return bool
     */
    public static function isUserAssignedToResource($user, $resource): bool
    {
        $userId = is_object($user) ? $user->id : $user;
        
        return static::query()
            ->where('user_id', $userId)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id)
            ->exists();
    }

    /**
     * Get all users assigned to a resource (with permissions or roles).
     * Optionally filter to only specific users.
     *
     * @param  mixed  $resource
     * @param  array|\Illuminate\Support\Collection|null  $users  Optional array of user IDs or User model instances to filter
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUsersForResource($resource, $users = null)
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        
        $query = static::forResource($resource)->distinct();
        
        if ($users !== null) {
            $userIds = collect($users)->map(function ($user) {
                return is_object($user) ? $user->id : $user;
            })->toArray();
            
            $query->whereIn('user_id', $userIds);
        }
        
        $userIds = $query->pluck('user_id');

        return $userModel::whereIn('id', $userIds)->get();
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


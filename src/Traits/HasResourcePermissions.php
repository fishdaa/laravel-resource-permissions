<?php

namespace Fishdaa\LaravelResourcePermissions\Traits;

use Fishdaa\LaravelResourcePermissions\Models\ModelHasResourceAndPermission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait HasResourcePermissions
{
    /**
     * Determine if the model has the given ability.
     * Extends Spatie's can() method to also check resource-specific permissions.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        // If arguments are provided and the first argument is a model instance (resource),
        // check both global and resource-specific permissions
        if (!empty($arguments)) {
            $resource = is_array($arguments) ? ($arguments[0] ?? null) : $arguments;
            
            // Check if resource is a model instance
            if ($resource instanceof \Illuminate\Database\Eloquent\Model) {
                // Check global permission first (Spatie's default behavior via hasPermissionTo)
                $hasGlobalPermission = false;
                if (method_exists($this, 'hasPermissionTo')) {
                    $hasGlobalPermission = $this->hasPermissionTo($ability);
                } else {
                    // Fallback to Gate if hasPermissionTo is not available
                    $hasGlobalPermission = Gate::forUser($this)->allows($ability, $arguments);
                }
                
                // Check resource-specific permission
                $hasResourcePermission = $this->hasPermissionForResource($ability, $resource);
                
                // User can perform action if they have global OR resource-specific permission
                return $hasGlobalPermission || $hasResourcePermission;
            }
        }
        
        // For non-resource checks, check if it's a Spatie permission first
        // If hasPermissionTo exists (from Spatie's HasRoles trait), use it
        if (method_exists($this, 'hasPermissionTo')) {
            // Check if the ability matches a permission name
            $permission = Permission::where('name', $ability)->first();
            if ($permission) {
                return $this->hasPermissionTo($ability);
            }
        }
        
        // Fall back to Gate for policy-based authorization or other abilities
        return Gate::forUser($this)->allows($ability, $arguments);
    }

    /**
     * Check if this model has a specific permission for a resource.
     *
     * @param  string|PermissionContract  $permission
     * @param  mixed  $resource
     * @return bool
     */
    public function hasPermissionForResource($permission, $resource): bool
    {
        $permission = $this->getStoredResourcePermission($permission);

        if (! $permission) {
            return false;
        }

        if ($this->getResourcePermissions($resource)
            ->contains('id', $permission->id)) {
            return true;
        }

        // Check if any role assigned for this resource has the permission
        // We use fresh roles to ensure we get the latest permissions if they changed
        $roles = $this->getRolesForResource($resource);
        
        foreach ($roles as $role) {
            if ($role->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this model has any of the given permissions for a resource.
     *
     * @param  array|string|SupportCollection  $permissions
     * @param  mixed  $resource
     * @return bool
     */
    public function hasAnyPermissionForResource($permissions, $resource): bool
    {
        $permissions = $this->convertToResourcePermissionModels($permissions);

        foreach ($permissions as $permission) {
            if ($this->hasPermissionForResource($permission, $resource)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this model has all of the given permissions for a resource.
     *
     * @param  array|string|SupportCollection  $permissions
     * @param  mixed  $resource
     * @return bool
     */
    public function hasAllPermissionsForResource($permissions, $resource): bool
    {
        if ($permissions instanceof SupportCollection) {
            $permissions = $permissions->toArray();
        }

        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        $permissionModels = collect($permissions)->map(function ($permission) {
            return $this->getStoredResourcePermission($permission);
        });

        // If any permission doesn't exist, return false
        if ($permissionModels->contains(null)) {
            return false;
        }

        foreach ($permissionModels as $permission) {
            if (! $this->hasPermissionForResource($permission, $resource)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Give permission to this model for a specific resource.
     *
     * @param  string|PermissionContract  $permission
     * @param  mixed  $resource
     * @param  int|null  $createdBy
     * @return $this
     */
    public function givePermissionToResource($permission, $resource, $createdBy = null): self
    {
        $permission = $this->getStoredResourcePermission($permission);

        if (! $permission) {
            return $this;
        }

        ModelHasResourceAndPermission::firstOrCreate([
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'resource_type' => get_class($resource),
            'resource_id' => $resource->id,
            'permission_id' => $permission->id,
        ], [
            'created_by' => $createdBy ?? auth()->id(),
        ]);

        return $this;
    }

    /**
     * Revoke permission from this model for a specific resource.
     *
     * @param  string|PermissionContract  $permission
     * @param  mixed  $resource
     * @return $this
     */
    public function revokePermissionFromResource($permission, $resource): self
    {
        $permission = $this->getStoredResourcePermission($permission);

        if (! $permission) {
            return $this;
        }

        ModelHasResourceAndPermission::where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id)
            ->where('permission_id', $permission->id)
            ->delete();

        return $this;
    }

    /**
     * Sync permissions for this model for a specific resource.
     *
     * @param  array|string|SupportCollection  $permissions
     * @param  mixed  $resource
     * @param  int|null  $createdBy
     * @return $this
     */
    public function syncPermissionsForResource($permissions, $resource, $createdBy = null): self
    {
        $permissions = $this->convertToResourcePermissionModels($permissions);
        $permissionIds = $permissions->pluck('id')->toArray();

        // Get existing permission IDs for this resource
        $existingPermissionIds = ModelHasResourceAndPermission::where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id)
            ->whereNotNull('permission_id')
            ->pluck('permission_id')
            ->toArray();

        // Remove permissions that are not in the new list
        $permissionsToRemove = array_diff($existingPermissionIds, $permissionIds);
        if (! empty($permissionsToRemove)) {
            ModelHasResourceAndPermission::where('model_type', get_class($this))
                ->where('model_id', $this->id)
                ->where('resource_type', get_class($resource))
                ->where('resource_id', $resource->id)
                ->whereIn('permission_id', $permissionsToRemove)
                ->delete();
        }

        // Add new permissions
        foreach ($permissionIds as $permissionId) {
            if (! in_array($permissionId, $existingPermissionIds)) {
                ModelHasResourceAndPermission::firstOrCreate([
                    'model_type' => get_class($this),
                    'model_id' => $this->id,
                    'resource_type' => get_class($resource),
                    'resource_id' => $resource->id,
                    'permission_id' => $permissionId,
                ], [
                    'created_by' => $createdBy ?? auth()->id(),
                ]);
            }
        }

        return $this;
    }

    /**
     * Get all permissions for a specific resource.
     *
     * @param  mixed  $resource
     * @return Collection
     */
    public function getPermissionsForResource($resource): Collection
    {
        return $this->getResourcePermissions($resource);
    }

    /**
     * Assign a role to this model for a specific resource.
     *
     * @param  string|RoleContract  $role
     * @param  mixed  $resource
     * @param  int|null  $createdBy
     * @return $this
     */
    public function assignRoleToResource($role, $resource, $createdBy = null): self
    {
        $role = $this->getStoredResourceRole($role);

        if (! $role) {
            return $this;
        }

        ModelHasResourceAndPermission::firstOrCreate([
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'resource_type' => get_class($resource),
            'resource_id' => $resource->id,
            'role_id' => $role->id,
        ], [
            'created_by' => $createdBy ?? auth()->id(),
        ]);

        return $this;
    }

    /**
     * Remove a role from this model for a specific resource.
     *
     * @param  string|RoleContract  $role
     * @param  mixed  $resource
     * @return $this
     */
    public function removeRoleFromResource($role, $resource): self
    {
        $role = $this->getStoredResourceRole($role);

        if (! $role) {
            return $this;
        }

        ModelHasResourceAndPermission::where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id)
            ->where('role_id', $role->id)
            ->delete();

        return $this;
    }

    /**
     * Sync roles for this model for a specific resource.
     *
     * @param  array|string|SupportCollection  $roles
     * @param  mixed  $resource
     * @param  int|null  $createdBy
     * @return $this
     */
    public function syncRolesForResource($roles, $resource, $createdBy = null): self
    {
        $roles = $this->convertToResourceRoleModels($roles);
        $roleIds = $roles->pluck('id')->toArray();

        // Get existing role IDs for this resource
        $existingRoleIds = ModelHasResourceAndPermission::where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id)
            ->whereNotNull('role_id')
            ->pluck('role_id')
            ->toArray();

        // Remove roles that are not in the new list
        $rolesToRemove = array_diff($existingRoleIds, $roleIds);
        if (! empty($rolesToRemove)) {
            ModelHasResourceAndPermission::where('model_type', get_class($this))
                ->where('model_id', $this->id)
                ->where('resource_type', get_class($resource))
                ->where('resource_id', $resource->id)
                ->whereIn('role_id', $rolesToRemove)
                ->delete();
        }

        // Add new roles
        foreach ($roleIds as $roleId) {
            if (! in_array($roleId, $existingRoleIds)) {
                ModelHasResourceAndPermission::firstOrCreate([
                    'model_type' => get_class($this),
                    'model_id' => $this->id,
                    'resource_type' => get_class($resource),
                    'resource_id' => $resource->id,
                    'role_id' => $roleId,
                ], [
                    'created_by' => $createdBy ?? auth()->id(),
                ]);
            }
        }

        return $this;
    }

    /**
     * Check if this model has a specific role for a resource.
     *
     * @param  string|RoleContract  $role
     * @param  mixed  $resource
     * @return bool
     */
    public function hasRoleForResource($role, $resource): bool
    {
        $role = $this->getStoredResourceRole($role);

        if (! $role) {
            return false;
        }

        return ModelHasResourceAndPermission::where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id)
            ->where('role_id', $role->id)
            ->exists();
    }

    /**
     * Get all roles for a specific resource.
     *
     * @param  mixed  $resource
     * @return Collection
     */
    public function getRolesForResource($resource): Collection
    {
        $roleIds = ModelHasResourceAndPermission::where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id)
            ->whereNotNull('role_id')
            ->pluck('role_id');

        return Role::whereIn('id', $roleIds)->get();
    }

    /**
     * Get resource permissions for a specific resource.
     *
     * @param  mixed  $resource
     * @return Collection
     */
    protected function getResourcePermissions($resource): Collection
    {
        $permissionIds = ModelHasResourceAndPermission::where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->id)
            ->whereNotNull('permission_id')
            ->pluck('permission_id');

        return Permission::whereIn('id', $permissionIds)->get();
    }

    /**
     * Get a stored permission instance.
     *
     * @param  string|PermissionContract  $permission
     * @return PermissionContract|null
     */
    protected function getStoredResourcePermission($permission): ?PermissionContract
    {
        if ($permission instanceof PermissionContract) {
            return $permission;
        }

        return Permission::where('name', $permission)->first();
    }

    /**
     * Get a stored role instance.
     *
     * @param  string|RoleContract  $role
     * @return RoleContract|null
     */
    protected function getStoredResourceRole($role): ?RoleContract
    {
        if ($role instanceof RoleContract) {
            return $role;
        }

        return Role::where('name', $role)->first();
    }

    /**
     * Convert permissions to permission models.
     *
     * @param  array|string|SupportCollection  $permissions
     * @return SupportCollection
     */
    protected function convertToResourcePermissionModels($permissions): SupportCollection
    {
        if ($permissions instanceof SupportCollection) {
            $permissions = $permissions->toArray();
        }

        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        return collect($permissions)->map(function ($permission) {
            return $this->getStoredResourcePermission($permission);
        })->filter();
    }

    /**
     * Convert roles to role models.
     *
     * @param  array|string|SupportCollection  $roles
     * @return SupportCollection
     */
    protected function convertToResourceRoleModels($roles): SupportCollection
    {
        if ($roles instanceof SupportCollection) {
            $roles = $roles->toArray();
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        return collect($roles)->map(function ($role) {
            return $this->getStoredResourceRole($role);
        })->filter();
    }
}


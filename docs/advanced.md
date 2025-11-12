# Advanced Usage

This document covers advanced patterns and best practices for using Laravel Resource Permissions.

## Custom Scopes

You can create custom scopes on the `ModelHasResourceAndPermission` model:

```php
use Fishdaa\LaravelResourcePermissions\Models\ModelHasResourceAndPermission;

// In a service provider or helper
ModelHasResourceAndPermission::macro('forUser', function ($user) {
    return $this->where('user_id', $user->id);
});

// Usage
$permissions = ModelHasResourceAndPermission::forUser($user)
    ->forResource($article)
    ->get();
```

## Query Optimization

When querying multiple resources, use eager loading:

```php
// Get all permissions for multiple articlees
$articlees = Article::with(['resourcePermissions' => function ($query) {
    $query->where('user_id', auth()->id());
}])->get();

// Check permissions efficiently
foreach ($articlees as $article) {
    $hasPermission = $article->resourcePermissions->isNotEmpty();
}
```

## Caching Resource Permissions

Cache resource permissions to improve performance:

```php
use Illuminate\Support\Facades\Cache;

public function hasPermissionForResourceCached($permission, $resource): bool
{
    $cacheKey = "user_{$this->id}_permission_{$permission}_resource_{get_class($resource)}_{$resource->id}";
    
    return Cache::remember($cacheKey, 3600, function () use ($permission, $resource) {
        return $this->hasPermissionForResource($permission, $resource);
    });
}
```

Remember to clear cache when permissions change:

```php
public function givePermissionToResource($permission, $resource, $createdBy = null): self
{
    parent::givePermissionToResource($permission, $resource, $createdBy);
    
    // Clear cache
    Cache::forget("user_{$this->id}_permission_{$permission}_resource_{get_class($resource)}_{$resource->id}");
    
    return $this;
}
```

## Event Listeners

Listen to permission changes:

```php
use Fishdaa\LaravelResourcePermissions\Models\ModelHasResourceAndPermission;
use Illuminate\Support\Facades\Event;

Event::listen('eloquent.created: ' . ModelHasResourceAndPermission::class, function ($model) {
    // Permission was assigned
    Log::info("Permission assigned: User {$model->user_id} to resource {$model->resource_type}:{$model->resource_id}");
});

Event::listen('eloquent.deleted: ' . ModelHasResourceAndPermission::class, function ($model) {
    // Permission was revoked
    Log::info("Permission revoked: User {$model->user_id} from resource {$model->resource_type}:{$model->resource_id}");
});
```

## Custom Permission Logic

Extend the trait to add custom permission logic:

```php
use Fishdaa\LaravelResourcePermissions\Traits\HasResourcePermissions;

trait CustomHasResourcePermissions
{
    use HasResourcePermissions;
    
    public function hasPermissionForResource($permission, $resource): bool
    {
        // Check if user is resource owner
        if (method_exists($resource, 'user_id') && $resource->user_id === $this->id) {
            return true; // Owners always have permission
        }
        
        // Fall back to standard check
        return parent::hasPermissionForResource($permission, $resource);
    }
}
```

## Bulk Operations

Perform bulk operations efficiently:

```php
// Assign permission to multiple users for one resource
$users = User::whereIn('id', [1, 2, 3])->get();
$article = Article::find(1);

foreach ($users as $user) {
    $user->givePermissionToResource('view-article', $article);
}

// Or use DB query for better performance
DB::table(config('resource-permissions.table_name', 'model_has_resource_and_permissions'))->insert(
    collect([1, 2, 3])->map(function ($userId) use ($article) {
        return [
            'user_id' => $userId,
            'resource_type' => get_class($article),
            'resource_id' => $article->id,
            'permission_id' => Permission::where('name', 'view-article')->first()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    })->toArray()
);
```

## Resource Permission Inheritance

Implement permission inheritance (e.g., project permissions inherit from organization):

```php
public function hasPermissionForResource($permission, $resource): bool
{
    // Check direct permission
    if (parent::hasPermissionForResource($permission, $resource)) {
        return true;
    }
    
    // Check parent resource (if exists)
    if (method_exists($resource, 'parent') && $resource->parent) {
        return $this->hasPermissionForResource($permission, $resource->parent);
    }
    
    return false;
}
```

## Audit Trail

Track who assigned permissions and when:

```php
// The created_by field is automatically tracked
        $permission = ModelHasResourceAndPermission::where('user_id', $user->id)
    ->forResource($article)
    ->first();

if ($permission) {
    $assignedBy = $permission->createdBy; // User who assigned
    $assignedAt = $permission->created_at; // When assigned
}
```

## Testing Helpers

Create test helpers for easier testing:

```php
// In tests/TestCase.php or a trait
trait HasResourcePermissionsTestHelpers
{
    protected function assertUserHasPermissionForResource($user, $permission, $resource)
    {
        $this->assertTrue(
            $user->hasPermissionForResource($permission, $resource),
            "User {$user->id} does not have permission '{$permission}' for resource {$resource->id}"
        );
    }
    
    protected function assertUserDoesNotHavePermissionForResource($user, $permission, $resource)
    {
        $this->assertFalse(
            $user->hasPermissionForResource($permission, $resource),
            "User {$user->id} has permission '{$permission}' for resource {$resource->id} but should not"
        );
    }
}
```

## Performance Considerations

1. **Index Usage**: The migration includes indexes on commonly queried columns. Ensure your queries use these indexes.

2. **Eager Loading**: When checking permissions for multiple resources, eager load the relationships.

3. **Caching**: Cache permission checks for frequently accessed resources.

4. **Batch Operations**: Use bulk inserts for assigning permissions to many users.

5. **Query Optimization**: Use `select()` to limit columns when you don't need all data:

```php
        $permissionIds = ModelHasResourceAndPermission::where('user_id', $user->id)
    ->forResource($article)
    ->select('permission_id')
    ->pluck('permission_id');
```

## Best Practices

1. **Consistent Naming**: Use consistent permission names across your application.

2. **Document Permissions**: Document which permissions are used for which resources.

3. **Regular Audits**: Periodically audit resource permissions to ensure they're still valid.

4. **Use Roles When Appropriate**: Use roles for common permission combinations rather than assigning individual permissions.

5. **Test Permission Logic**: Write tests for your permission checking logic.

6. **Handle Edge Cases**: Consider what happens when resources are deleted, users are deleted, etc.


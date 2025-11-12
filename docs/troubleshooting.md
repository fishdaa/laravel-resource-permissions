# Troubleshooting

Common issues and solutions when using Laravel Resource Permissions.

## Table of Contents

- [Migration Issues](#migration-issues)
- [Permission Not Found](#permission-not-found)
- [Relationship Errors](#relationship-errors)
- [Performance Issues](#performance-issues)
- [Common Mistakes](#common-mistakes)

## Migration Issues

### Error: Table 'permissions' doesn't exist

**Problem:** The migration fails because Spatie's permissions table doesn't exist.

**Solution:** Ensure Spatie Laravel Permission is installed and migrated first:

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Then install this package:

```bash
composer require fishdaa/laravel-resource-permissions
php artisan vendor:publish --tag=resource-permissions-migrations
php artisan migrate
```

### Error: Foreign key constraint fails

**Problem:** Foreign key constraints fail when creating the table.

**Solution:** Ensure:
1. Spatie's migrations have been run
2. The `users` table exists
3. The `permissions` and `roles` tables exist

Check table existence:

```bash
php artisan tinker
>>> Schema::hasTable('permissions')
>>> Schema::hasTable('roles')
>>> Schema::hasTable('users')
```

## Permission Not Found

### Permission check returns false even though permission was assigned

**Problem:** `hasPermissionForResource()` returns false even after assigning permission.

**Possible Causes:**

1. **Permission doesn't exist in Spatie's permissions table**
   ```php
   // Check if permission exists
   $permission = Permission::where('name', 'edit-article')->first();
   if (!$permission) {
        Permission::create(['name' => 'edit-article']);
    }
   ```

2. **Wrong resource instance**
   ```php
   // Wrong - using ID instead of model
   $user->hasPermissionForResource('edit-article', $articleId);
   
   // Correct - using model instance
   $user->hasPermissionForResource('edit-article', $article);
   ```

3. **Case sensitivity**
   ```php
   // Ensure permission names match exactly
   Permission::create(['name' => 'edit-article']);
   $user->hasPermissionForResource('Edit-Article', $article); // Wrong case
   $user->hasPermissionForResource('edit-article', $article); // Correct
   ```

4. **Cache issues**
   ```php
   // Clear cache if using caching
   Cache::flush();
   ```

### Permission assigned but not appearing in database

**Problem:** `givePermissionToResource()` runs without error but no record is created.

**Solution:** Check:
1. User ID is valid
2. Resource has an ID (is saved)
3. Permission exists
4. No unique constraint violation (check if permission already exists)

```php
// Debug
$user = User::find(1);
$article = Article::find(1);
$permission = Permission::where('name', 'edit-article')->first();

if (!$permission) {
    dd('Permission does not exist');
}

if (!$article->id) {
    dd('Article is not saved');
}

// Check if already exists
$exists = UserHasResourceAndPermission::where('user_id', $user->id)
    ->where('resource_type', get_class($article))
    ->where('resource_id', $article->id)
    ->where('permission_id', $permission->id)
    ->exists();

if ($exists) {
    dd('Permission already assigned');
}
```

## Relationship Errors

### Error: Call to undefined relationship

**Problem:** `$user->resourcePermissions()` or similar relationship doesn't exist.

**Solution:** The trait doesn't add relationships automatically. If you need relationships, add them to your User model:

```php
use Fishdaa\LaravelResourcePermissions\Models\UserHasResourceAndPermission;

public function resourcePermissions()
{
    return $this->hasMany(UserHasResourceAndPermission::class);
}
```

### Error: Class 'App\Models\User' not found

**Problem:** The model can't find the User class.

**Solution:** Update the config to use your User model:

```php
// config/resource-permissions.php
'user_model' => \App\Models\CustomUser::class,
```

Or ensure your User model is in the correct namespace.

## Performance Issues

### Slow permission checks

**Problem:** `hasPermissionForResource()` is slow when checking many resources.

**Solutions:**

1. **Use eager loading**
   ```php
   $articlees = Article::with(['resourcePermissions' => function ($query) use ($user) {
       $query->where('user_id', $user->id);
   }])->get();
   ```

2. **Cache results**
   ```php
   Cache::remember("user_{$user->id}_permissions", 3600, function () use ($user) {
       return $user->resourcePermissions()->pluck('permission_id');
   });
   ```

3. **Use database indexes** (already included in migration)

4. **Batch checks**
   ```php
   // Instead of checking one by one
   $permissionIds = UserHasResourceAndPermission::where('user_id', $user->id)
       ->whereIn('resource_id', $articleIds)
       ->pluck('permission_id');
   ```

## Common Mistakes

### Mistake 1: Using string instead of model for resource

```php
// Wrong
$user->hasPermissionForResource('edit-article', 'article-1');

// Correct
$article = Article::find(1);
$user->hasPermissionForResource('edit-article', $article);
```

### Mistake 2: Not creating permissions first

```php
// Wrong - permission doesn't exist
$user->givePermissionToResource('new-permission', $resource);

// Correct - create permission first
Permission::create(['name' => 'new-permission']);
$user->givePermissionToResource('new-permission', $resource);
```

### Mistake 3: Confusing global and resource permissions

```php
// These are different!
$user->hasPermissionTo('edit-article'); // Global - all articlees
$user->hasPermissionForResource('edit-article', $article); // Resource-specific - this article only
```

### Mistake 4: Not handling null resources

```php
// Wrong - will error if resource is null
$user->hasPermissionForResource('edit-article', $article);

// Correct - check first
if ($article && $user->hasPermissionForResource('edit-article', $article)) {
    // ...
}
```

### Mistake 5: Using wrong method names

```php
// Wrong - these methods don't exist
$user->hasResourcePermission('edit-article', $article);
$user->giveResourcePermission('edit-article', $article);

// Correct
$user->hasPermissionForResource('edit-article', $article);
$user->givePermissionToResource('edit-article', $article);
```

## Getting Help

If you're still experiencing issues:

1. Check the [Usage Documentation](usage.md) for correct method signatures
2. Review the [Examples](examples.md) for working code samples
3. Check Laravel and Spatie logs for detailed error messages
4. Ensure all dependencies are up to date

## Debugging Tips

Enable query logging to see what queries are being executed:

```php
DB::enableQueryLog();

$user->hasPermissionForResource('edit-article', $article);

dd(DB::getQueryLog());
```

Check the actual database records:

```php
$records = UserHasResourceAndPermission::where('user_id', $user->id)
    ->forResource($article)
    ->get();

dd($records);
```


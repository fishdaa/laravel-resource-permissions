# Usage & API Reference

This document provides a complete reference for all methods available in the `HasResourcePermissions` trait.

## Table of Contents

- [Permission Methods](#permission-methods)
- [Role Methods](#role-methods)
- [Helper Methods](#helper-methods)
- [Examples](#examples)

## Permission Methods

### hasPermissionForResource()

Check if the user has a specific permission for a resource.

```php
$user->hasPermissionForResource($permission, $resource): bool
```

**Parameters:**
- `$permission` (string|Permission): Permission name or Permission model instance
- `$resource` (Model): The resource model instance

**Returns:** `bool`

**Example:**
```php
$user = User::find(1);
$article = Article::find(1);

if ($user->hasPermissionForResource('edit-article', $article)) {
    // User can edit this article
}
```

### hasAnyPermissionForResource()

Check if the user has any of the given permissions for a resource.

```php
$user->hasAnyPermissionForResource($permissions, $resource): bool
```

**Parameters:**
- `$permissions` (array|string|Collection): Permission names or Permission model instances
- `$resource` (Model): The resource model instance

**Returns:** `bool`

**Example:**
```php
if ($user->hasAnyPermissionForResource(['edit-article', 'view-article'], $article)) {
    // User has at least one permission
}
```

### hasAllPermissionsForResource()

Check if the user has all of the given permissions for a resource.

```php
$user->hasAllPermissionsForResource($permissions, $resource): bool
```

**Parameters:**
- `$permissions` (array|string|Collection): Permission names or Permission model instances
- `$resource` (Model): The resource model instance

**Returns:** `bool`

**Example:**
```php
if ($user->hasAllPermissionsForResource(['edit-article', 'delete-article'], $article)) {
    // User has both permissions
}
```

### givePermissionToResource()

Give permission to the user for a specific resource.

```php
$user->givePermissionToResource($permission, $resource, $createdBy = null): self
```

**Parameters:**
- `$permission` (string|Permission): Permission name or Permission model instance
- `$resource` (Model): The resource model instance
- `$createdBy` (int|null): Optional user ID who created this assignment

**Returns:** `$this` (for method chaining)

**Example:**
```php
$user->givePermissionToResource('edit-article', $article);
$user->givePermissionToResource('edit-article', $article, auth()->id()); // With creator tracking
```

### revokePermissionFromResource()

Revoke permission from the user for a specific resource.

```php
$user->revokePermissionFromResource($permission, $resource): self
```

**Parameters:**
- `$permission` (string|Permission): Permission name or Permission model instance
- `$resource` (Model): The resource model instance

**Returns:** `$this` (for method chaining)

**Example:**
```php
$user->revokePermissionFromResource('edit-article', $article);
```

### syncPermissionsForResource()

Sync permissions for the user for a specific resource. This will remove permissions not in the list and add new ones.

```php
$user->syncPermissionsForResource($permissions, $resource, $createdBy = null): self
```

**Parameters:**
- `$permissions` (array|string|Collection): Permission names or Permission model instances
- `$resource` (Model): The resource model instance
- `$createdBy` (int|null): Optional user ID who created this assignment

**Returns:** `$this` (for method chaining)

**Example:**
```php
$user->syncPermissionsForResource(['edit-article', 'view-article'], $article);
```

### getPermissionsForResource()

Get all permissions for a specific resource.

```php
$user->getPermissionsForResource($resource): Collection
```

**Parameters:**
- `$resource` (Model): The resource model instance

**Returns:** `Collection` of Permission models

**Example:**
```php
$permissions = $user->getPermissionsForResource($article);
foreach ($permissions as $permission) {
    echo $permission->name;
}
```

## Role Methods

### assignRoleToResource()

Assign a role to the user for a specific resource.

```php
$user->assignRoleToResource($role, $resource, $createdBy = null): self
```

**Parameters:**
- `$role` (string|Role): Role name or Role model instance
- `$resource` (Model): The resource model instance
- `$createdBy` (int|null): Optional user ID who created this assignment

**Returns:** `$this` (for method chaining)

**Example:**
```php
$user->assignRoleToResource('article-manager', $article);
```

### removeRoleFromResource()

Remove a role from the user for a specific resource.

```php
$user->removeRoleFromResource($role, $resource): self
```

**Parameters:**
- `$role` (string|Role): Role name or Role model instance
- `$resource` (Model): The resource model instance

**Returns:** `$this` (for method chaining)

**Example:**
```php
$user->removeRoleFromResource('article-manager', $article);
```

### syncRolesForResource()

Sync roles for the user for a specific resource. This will remove roles not in the list and add new ones.

```php
$user->syncRolesForResource($roles, $resource, $createdBy = null): self
```

**Parameters:**
- `$roles` (array|string|Collection): Role names or Role model instances
- `$resource` (Model): The resource model instance
- `$createdBy` (int|null): Optional user ID who created this assignment

**Returns:** `$this` (for method chaining)

**Example:**
```php
$user->syncRolesForResource(['article-manager', 'article-viewer'], $article);
```

### hasRoleForResource()

Check if the user has a specific role for a resource.

```php
$user->hasRoleForResource($role, $resource): bool
```

**Parameters:**
- `$role` (string|Role): Role name or Role model instance
- `$resource` (Model): The resource model instance

**Returns:** `bool`

**Example:**
```php
if ($user->hasRoleForResource('article-manager', $article)) {
    // User has the article-manager role for this article
}
```

### getRolesForResource()

Get all roles for a specific resource.

```php
$user->getRolesForResource($resource): Collection
```

**Parameters:**
- `$resource` (Model): The resource model instance

**Returns:** `Collection` of Role models

**Example:**
```php
$roles = $user->getRolesForResource($article);
foreach ($roles as $role) {
    echo $role->name;
}
```

## Working with Spatie Permissions

This package works alongside Spatie's existing permission methods. You can use both:

```php
// Spatie's global permissions (still work)
$user->hasPermissionTo('edit-article'); // Global permission

// Resource-specific permissions (new)
$user->hasPermissionForResource('edit-article', $article); // Resource-specific permission
```

Both systems work independently and can be combined:

```php
// Check both global and resource-specific permissions
if ($user->hasPermissionTo('edit-article') || 
    $user->hasPermissionForResource('edit-article', $article)) {
    // User can edit
}
```

## Method Chaining

Most methods return `$this`, allowing method chaining:

```php
$user->givePermissionToResource('edit-article', $article)
     ->assignRoleToResource('article-manager', $article)
     ->syncPermissionsForResource(['view-article', 'edit-article'], $anotherArticle);
```


# Spatie Integration

This document explains how Laravel Resource Permissions integrates with Spatie Laravel Permission.

## Overview

Laravel Resource Permissions extends Spatie's permission system by adding resource-specific permissions and roles. It does not replace Spatie's functionality but adds an additional layer for resource-based access control.

## How It Works

### Separate Tables

- **Spatie Tables**: `permissions`, `roles`, `model_has_permissions`, `model_has_roles`
- **Resource Permissions Table**: `user_has_resource_and_permissions`

The resource permissions table references Spatie's `permissions` and `roles` tables via foreign keys, ensuring consistency.

### Shared Models

The package uses Spatie's `Permission` and `Role` models directly:

```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
```

This means:
- Permissions and roles are managed through Spatie
- No duplication of permission/role definitions
- Full compatibility with Spatie's features

## Using Both Systems Together

### Global Permissions (Spatie)

```php
// Create permissions (Spatie)
Permission::create(['name' => 'edit-article']);

// Assign globally (Spatie)
$user->givePermissionTo('edit-article');

// Check globally (Spatie)
$user->hasPermissionTo('edit-article'); // true for all articlees
```

### Resource Permissions (This Package)

```php
// Use same permission (already created via Spatie)
$article = Article::find(1);

// Assign for specific resource
$user->givePermissionToResource('edit-article', $article);

// Check for specific resource
$user->hasPermissionForResource('edit-article', $article); // true only for this article
```

## Combining Global and Resource Permissions

You can check both in your application logic:

```php
public function canEditArticle(User $user, Article $article): bool
{
    // User can edit if they have global permission OR resource-specific permission
    return $user->hasPermissionTo('edit-article') || 
           $user->hasPermissionForResource('edit-article', $article);
}
```

Or in policies:

```php
public function update(User $user, Article $article): bool
{
    return $user->hasPermissionTo('edit-article') || 
           $user->hasPermissionForResource('edit-article', $article);
}
```

## Roles

The same applies to roles:

```php
// Global role (Spatie)
$user->assignRole('article-manager'); // Manager of all articlees

// Resource-specific role (This Package)
$user->assignRoleToResource('article-manager', $article); // Manager of this specific article
```

## Permission Inheritance

Resource permissions do not inherit from global permissions. They are separate:

- Global permission: `$user->hasPermissionTo('edit-article')` → applies to all articlees
- Resource permission: `$user->hasPermissionForResource('edit-article', $article)` → applies only to that article

If you want inheritance behavior, implement it in your application logic:

```php
public function canEditArticle(User $user, Article $article): bool
{
    // Inherit from global if no resource-specific permission exists
    if ($user->hasPermissionTo('edit-article')) {
        return true;
    }
    
    return $user->hasPermissionForResource('edit-article', $article);
}
```

## Migration Order

When setting up a new application:

1. Install and migrate Spatie Laravel Permission first
2. Then install and migrate Laravel Resource Permissions

The resource permissions migration depends on Spatie's `permissions` and `roles` tables existing.

## Best Practices

1. **Use Global Permissions for System-Wide Access**: Use Spatie's global permissions for permissions that apply everywhere (e.g., "view-dashboard", "manage-users")

2. **Use Resource Permissions for Scoped Access**: Use resource permissions for permissions that vary by resource (e.g., "edit-article" for specific articlees, "view-project" for specific projects)

3. **Consistent Permission Names**: Use the same permission names in both systems when they represent the same action (e.g., "edit-article" in both global and resource contexts)

4. **Check Both in Policies**: When checking permissions, consider both global and resource-specific permissions

5. **Document Your Permission Strategy**: Document which permissions are global vs resource-specific in your application


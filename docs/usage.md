# Usage & API Reference

This document provides a complete reference for all methods available in the `HasResourcePermissions` trait.

## Table of Contents

- [Polymorphic Support](#polymorphic-support)
- [Permission Methods](#permission-methods)
- [Role Methods](#role-methods)
- [Static Helper Methods](#static-helper-methods)
- [Resource Methods](#resource-methods)
- [Working with Spatie Permissions](#working-with-spatie-permissions)
- [Method Chaining](#method-chaining)

## Polymorphic Support

The package supports polymorphic relationships, meaning **any model** can have resource permissions, not just users. This allows you to assign permissions to Users, Teams, Organizations, Roles, or any other Eloquent model.

### Using with Any Model

Any model can use the `HasResourcePermissions` trait:

```php
use Fishdaa\LaravelResourcePermissions\Traits\HasResourcePermissions;

class User extends Model
{
    use HasResourcePermissions;
}

class Team extends Model
{
    use HasResourcePermissions;
}

class Organization extends Model
{
    use HasResourcePermissions;
}
```

All models use the same API:

```php
// Works with any model
$user->givePermissionToResource('edit-article', $article);
$team->givePermissionToResource('edit-article', $article);
$organization->givePermissionToResource('edit-article', $article);
```

The package automatically tracks which model type has the permission using `model_type` and `model_id` columns.

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

## Static Helper Methods

The `ModelHasResourceAndPermission` model provides static helper methods that simplify common permission and role checking patterns without needing to write raw database queries.

### hasResourcePermission()

Check if a model has a specific permission for a resource using a simple static method call. Works with any model type (User, Team, Organization, etc.).

```php
ModelHasResourceAndPermission::hasResourcePermission($model, $resource, $permission): bool
```

**Parameters:**
- `$model` (Model): Any model instance (User, Team, Organization, etc.)
- `$resource` (Model): The resource model instance
- `$permission` (string|Permission): Permission name or Permission model instance

**Returns:** `bool`

**Example:**
```php
$user = User::find(1);
$team = Team::find(1);
$article = Article::find(1);

// Works with any model
if (ModelHasResourceAndPermission::hasResourcePermission($user, $article, 'update-article')) {
    // User has permission
}

if (ModelHasResourceAndPermission::hasResourcePermission($team, $article, 'update-article')) {
    // Team has permission
}

// Instead of writing raw DB queries:
// DB::table('model_has_resource_and_permissions')
//     ->where('model_type', get_class($model))
//     ->where('model_id', $model->id)
//     ->where('resource_type', Article::class)
//     ->where('resource_id', $article->id)
//     ->join('permissions', 'model_has_resource_and_permissions.permission_id', '=', 'permissions.id')
//     ->where('permissions.name', 'update-article')
//     ->exists();
```

### hasResourceRole()

Check if a model has a specific role for a resource using a simple static method call. Works with any model type.

```php
ModelHasResourceAndPermission::hasResourceRole($model, $resource, $role): bool
```

**Parameters:**
- `$model` (Model): Any model instance (User, Team, Organization, etc.)
- `$resource` (Model): The resource model instance
- `$role` (string|Role): Role name or Role model instance

**Returns:** `bool`

**Example:**
```php
if (ModelHasResourceAndPermission::hasResourceRole($user, $article, 'article-editor')) {
    // User has the role
}

if (ModelHasResourceAndPermission::hasResourceRole($team, $article, 'article-editor')) {
    // Team has the role
}
```

### forModelAndResource()

Get a query builder scoped to a specific model and resource. This is useful for building custom queries. Works with any model type.

```php
ModelHasResourceAndPermission::forModelAndResource($model, $resource): Builder
```

**Parameters:**
- `$model` (Model): Any model instance (User, Team, Organization, etc.)
- `$resource` (Model): The resource model instance

**Returns:** `\Illuminate\Database\Eloquent\Builder`

**Example:**
```php
// Get all permissions for model and resource
$permissions = ModelHasResourceAndPermission::forModelAndResource($user, $article)
    ->whereNotNull('permission_id')
    ->with('permission')
    ->get();

// Works with any model type
$teamPermissions = ModelHasResourceAndPermission::forModelAndResource($team, $article)
    ->whereNotNull('permission_id')
    ->get();

// Check for specific permission using scope
$hasPermission = ModelHasResourceAndPermission::forModelAndResource($user, $article)
    ->wherePermissionName('update-article')
    ->exists();

// Check for specific role using scope
$hasRole = ModelHasResourceAndPermission::forModelAndResource($user, $article)
    ->whereRoleName('article-editor')
    ->exists();
```

### forUserAndResource() (Deprecated)

**Deprecated:** Use `forModelAndResource()` instead. This method is kept for backward compatibility. **Will be removed in 0.3.0.**

```php
ModelHasResourceAndPermission::forUserAndResource($user, $resource): Builder
```

### wherePermissionName()

Scope to filter by permission name (automatically joins with permissions table).

```php
$query->wherePermissionName($permissionName): Builder
```

**Parameters:**
- `$permissionName` (string): Permission name

**Returns:** `\Illuminate\Database\Eloquent\Builder`

**Example:**
```php
ModelHasResourceAndPermission::forUserAndResource($user, $article)
    ->wherePermissionName('update-article')
    ->exists();
```

### whereRoleName()

Scope to filter by role name (automatically joins with roles table).

```php
$query->whereRoleName($roleName): Builder
```

**Parameters:**
- `$roleName` (string): Role name

**Returns:** `\Illuminate\Database\Eloquent\Builder`

**Example:**
```php
ModelHasResourceAndPermission::forUserAndResource($user, $article)
    ->whereRoleName('article-editor')
    ->exists();
```

## Resource Methods

Resource models (like Article, Branch) can use the `HasAssignedModels` trait to retrieve all users assigned to them.

### Using HasAssignedModels Trait

Add the trait to your resource model:

```php
use Fishdaa\LaravelResourcePermissions\Traits\HasAssignedModels;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasAssignedModels;
    
    // ... your model code
}
```

### getAssignedUsers()

Get all users assigned to this resource (with permissions or roles). Optionally filter to only specific users.

```php
$article->getAssignedUsers($users = null): Collection
```

**Parameters:**
- `$users` (array|Collection|null): Optional array of user IDs or User model instances to filter

**Returns:** `Collection` of User models

**Example:**
```php
$article = Article::find(1);

// Get all users assigned to this article
$users = $article->getAssignedUsers();

// Get only specific users that are assigned
$specificUsers = $article->getAssignedUsers([$user1, $user2, $user3]);
// Or with IDs
$specificUsers = $article->getAssignedUsers([1, 2, 3]);

// Since User models have HasResourcePermissions trait, you can:
foreach ($users as $user) {
    // Get permissions for this user on this article
    $permissions = $user->getPermissionsForResource($article);
    
    // Get roles for this user on this article
    $roles = $user->getRolesForResource($article);
    
    // Check specific permissions
    if ($user->hasPermissionForResource('edit-article', $article)) {
        // User can edit this article
    }
}
```

### hasUserAssigned()

Check if a specific user is assigned to this resource.

```php
$article->hasUserAssigned($user): bool
```

**Parameters:**
- `$user` (Model|int): User model instance or user ID

**Returns:** `bool`

**Example:**
```php
if ($article->hasUserAssigned($user)) {
    // User is assigned to this article
}
```

### hasAllUsersAssigned()

Check if all specified users are assigned to this resource.

```php
$article->hasAllUsersAssigned($users): bool
```

**Parameters:**
- `$users` (array|Collection): Array of user IDs or User model instances

**Returns:** `bool`

**Example:**
```php
if ($article->hasAllUsersAssigned([$user1, $user2])) {
    // Both users are assigned
}
```

### hasAnyUserAssigned()

Check if any of the specified users are assigned to this resource.

```php
$article->hasAnyUserAssigned($users): bool
```

**Parameters:**
- `$users` (array|Collection): Array of user IDs or User model instances

**Returns:** `bool`

**Example:**
```php
if ($article->hasAnyUserAssigned([$user1, $user2])) {
    // At least one user is assigned
}
```

### getModelsForResource() (Static Method)

Get all models assigned to a resource (with permissions or roles). Works with any model type. Optionally filter to only specific models.

```php
ModelHasResourceAndPermission::getModelsForResource($resource, $models = null): Collection
```

**Parameters:**
- `$resource` (Model): The resource model instance
- `$models` (array|Collection|null): Optional array of model instances to filter

**Returns:** `Collection` of model instances (mixed types)

**Example:**
```php
$article = Article::find(1);

// Get all models assigned to this article (Users, Teams, etc.)
$models = ModelHasResourceAndPermission::getModelsForResource($article);

// Get only specific models
$specificModels = ModelHasResourceAndPermission::getModelsForResource($article, [$user1, $team1]);

// Process models
foreach ($models as $model) {
    if ($model instanceof User) {
        $permissions = $model->getPermissionsForResource($article);
    }
}
```

### getUsersForResource() (Deprecated)

**Deprecated:** Use `getModelsForResource()` instead. This method is kept for backward compatibility and only returns User models. **Will be removed in 0.3.0.**

```php
ModelHasResourceAndPermission::getUsersForResource($resource, $users = null): Collection
```

**Returns:** `Collection` of User models only

### isModelAssignedToResource() (Static Method)

Check if a model is assigned to a resource using static method. Works with any model type.

```php
ModelHasResourceAndPermission::isModelAssignedToResource($model, $resource): bool
```

**Parameters:**
- `$model` (Model): Any model instance (User, Team, Organization, etc.)
- `$resource` (Model): The resource model instance

**Returns:** `bool`

**Example:**
```php
$isAssigned = ModelHasResourceAndPermission::isModelAssignedToResource($user, $article);
$isTeamAssigned = ModelHasResourceAndPermission::isModelAssignedToResource($team, $article);
```

### isUserAssignedToResource() (Deprecated)

**Deprecated:** Use `isModelAssignedToResource()` instead. This method is kept for backward compatibility. **Will be removed in 0.3.0.**

```php
ModelHasResourceAndPermission::isUserAssignedToResource($user, $resource): bool
```

**Note:** The methods return distinct models - if a model has both permissions and roles for the resource, they will only appear once in the collection.

## Method Chaining

Most methods return `$this`, allowing method chaining:

```php
$user->givePermissionToResource('edit-article', $article)
     ->assignRoleToResource('article-manager', $article)
     ->syncPermissionsForResource(['view-article', 'edit-article'], $anotherArticle);
```


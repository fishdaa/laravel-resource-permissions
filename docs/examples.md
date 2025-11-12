# Examples

This document provides real-world examples of using Laravel Resource Permissions.

## Example 1: Article Management System

A multi-article organization where users have different permissions for different articlees.

### Setup

```php
// Create permissions via Spatie
Permission::create(['name' => 'view-article']);
Permission::create(['name' => 'edit-article']);
Permission::create(['name' => 'delete-article']);
Permission::create(['name' => 'manage-article-users']);

// Create roles via Spatie
Role::create(['name' => 'article-manager']);
Role::create(['name' => 'article-viewer']);
```

### Assign Permissions

```php
$user = User::find(1);
$article1 = Article::find(1);
$article2 = Article::find(2);

// User is manager of article 1
$user->assignRoleToResource('article-manager', $article1);
$user->givePermissionToResource('edit-article', $article1);
$user->givePermissionToResource('delete-article', $article1);
$user->givePermissionToResource('manage-article-users', $article1);

// User is viewer of article 2
$user->assignRoleToResource('article-viewer', $article2);
$user->givePermissionToResource('view-article', $article2);
```

### Check Permissions

```php
// In a controller
public function edit(Article $article)
{
    if (!auth()->user()->hasPermissionForResource('edit-article', $article)) {
        abort(403, 'You do not have permission to edit this article');
    }
    
    // Edit article logic
}

// In a policy
public function update(User $user, Article $article): bool
{
    return $user->hasPermissionForResource('edit-article', $article);
}
```

## Example 2: Project-Based Permissions

A project management system where users have different access levels per project.

### Setup

```php
Permission::create(['name' => 'view-project']);
Permission::create(['name' => 'edit-project']);
Permission::create(['name' => 'delete-project']);
Permission::create(['name' => 'manage-project-tasks']);
```

### Bulk Assignment

```php
$user = User::find(1);
$project = Project::find(1);

// Assign multiple permissions at once
$user->syncPermissionsForResource([
    'view-project',
    'edit-project',
    'manage-project-tasks'
], $project);
```

### Check Multiple Permissions

```php
// User needs all permissions
if ($user->hasAllPermissionsForResource([
    'view-project',
    'edit-project'
], $project)) {
    // User can view and edit
}

// User needs any permission
if ($user->hasAnyPermissionForResource([
    'edit-project',
    'delete-project'
], $project)) {
    // User can edit or delete
}
```

## Example 3: Document Access Control

A document management system where access is controlled per document.

### Setup

```php
Permission::create(['name' => 'view-document']);
Permission::create(['name' => 'edit-document']);
Permission::create(['name' => 'delete-document']);
Permission::create(['name' => 'share-document']);
```

### Assign with Creator Tracking

```php
$admin = User::find(1); // Admin assigning permissions
$user = User::find(2);
$document = Document::find(1);

// Track who assigned the permission
$user->givePermissionToResource('edit-document', $document, $admin->id);
```

### Query Permissions

```php
// Get all users who can edit a document
$document = Document::find(1);
$users = ModelHasResourceAndPermission::forResource($document)
    ->forPermission('edit-document')
    ->with('user')
    ->get()
    ->pluck('user');
```

## Example 4: Combining Global and Resource Permissions

A system where some users have global access and others have resource-specific access.

### Setup

```php
// Super admin has global permission
$superAdmin = User::find(1);
$superAdmin->givePermissionTo('edit-article'); // Global - can edit all articlees

// Regular user has resource-specific permission
$regularUser = User::find(2);
$article = Article::find(1);
$regularUser->givePermissionToResource('edit-article', $article); // Only this article
```

### Check Logic

```php
public function canEditArticle(User $user, Article $article): bool
{
    // Super admins can edit all articlees
    if ($user->hasPermissionTo('edit-article')) {
        return true;
    }
    
    // Regular users can edit specific articlees
    return $user->hasPermissionForResource('edit-article', $article);
}
```

## Example 5: Role-Based Resource Access

Using roles for resource access instead of individual permissions.

### Setup

```php
// Create role with permissions via Spatie
$managerRole = Role::create(['name' => 'project-manager']);
$managerRole->givePermissionTo(['view-project', 'edit-project', 'delete-project']);

$viewerRole = Role::create(['name' => 'project-viewer']);
$viewerRole->givePermissionTo(['view-project']);
```

### Assign Roles to Resources

```php
$user = User::find(1);
$project1 = Project::find(1);
$project2 = Project::find(2);

// User is manager of project 1
$user->assignRoleToResource('project-manager', $project1);

// User is viewer of project 2
$user->assignRoleToResource('project-viewer', $project2);
```

### Check Role

```php
if ($user->hasRoleForResource('project-manager', $project)) {
    // User has manager role for this project
    // This means they have view, edit, and delete permissions via the role
}
```

## Example 6: API Endpoint Protection

Protecting API endpoints with resource permissions.

```php
// In a controller
class ArticleController extends Controller
{
    public function update(Request $request, Article $article)
    {
        // Check permission
        if (!auth()->user()->hasPermissionForResource('edit-article', $article)) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }
        
        // Update logic
        $article->update($request->validated());
        
        return response()->json($article);
    }
}
```

## Example 7: Blade Directives

Creating custom Blade directives for resource permissions.

```php
// In AppServiceProvider
use Illuminate\Support\Facades\Blade;

Blade::if('hasPermissionForResource', function ($permission, $resource) {
    return auth()->check() && 
           auth()->user()->hasPermissionForResource($permission, $resource);
});
```

```blade
@hasPermissionForResource('edit-article', $article)
    <a href="{{ route('articlees.edit', $article) }}">Edit Article</a>
@endhasPermissionForResource
```

## Example 8: Middleware

Creating middleware for resource permission checks.

```php
class CheckResourcePermission
{
    public function handle($request, Closure $next, $permission)
    {
        $resource = $request->route('article'); // or get from route
        
        if (!auth()->user()->hasPermissionForResource($permission, $resource)) {
            abort(403);
        }
        
        return $next($request);
    }
}
```

```php
// In routes
Route::put('/articlees/{article}', [ArticleController::class, 'update'])
    ->middleware('resource.permission:edit-article');
```


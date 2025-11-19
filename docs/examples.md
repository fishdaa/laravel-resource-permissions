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

## Example 9: Authors Editing Only Their Own Articles

A content management system where authors can only edit their own articles, while editors can edit assigned articles.

### Setup

```php
Permission::create(['name' => 'view-article']);
Permission::create(['name' => 'edit-article']);
Permission::create(['name' => 'delete-article']);
Permission::create(['name' => 'publish-article']);
```

### Assign Permissions Based on Ownership

```php
// Author creates an article
$author = User::find(1);
$article = Article::create([
    'title' => 'My Article',
    'content' => 'Content here',
    'author_id' => $author->id
]);

// Author automatically gets edit permission for their own article
$author->givePermissionToResource('edit-article', $article);
$author->givePermissionToResource('delete-article', $article);

// Editor gets assigned to review/edit specific articles
$editor = User::find(2);
$article2 = Article::find(2);
$editor->givePermissionToResource('edit-article', $article2);
$editor->givePermissionToResource('publish-article', $article2);
```

### Check Permissions with Ownership Fallback

```php
// In ArticlePolicy
public function update(User $user, Article $article): bool
{
    // Authors can always edit their own articles
    if ($article->author_id === $user->id) {
        return true;
    }
    
    // Check resource-specific permission
    return $user->hasPermissionForResource('edit-article', $article);
}

// In controller
public function edit(Article $article)
{
    $this->authorize('update', $article);
    
    // Edit logic
}
```

## Example 10: Team-Based Resource Permissions

Using teams (polymorphic models) to assign permissions to groups of users.

### Setup Models

```php
use Fishdaa\LaravelResourcePermissions\Traits\HasResourcePermissions;

class Team extends Model
{
    use HasResourcePermissions;
    
    // Team model with resource permissions
}

class User extends Model
{
    use HasResourcePermissions;
    
    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }
}
```

### Assign Permissions to Teams

```php
$marketingTeam = Team::find(1);
$salesTeam = Team::find(2);
$article = Article::find(1);
$report = Report::find(1);

// Marketing team can edit Article #123
$marketingTeam->givePermissionToResource('edit-article', $article);

// Sales team can view Report #456
$salesTeam->givePermissionToResource('view-report', $report);
```

### Check Team Permissions

```php
// Check if team has permission
if ($marketingTeam->hasPermissionForResource('edit-article', $article)) {
    // Team can edit
}

// Check if user's team has permission
$user = User::find(1);
$userTeams = $user->teams;

foreach ($userTeams as $team) {
    if ($team->hasPermissionForResource('edit-article', $article)) {
        // User's team has permission
        break;
    }
}
```

## Example 11: Multi-Tenant Application

Isolating data between organizations in a SaaS application.

### Setup

```php
class Organization extends Model
{
    use HasResourcePermissions;
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
}

class Project extends Model
{
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
```

### Assign Organization-Level Permissions

```php
$orgA = Organization::find(1);
$orgB = Organization::find(2);
$projectA = Project::where('organization_id', 1)->first();
$projectB = Project::where('organization_id', 2)->first();

// Organization A can only access their own projects
$orgA->givePermissionToResource('view-project', $projectA);
$orgA->givePermissionToResource('edit-project', $projectA);

// Organization B can only access their own projects
$orgB->givePermissionToResource('view-project', $projectB);
```

### Enforce Organization Isolation

```php
// In ProjectController
public function show(Project $project)
{
    $user = auth()->user();
    $organization = $user->organization;
    
    // Check if user's organization has permission for this project
    if (!$organization->hasPermissionForResource('view-project', $project)) {
        abort(403, 'You do not have access to this project');
    }
    
    // Also verify project belongs to organization
    if ($project->organization_id !== $organization->id) {
        abort(403, 'Project does not belong to your organization');
    }
    
    return view('projects.show', compact('project'));
}
```

### Super Admin Access

```php
// Super admin can access all organizations
$superAdmin = User::find(1);
$superAdmin->givePermissionTo('view-all-projects'); // Global permission

// In controller
public function show(Project $project)
{
    $user = auth()->user();
    
    // Super admins bypass organization checks
    if ($user->hasPermissionTo('view-all-projects')) {
        return view('projects.show', compact('project'));
    }
    
    // Regular users check organization permissions
    $organization = $user->organization;
    if (!$organization->hasPermissionForResource('view-project', $project)) {
        abort(403);
    }
    
    return view('projects.show', compact('project'));
}
```

## Example 12: Branch/Location-Based Permissions

Managing permissions for physical locations or branches.

### Setup

```php
class Branch extends Model
{
    use HasAssignedModels;
    
    // Branch can have assigned users
}

class User extends Model
{
    use HasResourcePermissions;
    
    public function branches()
    {
        return $this->belongsToMany(Branch::class);
    }
}
```

### Assign Branch Permissions

```php
$downtownBranch = Branch::create(['name' => 'Downtown Location']);
$suburbanBranch = Branch::create(['name' => 'Suburban Location']);

$manager = User::find(1);
$employee = User::find(2);

// Manager can manage downtown branch
$manager->givePermissionToResource('manage-branch', $downtownBranch);
$manager->givePermissionToResource('view-reports', $downtownBranch);

// Employee can only view suburban branch
$employee->givePermissionToResource('view-branch', $suburbanBranch);
```

### Get Users Assigned to Branch

```php
$branch = Branch::find(1);

// Get all models assigned to this branch
$assignedModels = $branch->getAssignedModels();

// Get only specific models if they're assigned
$specificModels = $branch->getAssignedModels([$user1, $user2]);

// Check if user is assigned
if ($branch->hasModelAssigned($user)) {
    // User has access to this branch
}
```

## Example 13: Department-Based Project Access

Managers can see all projects in their department, while team members see only assigned projects.

### Setup

```php
class Department extends Model
{
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
    
    public function managers()
    {
        return $this->hasMany(User::class, 'department_id')
            ->where('role', 'manager');
    }
}

class Project extends Model
{
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
```

### Assign Department-Level Permissions

```php
$marketingDept = Department::find(1);
$salesDept = Department::find(2);

$marketingProjects = $marketingDept->projects;
$salesProjects = $salesDept->projects;

$marketingManager = User::where('department_id', 1)
    ->where('role', 'manager')
    ->first();

// Manager can view all projects in their department
foreach ($marketingProjects as $project) {
    $marketingManager->givePermissionToResource('view-project', $project);
    $marketingManager->givePermissionToResource('edit-project', $project);
}

// Team members get assigned to specific projects
$teamMember = User::find(5);
$specificProject = Project::find(10);
$teamMember->givePermissionToResource('view-project', $specificProject);
```

### Filter Projects by Department

```php
// In ProjectController
public function index()
{
    $user = auth()->user();
    
    if ($user->role === 'manager') {
        // Managers see all projects in their department
        $department = $user->department;
        $projects = $department->projects()->get();
        
        // Filter to only projects user has permission for
        $projects = $projects->filter(function ($project) use ($user) {
            return $user->hasPermissionForResource('view-project', $project);
        });
    } else {
        // Team members see only assigned projects
        $allProjects = Project::all();
        $projects = $allProjects->filter(function ($project) use ($user) {
            return $user->hasPermissionForResource('view-project', $project);
        });
    }
    
    return view('projects.index', compact('projects'));
}
```

## Example 14: External Contractors

External contractors can only see specific projects they're working on.

### Setup

```php
class Contractor extends Model
{
    use HasResourcePermissions;
    
    // Contractors can have resource permissions
}
```

### Assign Contractor Permissions

```php
$contractor = Contractor::find(1);
$project1 = Project::find(1);
$project2 = Project::find(2);

// Contractor can only access specific projects
$contractor->givePermissionToResource('view-project', $project1);
$contractor->givePermissionToResource('edit-tasks', $project1);

// Contractor cannot access project 2
// No permissions assigned
```

### Check Contractor Access

```php
// In ProjectController
public function show(Project $project)
{
    $user = auth()->user();
    
    // Check if contractor has access
    if ($user instanceof Contractor) {
        if (!$user->hasPermissionForResource('view-project', $project)) {
            abort(403, 'You do not have access to this project');
        }
    }
    
    // Regular employees check normally
    if ($user instanceof User && !$user->hasPermissionForResource('view-project', $project)) {
        abort(403);
    }
    
    return view('projects.show', compact('project'));
}
```

## Example 15: Getting All Models with Permissions

Find all models (users, teams, organizations) that have permissions for a resource.

### Get All Models for a Resource

```php
$article = Article::find(1);

// Get all models (users, teams, etc.) assigned to this article
$models = ModelHasResourceAndPermission::getModelsForResource($article);

// Filter by model type
$users = $models->filter(function ($model) {
    return $model instanceof User;
});

$teams = $models->filter(function ($model) {
    return $model instanceof Team;
});

// Process each model type
foreach ($models as $model) {
    if ($model instanceof User) {
        echo "User: {$model->name} has access\n";
    } elseif ($model instanceof Team) {
        echo "Team: {$model->name} has access\n";
    }
}
```

### Get Specific Models

```php
$article = Article::find(1);
$user1 = User::find(1);
$team1 = Team::find(1);

// Get only specific models if they're assigned
$assignedModels = ModelHasResourceAndPermission::getModelsForResource(
    $article,
    [$user1, $team1]
);
```

## Example 16: Reviewers Viewing Specific Articles

Reviewers can view and comment on specific articles assigned to them.

### Setup

```php
Permission::create(['name' => 'view-article']);
Permission::create(['name' => 'comment-article']);
Permission::create(['name' => 'approve-article']);
```

### Assign Reviewer Permissions

```php
$reviewer = User::find(1);
$article1 = Article::find(1);
$article2 = Article::find(2);

// Reviewer can view and comment on article 1
$reviewer->givePermissionToResource('view-article', $article1);
$reviewer->givePermissionToResource('comment-article', $article1);

// Reviewer can view, comment, and approve article 2
$reviewer->givePermissionToResource('view-article', $article2);
$reviewer->givePermissionToResource('comment-article', $article2);
$reviewer->givePermissionToResource('approve-article', $article2);
```

### Check Reviewer Permissions

```php
// In ArticleController
public function show(Article $article)
{
    $user = auth()->user();
    
    if (!$user->hasPermissionForResource('view-article', $article)) {
        abort(403, 'You do not have permission to view this article');
    }
    
    return view('articles.show', compact('article'));
}

public function comment(Request $request, Article $article)
{
    $user = auth()->user();
    
    if (!$user->hasPermissionForResource('comment-article', $article)) {
        abort(403, 'You do not have permission to comment on this article');
    }
    
    // Create comment
    $article->comments()->create([
        'user_id' => $user->id,
        'content' => $request->content
    ]);
    
    return redirect()->back();
}

public function approve(Article $article)
{
    $user = auth()->user();
    
    if (!$user->hasPermissionForResource('approve-article', $article)) {
        abort(403, 'You do not have permission to approve this article');
    }
    
    $article->update(['status' => 'approved']);
    
    return redirect()->back();
}
```


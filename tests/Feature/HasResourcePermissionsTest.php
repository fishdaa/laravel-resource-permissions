<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Feature;

use Fishdaa\LaravelResourcePermissions\Models\ModelHasResourceAndPermission;
use Fishdaa\LaravelResourcePermissions\Tests\Article;
use Fishdaa\LaravelResourcePermissions\Tests\TestCase;
use Fishdaa\LaravelResourcePermissions\Tests\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HasResourcePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Article $article;
    protected Permission $permission;
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);

        $this->permission = Permission::create(['name' => 'edit-article']);
        $this->role = Role::create(['name' => 'article-editor']);
    }

    public function test_has_permission_for_resource_returns_false_when_no_permission(): void
    {
        $this->assertFalse($this->user->hasPermissionForResource('edit-article', $this->article));
    }

    public function test_give_permission_to_resource(): void
    {
        $this->user->givePermissionToResource('edit-article', $this->article);

        $this->assertTrue($this->user->hasPermissionForResource('edit-article', $this->article));
        $this->assertDatabaseHas(config('resource-permissions.table_name', 'model_has_resource_and_permissions'), [
            'user_id' => $this->user->id,
            'resource_type' => Article::class,
            'resource_id' => $this->article->id,
            'permission_id' => $this->permission->id,
        ]);
    }

    public function test_give_permission_to_resource_with_permission_model(): void
    {
        $this->user->givePermissionToResource($this->permission, $this->article);

        $this->assertTrue($this->user->hasPermissionForResource($this->permission, $this->article));
    }

    public function test_give_permission_to_resource_tracks_creator(): void
    {
        $creator = User::create([
            'name' => 'Creator',
            'email' => 'creator@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->user->givePermissionToResource('edit-article', $this->article, $creator->id);

        $this->assertDatabaseHas(config('resource-permissions.table_name', 'model_has_resource_and_permissions'), [
            'user_id' => $this->user->id,
            'created_by' => $creator->id,
        ]);
    }

    public function test_revoke_permission_from_resource(): void
    {
        $this->user->givePermissionToResource('edit-article', $this->article);
        $this->assertTrue($this->user->hasPermissionForResource('edit-article', $this->article));

        $this->user->revokePermissionFromResource('edit-article', $this->article);

        $this->assertFalse($this->user->hasPermissionForResource('edit-article', $this->article));
    }

    public function test_sync_permissions_for_resource(): void
    {
        $permission2 = Permission::create(['name' => 'view-article']);
        $permission3 = Permission::create(['name' => 'delete-article']);

        // Give initial permissions
        $this->user->givePermissionToResource('edit-article', $this->article);
        $this->user->givePermissionToResource('view-article', $this->article);

        // Sync to different permissions
        $this->user->syncPermissionsForResource(['view-article', 'delete-article'], $this->article);

        $this->assertFalse($this->user->hasPermissionForResource('edit-article', $this->article));
        $this->assertTrue($this->user->hasPermissionForResource('view-article', $this->article));
        $this->assertTrue($this->user->hasPermissionForResource('delete-article', $this->article));
    }

    public function test_get_permissions_for_resource(): void
    {
        $permission2 = Permission::create(['name' => 'view-article']);

        $this->user->givePermissionToResource('edit-article', $this->article);
        $this->user->givePermissionToResource('view-article', $this->article);

        $permissions = $this->user->getPermissionsForResource($this->article);

        $this->assertCount(2, $permissions);
        $this->assertTrue($permissions->contains('id', $this->permission->id));
        $this->assertTrue($permissions->contains('id', $permission2->id));
    }

    public function test_has_any_permission_for_resource(): void
    {
        $permission2 = Permission::create(['name' => 'view-article']);

        $this->user->givePermissionToResource('edit-article', $this->article);

        $this->assertTrue($this->user->hasAnyPermissionForResource(['edit-article', 'view-article'], $this->article));
        $this->assertFalse($this->user->hasAnyPermissionForResource(['view-article', 'delete-article'], $this->article));
    }

    public function test_has_all_permissions_for_resource(): void
    {
        $permission2 = Permission::create(['name' => 'view-article']);

        $this->user->givePermissionToResource('edit-article', $this->article);
        $this->user->givePermissionToResource('view-article', $this->article);

        $this->assertTrue($this->user->hasAllPermissionsForResource(['edit-article', 'view-article'], $this->article));
        $this->assertFalse($this->user->hasAllPermissionsForResource(['edit-article', 'delete-article'], $this->article));
    }

    public function test_assign_role_to_resource(): void
    {
        $this->user->assignRoleToResource('article-editor', $this->article);

        $this->assertTrue($this->user->hasRoleForResource('article-editor', $this->article));
        $this->assertDatabaseHas(config('resource-permissions.table_name', 'model_has_resource_and_permissions'), [
            'user_id' => $this->user->id,
            'resource_type' => Article::class,
            'resource_id' => $this->article->id,
            'role_id' => $this->role->id,
        ]);
    }

    public function test_remove_role_from_resource(): void
    {
        $this->user->assignRoleToResource('article-editor', $this->article);
        $this->assertTrue($this->user->hasRoleForResource('article-editor', $this->article));

        $this->user->removeRoleFromResource('article-editor', $this->article);

        $this->assertFalse($this->user->hasRoleForResource('article-editor', $this->article));
    }

    public function test_sync_roles_for_resource(): void
    {
        $role2 = Role::create(['name' => 'article-viewer']);

        $this->user->assignRoleToResource('article-editor', $this->article);
        $this->user->syncRolesForResource(['article-viewer'], $this->article);

        $this->assertFalse($this->user->hasRoleForResource('article-editor', $this->article));
        $this->assertTrue($this->user->hasRoleForResource('article-viewer', $this->article));
    }

    public function test_get_roles_for_resource(): void
    {
        $role2 = Role::create(['name' => 'article-viewer']);

        $this->user->assignRoleToResource('article-editor', $this->article);
        $this->user->assignRoleToResource('article-viewer', $this->article);

        $roles = $this->user->getRolesForResource($this->article);

        $this->assertCount(2, $roles);
        $this->assertTrue($roles->contains('id', $this->role->id));
        $this->assertTrue($roles->contains('id', $role2->id));
    }

    public function test_permissions_are_resource_specific(): void
    {
        $article2 = Article::create(['title' => 'Article 2', 'content' => 'Content 2']);

        $this->user->givePermissionToResource('edit-article', $this->article);

        $this->assertTrue($this->user->hasPermissionForResource('edit-article', $this->article));
        $this->assertFalse($this->user->hasPermissionForResource('edit-article', $article2));
    }

    public function test_method_chaining(): void
    {
        $result = $this->user->givePermissionToResource('edit-article', $this->article)
            ->assignRoleToResource('article-editor', $this->article);

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($this->user->hasPermissionForResource('edit-article', $this->article));
        $this->assertTrue($this->user->hasRoleForResource('article-editor', $this->article));
    }

    public function test_unique_constraint_prevents_duplicate_permissions(): void
    {
        $this->user->givePermissionToResource('edit-article', $this->article);
        $this->user->givePermissionToResource('edit-article', $this->article); // Should not create duplicate

        $count = ModelHasResourceAndPermission::where('user_id', $this->user->id)
            ->where('resource_type', Article::class)
            ->where('resource_id', $this->article->id)
            ->where('permission_id', $this->permission->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_unique_constraint_prevents_duplicate_roles(): void
    {
        $this->user->assignRoleToResource('article-editor', $this->article);
        $this->user->assignRoleToResource('article-editor', $this->article); // Should not create duplicate

        $count = ModelHasResourceAndPermission::where('user_id', $this->user->id)
            ->where('resource_type', Article::class)
            ->where('resource_id', $this->article->id)
            ->where('role_id', $this->role->id)
            ->count();

        $this->assertEquals(1, $count);
    }
}


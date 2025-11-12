<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Feature;

use Fishdaa\LaravelResourcePermissions\Models\ModelHasResourceAndPermission;
use Fishdaa\LaravelResourcePermissions\Tests\Article;
use Fishdaa\LaravelResourcePermissions\Tests\TestCase;
use Fishdaa\LaravelResourcePermissions\Tests\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HasAssignedUsersTest extends TestCase
{
    use RefreshDatabase;

    protected Article $article;
    protected User $user1;
    protected User $user2;
    protected User $user3;
    protected Permission $permission;
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);

        $this->user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->user3 = User::create([
            'name' => 'User 3',
            'email' => 'user3@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->permission = Permission::create(['name' => 'edit-article']);
        $this->role = Role::create(['name' => 'article-editor']);
    }

    public function test_get_assigned_users_returns_empty_collection_when_no_users_assigned(): void
    {
        $users = $this->article->getAssignedUsers();

        $this->assertCount(0, $users);
    }

    public function test_get_assigned_users_includes_users_with_permissions_only(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);

        $users = $this->article->getAssignedUsers();

        $this->assertCount(1, $users);
        $this->assertTrue($users->contains('id', $this->user1->id));
    }

    public function test_get_assigned_users_includes_users_with_roles_only(): void
    {
        $this->user1->assignRoleToResource('article-editor', $this->article);

        $users = $this->article->getAssignedUsers();

        $this->assertCount(1, $users);
        $this->assertTrue($users->contains('id', $this->user1->id));
    }

    public function test_get_assigned_users_includes_users_with_both_permissions_and_roles(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user1->assignRoleToResource('article-editor', $this->article);

        $users = $this->article->getAssignedUsers();

        $this->assertCount(1, $users);
        $this->assertTrue($users->contains('id', $this->user1->id));
    }

    public function test_get_assigned_users_returns_distinct_users(): void
    {
        // User has both permission and role - should only appear once
        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user1->assignRoleToResource('article-editor', $this->article);

        $users = $this->article->getAssignedUsers();

        $this->assertCount(1, $users);
        $this->assertEquals(1, $users->where('id', $this->user1->id)->count());
    }

    public function test_get_assigned_users_returns_multiple_users(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user2->assignRoleToResource('article-editor', $this->article);
        $this->user3->givePermissionToResource('edit-article', $this->article);
        $this->user3->assignRoleToResource('article-editor', $this->article);

        $users = $this->article->getAssignedUsers();

        $this->assertCount(3, $users);
        $this->assertTrue($users->contains('id', $this->user1->id));
        $this->assertTrue($users->contains('id', $this->user2->id));
        $this->assertTrue($users->contains('id', $this->user3->id));
    }

    public function test_get_assigned_users_returns_user_models_with_has_resource_permissions_trait(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);

        $users = $this->article->getAssignedUsers();
        $user = $users->first();

        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->hasPermissionForResource('edit-article', $this->article));
    }

    public function test_static_get_users_for_resource_method(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user2->assignRoleToResource('article-editor', $this->article);

        $users = ModelHasResourceAndPermission::getUsersForResource($this->article);

        $this->assertCount(2, $users);
        $this->assertTrue($users->contains('id', $this->user1->id));
        $this->assertTrue($users->contains('id', $this->user2->id));
    }

    public function test_static_get_users_for_resource_returns_distinct_users(): void
    {
        // User has both permission and role - should only appear once
        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user1->assignRoleToResource('article-editor', $this->article);

        $users = ModelHasResourceAndPermission::getUsersForResource($this->article);

        $this->assertCount(1, $users);
        $this->assertEquals(1, $users->where('id', $this->user1->id)->count());
    }

    public function test_get_assigned_users_only_returns_users_for_specific_resource(): void
    {
        $article2 = Article::create(['title' => 'Another Article', 'content' => 'Content']);

        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user2->givePermissionToResource('edit-article', $article2);

        $users = $this->article->getAssignedUsers();

        $this->assertCount(1, $users);
        $this->assertTrue($users->contains('id', $this->user1->id));
        $this->assertFalse($users->contains('id', $this->user2->id));
    }

    public function test_get_assigned_users_filters_to_specific_users(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user2->assignRoleToResource('article-editor', $this->article);
        $this->user3->givePermissionToResource('edit-article', $this->article);

        // Get only user1 and user2
        $specificUsers = $this->article->getAssignedUsers([$this->user1, $this->user2]);

        $this->assertCount(2, $specificUsers);
        $this->assertTrue($specificUsers->contains('id', $this->user1->id));
        $this->assertTrue($specificUsers->contains('id', $this->user2->id));
        $this->assertFalse($specificUsers->contains('id', $this->user3->id));
    }

    public function test_get_assigned_users_filters_to_specific_users_by_id(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user2->assignRoleToResource('article-editor', $this->article);
        $this->user3->givePermissionToResource('edit-article', $this->article);

        // Get only user1 and user2 by ID
        $specificUsers = $this->article->getAssignedUsers([$this->user1->id, $this->user2->id]);

        $this->assertCount(2, $specificUsers);
        $this->assertTrue($specificUsers->contains('id', $this->user1->id));
        $this->assertTrue($specificUsers->contains('id', $this->user2->id));
        $this->assertFalse($specificUsers->contains('id', $this->user3->id));
    }

    public function test_has_user_assigned_returns_true_when_user_is_assigned(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);

        $this->assertTrue($this->article->hasUserAssigned($this->user1));
        $this->assertFalse($this->article->hasUserAssigned($this->user2));
    }

    public function test_has_user_assigned_works_with_user_id(): void
    {
        $this->user1->assignRoleToResource('article-editor', $this->article);

        $this->assertTrue($this->article->hasUserAssigned($this->user1->id));
    }

    public function test_has_all_users_assigned_returns_true_when_all_assigned(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user2->assignRoleToResource('article-editor', $this->article);

        $this->assertTrue($this->article->hasAllUsersAssigned([$this->user1, $this->user2]));
        $this->assertFalse($this->article->hasAllUsersAssigned([$this->user1, $this->user2, $this->user3]));
    }

    public function test_has_all_users_assigned_works_with_user_ids(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user2->assignRoleToResource('article-editor', $this->article);

        $this->assertTrue($this->article->hasAllUsersAssigned([$this->user1->id, $this->user2->id]));
    }

    public function test_has_any_user_assigned_returns_true_when_any_assigned(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);

        $this->assertTrue($this->article->hasAnyUserAssigned([$this->user1, $this->user2]));
        $this->assertFalse($this->article->hasAnyUserAssigned([$this->user2, $this->user3]));
    }

    public function test_has_any_user_assigned_works_with_user_ids(): void
    {
        $this->user1->assignRoleToResource('article-editor', $this->article);

        $this->assertTrue($this->article->hasAnyUserAssigned([$this->user1->id, $this->user2->id]));
    }

    public function test_static_is_user_assigned_to_resource(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);

        $this->assertTrue(ModelHasResourceAndPermission::isUserAssignedToResource($this->user1, $this->article));
        $this->assertFalse(ModelHasResourceAndPermission::isUserAssignedToResource($this->user2, $this->article));
    }

    public function test_static_get_users_for_resource_filters_to_specific_users(): void
    {
        $this->user1->givePermissionToResource('edit-article', $this->article);
        $this->user2->assignRoleToResource('article-editor', $this->article);
        $this->user3->givePermissionToResource('edit-article', $this->article);

        $specificUsers = ModelHasResourceAndPermission::getUsersForResource($this->article, [$this->user1, $this->user2]);

        $this->assertCount(2, $specificUsers);
        $this->assertTrue($specificUsers->contains('id', $this->user1->id));
        $this->assertTrue($specificUsers->contains('id', $this->user2->id));
        $this->assertFalse($specificUsers->contains('id', $this->user3->id));
    }
}


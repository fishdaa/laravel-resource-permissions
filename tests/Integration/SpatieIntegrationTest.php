<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Integration;

use Fishdaa\LaravelResourcePermissions\Tests\Article;
use Fishdaa\LaravelResourcePermissions\Tests\TestCase;
use Fishdaa\LaravelResourcePermissions\Tests\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SpatieIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_permissions_work_alongside_spatie_permissions(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission = Permission::create(['name' => 'edit-article']);

        // Spatie global permission
        $user->givePermissionTo('edit-article');
        $this->assertTrue($user->hasPermissionTo('edit-article'));

        // Resource-specific permission
        $user->givePermissionToResource('edit-article', $article);
        $this->assertTrue($user->hasPermissionForResource('edit-article', $article));

        // Both should work independently
        $this->assertTrue($user->hasPermissionTo('edit-article'));
        $this->assertTrue($user->hasPermissionForResource('edit-article', $article));
    }

    public function test_resource_permissions_use_spatie_permission_model(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission = Permission::create(['name' => 'edit-article']);

        $user->givePermissionToResource($permission, $article);

        $this->assertTrue($user->hasPermissionForResource($permission, $article));
        $this->assertTrue($user->hasPermissionForResource('edit-article', $article));
    }

    public function test_resource_roles_use_spatie_role_model(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $role = Role::create(['name' => 'article-manager']);

        $user->assignRoleToResource($role, $article);

        $this->assertTrue($user->hasRoleForResource($role, $article));
        $this->assertTrue($user->hasRoleForResource('article-manager', $article));
    }

    public function test_global_and_resource_permissions_are_independent(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article1 = Article::create(['title' => 'Article 1', 'content' => 'Content 1']);
        $article2 = Article::create(['title' => 'Article 2', 'content' => 'Content 2']);
        $permission = Permission::create(['name' => 'edit-article']);

        // Global permission
        $user->givePermissionTo('edit-article');

        // Resource permission for article 1 only
        $user->givePermissionToResource('edit-article', $article1);

        // Global permission applies everywhere
        $this->assertTrue($user->hasPermissionTo('edit-article'));

        // Resource permission applies only to article1
        $this->assertTrue($user->hasPermissionForResource('edit-article', $article1));
        $this->assertFalse($user->hasPermissionForResource('edit-article', $article2));
    }

    public function test_global_and_resource_roles_are_independent(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article1 = Article::create(['title' => 'Article 1', 'content' => 'Content 1']);
        $article2 = Article::create(['title' => 'Article 2', 'content' => 'Content 2']);
        $role = Role::create(['name' => 'article-manager']);

        // Global role
        $user->assignRole('article-manager');

        // Resource role for article 1 only
        $user->assignRoleToResource('article-manager', $article1);

        // Global role applies everywhere
        $this->assertTrue($user->hasRole('article-manager'));

        // Resource role applies only to article1
        $this->assertTrue($user->hasRoleForResource('article-manager', $article1));
        $this->assertFalse($user->hasRoleForResource('article-manager', $article2));
    }

    public function test_permission_deletion_cascades_correctly(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission = Permission::create(['name' => 'edit-article']);

        $user->givePermissionToResource('edit-article', $article);

        $this->assertTrue($user->hasPermissionForResource('edit-article', $article));

        // Delete permission (should cascade)
        $permission->delete();

        $this->assertFalse($user->hasPermissionForResource('edit-article', $article));
    }

    public function test_role_deletion_cascades_correctly(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $role = Role::create(['name' => 'article-manager']);

        $user->assignRoleToResource('article-manager', $article);

        $this->assertTrue($user->hasRoleForResource('article-manager', $article));

        // Delete role (should cascade)
        $role->delete();

        $this->assertFalse($user->hasRoleForResource('article-manager', $article));
    }

    public function test_can_combine_global_and_resource_permission_checks(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        Permission::create(['name' => 'edit-article']);

        // User has global permission
        $user->givePermissionTo('edit-article');

        // Check both - should return true because of global permission
        $canEdit = $user->hasPermissionTo('edit-article') || 
                   $user->hasPermissionForResource('edit-article', $article);

        $this->assertTrue($canEdit);

        // Revoke global permission
        $user->revokePermissionTo('edit-article');

        // Now should return false
        $canEdit = $user->hasPermissionTo('edit-article') || 
                   $user->hasPermissionForResource('edit-article', $article);

        $this->assertFalse($canEdit);

        // Add resource permission
        $user->givePermissionToResource('edit-article', $article);

        // Now should return true because of resource permission
        $canEdit = $user->hasPermissionTo('edit-article') || 
                   $user->hasPermissionForResource('edit-article', $article);

        $this->assertTrue($canEdit);
    }

    public function test_can_method_checks_resource_permissions(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        Permission::create(['name' => 'edit-article']);

        // User has no global permission
        $this->assertFalse($user->can('edit-article'));

        // User has no resource permission
        $this->assertFalse($user->can('edit-article', $article));

        // Give resource-specific permission
        $user->givePermissionToResource('edit-article', $article);

        // Now can() should return true for this resource
        $this->assertTrue($user->can('edit-article', $article));

        // But still false for global check (no global permission)
        $this->assertFalse($user->can('edit-article'));

        // Give global permission
        $user->givePermissionTo('edit-article');

        // Now both should work
        $this->assertTrue($user->can('edit-article'));
        $this->assertTrue($user->can('edit-article', $article));
    }

    public function test_can_method_with_multiple_resources(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article1 = Article::create(['title' => 'Article 1', 'content' => 'Content 1']);
        $article2 = Article::create(['title' => 'Article 2', 'content' => 'Content 2']);
        Permission::create(['name' => 'edit-article']);

        // Give permission only for article1
        $user->givePermissionToResource('edit-article', $article1);

        // Can edit article1
        $this->assertTrue($user->can('edit-article', $article1));

        // Cannot edit article2
        $this->assertFalse($user->can('edit-article', $article2));
    }
}


<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Unit;

use Fishdaa\LaravelResourcePermissions\Tests\Article;
use Fishdaa\LaravelResourcePermissions\Tests\TestCase;
use Fishdaa\LaravelResourcePermissions\Tests\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HasResourcePermissionsTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_trait_is_used_on_user_model(): void
    {
        $user = new User();
        $traits = class_uses_recursive($user);

        $this->assertContains(
            'Fishdaa\LaravelResourcePermissions\Traits\HasResourcePermissions',
            $traits
        );
    }

    public function test_has_permission_for_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'hasPermissionForResource'));
    }

    public function test_give_permission_to_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'givePermissionToResource'));
    }

    public function test_revoke_permission_from_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'revokePermissionFromResource'));
    }

    public function test_sync_permissions_for_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'syncPermissionsForResource'));
    }

    public function test_get_permissions_for_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'getPermissionsForResource'));
    }

    public function test_assign_role_to_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'assignRoleToResource'));
    }

    public function test_remove_role_from_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'removeRoleFromResource'));
    }

    public function test_sync_roles_for_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'syncRolesForResource'));
    }

    public function test_has_role_for_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'hasRoleForResource'));
    }

    public function test_get_roles_for_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'getRolesForResource'));
    }

    public function test_has_any_permission_for_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'hasAnyPermissionForResource'));
    }

    public function test_has_all_permissions_for_resource_method_exists(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'hasAllPermissionsForResource'));
    }

    public function test_handles_non_existent_permission_gracefully(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $branch = Article::create(['title' => 'Test Article', 'content' => 'Test content']);

        $this->assertFalse($user->hasPermissionForResource('non-existent-permission', $branch));
    }

    public function test_handles_non_existent_role_gracefully(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $branch = Article::create(['title' => 'Test Article', 'content' => 'Test content']);

        $this->assertFalse($user->hasRoleForResource('non-existent-role', $branch));
    }
}


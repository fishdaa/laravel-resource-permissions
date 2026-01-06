<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Integration;

use Fishdaa\LaravelResourcePermissions\Tests\Article;
use Fishdaa\LaravelResourcePermissions\Tests\TestCase;
use Fishdaa\LaravelResourcePermissions\Tests\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InterpretationTest extends TestCase
{
    /** @test */
    public function it_can_check_permissions_inherited_from_resource_assigned_role()
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'password']);
        $article = Article::create(['title' => 'Test Article']);

        // Create role and permission
        $role = Role::create(['name' => 'Article Editor', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'edit articles', 'guard_name' => 'web']);

        // Give permission to role
        $role->givePermissionTo($permission);

        // Assign Role to User for the Resource
        $user->assignRoleToResource($role, $article);

        // Assert: User has Role on Resource
        $this->assertTrue($user->hasRoleForResource($role, $article), 'User should have role for resource');

        // Assert: User CAN perform action on resource (via role)
        $this->assertTrue($user->can('edit articles', $article), 'User should have permission via resource role');
    }
}

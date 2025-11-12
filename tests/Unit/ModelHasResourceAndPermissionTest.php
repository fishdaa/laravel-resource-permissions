<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Unit;

use Fishdaa\LaravelResourcePermissions\Models\ModelHasResourceAndPermission;
use Fishdaa\LaravelResourcePermissions\Tests\Article;
use Fishdaa\LaravelResourcePermissions\Tests\TestCase;
use Fishdaa\LaravelResourcePermissions\Tests\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModelHasResourceAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relationship(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission = Permission::create(['name' => 'edit-article']);

        $resourcePermission = ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'permission_id' => $permission->id,
        ]);

        $this->assertInstanceOf(User::class, $resourcePermission->user);
        $this->assertEquals($user->id, $resourcePermission->user->id);
    }

    public function test_resource_relationship(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission = Permission::create(['name' => 'edit-article']);

        $resourcePermission = ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'permission_id' => $permission->id,
        ]);

        $this->assertInstanceOf(Article::class, $resourcePermission->resource);
        $this->assertEquals($article->id, $resourcePermission->resource->id);
    }

    public function test_permission_relationship(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission = Permission::create(['name' => 'edit-article']);

        $resourcePermission = ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'permission_id' => $permission->id,
        ]);

        $this->assertInstanceOf(Permission::class, $resourcePermission->permission);
        $this->assertEquals($permission->id, $resourcePermission->permission->id);
    }

    public function test_role_relationship(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $role = Role::create(['name' => 'article-manager']);

        $resourcePermission = ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'role_id' => $role->id,
        ]);

        $this->assertInstanceOf(Role::class, $resourcePermission->role);
        $this->assertEquals($role->id, $resourcePermission->role->id);
    }

    public function test_created_by_relationship(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $creator = User::create([
            'name' => 'Creator',
            'email' => 'creator@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission = Permission::create(['name' => 'edit-article']);

        $resourcePermission = ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'permission_id' => $permission->id,
            'created_by' => $creator->id,
        ]);

        $this->assertInstanceOf(User::class, $resourcePermission->createdBy);
        $this->assertEquals($creator->id, $resourcePermission->createdBy->id);
    }

    public function test_for_resource_scope(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article1 = Article::create(['title' => 'Article 1', 'content' => 'Content 1']);
        $article2 = Article::create(['title' => 'Article 2', 'content' => 'Content 2']);
        $permission = Permission::create(['name' => 'edit-article']);

        ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article1->id,
            'permission_id' => $permission->id,
        ]);

        ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article2->id,
            'permission_id' => $permission->id,
        ]);

        $results = ModelHasResourceAndPermission::forResource($article1)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($article1->id, $results->first()->resource_id);
    }

    public function test_for_permission_scope(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission1 = Permission::create(['name' => 'edit-article']);
        $permission2 = Permission::create(['name' => 'view-article']);

        ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'permission_id' => $permission1->id,
        ]);

        ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'permission_id' => $permission2->id,
        ]);

        $results = ModelHasResourceAndPermission::forPermission('edit-article')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($permission1->id, $results->first()->permission_id);
    }

    public function test_for_role_scope(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $role1 = Role::create(['name' => 'article-manager']);
        $role2 = Role::create(['name' => 'article-viewer']);

        ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'role_id' => $role1->id,
        ]);

        ModelHasResourceAndPermission::create([
            'user_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'role_id' => $role2->id,
        ]);

        $results = ModelHasResourceAndPermission::forRole('article-manager')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($role1->id, $results->first()->role_id);
    }
}


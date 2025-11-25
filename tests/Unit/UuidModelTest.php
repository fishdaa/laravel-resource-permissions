<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Unit;

use Fishdaa\LaravelResourcePermissions\Models\ModelHasResourceAndPermission;
use Fishdaa\LaravelResourcePermissions\Tests\Article;
use Fishdaa\LaravelResourcePermissions\Tests\TestCase;
use Fishdaa\LaravelResourcePermissions\Tests\User;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;

class UuidModelTest extends TestCase
{
    /**
     * Define environment setup with UUID configuration.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Enable UUIDs for primary key
        $app['config']->set('resource-permissions.use_uuids', true);
        $app['config']->set('resource-permissions.use_uuids_for_models', false);
    }

    /**
     * Test that model generates UUID for primary key when use_uuids is enabled.
     */
    public function test_model_generates_uuid_for_primary_key(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission = Permission::create(['name' => 'edit-article']);

        $resourcePermission = ModelHasResourceAndPermission::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'permission_id' => $permission->id,
        ]);

        // Verify UUID was generated
        $this->assertNotNull($resourcePermission->id);
        $this->assertIsString($resourcePermission->id);
        
        // Verify it's a valid UUID format (36 characters with dashes)
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $resourcePermission->id,
            'ID should be a valid UUID format'
        );
    }

    /**
     * Test that model has correct properties when UUIDs are enabled.
     */
    public function test_model_properties_when_uuids_enabled(): void
    {
        $model = new ModelHasResourceAndPermission();
        
        // Verify incrementing is false
        $this->assertFalse($model->incrementing);
        
        // Verify keyType is string (use reflection to access protected property)
        $reflection = new \ReflectionClass($model);
        $keyTypeProperty = $reflection->getProperty('keyType');
        $keyTypeProperty->setAccessible(true);
        $this->assertEquals('string', $keyTypeProperty->getValue($model));
    }

    /**
     * Test that model works correctly with UUID primary keys.
     */
    public function test_model_operations_with_uuid_primary_key(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission = Permission::create(['name' => 'edit-article']);

        $resourcePermission = ModelHasResourceAndPermission::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'permission_id' => $permission->id,
        ]);

        $uuid = $resourcePermission->id;

        // Test find by UUID
        $found = ModelHasResourceAndPermission::find($uuid);
        $this->assertNotNull($found);
        $this->assertEquals($uuid, $found->id);

        // Test update
        $found->update(['permission_id' => null]);
        $this->assertNull($found->fresh()->permission_id);

        // Test delete
        $found->delete();
        $this->assertNull(ModelHasResourceAndPermission::find($uuid));
    }

    /**
     * Test that model relationships work with UUID primary keys.
     */
    public function test_model_relationships_with_uuid_primary_key(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $article = Article::create(['title' => 'Test Article', 'content' => 'Test content']);
        $permission = Permission::create(['name' => 'edit-article']);

        $resourcePermission = ModelHasResourceAndPermission::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'resource_type' => Article::class,
            'resource_id' => $article->id,
            'permission_id' => $permission->id,
        ]);

        // Verify relationships still work
        $this->assertInstanceOf(User::class, $resourcePermission->model);
        $this->assertInstanceOf(Article::class, $resourcePermission->resource);
        $this->assertInstanceOf(Permission::class, $resourcePermission->permission);
    }
}


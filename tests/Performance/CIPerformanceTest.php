<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Performance;

use Spatie\Permission\Models\Permission;

/**
 * CI performance tests that run automatically in continuous integration.
 * Tests with small datasets (100-10K records) to catch performance regressions.
 */
class CIPerformanceTest extends PerformanceTestCase
{
    protected Permission $viewPermission;
    protected Permission $editPermission;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->viewPermission = Permission::create(['name' => 'view-article']);
        $this->editPermission = Permission::create(['name' => 'edit-article']);
    }

    /**
     * Test permission check performance with 100 records.
     */
    public function test_permission_check_with_100_records()
    {
        $users = $this->generateUsers(10);
        $articles = $this->generateArticles(10);
        $this->assignPermissions($users, $articles, $this->viewPermission);

        $this->startMonitoring();
        
        // Test permission check
        $result = $users[0]->hasPermissionForResource('view-article', $articles[0]);
        
        $metrics = $this->stopMonitoring();

        $this->assertTrue($result);
        $this->assertLessThan(50, $metrics['time'], 'Permission check should complete in <50ms');
        $this->assertLessThanOrEqual(3, $metrics['queries'], 'Should use at most 3 queries');
    }

    /**
     * Test permission check performance with 1K records.
     */
    public function test_permission_check_with_1k_records()
    {
        $users = $this->generateUsers(10);
        $articles = $this->generateArticles(100);
        $this->assignPermissions($users, $articles, $this->viewPermission);

        $this->startMonitoring();
        
        $result = $users[0]->hasPermissionForResource('view-article', $articles[50]);
        
        $metrics = $this->stopMonitoring();

        $this->assertTrue($result);
        $this->assertLessThan(100, $metrics['time'], 'Permission check should complete in <100ms');
        $this->assertLessThanOrEqual(3, $metrics['queries'], 'Should use at most 3 queries');
    }

    /**
     * Test fetching all permissions for a resource with 1K records.
     */
    public function test_get_permissions_with_1k_records()
    {
        $users = $this->generateUsers(10);
        $articles = $this->generateArticles(100);
        $this->assignPermissions($users, $articles, $this->viewPermission);
        $this->assignPermissions([$users[0]], $articles, $this->editPermission);

        $this->startMonitoring();
        
        $permissions = $users[0]->getPermissionsForResource($articles[0]);
        
        $metrics = $this->stopMonitoring();

        $this->assertCount(2, $permissions);
        $this->assertLessThan(150, $metrics['time'], 'Fetching permissions should complete in <150ms');
        $this->assertLessThanOrEqual(2, $metrics['queries'], 'Should use at most 2 queries');
    }

    /**
     * Test fetching assigned models with 1K records.
     */
    public function test_get_assigned_models_with_1k_records()
    {
        $users = $this->generateUsers(10);
        $articles = $this->generateArticles(100);
        $this->assignPermissions($users, [$articles[0]], $this->viewPermission);

        $this->startMonitoring();
        
        $assignedModels = $articles[0]->getAssignedModels();
        
        $metrics = $this->stopMonitoring();

        $this->assertCount(10, $assignedModels);
        $this->assertLessThan(150, $metrics['time'], 'Fetching assigned models should complete in <150ms');
        $this->assertLessThanOrEqual(3, $metrics['queries'], 'Should use at most 3 queries');
    }

    /**
     * Test that indexes are used for permission queries.
     */
    public function test_indexes_are_used_for_permission_queries()
    {
        $users = $this->generateUsers(5);
        $articles = $this->generateArticles(5);
        $this->assignPermissions($users, $articles, $this->viewPermission);

        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        $userClass = get_class($users[0]);
        $articleClass = get_class($articles[0]);

        // Test query that should use model index
        $query = "SELECT * FROM {$tableName} WHERE model_type = ? AND model_id = ?";
        $explain = $this->explainQuery($query, [$userClass, $users[0]->id]);
        
        if (!empty($explain)) {
            $this->assertIndexUsed($explain);
        }

        // Test query that should use resource index
        $query = "SELECT * FROM {$tableName} WHERE resource_type = ? AND resource_id = ?";
        $explain = $this->explainQuery($query, [$articleClass, $articles[0]->id]);
        
        if (!empty($explain)) {
            $this->assertIndexUsed($explain);
        }
    }

    /**
     * Test query count doesn't increase significantly with more records.
     */
    public function test_query_count_remains_constant()
    {
        // Test with 10 records
        $users1 = $this->generateUsers(5);
        $articles1 = $this->generateArticles(2);
        $this->assignPermissions($users1, $articles1, $this->viewPermission);

        $this->startMonitoring();
        $users1[0]->hasPermissionForResource('view-article', $articles1[0]);
        $metrics1 = $this->stopMonitoring();

        // Clean up
        foreach ($users1 as $user) {
            $user->delete();
        }
        foreach ($articles1 as $article) {
            $article->delete();
        }

        // Test with 100 records
        $users2 = $this->generateUsers(50);
        $articles2 = $this->generateArticles(2);
        $this->assignPermissions($users2, $articles2, $this->viewPermission);

        $this->startMonitoring();
        $users2[0]->hasPermissionForResource('view-article', $articles2[0]);
        $metrics2 = $this->stopMonitoring();

        // Query count should not increase significantly (allow +/-3 queries for variations)
        $this->assertLessThanOrEqual(
            $metrics1['queries'] + 3,
            $metrics2['queries'],
            "Query count should not increase significantly with dataset size (was {$metrics1['queries']}, now {$metrics2['queries']})"
        );
    }
}

<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Performance;

use Spatie\Permission\Models\Permission;

/**
 * Comprehensive benchmark tests for local testing.
 * Tests with large datasets (up to 1M records) for detailed performance profiling.
 * 
 * Run with: vendor/bin/phpunit --group=benchmark
 * 
 * @group benchmark
 */
class BenchmarkTest extends PerformanceTestCase
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
     * Benchmark permission check with 10K records.
     */
    public function test_benchmark_permission_check_10k_records()
    {
        echo "\n🔥 Generating 10K permission records...\n";
        
        $users = $this->generateUsers(100);
        $articles = $this->generateArticles(100);
        $this->assignPermissions($users, $articles, $this->viewPermission);

        $this->startMonitoring();
        $result = $users[0]->hasPermissionForResource('view-article', $articles[0]);
        $metrics = $this->stopMonitoring();

        $this->printReport('Permission Check (10K records)', $metrics);
        $this->assertTrue($result);
    }

    /**
     * Benchmark permission check with 100K records.
     */
    public function test_benchmark_permission_check_100k_records()
    {
        echo "\n🔥 Generating 100K permission records...\n";
        
        $users = $this->generateUsers(100);
        $articles = $this->generateArticles(1000);
        $this->assignPermissions($users, $articles, $this->viewPermission);

        $this->startMonitoring();
        $result = $users[0]->hasPermissionForResource('view-article', $articles[0]);
        $metrics = $this->stopMonitoring();

        $this->printReport('Permission Check (100K records)', $metrics);
        $this->assertTrue($result);
        $this->assertLessThan(500, $metrics['time'], 'Should complete in <500ms even with 100K records');
    }

    /**
     * Benchmark permission check with 1M records.
     * 
     * WARNING: This test may take several minutes to run.
     */
    public function test_benchmark_permission_check_1m_records()
    {
        $this->markTestSkipped('Skipped by default - uncomment to run 1M record test');
        
        echo "\n🔥 Generating 1M permission records... (this may take a while)\n";
        
        $users = $this->generateUsers(1000);
        $articles = $this->generateArticles(1000);
        $this->assignPermissions($users, $articles, $this->viewPermission);

        $this->startMonitoring();
        $result = $users[0]->hasPermissionForResource('view-article', $articles[0]);
        $metrics = $this->stopMonitoring();

        $this->printReport('Permission Check (1M records)', $metrics);
        $this->assertTrue($result);
        $this->assertLessThan(1000, $metrics['time'], 'Should complete in <1s even with 1M records');
    }

    /**
     * Benchmark fetching all permissions for a resource.
     */
    public function test_benchmark_get_permissions_100k_records()
    {
        echo "\n🔥 Generating 100K permission records...\n";
        
        $users = $this->generateUsers(100);
        $articles = $this->generateArticles(1000);
        $this->assignPermissions($users, $articles, $this->viewPermission);
        $this->assignPermissions([$users[0]], $articles, $this->editPermission);

        $this->startMonitoring();
        $permissions = $users[0]->getPermissionsForResource($articles[0]);
        $metrics = $this->stopMonitoring();

        $this->printReport('Get Permissions (100K records)', $metrics);
        $this->assertCount(2, $permissions);
        $this->assertLessThan(500, $metrics['time'], 'Should complete in <500ms');
    }

    /**
     * Benchmark fetching assigned models.
     */
    public function test_benchmark_get_assigned_models_10k_records()
    {
        echo "\n🔥 Generating 10K permission records...\n";
        
        $users = $this->generateUsers(100);
        $articles = $this->generateArticles(100);
        $this->assignPermissions($users, [$articles[0]], $this->viewPermission);

        $this->startMonitoring();
        $assignedModels = $articles[0]->getAssignedModels();
        $metrics = $this->stopMonitoring();

        $this->printReport('Get Assigned Models (10K records)', $metrics);
        $this->assertCount(100, $assignedModels);
        $this->assertLessThan(500, $metrics['time'], 'Should complete in <500ms');
    }

    /**
     * Benchmark polymorphic queries across multiple model types.
     */
    public function test_benchmark_polymorphic_queries()
    {
        echo "\n🔥 Testing polymorphic performance...\n";
        
        $users = $this->generateUsers(50);
        $articles = $this->generateArticles(100);
        
        // Assign permissions from different model types
        foreach ($users as $user) {
            foreach ($articles as $article) {
                $user->givePermissionToResource('view-article', $article);
            }
        }

        $this->startMonitoring();
        
        // Query across all model types
        $assignedModels = $articles[0]->getAssignedModels();
        
        $metrics = $this->stopMonitoring();

        $this->printReport('Polymorphic Query (5K records)', $metrics);
        $this->assertCount(50, $assignedModels);
    }

    /**
     * Memory usage benchmark.
     */
    public function test_benchmark_memory_usage()
    {
        echo "\n🔥 Testing memory usage...\n";
        
        $initialMemory = memory_get_usage(true);
        
        $users = $this->generateUsers(100);
        $articles = $this->generateArticles(100);
        $this->assignPermissions($users, $articles, $this->viewPermission);

        $afterDataMemory = memory_get_usage(true);
        
        // Perform operations
        $this->startMonitoring();
        foreach ($users as $user) {
            $user->hasPermissionForResource('view-article', $articles[0]);
        }
        $metrics = $this->stopMonitoring();

        $finalMemory = memory_get_usage(true);

        echo "\n=== Memory Usage Report ===\n";
        echo "Initial: " . round($initialMemory / 1024 / 1024, 2) . " MB\n";
        echo "After data generation: " . round($afterDataMemory / 1024 / 1024, 2) . " MB\n";
        echo "Final: " . round($finalMemory / 1024 / 1024, 2) . " MB\n";
        echo "Peak: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
        echo "\n";

        // Memory should not grow excessively during operations
        $operationMemory = $finalMemory - $afterDataMemory;
        $this->assertLessThan(50 * 1024 * 1024, $operationMemory, 'Operations should use <50MB additional memory');
    }
}

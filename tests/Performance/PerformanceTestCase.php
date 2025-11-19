<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Performance;

use Fishdaa\LaravelResourcePermissions\Tests\TestCase;
use Fishdaa\LaravelResourcePermissions\Tests\User;
use Fishdaa\LaravelResourcePermissions\Tests\Article;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class PerformanceTestCase extends TestCase
{
    protected array $queryLog = [];
    protected float $startTime;
    protected int $startMemory;

    /**
     * Start performance monitoring.
     */
    protected function startMonitoring(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->queryLog = [];
        
        DB::enableQueryLog();
    }

    /**
     * Stop performance monitoring and return metrics.
     */
    protected function stopMonitoring(): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $queries = DB::getQueryLog();
        
        DB::disableQueryLog();

        return [
            'time' => round(($endTime - $this->startTime) * 1000, 2), // ms
            'memory' => round(($endMemory - $this->startMemory) / 1024 / 1024, 2), // MB
            'queries' => count($queries),
            'query_details' => $queries,
        ];
    }

    /**
     * Generate test users (optimized).
     */
    protected function generateUsers(int $count): mixed
    {
        $timestamp = microtime(true);
        $userData = [];
        
        for ($i = 0; $i < $count; $i++) {
            $userData[] = [
                'name' => "User {$i}",
                'email' => "user{$i}_{$timestamp}@example.com",
                'password' => 'test', // password
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($userData, 1000) as $chunk) {
            User::insert($chunk);
        }

        return User::where('email', 'like', "%_{$timestamp}@example.com")->get();
    }

    /**
     * Generate test articles (optimized).
     */
    protected function generateArticles(int $count): mixed
    {
        $timestamp = microtime(true);
        $articleData = [];
        
        for ($i = 0; $i < $count; $i++) {
            $articleData[] = [
                'title' => "Article {$i}_{$timestamp}",
                'content' => "Content for article {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($articleData, 1000) as $chunk) {
            Article::insert($chunk);
        }

        return Article::where('title', 'like', "%_{$timestamp}")->get();
    }

    /**
     * Assign permissions to users for resources (optimized).
     */
    protected function assignPermissions(mixed $users, mixed $resources, Permission $permission): void
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        $chunkSize = 1000;
        $permissionData = [];
        $now = now();

        foreach ($users as $user) {
            foreach ($resources as $resource) {
                $permissionData[] = [
                    'model_type' => get_class($user),
                    'model_id' => $user->id,
                    'resource_type' => get_class($resource),
                    'resource_id' => $resource->id,
                    'permission_id' => $permission->id,
                    'role_id' => null,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($permissionData) >= $chunkSize) {
                    DB::table($tableName)->insert($permissionData);
                    $permissionData = [];
                }
            }
        }

        if (!empty($permissionData)) {
            DB::table($tableName)->insert($permissionData);
        }
        
        // Clear permission cache to ensure fresh data is loaded if needed
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Get EXPLAIN output for a query.
     */
    protected function explainQuery(string $query, array $bindings = []): array
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'pgsql') {
            $results = DB::select("EXPLAIN {$query}", $bindings);
            return json_decode(json_encode($results), true);
        }
        
        if ($driver === 'sqlite') {
            $results = DB::select("EXPLAIN QUERY PLAN {$query}", $bindings);
            return json_decode(json_encode($results), true);
        }
        
        return [];
    }

    /**
     * Assert that indexes are being used in the query.
     */
    protected function assertIndexUsed(array $explainOutput): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL: check if 'key' column is not NULL
            $hasIndex = false;
            foreach ($explainOutput as $row) {
                if (!empty($row['key']) && $row['key'] !== 'NULL') {
                    $hasIndex = true;
                    break;
                }
            }
            $this->assertTrue($hasIndex, 'Query should use an index');
        } elseif ($driver === 'sqlite') {
            // SQLite: check for SEARCH or SCAN with index
            $hasIndex = false;
            foreach ($explainOutput as $row) {
                if (isset($row['detail']) && 
                    (str_contains($row['detail'], 'INDEX') || str_contains($row['detail'], 'SEARCH'))) {
                    $hasIndex = true;
                    break;
                }
            }
            $this->assertTrue($hasIndex, 'Query should use an index');
        }
    }

    /**
     * Print performance report.
     */
    protected function printReport(string $testName, array $metrics): void
    {
        echo "\n";
        echo "=== {$testName} ===\n";
        echo "Time: {$metrics['time']} ms\n";
        echo "Memory: {$metrics['memory']} MB\n";
        echo "Queries: {$metrics['queries']}\n";
        
        if (!empty($metrics['query_details'])) {
            echo "\nQuery Details:\n";
            foreach ($metrics['query_details'] as $i => $query) {
                $time = isset($query['time']) ? round($query['time'], 2) : 'N/A';
                echo "  " . ($i + 1) . ". [{$time}ms] {$query['query']}\n";
            }
        }
        echo "\n";
    }
    /**
     * Benchmark permission check with given dataset size.
     */
    protected function benchmarkPermissionCheck(int $userCount, int $articleCount): array
    {
        $totalRecords = $userCount * $articleCount;
        echo "Testing {$totalRecords} records ({$userCount} users × {$articleCount} articles)...\n";

        // Use batch insert for users (much faster)
        $timestamp = microtime(true);
        $userData = [];
        for ($i = 0; $i < $userCount; $i++) {
            $userData[] = [
                'name' => "User {$i}",
                'email' => "user{$i}_{$timestamp}@example.com",
                'password' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Insert users in chunks of 500
        foreach (array_chunk($userData, 500) as $chunk) {
            User::insert($chunk);
        }
        
        // Retrieve users for relationship assignment
        $users = User::where('email', 'like', "%_{$timestamp}@example.com")->get();

        // Use batch insert for articles
        $articleData = [];
        for ($i = 0; $i < $articleCount; $i++) {
            $articleData[] = [
                'title' => "Article {$i}",
                'content' => "Content for article {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Insert articles in chunks of 500
        foreach (array_chunk($articleData, 500) as $chunk) {
            Article::insert($chunk);
        }
        
        // Retrieve articles for relationship assignment
        $articles = Article::latest()->take($articleCount)->get();

        // Batch assign permissions
        // Use the existing assignPermissions method in this class
        $this->assignPermissions($users, $articles, $this->viewPermission ?? Permission::create(['name' => 'view-article']));

        // Run multiple iterations for average and P90
        $iterations = 10; // Increased for better P90 accuracy
        $times = [];
        $queries = [];

        for ($i = 0; $i < $iterations; $i++) {
            $this->startMonitoring();
            $users[0]->hasPermissionForResource('view-article', $articles[0]);
            $metrics = $this->stopMonitoring();

            $times[] = $metrics['time'];
            $queries[] = $metrics['queries'];
        }

        $result = [
            'records' => $totalRecords,
            'avg_time' => round(array_sum($times) / count($times), 2),
            'min_time' => round(min($times), 2),
            'max_time' => round(max($times), 2),
            'p90_time' => $this->calculateP90($times),
            'queries' => (int) round(array_sum($queries) / count($queries)),
            'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        // Cleanup
        User::where('email', 'like', "%_{$timestamp}@example.com")->delete();
        Article::latest()->take($articleCount)->delete();

        return $result;
    }

    /**
     * Benchmark polymorphic permission check.
     */
    protected function benchmarkPolymorphicCheck(int $userCount, int $articleCount): array
    {
        $totalRecords = $userCount * $articleCount;
        echo "Testing Polymorphic Performance ({$totalRecords} records)...\n";
        
        $timestamp = microtime(true);
        
        // Create Users
        $userData = [];
        for ($i = 0; $i < $userCount; $i++) {
            $userData[] = [
                'name' => "PolyUser {$i}",
                'email' => "polyuser{$i}_{$timestamp}@example.com",
                'password' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        foreach (array_chunk($userData, 500) as $chunk) {
            User::insert($chunk);
        }
        $users = User::where('email', 'like', "%_{$timestamp}@example.com")->get();

        // Create Articles (Resource Type A)
        $articleData = [];
        for ($i = 0; $i < $articleCount; $i++) {
            $articleData[] = [
                'title' => "PolyArticle {$i}",
                'content' => "Content",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        foreach (array_chunk($articleData, 500) as $chunk) {
            Article::insert($chunk);
        }
        $articles = Article::latest()->take($articleCount)->get();

        // Assign permissions
        $this->assignPermissions($users, $articles, $this->viewPermission ?? Permission::create(['name' => 'view-article']));

        // Run benchmark
        $iterations = 10; // Increased for P90
        $times = [];
        $queries = [];

        for ($i = 0; $i < $iterations; $i++) {
            $this->startMonitoring();
            // Polymorphic query: get all assigned models for a resource
            $assignedModels = $articles[0]->getAssignedModels();
            $metrics = $this->stopMonitoring();

            $times[] = $metrics['time'];
            $queries[] = $metrics['queries'];
        }

        $result = [
            'records' => $totalRecords, // Approximate records involved
            'avg_time' => round(array_sum($times) / count($times), 2),
            'min_time' => round(min($times), 2),
            'max_time' => round(max($times), 2),
            'p90_time' => $this->calculateP90($times),
            'queries' => (int) round(array_sum($queries) / count($queries)),
            'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        // Cleanup
        User::where('email', 'like', "%_{$timestamp}@example.com")->delete();
        Article::latest()->take($articleCount)->delete();

        return $result;
    }

    /**
     * Calculate P90 (90th percentile) latency.
     */
    protected function calculateP90(array $times): float
    {
        sort($times);
        $count = count($times);
        $index = (int) ceil($count * 0.9) - 1;
        return round($times[$index], 2);
    }

    /**
     * Get hardware and environment specs.
     */
    protected function getHardwareSpecs(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'kernel_version' => php_uname('r'),
            'cpu_model' => $this->getCpuModel(),
            'cpu_cores' => $this->getCpuCores(),
            'memory_limit' => ini_get('memory_limit'),
            'database' => config('database.default'),
            'timestamp' => date('Y-m-d H:i:s T'),
        ];
    }

    /**
     * Get CPU model name.
     */
    protected function getCpuModel(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $model = shell_exec("cat /proc/cpuinfo | grep 'model name' | head -n 1 | cut -d ':' -f 2");
            return $model ? trim($model) : 'Unknown';
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            $model = shell_exec('sysctl -n machdep.cpu.brand_string');
            return $model ? trim($model) : 'Unknown';
        }

        return 'Unknown';
    }

    /**
     * Get CPU core count.
     */
    protected function getCpuCores(): int
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $cores = shell_exec('nproc');
            return $cores ? (int) trim($cores) : 1;
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            $cores = shell_exec('sysctl -n hw.ncpu');
            return $cores ? (int) trim($cores) : 1;
        }

        return 1; // Default
    }
}

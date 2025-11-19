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
     * Generate test users.
     */
    protected function generateUsers(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
        }
        return $users;
    }

    /**
     * Generate test articles.
     */
    protected function generateArticles(int $count): array
    {
        $articles = [];
        for ($i = 0; $i < $count; $i++) {
            $articles[] = Article::create([
                'title' => "Article {$i}",
                'content' => "Content for article {$i}",
            ]);
        }
        return $articles;
    }

    /**
     * Assign permissions to users for resources.
     */
    protected function assignPermissions(array $users, array $resources, Permission $permission): void
    {
        foreach ($users as $user) {
            foreach ($resources as $resource) {
                $user->givePermissionToResource($permission->name, $resource);
            }
        }
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
}

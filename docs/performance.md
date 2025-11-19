# Performance Testing

This package includes comprehensive performance testing to ensure it scales well with large datasets.

## Test Suites

### CI Performance Tests
Automatically run on every push to catch performance regressions.

- **Dataset sizes:** 100, 1K, 10K records
- **Runtime:** ~30 seconds
- **Purpose:** Catch performance regressions early

**What's tested:**
- Permission checks remain fast
- Query counts don't increase with dataset size
- Indexes are properly used
- Memory usage stays reasonable

### Benchmark Tests (Local)
Run on-demand for detailed performance profiling.

- **Dataset sizes:** 10K, 100K, 1M records
- **Runtime:** Several minutes
- **Purpose:** Detailed performance analysis

**What's tested:**
- Performance at scale (up to 1M records)
- Memory usage patterns
- Query optimization
- Polymorphic query performance

## Running Tests

### Run CI Tests
```bash
vendor/bin/phpunit tests/Performance/CIPerformanceTest.php
```

### Run Benchmark Tests (Local)
```bash
# Run all benchmarks
vendor/bin/phpunit --group=benchmark

# Run specific benchmark
vendor/bin/phpunit tests/Performance/BenchmarkTest.php --filter=test_benchmark_permission_check_100k_records
```

### Run 1M Record Test
The 1M record test is skipped by default. To enable it:

1. Edit `tests/Performance/BenchmarkTest.php`
2. Comment out the `markTestSkipped()` line in `test_benchmark_permission_check_1m_records()`
3. Run: `vendor/bin/phpunit --group=benchmark --filter=1m_records`

**Warning:** This test may take 10+ minutes to run.

## Performance Expectations

### Permission Checks
- **100 records:** <50ms
- **1K records:** <100ms
- **10K records:** <150ms
- **100K records:** <500ms
- **1M records:** <1000ms

### Query Counts
Query counts should remain constant regardless of dataset size:
- `hasPermissionForResource()`: ≤3 queries
- `getPermissionsForResource()`: ≤2 queries
- `getAssignedModels()`: ≤3 queries

### Index Usage
All queries should use indexes. The tests verify this using `EXPLAIN` queries.

## Optimization Tips

### 1. Use Eager Loading
```php
// Bad: N+1 queries
foreach ($articles as $article) {
    $permissions = $user->getPermissionsForResource($article);
}

// Good: Eager load
$permissions = ModelHasResourceAndPermission::forModel($user)
    ->whereIn('resource_id', $articles->pluck('id'))
    ->with('permission')
    ->get();
```

### 2. Cache Permission Checks
```php
// Cache permission checks for frequently accessed resources
$cacheKey = "user.{$user->id}.article.{$article->id}.can.edit";
$canEdit = Cache::remember($cacheKey, 3600, function () use ($user, $article) {
    return $user->hasPermissionForResource('edit-article', $article);
});
```

### 3. Use Batch Operations
```php
// Assign permissions in batch
$user->syncPermissionsForResource(['view', 'edit'], $article);

// Instead of individual assignments
$user->givePermissionToResource('view', $article);
$user->givePermissionToResource('edit', $article);
```

### 4. Limit Result Sets
```php
// When fetching assigned models, filter to specific models
$assignedUsers = $article->getAssignedModels([$user1, $user2, $user3]);

// Instead of fetching all
$allAssignedUsers = $article->getAssignedModels();
```

## Interpreting Results

### Time Metrics
- **<100ms:** Excellent
- **100-500ms:** Good
- **500ms-1s:** Acceptable for complex queries
- **>1s:** Needs optimization

### Memory Metrics
- **<10MB:** Excellent
- **10-50MB:** Good
- **50-100MB:** Acceptable
- **>100MB:** Needs optimization

### Query Count
- Constant query count = Good index usage
- Increasing query count = Missing indexes or N+1 problem

## CI Integration

Performance tests run automatically on every push via GitHub Actions. The workflow:

1. Runs lightweight tests with SQLite (fastest)
2. Fails if performance degrades beyond thresholds
3. Reports timing and query metrics

View results at: `https://github.com/fishdaa/laravel-resource-permissions/actions`

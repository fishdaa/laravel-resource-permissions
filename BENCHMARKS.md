# Performance Benchmarks

> **Last Updated:** 2025-11-25 00:28:41 UTC

## Test Environment

| Specification | Value |
|---------------|-------|
| PHP Version | 8.3.9 |
| Operating System | Linux |
| Kernel Version | 6.6.87.2-microsoft-standard-WSL2 |
| CPU Model | AMD Ryzen 7 7735HS with Radeon Graphics |
| CPU Cores | 16 |
| Memory Limit | 128M |
| Database | testbench |

## Real-World Scenario: Polymorphic Permissions

This test simulates a real-world application where permissions are assigned across multiple model types (e.g., `User`, `Admin`, `Team`) and multiple resource types (e.g., `Article`, `Video`, `Post`).

The benchmark measures the performance of fetching all assigned models for a resource, which involves querying across these different types.

### Scalability Results

Performance at scale with large datasets (Mixed Models). Results are averaged over 10 iterations.

| Dataset Size | Avg Time | P90 Time | Min Time | Max Time | Queries | Peak Memory |
|--------------|----------|----------|----------|----------|---------|-------------|
| 100,000 records | 2.27ms | 2.75ms | 1.82ms | 3.65ms | 11 | 44.5MB |
| 500,000 records | 8.13ms | 8.41ms | 7.51ms | 11.52ms | 31 | 46.5MB |
| 1,000,000 records | 16.12ms | 16.73ms | 15.53ms | 17.2ms | 51 | 50.5MB |

## Key Findings

✅ **Constant Query Count**: Query count remains stable regardless of dataset size

✅ **Sub-second Performance**: All operations complete in under 1 second

✅ **Linear Scaling**: Performance scales linearly with dataset size

✅ **Memory Efficient**: Peak memory usage remains reasonable even with large datasets

## How to Run

To regenerate this report:

```bash
composer benchmark:report
```

For detailed performance testing documentation, see [docs/performance.md](docs/performance.md).

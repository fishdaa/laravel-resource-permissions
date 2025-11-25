# Laravel Resource Permissions

A Laravel package that extends [Spatie Laravel Permission](https://github.com/spatie/laravel-permission) with resource-based permissions and roles. This package allows you to assign permissions and roles to users for specific resources (polymorphic relationships), while maintaining full compatibility with Spatie's existing global permission system.

## Features

- Resource-based permissions: Assign permissions to users for specific resources (e.g., articles, projects, documents)
- Resource-based roles: Assign roles to users for specific resources
- Polymorphic relationships: Works with any Eloquent model as a resource
- Seamless integration: Works alongside Spatie's existing permission system
- Full Spatie compatibility: Uses Spatie's Permission and Role models directly

## Installation

```bash
composer require fishdaa/laravel-resource-permissions
```

### Publish Migrations

```bash
php artisan vendor:publish --tag=resource-permissions-migrations
```

### Run Migrations

```bash
php artisan migrate
```

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=resource-permissions-config
```

## Quick Start

### 1. Add Trait to User Model

```php
use Fishdaa\LaravelResourcePermissions\Traits\HasResourcePermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles; // Spatie's trait
    use HasResourcePermissions; // Resource permissions trait
}
```

### 2. Use Resource Permissions

```php
$user = User::find(1);
$article = Article::find(1);

// Give permission for a specific resource
$user->givePermissionToResource('edit-article', $article);

// Check permission for a resource
if ($user->hasPermissionForResource('edit-article', $article)) {
    // User can edit this article
}

// Assign role for a resource
$user->assignRoleToResource('article-editor', $article);

// Get all permissions for a resource
$permissions = $user->getPermissionsForResource($article);
```

## Documentation

For detailed documentation, please see the [docs folder](docs/README.md).

- [Installation Guide](docs/installation.md)
- [Configuration](docs/configuration.md)
- [UUID Setup](docs/uuid-setup.md) - Configure UUIDs instead of integer IDs
- [Usage & API Reference](docs/usage.md)
- [Spatie Integration](docs/integration.md)
- [Examples](docs/examples.md)
- [Advanced Usage](docs/advanced.md)
- [Troubleshooting](docs/troubleshooting.md)

## Requirements

- PHP >= 8.0
- Laravel >= 8.0 (supports Laravel 8, 9, 10, 11, and 12)
- Spatie Laravel Permission >= 6.0

## Testing

Run the test suite using Composer:

```bash
composer test
```

Or run PHPUnit directly:

```bash
vendor/bin/phpunit
```

To run specific test suites:

```bash
# Run unit tests only
vendor/bin/phpunit tests/Unit

# Run feature tests only
vendor/bin/phpunit tests/Feature

# Run integration tests only
vendor/bin/phpunit tests/Integration
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
Please make sure your code follows the existing code style and includes tests for new features.

## License

The MIT License (MIT). Please see [https://opensource.org/licenses/MIT](https://opensource.org/licenses/MIT) for more information.

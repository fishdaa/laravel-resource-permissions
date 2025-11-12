# Installation Guide

This guide will walk you through installing and setting up Laravel Resource Permissions.

## Prerequisites

- PHP >= 8.0
- Laravel >= 10.0
- [Spatie Laravel Permission](https://github.com/spatie/laravel-permission) >= 6.0 installed and configured

## Step 1: Install the Package

Install the package via Composer:

```bash
composer require fishdaa/laravel-resource-permissions
```

## Step 2: Publish Migrations

Publish the migration files:

```bash
php artisan vendor:publish --tag=resource-permissions-migrations
```

This will copy the migration file to your `database/migrations` directory.

## Step 3: Run Migrations

Run the migrations to create the `model_has_resource_and_permissions` table (or `user_has_resource_and_permissions` if configured):

```bash
php artisan migrate
```

## Step 4: Add Trait to User Model

Add the `HasResourcePermissions` trait to your User model:

```php
<?php

namespace App\Models;

use Fishdaa\LaravelResourcePermissions\Traits\HasResourcePermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles; // Spatie's trait (if not already added)
    use HasResourcePermissions; // Resource permissions trait
}
```

## Step 5: Publish Configuration (Optional)

If you want to customize the configuration, publish the config file:

```bash
php artisan vendor:publish --tag=resource-permissions-config
```

This will create `config/resource-permissions.php` in your application.

## Verification

To verify the installation, you can check:

1. The migration was run successfully:
   ```bash
   php artisan migrate:status
   ```

2. The table exists:
   ```bash
   php artisan tinker
   >>> Schema::hasTable(config('resource-permissions.table_name', 'model_has_resource_and_permissions'))
   ```

3. The trait is available:
   ```php
   $user = User::first();
   method_exists($user, 'hasPermissionForResource'); // Should return true
   ```

## Next Steps

- Read the [Usage Guide](usage.md) to learn how to use resource permissions
- Check out [Examples](examples.md) for real-world use cases
- Learn about [Spatie Integration](integration.md) to understand how it works with Spatie


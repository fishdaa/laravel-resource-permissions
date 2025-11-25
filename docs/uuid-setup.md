# UUID Setup Guide

This guide explains how to configure Laravel Resource Permissions to use UUIDs instead of auto-incrementing integers. The package supports separate configuration for primary keys and resource foreign keys.

## Overview

By default, Laravel Resource Permissions uses auto-incrementing integer IDs. However, you can configure it to use UUIDs (Universally Unique Identifiers) for better security, distributed system compatibility, and to avoid exposing sequential IDs.

The package provides two independent UUID options:
- **Primary Key UUIDs** (`use_uuids`) - Controls the `id` column and related foreign keys
- **Model UUIDs** (`use_uuids_for_models`) - Controls `model_id` and `resource_id` polymorphic columns

These options are independent, allowing you to mix and match based on your needs.

## Prerequisites

Before enabling UUIDs, ensure that:

- If enabling **primary key UUIDs** (`use_uuids`):
  - Your **Spatie Permission tables** (`permissions` and `roles`) use UUIDs
  - Your **users table** uses UUIDs (for `created_by` foreign key)
  
- If enabling **model UUIDs** (`use_uuids_for_models`):
  - Your **User model** (or other models) uses UUIDs as primary keys
  - Your **resource models** (e.g., Article, Project, Document) use UUIDs as primary keys

## Step 1: Configure UUIDs Before Migrations

**Important:** You must configure UUIDs **BEFORE** running migrations. Changing this setting after migrations have been run will require manual database changes.

### Option A: Publish and Edit Config File

1. Publish the configuration file:
   ```bash
   php artisan vendor:publish --tag=resource-permissions-config
   ```

2. Edit `config/resource-permissions.php` and configure UUID options:
   ```php
   // For primary key UUIDs
   'use_uuids' => true,
   
   // For model UUIDs (model_id, resource_id)
   'use_uuids_for_models' => true,
   ```
   
   You can enable either option independently, or both together.

### Option B: Set Before Publishing Migrations

If you haven't published the config file yet, you can set it directly in your application's config before publishing:

1. Create or edit `config/resource-permissions.php`:
   ```php
   <?php

   return [
       'table_name' => 'model_has_resource_and_permissions',
       'use_uuids' => true, // Enable UUIDs for primary key
       'use_uuids_for_models' => true, // Enable UUIDs for models
       // ... other config options
   ];
   ```

## Step 2: Publish and Run Migrations

After configuring UUIDs, publish and run the migrations:

```bash
# Publish migrations
php artisan vendor:publish --tag=resource-permissions-migrations

# Run migrations
php artisan migrate
```

The migration will automatically create the table with UUID columns based on your configuration.

## What Changes When Using UUIDs?

The following columns are affected by UUID configuration:

### Primary Key UUIDs (`use_uuids`)

When `use_uuids` is `true`:
- **Primary key** (`id`) - UUID instead of auto-incrementing integer
- **Permission ID** (`permission_id`) - UUID (requires Spatie permissions table to use UUIDs)
- **Role ID** (`role_id`) - UUID (requires Spatie roles table to use UUIDs)
- **Created By** (`created_by`) - UUID (requires users table to use UUIDs)

### Model UUIDs (`use_uuids_for_models`)

When `use_uuids_for_models` is `true`:
- **Model ID** (`model_id`) - UUID for polymorphic model references
- **Resource ID** (`resource_id`) - UUID for polymorphic resource references

### Configuration Examples

**Example 1: UUIDs for models only**
```php
'use_uuids' => false,              // Integer primary key
'use_uuids_for_models' => true,    // UUID models
```
This setup uses integer primary keys but UUIDs for `model_id` and `resource_id`.

**Example 2: UUIDs for primary key only**
```php
'use_uuids' => true,               // UUID primary key
'use_uuids_for_models' => false,   // Integer models
```
This setup uses UUID primary keys but integers for `model_id` and `resource_id`.

**Example 3: UUIDs everywhere**
```php
'use_uuids' => true,               // UUID primary key
'use_uuids_for_models' => true,    // UUID models
```
This setup uses UUIDs for all columns.

## Example: Setting Up UUIDs for User Model

If you're using UUIDs, your User model should look like this:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Fishdaa\LaravelResourcePermissions\Traits\HasResourcePermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasUuids; // Laravel's UUID trait
    use HasRoles;
    use HasResourcePermissions;

    // Your model code...
}
```

## Example: Setting Up UUIDs for Resource Models

Your resource models should also use UUIDs:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasUuids;

    // Your model code...
}
```

## Example: Setting Up UUIDs for Spatie Permission Tables

If you want the foreign keys (`permission_id`, `role_id`) to use UUIDs, you need to configure Spatie Laravel Permission to use UUIDs as well.

### For Spatie Permission Models

Create custom Permission and Role models that use UUIDs:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUuids;
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuids;
}
```

Then update your `config/resource-permissions.php`:

```php
'permission_model' => \App\Models\Permission::class,
'role_model' => \App\Models\Role::class,
```

And update Spatie's config (`config/permission.php`):

```php
'models' => [
    'permission' => \App\Models\Permission::class,
    'role' => \App\Models\Role::class,
],
```

## Migration Considerations

### If You Already Ran Migrations

If you've already run migrations with integer IDs and want to switch to UUIDs:

1. **Option 1: Fresh Migration (Development Only)**
   ```bash
   php artisan migrate:fresh
   ```
   ⚠️ **Warning:** This will drop all tables and data. Only use in development.

2. **Option 2: Manual Migration (Production)**
   - Create a new migration to alter the table structure
   - Convert existing integer IDs to UUIDs
   - Update foreign key constraints
   - This is complex and should be done carefully with backups

## Verification

After setting up UUIDs, verify the configuration:

```bash
php artisan tinker
```

```php
// Check if UUIDs are enabled
config('resource-permissions.use_uuids'); // Primary key UUIDs
config('resource-permissions.use_uuids_for_models'); // Model UUIDs

// Check table structure
Schema::getColumnType('model_has_resource_and_permissions', 'id'); 
// Should return 'guid' or 'uuid' if use_uuids is true

Schema::getColumnType('model_has_resource_and_permissions', 'model_id'); 
// Should return 'guid' or 'uuid' if use_uuids_for_models is true

Schema::getColumnType('model_has_resource_and_permissions', 'resource_id'); 
// Should return 'guid' or 'uuid' if use_uuids_for_models is true

// Test creating a resource permission
$user = User::first();
$article = Article::first();
$permission = Permission::first();

$user->givePermissionToResource('edit-article', $article);

// Check the created record
$record = \Fishdaa\LaravelResourcePermissions\Models\ModelHasResourceAndPermission::first();
$record->id; // UUID string if use_uuids is true, integer otherwise
$record->model_id; // UUID string if use_uuids_for_models is true, integer otherwise
$record->resource_id; // UUID string if use_uuids_for_models is true, integer otherwise
```

## Troubleshooting

### Error: "Column type mismatch"

If you see errors about column type mismatches, ensure that:
- If `use_uuids` is enabled: users, permissions, and roles tables use UUIDs
- If `use_uuids_for_models` is enabled: your User and resource models use UUIDs
- Both config options are set **before** running migrations
- You've republished migrations after changing the config

### Error: "Foreign key constraint fails"

This usually means:
- The referenced table (users, permissions, roles) doesn't use UUIDs
- You need to configure those tables to use UUIDs first
- Or disable UUIDs for foreign keys by keeping them as integers (requires custom migration)

### UUIDs Not Generating

If UUIDs aren't being generated automatically:
- Ensure `use_uuids` is set to `true` for primary key UUIDs
- Note: `use_uuids_for_models` doesn't generate UUIDs automatically - your models must generate them
- Check that the model's `boot()` method is being called
- Verify Laravel's `Str::uuid()` helper is available

## Best Practices

1. **Set UUIDs Before First Migration**: Always configure UUID options before running migrations for the first time
2. **Match Related Tables**: 
   - If `use_uuids` is enabled, ensure permissions, roles, and users tables use UUIDs
   - If `use_uuids_for_models` is enabled, ensure your User and resource models use UUIDs
3. **Independent Configuration**: Remember that primary key UUIDs and resource UUIDs are independent - configure based on your needs
4. **Test in Development**: Test UUID setup thoroughly in development before deploying to production
5. **Document Your Setup**: Document which models use UUIDs and which UUID options you've enabled
6. **Backup Before Changes**: Always backup your database before making structural changes

## Additional Resources

- [Laravel UUID Documentation](https://laravel.com/docs/eloquent#uuid-and-ulid-keys)
- [Spatie Laravel Permission Documentation](https://spatie.be/docs/laravel-permission)
- [Configuration Guide](configuration.md)
- [Installation Guide](installation.md)


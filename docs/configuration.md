# Configuration

The package comes with sensible defaults, but you can customize various aspects through the configuration file.

## Publishing Configuration

To customize the configuration, publish the config file:

```bash
php artisan vendor:publish --tag=resource-permissions-config
```

This creates `config/resource-permissions.php` in your application.

## Configuration Options

### Table Name

```php
'table_name' => 'model_has_resource_and_permissions', // or 'user_has_resource_and_permissions' if preferred
```

The name of the database table used to store resource permissions. 

**Default:** `model_has_resource_and_permissions` (follows Spatie's naming convention with `model_has_permissions`)

**Alternative:** Change to `user_has_resource_and_permissions` if you prefer user-specific naming.

**Important:** If you change this after running migrations, you'll need to:
1. Update the config file before running migrations
2. Or manually rename the table in your database
3. The model will automatically use the configured table name

### User Model

```php
'user_model' => config('auth.providers.users.model', \App\Models\User::class),
```

The user model class used by the package. By default, it uses Laravel's configured user model.

### Permission Model

```php
'permission_model' => \Spatie\Permission\Models\Permission::class,
```

The permission model from Spatie Laravel Permission. Only change this if you're using a custom permission model that extends Spatie's Permission model.

### Role Model

```php
'role_model' => \Spatie\Permission\Models\Role::class,
```

The role model from Spatie Laravel Permission. Only change this if you're using a custom role model that extends Spatie's Role model.

## Custom User Model

If you're using a custom user model, update the configuration:

```php
'user_model' => \App\Models\CustomUser::class,
```

Or ensure your `config/auth.php` has the correct model:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\CustomUser::class,
    ],
],
```

## Custom Permission/Role Models

If you've extended Spatie's Permission or Role models, update the configuration accordingly:

```php
'permission_model' => \App\Models\CustomPermission::class,
'role_model' => \App\Models\CustomRole::class,
```

Make sure your custom models extend Spatie's models:

```php
use Spatie\Permission\Models\Permission as SpatiePermission;

class CustomPermission extends SpatiePermission
{
    // Your customizations
}
```

## Environment Variables

You can also override configuration values using environment variables by modifying the config file to use `env()`:

```php
'user_model' => env('RESOURCE_PERMISSIONS_USER_MODEL', config('auth.providers.users.model')),
```

However, it's recommended to keep model references in code rather than environment variables for better IDE support and type safety.


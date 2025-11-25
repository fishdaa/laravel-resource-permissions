<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Resource Permissions Table
    |--------------------------------------------------------------------------
    |
    | This is the table name for storing resource-based permissions.
    | Defaults to 'model_has_resource_and_permissions' to follow Spatie's
    | naming convention. Change to 'user_has_resource_and_permissions' if
    | you prefer user-specific naming.
    |
    */
    'table_name' => 'model_has_resource_and_permissions',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This is the user model used by the package. You can override this
    | if you're using a custom user model.
    |
    */
    'user_model' => config('auth.providers.users.model', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Permission Model
    |--------------------------------------------------------------------------
    |
    | This is the permission model from Spatie Laravel Permission.
    | You can override this if you're using a custom permission model.
    |
    */
    'permission_model' => \Spatie\Permission\Models\Permission::class,

    /*
    |--------------------------------------------------------------------------
    | Role Model
    |--------------------------------------------------------------------------
    |
    | This is the role model from Spatie Laravel Permission.
    | You can override this if you're using a custom role model.
    |
    */
    'role_model' => \Spatie\Permission\Models\Role::class,

    /*
    |--------------------------------------------------------------------------
    | Use UUIDs for Primary Key
    |--------------------------------------------------------------------------
    |
    | When set to true, the package will use UUIDs instead of auto-incrementing
    | integers for the primary key (id column).
    |
    | Important: Set this BEFORE running migrations. Changing this after migrations
    | have been run will require manual database changes.
    |
    */
    'use_uuids' => false,

    /*
    |--------------------------------------------------------------------------
    | Use UUIDs for Models
    |--------------------------------------------------------------------------
    |
    | When set to true, the package will use UUIDs instead of integers for
    | polymorphic foreign keys (model_id and resource_id columns).
    |
    | This is independent of the primary key UUID setting. You can use UUIDs
    | for models while keeping integer primary keys, or vice versa.
    |
    | Important: If you enable this, ensure that:
    | - Your User model (or other models) uses UUIDs as primary keys
    | - Your resource models use UUIDs as primary keys
    |
    | Set this BEFORE running migrations. Changing this after migrations have
    | been run will require manual database changes.
    |
    */
    'use_uuids_for_models' => false,
];


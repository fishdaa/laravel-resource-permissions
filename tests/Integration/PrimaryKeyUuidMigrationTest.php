<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Integration;

use Illuminate\Support\Facades\Schema;

class PrimaryKeyUuidMigrationTest extends UuidMigrationTestBase
{
    protected function getUuidConfig(): array
    {
        return [
            'use_uuids' => true,
            'use_uuids_for_models' => false,
        ];
    }

    /**
     * Test that primary key uses UUID when use_uuids is enabled.
     */
    public function test_primary_key_uses_uuid_when_enabled(): void
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        
        // Verify table exists
        $this->assertTrue(Schema::hasTable($tableName));
        
        // Check column type
        $columnType = $this->getColumnType($tableName, 'id');
        
        // Should be UUID/guid type
        $this->assertTrue(
            in_array(strtolower($columnType), ['guid', 'uuid', 'char', 'varchar']),
            "Expected UUID type for id column, got: {$columnType}"
        );
        
        // Verify it's not an integer type
        $this->assertNotEquals('integer', strtolower($columnType));
        $this->assertNotEquals('bigint', strtolower($columnType));
    }

    /**
     * Test that foreign keys use UUID when use_uuids is enabled.
     */
    public function test_foreign_keys_use_uuid_when_primary_key_uuids_enabled(): void
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        
        // Check permission_id, role_id, created_by columns
        $permissionIdType = $this->getColumnType($tableName, 'permission_id');
        $roleIdType = $this->getColumnType($tableName, 'role_id');
        $createdByType = $this->getColumnType($tableName, 'created_by');
        
        foreach ([$permissionIdType, $roleIdType, $createdByType] as $type) {
            $this->assertTrue(
                in_array(strtolower($type), ['guid', 'uuid', 'char', 'varchar']),
                "Expected UUID type for foreign key column, got: {$type}"
            );
        }
    }

    /**
     * Test that model_id and resource_id use integers when use_uuids_for_models is disabled.
     */
    public function test_model_uuids_uses_integer_when_disabled(): void
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        
        // Check model_id and resource_id columns
        $modelIdType = $this->getColumnType($tableName, 'model_id');
        $resourceIdType = $this->getColumnType($tableName, 'resource_id');
        
        foreach ([$modelIdType, $resourceIdType] as $type) {
            $this->assertTrue(
                in_array(strtolower($type), ['integer', 'bigint', 'int']),
                "Expected integer type for polymorphic column, got: {$type}"
            );
        }
    }
}


<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Integration;

use Illuminate\Support\Facades\Schema;

class ModelUuidMigrationTest extends UuidMigrationTestBase
{
    protected function getUuidConfig(): array
    {
        return [
            'use_uuids' => false,
            'use_uuids_for_models' => true,
        ];
    }

    /**
     * Test that model_id and resource_id use UUID when use_uuids_for_models is enabled.
     */
    public function test_model_uuids_uses_uuid_for_polymorphic_columns(): void
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        
        // Check model_id and resource_id columns
        $modelIdType = $this->getColumnType($tableName, 'model_id');
        $resourceIdType = $this->getColumnType($tableName, 'resource_id');
        
        foreach ([$modelIdType, $resourceIdType] as $type) {
            $this->assertTrue(
                in_array(strtolower($type), ['guid', 'uuid', 'char', 'varchar']),
                "Expected UUID type for polymorphic column, got: {$type}"
            );
        }
    }

    /**
     * Test that primary key uses integer when use_uuids is disabled.
     */
    public function test_primary_key_uses_integer_when_disabled(): void
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        
        // Check column type
        $columnType = $this->getColumnType($tableName, 'id');
        
        // Should be integer type
        $this->assertTrue(
            in_array(strtolower($columnType), ['integer', 'bigint', 'int']),
            "Expected integer type for id column, got: {$columnType}"
        );
    }
}


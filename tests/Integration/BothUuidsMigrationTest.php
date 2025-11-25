<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Integration;

class BothUuidsMigrationTest extends UuidMigrationTestBase
{
    protected function getUuidConfig(): array
    {
        return [
            'use_uuids' => true,
            'use_uuids_for_models' => true,
        ];
    }

    /**
     * Test that both UUID options work together.
     */
    public function test_both_uuids_enabled_creates_all_uuid_columns(): void
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        
        // Check all columns that should be UUIDs
        $idType = $this->getColumnType($tableName, 'id');
        $modelIdType = $this->getColumnType($tableName, 'model_id');
        $resourceIdType = $this->getColumnType($tableName, 'resource_id');
        $permissionIdType = $this->getColumnType($tableName, 'permission_id');
        $roleIdType = $this->getColumnType($tableName, 'role_id');
        $createdByType = $this->getColumnType($tableName, 'created_by');
        
        $uuidColumns = [$idType, $modelIdType, $resourceIdType, $permissionIdType, $roleIdType, $createdByType];
        
        foreach ($uuidColumns as $type) {
            $this->assertTrue(
                in_array(strtolower($type), ['guid', 'uuid', 'char', 'varchar']),
                "Expected UUID type, got: {$type}"
            );
        }
    }
}


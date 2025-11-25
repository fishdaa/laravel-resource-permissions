<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Integration;

use Fishdaa\LaravelResourcePermissions\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

abstract class UuidMigrationTestBase extends TestCase
{
    /**
     * Get UUID configuration for this test class.
     *
     * @return array{use_uuids: bool, use_uuids_for_models: bool}
     */
    abstract protected function getUuidConfig(): array;

    /**
     * Define environment setup with UUID configuration.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $config = $this->getUuidConfig();
        $app['config']->set('resource-permissions.use_uuids', $config['use_uuids']);
        $app['config']->set('resource-permissions.use_uuids_for_models', $config['use_uuids_for_models']);
    }

    /**
     * Get the column type for a specific column in a table.
     *
     * @param string $tableName
     * @param string $columnName
     * @return string
     */
    protected function getColumnType(string $tableName, string $columnName): string
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'sqlite') {
            $result = DB::select("PRAGMA table_info({$tableName})");
            foreach ($result as $column) {
                if ($column->name === $columnName) {
                    $type = strtolower($column->type);
                    // SQLite stores UUIDs as TEXT, so check the actual type from migration
                    // For UUID columns, Laravel uses 'uuid' type which SQLite stores as TEXT
                    if ($type === 'text' && $this->isUuidColumn($tableName, $columnName)) {
                        return 'uuid';
                    }
                    return $type;
                }
            }
        } elseif ($driver === 'mysql') {
            $result = DB::select("
                SELECT DATA_TYPE, COLUMN_TYPE
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = ?
            ", [$tableName, $columnName]);
            
            if (!empty($result)) {
                $dataType = strtolower($result[0]->DATA_TYPE);
                $columnType = strtolower($result[0]->COLUMN_TYPE);
                // MySQL stores UUIDs as char(36) or varchar(36)
                if (str_contains($columnType, 'char') && str_contains($columnType, '36')) {
                    return 'uuid';
                }
                return $dataType;
            }
        } elseif ($driver === 'pgsql') {
            $result = DB::select("
                SELECT data_type, udt_name
                FROM information_schema.columns 
                WHERE table_name = ? 
                AND column_name = ?
            ", [$tableName, $columnName]);
            
            if (!empty($result)) {
                $dataType = strtolower($result[0]->data_type);
                $udtName = strtolower($result[0]->udt_name);
                // PostgreSQL uses 'uuid' type or 'character varying'
                if ($udtName === 'uuid' || ($dataType === 'character varying' && $this->isUuidColumn($tableName, $columnName))) {
                    return 'uuid';
                }
                return $dataType;
            }
        }
        
        // Fallback: try to get from Schema facade
        try {
            $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
            $doctrineTable = $doctrineSchemaManager->introspectTable($tableName);
            $column = $doctrineTable->getColumn($columnName);
            $typeName = strtolower($column->getType()->getName());
            // Check if it's a UUID column by checking the type name
            if (in_array($typeName, ['guid', 'uuid']) || ($typeName === 'string' && $this->isUuidColumn($tableName, $columnName))) {
                return 'uuid';
            }
            return $typeName;
        } catch (\Exception $e) {
            $this->fail("Could not determine column type for {$tableName}.{$columnName}: " . $e->getMessage());
        }
        
        return '';
    }

    /**
     * Check if a column is configured as UUID by checking the config.
     *
     * @param string $tableName
     * @param string $columnName
     * @return bool
     */
    protected function isUuidColumn(string $tableName, string $columnName): bool
    {
        $config = $this->getUuidConfig();
        
        // Primary key and foreign keys use use_uuids
        if (in_array($columnName, ['id', 'permission_id', 'role_id', 'created_by'])) {
            return $config['use_uuids'];
        }
        
        // Polymorphic columns use use_uuids_for_models
        if (in_array($columnName, ['model_id', 'resource_id'])) {
            return $config['use_uuids_for_models'];
        }
        
        return false;
    }
}


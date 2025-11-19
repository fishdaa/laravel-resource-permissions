<?php

namespace Fishdaa\LaravelResourcePermissions\Tests\Integration;

use Fishdaa\LaravelResourcePermissions\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrationTest extends TestCase
{
    /**
     * Test that the migration runs successfully.
     */
    public function test_migration_runs_successfully()
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        
        // Migration should have run in setUp
        $this->assertTrue(Schema::hasTable($tableName));
    }

    /**
     * Test that all expected columns exist.
     */
    public function test_table_has_expected_columns()
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        
        $this->assertTrue(Schema::hasColumn($tableName, 'id'));
        $this->assertTrue(Schema::hasColumn($tableName, 'model_type'));
        $this->assertTrue(Schema::hasColumn($tableName, 'model_id'));
        $this->assertTrue(Schema::hasColumn($tableName, 'resource_type'));
        $this->assertTrue(Schema::hasColumn($tableName, 'resource_id'));
        $this->assertTrue(Schema::hasColumn($tableName, 'permission_id'));
        $this->assertTrue(Schema::hasColumn($tableName, 'role_id'));
        $this->assertTrue(Schema::hasColumn($tableName, 'created_by'));
        $this->assertTrue(Schema::hasColumn($tableName, 'created_at'));
        $this->assertTrue(Schema::hasColumn($tableName, 'updated_at'));
    }

    /**
     * Test that indexes are created with proper names.
     */
    public function test_indexes_are_created_with_short_names()
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        // Get all indexes using raw SQL based on driver
        $indexes = [];
        
        if ($driver === 'mysql') {
            $results = DB::select("SHOW INDEX FROM {$tableName}");
            foreach ($results as $result) {
                $indexes[$result->Key_name] = strlen($result->Key_name);
            }
        } elseif ($driver === 'pgsql') {
            $results = DB::select("
                SELECT indexname 
                FROM pg_indexes 
                WHERE tablename = ?
            ", [$tableName]);
            foreach ($results as $result) {
                $indexes[$result->indexname] = strlen($result->indexname);
            }
        } elseif ($driver === 'sqlite') {
            $results = DB::select("
                SELECT name 
                FROM sqlite_master 
                WHERE type = 'index' AND tbl_name = ?
            ", [$tableName]);
            foreach ($results as $result) {
                $indexes[$result->name] = strlen($result->name);
            }
        }
        
        // Verify all index names are within database limits (64 chars for MySQL)
        foreach ($indexes as $indexName => $length) {
            $this->assertLessThanOrEqual(
                64,
                $length,
                "Index name '{$indexName}' exceeds 64 character limit"
            );
        }
        
        // Verify our custom index names exist
        $indexNames = array_keys($indexes);
        
        // Check for our custom short index names
        $this->assertContains('mhrp_model_idx', $indexNames, "Model index not found");
        $this->assertContains('mhrp_resource_idx', $indexNames, "Resource index not found");
        $this->assertContains('mhrp_permission_idx', $indexNames, "Permission index not found");
        $this->assertContains('mhrp_role_idx', $indexNames, "Role index not found");
    }

    /**
     * Test that unique constraints exist.
     */
    public function test_unique_constraints_exist()
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        $uniqueIndexes = [];
        
        if ($driver === 'mysql') {
            $results = DB::select("SHOW INDEX FROM {$tableName} WHERE Non_unique = 0");
            foreach ($results as $result) {
                $uniqueIndexes[] = $result->Key_name;
            }
        } elseif ($driver === 'pgsql') {
            $results = DB::select("
                SELECT indexname 
                FROM pg_indexes 
                WHERE tablename = ? 
                AND indexdef LIKE '%UNIQUE%'
            ", [$tableName]);
            foreach ($results as $result) {
                $uniqueIndexes[] = $result->indexname;
            }
        } elseif ($driver === 'sqlite') {
            $results = DB::select("
                SELECT name 
                FROM sqlite_master 
                WHERE type = 'index' 
                AND tbl_name = ? 
                AND sql LIKE '%UNIQUE%'
            ", [$tableName]);
            foreach ($results as $result) {
                $uniqueIndexes[] = $result->name;
            }
        }
        
        // Should have our two unique constraints
        $this->assertContains('model_resource_permission_unique', $uniqueIndexes);
        $this->assertContains('model_resource_role_unique', $uniqueIndexes);
    }

    /**
     * Test that foreign keys are created.
     */
    public function test_foreign_keys_exist()
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        // Skip for SQLite as foreign key introspection is complex
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite foreign key introspection not supported in this test');
        }
        
        $foreignKeyCount = 0;
        
        if ($driver === 'mysql') {
            $results = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [$tableName]);
            $foreignKeyCount = count($results);
        } elseif ($driver === 'pgsql') {
            $results = DB::select("
                SELECT conname 
                FROM pg_constraint 
                WHERE conrelid = ?::regclass 
                AND contype = 'f'
            ", [$tableName]);
            $foreignKeyCount = count($results);
        }
        
        $this->assertGreaterThanOrEqual(3, $foreignKeyCount, 'Should have at least 3 foreign keys');
    }
}

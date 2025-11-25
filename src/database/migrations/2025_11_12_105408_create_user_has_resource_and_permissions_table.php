<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        $useUuids = config('resource-permissions.use_uuids', false);
        $useUuidsForModels = config('resource-permissions.use_uuids_for_models', false);
        
        Schema::create($tableName, function (Blueprint $table) use ($useUuids, $useUuidsForModels) {
            // Primary key
            if ($useUuids) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }
            
            $table->string('model_type');
            
            // Model ID (polymorphic) - uses separate model UUID option
            if ($useUuidsForModels) {
                $table->uuid('model_id');
            } else {
                $table->unsignedBigInteger('model_id');
            }
            
            $table->string('resource_type');
            
            // Resource ID (polymorphic) - uses separate model UUID option
            if ($useUuidsForModels) {
                $table->uuid('resource_id');
            } else {
                $table->unsignedBigInteger('resource_id');
            }
            
            // Permission and role foreign keys - use UUIDs if primary key uses UUIDs
            // Note: Ensure your permissions, roles, and users tables also use UUIDs
            // if you enable UUIDs for primary key
            if ($useUuids) {
                $table->uuid('permission_id')->nullable();
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                
                $table->uuid('role_id')->nullable();
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            } else {
                $table->foreignId('permission_id')->nullable()->constrained('permissions')->onDelete('cascade');
                $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('cascade');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            }
            
            $table->timestamps();

            // Unique constraints
            $table->unique(['model_type', 'model_id', 'resource_type', 'resource_id', 'permission_id'], 'model_resource_permission_unique');
            $table->unique(['model_type', 'model_id', 'resource_type', 'resource_id', 'role_id'], 'model_resource_role_unique');

            // Indexes for polymorphic queries
            $table->index(['model_type', 'model_id'], 'mhrp_model_idx');
            $table->index(['resource_type', 'resource_id'], 'mhrp_resource_idx');
            $table->index('permission_id', 'mhrp_permission_idx');
            $table->index('role_id', 'mhrp_role_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('resource-permissions.table_name', 'model_has_resource_and_permissions');
        Schema::dropIfExists($tableName);
    }
};


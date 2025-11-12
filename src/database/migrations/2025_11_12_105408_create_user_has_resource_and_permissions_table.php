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
        
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');
            $table->foreignId('permission_id')->nullable()->constrained('permissions')->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Unique constraints
            $table->unique(['model_type', 'model_id', 'resource_type', 'resource_id', 'permission_id'], 'model_resource_permission_unique');
            $table->unique(['model_type', 'model_id', 'resource_type', 'resource_id', 'role_id'], 'model_resource_role_unique');

            // Indexes for polymorphic queries
            $table->index(['model_type', 'model_id']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('permission_id');
            $table->index('role_id');
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


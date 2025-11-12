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
        Schema::create('user_has_resource_and_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');
            $table->foreignId('permission_id')->nullable()->constrained('permissions')->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Unique constraints
            $table->unique(['user_id', 'resource_type', 'resource_id', 'permission_id'], 'user_resource_permission_unique');
            $table->unique(['user_id', 'resource_type', 'resource_id', 'role_id'], 'user_resource_role_unique');

            // Indexes for polymorphic queries
            $table->index(['resource_type', 'resource_id']);
            $table->index('user_id');
            $table->index('permission_id');
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_has_resource_and_permissions');
    }
};


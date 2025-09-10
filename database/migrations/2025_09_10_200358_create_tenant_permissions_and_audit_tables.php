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
        // Create tenant-specific roles table
        Schema::create('tenant_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('tenant');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        // Create tenant-specific permissions table
        Schema::create('tenant_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('tenant');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // e.g., 'contacts', 'deals', 'activities'
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        // Create role-permission pivot table
        Schema::create('tenant_role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')->references('id')->on('tenant_permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('tenant_roles')->onDelete('cascade');

            $table->primary(['permission_id', 'role_id']);
        });

        // Create user-role pivot table
        Schema::create('tenant_model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);

            $table->foreign('role_id')->references('id')->on('tenant_roles')->onDelete('cascade');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        // Create user-permission pivot table
        Schema::create('tenant_model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);

            $table->foreign('permission_id')->references('id')->on('tenant_permissions')->onDelete('cascade');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        // Create audit logs table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            $table->string('user_email');
            $table->string('action'); // created, updated, deleted, viewed, etc.
            $table->string('resource_type'); // Contact, Deal, Activity, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('resource_name')->nullable(); // Human-readable name
            $table->json('old_values')->nullable(); // Previous values
            $table->json('new_values')->nullable(); // New values
            $table->json('metadata')->nullable(); // Additional context
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable(); // HTTP method
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index(['action', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('tenant_model_has_permissions');
        Schema::dropIfExists('tenant_model_has_roles');
        Schema::dropIfExists('tenant_role_has_permissions');
        Schema::dropIfExists('tenant_permissions');
        Schema::dropIfExists('tenant_roles');
    }
};

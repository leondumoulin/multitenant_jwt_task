<?php

namespace Tests;

use App\Models\Admin\Admin;
use App\Models\Admin\Tenant;
use App\Services\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $tenant1;
    protected $tenant2;
    protected $dbManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbManager = app(DatabaseManager::class);

        // Create test admin
        $this->admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);

        // Create test tenants
        $this->createTestTenants();
    }

    protected function tearDown(): void
    {
        // Clean up tenant databases
        $this->cleanupTenantDatabases();

        parent::tearDown();
    }

    /**
     * Create test tenants for isolation testing
     */
    protected function createTestTenants(): void
    {
        // Create Tenant 1
        $this->tenant1 = Tenant::create([
            'name' => 'Test Company 1',
            'slug' => 'test-company-1',
            'db_name' => 'tenant_test_company_1',
            'db_user' => 'u' . substr(md5('Test Company 1'), 0, 15),
            'db_pass' => 'password123',
            'status' => true,
            'admin_email' => 'admin1@testcompany1.com',
        ]);

        // Create Tenant 2
        $this->tenant2 = Tenant::create([
            'name' => 'Test Company 2',
            'slug' => 'test-company-2',
            'db_name' => 'tenant_test_company_2',
            'db_user' => 'u' . substr(md5('Test Company 2'), 0, 15),
            'db_pass' => 'password456',
            'status' => true,
            'admin_email' => 'admin2@testcompany2.com',
        ]);

        // Create tenant databases and run migrations
        $this->setupTenantDatabase($this->tenant1);
        $this->setupTenantDatabase($this->tenant2);
    }

    /**
     * Setup tenant database with migrations and seeders
     */
    protected function setupTenantDatabase(Tenant $tenant): void
    {
        // Create database
        $this->dbManager->createTenantDatabase($tenant);

        // Set tenant connection
        $this->dbManager->setTenantConnection($tenant);

        // Run tenant migrations
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        // Seed roles and permissions
        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => 'TenantRolesAndPermissionsSeeder',
            '--force' => true,
        ]);

        // Seed tenant data
        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => 'TenantDataSeeder',
            '--force' => true,
        ]);
    }

    /**
     * Clean up tenant databases
     */
    protected function cleanupTenantDatabases(): void
    {
        if ($this->tenant1) {
            $this->dbManager->dropTenantDatabase($this->tenant1);
        }
        if ($this->tenant2) {
            $this->dbManager->dropTenantDatabase($this->tenant2);
        }
    }

    /**
     * Switch to tenant database context
     */
    protected function switchToTenant(Tenant $tenant): void
    {
        $this->dbManager->setTenantConnection($tenant);
    }

    /**
     * Create a user in a specific tenant
     */
    protected function createTenantUser(Tenant $tenant, array $data = []): \App\Models\Tenant\User
    {
        $this->switchToTenant($tenant);

        $userData = array_merge([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
        ], $data);

        return \App\Models\Tenant\User::on('tenant')->create($userData);
    }

    /**
     * Create a contact in a specific tenant
     */
    protected function createTenantContact(Tenant $tenant, array $data = []): \App\Models\Tenant\Contact
    {
        $this->switchToTenant($tenant);

        $contactData = array_merge([
            'user_id' => 1,
            'name' => 'Test Contact',
            'email' => 'contact@test.com',
            'phone' => '1234567890',
        ], $data);

        return \App\Models\Tenant\Contact::on('tenant')->create($contactData);
    }

    /**
     * Create a deal in a specific tenant
     */
    protected function createTenantDeal(Tenant $tenant, array $data = []): \App\Models\Tenant\Deal
    {
        $this->switchToTenant($tenant);

        $dealData = array_merge([
            'user_id' => 1,
            'contact_id' => 1,
            'title' => 'Test Deal',
            'value' => 1000.00,
            'stage' => 'prospecting',
        ], $data);

        return \App\Models\Tenant\Deal::on('tenant')->create($dealData);
    }

    /**
     * Create an activity in a specific tenant
     */
    protected function createTenantActivity(Tenant $tenant, array $data = []): \App\Models\Tenant\Activity
    {
        $this->switchToTenant($tenant);

        $activityData = array_merge([
            'user_id' => 1,
            'contact_id' => 1,
            'deal_id' => null,
            'type' => 'call',
            'description' => 'Test activity',
            'date' => now(),
        ], $data);

        return \App\Models\Tenant\Activity::on('tenant')->create($activityData);
    }

    /**
     * Get admin JWT token
     */
    protected function getAdminToken(): string
    {
        $response = $this->postJson('/api/admin/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);

        return $response->json('data.token');
    }

    /**
     * Get tenant user JWT token
     */
    protected function getTenantToken(Tenant $tenant, string $email = 'user@test.com', string $password = 'password'): string
    {
        $this->switchToTenant($tenant);

        $response = $this->postJson('/api/tenant/login', [
            'email' => $email,
            'password' => $password,
        ]);

        return $response->json('data.token');
    }

    /**
     * Assert that data exists in tenant database
     */
    protected function assertDataExistsInTenant(Tenant $tenant, string $table, array $conditions): void
    {
        $this->switchToTenant($tenant);

        $this->assertDatabaseHas($table, $conditions, 'tenant');
    }

    /**
     * Assert that data does not exist in tenant database
     */
    protected function assertDataNotExistsInTenant(Tenant $tenant, string $table, array $conditions): void
    {
        $this->switchToTenant($tenant);

        $this->assertDatabaseMissing($table, $conditions, 'tenant');
    }

    /**
     * Assert tenant isolation - data from one tenant should not be visible in another
     */
    protected function assertTenantIsolation(string $table, array $tenant1Data, array $tenant2Data): void
    {
        // Create data in tenant 1
        $this->switchToTenant($this->tenant1);
        $this->assertDatabaseHas($table, $tenant1Data, 'tenant');

        // Create data in tenant 2
        $this->switchToTenant($this->tenant2);
        $this->assertDatabaseHas($table, $tenant2Data, 'tenant');

        // Verify tenant 1 data is not visible in tenant 2
        $this->assertDatabaseMissing($table, $tenant1Data, 'tenant');

        // Verify tenant 2 data is not visible in tenant 1
        $this->switchToTenant($this->tenant1);
        $this->assertDatabaseMissing($table, $tenant2Data, 'tenant');
    }

    /**
     * Assert that admin data is separate from tenant data
     */
    protected function assertAdminTenantSeparation(): void
    {
        // Admin data should exist in main database
        $this->assertDatabaseHas('admins', [
            'email' => $this->admin->email,
        ]);

        // Admin data should not exist in tenant databases
        $this->switchToTenant($this->tenant1);
        $this->assertDatabaseMissing('admins', [
            'email' => $this->admin->email,
        ], 'tenant');

        $this->switchToTenant($this->tenant2);
        $this->assertDatabaseMissing('admins', [
            'email' => $this->admin->email,
        ], 'tenant');
    }
}

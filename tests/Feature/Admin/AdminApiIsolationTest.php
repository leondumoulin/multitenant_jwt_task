<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Admin\Tenant;

class AdminApiIsolationTest extends TestCase
{
    /**
     * Test that admin can access tenant management endpoints
     */
    public function test_admin_can_access_tenant_management_endpoints(): void
    {
        $adminToken = $this->getAdminToken();

        // Test tenant listing
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/admin/tenants');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'status',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);

        // Verify both test tenants are returned
        $tenants = $response->json('data');
        $this->assertCount(2, $tenants);

        $tenantNames = collect($tenants)->pluck('name')->toArray();
        $this->assertContains('Test Company 1', $tenantNames);
        $this->assertContains('Test Company 2', $tenantNames);
    }

    /**
     * Test that admin can view individual tenant details
     */
    public function test_admin_can_view_individual_tenant_details(): void
    {
        $adminToken = $this->getAdminToken();

        // Test viewing tenant 1
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson("/api/admin/tenants/{$this->tenant1->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $this->tenant1->id);
        $response->assertJsonPath('data.name', $this->tenant1->name);
        $response->assertJsonPath('data.slug', $this->tenant1->slug);

        // Test viewing tenant 2
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson("/api/admin/tenants/{$this->tenant2->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $this->tenant2->id);
        $response->assertJsonPath('data.name', $this->tenant2->name);
        $response->assertJsonPath('data.slug', $this->tenant2->slug);
    }

    /**
     * Test that admin can check tenant creation status
     */
    public function test_admin_can_check_tenant_creation_status(): void
    {
        $adminToken = $this->getAdminToken();

        // Test checking status for tenant 1
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson("/api/admin/tenants/{$this->tenant1->id}/status");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'tenant_id',
                'tenant_name',
                'status',
                'status_message',
            ]
        ]);

        $response->assertJsonPath('data.tenant_id', $this->tenant1->id);
        $response->assertJsonPath('data.tenant_name', $this->tenant1->name);
        $response->assertJsonPath('data.status', 'active');
    }

    /**
     * Test that admin can suspend and activate tenants
     */
    public function test_admin_can_suspend_and_activate_tenants(): void
    {
        $adminToken = $this->getAdminToken();

        // Suspend tenant 1
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->patchJson("/api/admin/tenants/{$this->tenant1->id}/suspend");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Tenant suspended successfully');

        // Verify tenant is suspended
        $this->tenant1->refresh();
        $this->assertFalse($this->tenant1->status);

        // Activate tenant 1
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->patchJson("/api/admin/tenants/{$this->tenant1->id}/activate");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Tenant activated successfully');

        // Verify tenant is active
        $this->tenant1->refresh();
        $this->assertTrue($this->tenant1->status);
    }

    /**
     * Test that admin cannot access tenant-specific endpoints
     */
    public function test_admin_cannot_access_tenant_specific_endpoints(): void
    {
        $adminToken = $this->getAdminToken();

        // Try to access tenant contacts endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/tenant/contacts');

        $response->assertStatus(401);

        // Try to access tenant deals endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/tenant/deals');

        $response->assertStatus(401);

        // Try to access tenant activities endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/tenant/activities');

        $response->assertStatus(401);

        // Try to access tenant roles endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/tenant/roles');

        $response->assertStatus(401);

        // Try to access tenant audit logs endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/tenant/audit-logs');

        $response->assertStatus(401);
    }

    /**
     * Test that admin can create new tenants
     */
    public function test_admin_can_create_new_tenants(): void
    {
        $adminToken = $this->getAdminToken();

        $tenantData = [
            'name' => 'New Test Company',
            'admin_name' => 'New Admin',
            'admin_email' => 'admin@newtestcompany.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson('/api/admin/tenants', $tenantData);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Tenant creation started. You will be notified when the process is complete.');

        // Verify tenant record was created
        $this->assertDatabaseHas('tenants', [
            'name' => 'New Test Company',
            'slug' => 'new-test-company',
            'admin_email' => 'admin@newtestcompany.com',
        ]);

        // Clean up the created tenant
        $tenant = Tenant::where('name', 'New Test Company')->first();
        if ($tenant) {
            $tenant->delete();
        }
    }

    /**
     * Test that admin tenant creation validation works
     */
    public function test_admin_tenant_creation_validation_works(): void
    {
        $adminToken = $this->getAdminToken();

        // Test with missing required fields
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson('/api/admin/tenants', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'admin_name', 'admin_email', 'admin_password']);

        // Test with invalid email
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson('/api/admin/tenants', [
            'name' => 'Test Company',
            'admin_name' => 'Test Admin',
            'admin_email' => 'invalid-email',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['admin_email']);

        // Test with password mismatch
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson('/api/admin/tenants', [
            'name' => 'Test Company',
            'admin_name' => 'Test Admin',
            'admin_email' => 'admin@testcompany.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['admin_password']);
    }

    /**
     * Test that admin can access admin-specific endpoints
     */
    public function test_admin_can_access_admin_specific_endpoints(): void
    {
        $adminToken = $this->getAdminToken();

        // Test admin login endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson('/api/admin/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'admin' => [
                    'id',
                    'name',
                    'email',
                ]
            ]
        ]);

        // Test admin logout endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson('/api/admin/logout');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    /**
     * Test that admin authentication is isolated from tenant authentication
     */
    public function test_admin_authentication_is_isolated_from_tenant_authentication(): void
    {
        // Create tenant user
        $tenantUser = $this->createTenantUser($this->tenant1, [
            'name' => 'Tenant User',
            'email' => 'user@tenant1.com',
        ]);

        // Admin should not be able to login with tenant credentials
        $response = $this->postJson('/api/admin/login', [
            'email' => $tenantUser->email,
            'password' => 'password',
        ]);

        $response->assertStatus(401);

        // Tenant user should not be able to login with admin credentials
        $response = $this->postJson('/api/tenant/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test that admin data is isolated from tenant data
     */
    public function test_admin_data_is_isolated_from_tenant_data(): void
    {
        $adminToken = $this->getAdminToken();

        // Admin should be able to see admin data
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/admin/tenants');

        $response->assertStatus(200);
        $tenants = $response->json('data');
        $this->assertGreaterThan(0, count($tenants));

        // Admin should not be able to see tenant user data
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/tenant/contacts');

        $response->assertStatus(401);

        // Verify admin data exists in main database
        $this->assertDatabaseHas('admins', [
            'email' => $this->admin->email,
        ]);

        // Verify admin data does not exist in tenant databases
        $this->switchToTenant($this->tenant1);
        $this->assertDatabaseMissing('admins', [
            'email' => $this->admin->email,
        ], 'tenant');

        $this->switchToTenant($this->tenant2);
        $this->assertDatabaseMissing('admins', [
            'email' => $this->admin->email,
        ], 'tenant');
    }

    /**
     * Test that admin can manage multiple tenants independently
     */
    public function test_admin_can_manage_multiple_tenants_independently(): void
    {
        $adminToken = $this->getAdminToken();

        // Suspend tenant 1
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->patchJson("/api/admin/tenants/{$this->tenant1->id}/suspend");

        $response->assertStatus(200);

        // Verify tenant 1 is suspended but tenant 2 is still active
        $this->tenant1->refresh();
        $this->tenant2->refresh();

        $this->assertFalse($this->tenant1->status);
        $this->assertTrue($this->tenant2->status);

        // Activate tenant 1
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->patchJson("/api/admin/tenants/{$this->tenant1->id}/activate");

        $response->assertStatus(200);

        // Verify both tenants are now active
        $this->tenant1->refresh();
        $this->tenant2->refresh();

        $this->assertTrue($this->tenant1->status);
        $this->assertTrue($this->tenant2->status);
    }

    /**
     * Test that admin operations are logged in admin context
     */
    public function test_admin_operations_are_logged_in_admin_context(): void
    {
        $adminToken = $this->getAdminToken();

        // Perform admin operations
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/admin/tenants');

        $response->assertStatus(200);

        // Verify admin operations are not logged in tenant audit logs
        $this->switchToTenant($this->tenant1);
        $this->assertDatabaseMissing('audit_logs', [
            'user_email' => $this->admin->email,
        ], 'tenant');

        $this->switchToTenant($this->tenant2);
        $this->assertDatabaseMissing('audit_logs', [
            'user_email' => $this->admin->email,
        ], 'tenant');
    }
}

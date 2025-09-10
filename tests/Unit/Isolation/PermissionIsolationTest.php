<?php

namespace Tests\Unit\Isolation;

use Tests\TestCase;
use App\Models\Tenant\User;
use App\Services\TenantPermissionService;

class PermissionIsolationTest extends TestCase
{
    private TenantPermissionService $permissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionService = app(TenantPermissionService::class);
    }

    /**
     * Test that permissions are isolated between tenants
     */
    public function test_permissions_are_isolated_between_tenants(): void
    {
        // Create users in different tenants
        $user1 = $this->createTenantUser($this->tenant1, [
            'name' => 'User Tenant 1',
            'email' => 'user1@tenant1.com',
        ]);

        $user2 = $this->createTenantUser($this->tenant2, [
            'name' => 'User Tenant 2',
            'email' => 'user2@tenant2.com',
        ]);

        // Assign different roles to users in different tenants
        $this->switchToTenant($this->tenant1);
        $user1->assignRole('manager');

        $this->switchToTenant($this->tenant2);
        $user2->assignRole('sales_rep');

        // Verify permissions are isolated
        $this->switchToTenant($this->tenant1);
        $this->assertTrue($user1->hasPermission('contacts.view_all'));
        $this->assertTrue($user1->hasPermission('deals.view_all'));

        $this->switchToTenant($this->tenant2);
        $this->assertFalse($user2->hasPermission('contacts.view_all'));
        $this->assertFalse($user2->hasPermission('deals.view_all'));
        $this->assertTrue($user2->hasPermission('contacts.view'));
        $this->assertTrue($user2->hasPermission('deals.view'));
    }

    /**
     * Test that roles are isolated between tenants
     */
    public function test_roles_are_isolated_between_tenants(): void
    {
        // Create users in different tenants
        $user1 = $this->createTenantUser($this->tenant1, [
            'name' => 'User Tenant 1',
            'email' => 'user1@tenant1.com',
        ]);

        $user2 = $this->createTenantUser($this->tenant2, [
            'name' => 'User Tenant 2',
            'email' => 'user2@tenant2.com',
        ]);

        // Assign same role to users in different tenants
        $this->switchToTenant($this->tenant1);
        $user1->assignRole('admin');

        $this->switchToTenant($this->tenant2);
        $user2->assignRole('admin');

        // Verify roles are isolated
        $this->switchToTenant($this->tenant1);
        $this->assertTrue($user1->hasRole('admin'));
        $this->assertFalse($user1->hasRole('sales_rep'));

        $this->switchToTenant($this->tenant2);
        $this->assertTrue($user2->hasRole('admin'));
        $this->assertFalse($user2->hasRole('sales_rep'));

        // Verify role data is isolated in database
        $this->assertTenantIsolation('tenant_model_has_roles', [
            'model_id' => $user1->id,
            'model_type' => 'App\\Models\\Tenant\\User',
        ], [
            'model_id' => $user2->id,
            'model_type' => 'App\\Models\\Tenant\\User',
        ]);
    }

    /**
     * Test that direct permissions are isolated between tenants
     */
    public function test_direct_permissions_are_isolated_between_tenants(): void
    {
        // Create users in different tenants
        $user1 = $this->createTenantUser($this->tenant1, [
            'name' => 'User Tenant 1',
            'email' => 'user1@tenant1.com',
        ]);

        $user2 = $this->createTenantUser($this->tenant2, [
            'name' => 'User Tenant 2',
            'email' => 'user2@tenant2.com',
        ]);

        // Give direct permissions to users in different tenants
        $this->switchToTenant($this->tenant1);
        $this->permissionService->givePermission($user1->id, 'contacts.delete');

        $this->switchToTenant($this->tenant2);
        $this->permissionService->givePermission($user2->id, 'deals.delete');

        // Verify direct permissions are isolated
        $this->switchToTenant($this->tenant1);
        $this->assertTrue($this->permissionService->hasPermission('contacts.delete'));
        $this->assertFalse($this->permissionService->hasPermission('deals.delete'));

        $this->switchToTenant($this->tenant2);
        $this->assertFalse($this->permissionService->hasPermission('contacts.delete'));
        $this->assertTrue($this->permissionService->hasPermission('deals.delete'));

        // Verify permission data is isolated in database
        $this->assertTenantIsolation('tenant_model_has_permissions', [
            'model_id' => $user1->id,
            'model_type' => 'App\\Models\\Tenant\\User',
        ], [
            'model_id' => $user2->id,
            'model_type' => 'App\\Models\\Tenant\\User',
        ]);
    }

    /**
     * Test that permission middleware enforces tenant isolation
     */
    public function test_permission_middleware_enforces_tenant_isolation(): void
    {
        // Create users in different tenants with different permissions
        $user1 = $this->createTenantUser($this->tenant1, [
            'name' => 'User Tenant 1',
            'email' => 'user1@tenant1.com',
        ]);

        $user2 = $this->createTenantUser($this->tenant2, [
            'name' => 'User Tenant 2',
            'email' => 'user2@tenant2.com',
        ]);

        // Assign different roles
        $this->switchToTenant($this->tenant1);
        $user1->assignRole('manager'); // Has contacts.view_all permission

        $this->switchToTenant($this->tenant2);
        $user2->assignRole('user'); // Only has basic view permissions

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // Create contacts in both tenants
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
        ]);

        // User 1 should be able to access contacts (manager role)
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/contacts');

        $response1->assertStatus(200);

        // User 2 should be able to access contacts (user role has contacts.view)
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/contacts');

        $response2->assertStatus(200);

        // But they should only see their own tenant's data
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonPath('data.0.name', 'Contact Tenant 1');

        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonPath('data.0.name', 'Contact Tenant 2');
    }

    /**
     * Test that role management is isolated between tenants
     */
    public function test_role_management_is_isolated_between_tenants(): void
    {
        // Create users in different tenants
        $user1 = $this->createTenantUser($this->tenant1, [
            'name' => 'User Tenant 1',
            'email' => 'user1@tenant1.com',
        ]);

        $user2 = $this->createTenantUser($this->tenant2, [
            'name' => 'User Tenant 2',
            'email' => 'user2@tenant2.com',
        ]);

        // Get admin tokens for both tenants
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // User 1 tries to assign role to user 2 (should fail due to isolation)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->postJson('/api/tenant/roles/assign-role', [
            'user_id' => $user2->id,
            'role_name' => 'manager',
        ]);

        // This should fail because user2 doesn't exist in tenant1's database
        $response->assertStatus(422); // Validation error - user not found

        // User 2 tries to assign role to user 1 (should fail due to isolation)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->postJson('/api/tenant/roles/assign-role', [
            'user_id' => $user1->id,
            'role_name' => 'manager',
        ]);

        // This should fail because user1 doesn't exist in tenant2's database
        $response->assertStatus(422); // Validation error - user not found
    }

    /**
     * Test that permission checks work correctly across tenant boundaries
     */
    public function test_permission_checks_work_correctly_across_tenant_boundaries(): void
    {
        // Create users in different tenants
        $user1 = $this->createTenantUser($this->tenant1, [
            'name' => 'User Tenant 1',
            'email' => 'user1@tenant1.com',
        ]);

        $user2 = $this->createTenantUser($this->tenant2, [
            'name' => 'User Tenant 2',
            'email' => 'user2@tenant2.com',
        ]);

        // Assign different roles
        $this->switchToTenant($this->tenant1);
        $user1->assignRole('super_admin');

        $this->switchToTenant($this->tenant2);
        $user2->assignRole('user');

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // User 1 (super_admin) should have access to role management
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/roles');

        $response1->assertStatus(200);

        // User 2 (user) should not have access to role management
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/roles');

        $response2->assertStatus(403);
    }

    /**
     * Test that permission inheritance works within tenant boundaries
     */
    public function test_permission_inheritance_works_within_tenant_boundaries(): void
    {
        // Create user in tenant 1
        $user = $this->createTenantUser($this->tenant1, [
            'name' => 'Test User',
            'email' => 'user@tenant1.com',
        ]);

        $this->switchToTenant($this->tenant1);

        // Assign role with permissions
        $user->assignRole('manager');

        // Verify role permissions are inherited
        $this->assertTrue($user->hasPermission('contacts.view'));
        $this->assertTrue($user->hasPermission('contacts.create'));
        $this->assertTrue($user->hasPermission('deals.view'));
        $this->assertTrue($user->hasPermission('deals.create'));

        // Give additional direct permission
        $this->permissionService->givePermission($user->id, 'contacts.delete');

        // Verify direct permission is added
        $this->assertTrue($user->hasPermission('contacts.delete'));

        // Verify all permissions are available
        $permissions = $user->getPermissions();
        $this->assertContains('contacts.view', $permissions);
        $this->assertContains('contacts.create', $permissions);
        $this->assertContains('contacts.delete', $permissions);
        $this->assertContains('deals.view', $permissions);
        $this->assertContains('deals.create', $permissions);
    }

    /**
     * Test that permission revocation works within tenant boundaries
     */
    public function test_permission_revocation_works_within_tenant_boundaries(): void
    {
        // Create user in tenant 1
        $user = $this->createTenantUser($this->tenant1, [
            'name' => 'Test User',
            'email' => 'user@tenant1.com',
        ]);

        $this->switchToTenant($this->tenant1);

        // Assign role and direct permission
        $user->assignRole('manager');
        $this->permissionService->givePermission($user->id, 'contacts.delete');

        // Verify permissions exist
        $this->assertTrue($user->hasPermission('contacts.view'));
        $this->assertTrue($user->hasPermission('contacts.delete'));

        // Revoke direct permission
        $this->permissionService->revokePermission($user->id, 'contacts.delete');

        // Verify direct permission is revoked but role permission remains
        $this->assertTrue($user->hasPermission('contacts.view')); // From role
        $this->assertFalse($user->hasPermission('contacts.delete')); // Revoked

        // Remove role
        $user->removeRole('manager');

        // Verify role permissions are revoked
        $this->assertFalse($user->hasPermission('contacts.view'));
        $this->assertFalse($user->hasPermission('contacts.delete'));
    }

    /**
     * Test that permission data is properly isolated in database
     */
    public function test_permission_data_is_properly_isolated_in_database(): void
    {
        // Create users in different tenants
        $user1 = $this->createTenantUser($this->tenant1, [
            'name' => 'User Tenant 1',
            'email' => 'user1@tenant1.com',
        ]);

        $user2 = $this->createTenantUser($this->tenant2, [
            'name' => 'User Tenant 2',
            'email' => 'user2@tenant2.com',
        ]);

        // Assign roles in different tenants
        $this->switchToTenant($this->tenant1);
        $user1->assignRole('admin');

        $this->switchToTenant($this->tenant2);
        $user2->assignRole('sales_rep');

        // Verify role assignments are isolated
        $this->assertTenantIsolation('tenant_model_has_roles', [
            'model_id' => $user1->id,
            'model_type' => 'App\\Models\\Tenant\\User',
        ], [
            'model_id' => $user2->id,
            'model_type' => 'App\\Models\\Tenant\\User',
        ]);

        // Give direct permissions in different tenants
        $this->switchToTenant($this->tenant1);
        $this->permissionService->givePermission($user1->id, 'contacts.delete');

        $this->switchToTenant($this->tenant2);
        $this->permissionService->givePermission($user2->id, 'deals.delete');

        // Verify direct permissions are isolated
        $this->assertTenantIsolation('tenant_model_has_permissions', [
            'model_id' => $user1->id,
            'model_type' => 'App\\Models\\Tenant\\User',
        ], [
            'model_id' => $user2->id,
            'model_type' => 'App\\Models\\Tenant\\User',
        ]);
    }
}

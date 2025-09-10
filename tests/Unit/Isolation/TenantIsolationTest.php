<?php

namespace Tests\Unit\Isolation;

use Tests\TestCase;
use App\Models\Tenant\User;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Deal;
use App\Models\Tenant\Activity;

class TenantIsolationTest extends TestCase
{
    /**
     * Test that users are completely isolated between tenants
     */
    public function test_user_isolation_between_tenants(): void
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

        // Verify isolation
        $this->assertTenantIsolation('users', [
            'email' => 'user1@tenant1.com',
        ], [
            'email' => 'user2@tenant2.com',
        ]);

        // Verify users can't see each other's data
        $this->switchToTenant($this->tenant1);
        $this->assertEquals(1, User::on('tenant')->count());
        $this->assertEquals('user1@tenant1.com', User::on('tenant')->first()->email);

        $this->switchToTenant($this->tenant2);
        $this->assertEquals(1, User::on('tenant')->count());
        $this->assertEquals('user2@tenant2.com', User::on('tenant')->first()->email);
    }

    /**
     * Test that contacts are completely isolated between tenants
     */
    public function test_contact_isolation_between_tenants(): void
    {
        // Create users first
        $user1 = $this->createTenantUser($this->tenant1);
        $user2 = $this->createTenantUser($this->tenant2);

        // Create contacts in different tenants
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
            'email' => 'contact1@tenant1.com',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
            'email' => 'contact2@tenant2.com',
        ]);

        // Verify isolation
        $this->assertTenantIsolation('contacts', [
            'email' => 'contact1@tenant1.com',
        ], [
            'email' => 'contact2@tenant2.com',
        ]);

        // Verify contacts can't see each other's data
        $this->switchToTenant($this->tenant1);
        $this->assertEquals(1, Contact::on('tenant')->count());
        $this->assertEquals('contact1@tenant1.com', Contact::on('tenant')->first()->email);

        $this->switchToTenant($this->tenant2);
        $this->assertEquals(1, Contact::on('tenant')->count());
        $this->assertEquals('contact2@tenant2.com', Contact::on('tenant')->first()->email);
    }

    /**
     * Test that deals are completely isolated between tenants
     */
    public function test_deal_isolation_between_tenants(): void
    {
        // Create users and contacts first
        $user1 = $this->createTenantUser($this->tenant1);
        $user2 = $this->createTenantUser($this->tenant2);

        $contact1 = $this->createTenantContact($this->tenant1, ['user_id' => $user1->id]);
        $contact2 = $this->createTenantContact($this->tenant2, ['user_id' => $user2->id]);

        // Create deals in different tenants
        $deal1 = $this->createTenantDeal($this->tenant1, [
            'user_id' => $user1->id,
            'contact_id' => $contact1->id,
            'title' => 'Deal Tenant 1',
            'value' => 1000.00,
        ]);

        $deal2 = $this->createTenantDeal($this->tenant2, [
            'user_id' => $user2->id,
            'contact_id' => $contact2->id,
            'title' => 'Deal Tenant 2',
            'value' => 2000.00,
        ]);

        // Verify isolation
        $this->assertTenantIsolation('deals', [
            'title' => 'Deal Tenant 1',
        ], [
            'title' => 'Deal Tenant 2',
        ]);

        // Verify deals can't see each other's data
        $this->switchToTenant($this->tenant1);
        $this->assertEquals(1, Deal::on('tenant')->count());
        $this->assertEquals('Deal Tenant 1', Deal::on('tenant')->first()->title);

        $this->switchToTenant($this->tenant2);
        $this->assertEquals(1, Deal::on('tenant')->count());
        $this->assertEquals('Deal Tenant 2', Deal::on('tenant')->first()->title);
    }

    /**
     * Test that activities are completely isolated between tenants
     */
    public function test_activity_isolation_between_tenants(): void
    {
        // Create users and contacts first
        $user1 = $this->createTenantUser($this->tenant1);
        $user2 = $this->createTenantUser($this->tenant2);

        $contact1 = $this->createTenantContact($this->tenant1, ['user_id' => $user1->id]);
        $contact2 = $this->createTenantContact($this->tenant2, ['user_id' => $user2->id]);

        // Create activities in different tenants
        $activity1 = $this->createTenantActivity($this->tenant1, [
            'user_id' => $user1->id,
            'contact_id' => $contact1->id,
            'type' => 'call',
            'description' => 'Activity Tenant 1',
        ]);

        $activity2 = $this->createTenantActivity($this->tenant2, [
            'user_id' => $user2->id,
            'contact_id' => $contact2->id,
            'type' => 'email',
            'description' => 'Activity Tenant 2',
        ]);

        // Verify isolation
        $this->assertTenantIsolation('activities', [
            'description' => 'Activity Tenant 1',
        ], [
            'description' => 'Activity Tenant 2',
        ]);

        // Verify activities can't see each other's data
        $this->switchToTenant($this->tenant1);
        $this->assertEquals(1, Activity::on('tenant')->count());
        $this->assertEquals('Activity Tenant 1', Activity::on('tenant')->first()->description);

        $this->switchToTenant($this->tenant2);
        $this->assertEquals(1, Activity::on('tenant')->count());
        $this->assertEquals('Activity Tenant 2', Activity::on('tenant')->first()->description);
    }

    /**
     * Test that roles and permissions are isolated between tenants
     */
    public function test_roles_permissions_isolation_between_tenants(): void
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

        // Verify role isolation
        $this->switchToTenant($this->tenant1);
        $this->assertTrue($user1->hasRole('manager'));
        $this->assertFalse($user1->hasRole('sales_rep'));

        $this->switchToTenant($this->tenant2);
        $this->assertTrue($user2->hasRole('sales_rep'));
        $this->assertFalse($user2->hasRole('manager'));

        // Verify roles are isolated between tenants
        $this->assertTenantIsolation('tenant_model_has_roles', [
            'model_id' => $user1->id,
            'model_type' => 'App\\Models\\Tenant\\User',
        ], [
            'model_id' => $user2->id,
            'model_type' => 'App\\Models\\Tenant\\User',
        ]);
    }

    /**
     * Test that audit logs are isolated between tenants
     */
    public function test_audit_logs_isolation_between_tenants(): void
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

        // Create contacts to generate audit logs
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
        ]);

        // Verify audit log isolation
        $this->switchToTenant($this->tenant1);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user1->id,
            'resource_type' => 'Contact',
        ], 'tenant');

        $this->switchToTenant($this->tenant2);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user2->id,
            'resource_type' => 'Contact',
        ], 'tenant');

        // Verify audit logs are isolated between tenants
        $this->assertTenantIsolation('audit_logs', [
            'user_id' => $user1->id,
        ], [
            'user_id' => $user2->id,
        ]);
    }

    /**
     * Test that admin data is completely separate from tenant data
     */
    public function test_admin_tenant_separation(): void
    {
        $this->assertAdminTenantSeparation();

        // Create tenant users
        $user1 = $this->createTenantUser($this->tenant1);
        $user2 = $this->createTenantUser($this->tenant2);

        // Verify admin data doesn't exist in tenant databases
        $this->switchToTenant($this->tenant1);
        $this->assertDatabaseMissing('users', [
            'email' => $this->admin->email,
        ], 'tenant');

        $this->switchToTenant($this->tenant2);
        $this->assertDatabaseMissing('users', [
            'email' => $this->admin->email,
        ], 'tenant');

        // Verify tenant user data doesn't exist in admin database
        $this->assertDatabaseMissing('users', [
            'email' => $user1->email,
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => $user2->email,
        ]);
    }

    /**
     * Test that database connections are properly isolated
     */
    public function test_database_connection_isolation(): void
    {
        // Create data in tenant 1
        $this->switchToTenant($this->tenant1);
        $user1 = $this->createTenantUser($this->tenant1, [
            'name' => 'User Tenant 1',
            'email' => 'user1@tenant1.com',
        ]);

        // Switch to tenant 2
        $this->switchToTenant($this->tenant2);

        // Verify we're in tenant 2 database
        $this->assertDatabaseMissing('users', [
            'email' => 'user1@tenant1.com',
        ], 'tenant');

        // Create data in tenant 2
        $user2 = $this->createTenantUser($this->tenant2, [
            'name' => 'User Tenant 2',
            'email' => 'user2@tenant2.com',
        ]);

        // Switch back to tenant 1
        $this->switchToTenant($this->tenant1);

        // Verify we're back in tenant 1 database
        $this->assertDatabaseHas('users', [
            'email' => 'user1@tenant1.com',
        ], 'tenant');
        $this->assertDatabaseMissing('users', [
            'email' => 'user2@tenant2.com',
        ], 'tenant');
    }

    /**
     * Test that tenant data persists across multiple operations
     */
    public function test_tenant_data_persistence(): void
    {
        // Create user in tenant 1
        $this->switchToTenant($this->tenant1);
        $user1 = $this->createTenantUser($this->tenant1, [
            'name' => 'Persistent User',
            'email' => 'persistent@tenant1.com',
        ]);

        // Switch to tenant 2 and create data
        $this->switchToTenant($this->tenant2);
        $user2 = $this->createTenantUser($this->tenant2, [
            'name' => 'Another User',
            'email' => 'another@tenant2.com',
        ]);

        // Switch back to tenant 1
        $this->switchToTenant($this->tenant1);

        // Verify tenant 1 data still exists
        $this->assertDatabaseHas('users', [
            'email' => 'persistent@tenant1.com',
        ], 'tenant');
        $this->assertDatabaseMissing('users', [
            'email' => 'another@tenant2.com',
        ], 'tenant');

        // Perform operations in tenant 1
        $contact = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Persistent Contact',
        ]);

        // Switch to tenant 2
        $this->switchToTenant($this->tenant2);

        // Verify tenant 2 data still exists and tenant 1 data is not visible
        $this->assertDatabaseHas('users', [
            'email' => 'another@tenant2.com',
        ], 'tenant');
        $this->assertDatabaseMissing('users', [
            'email' => 'persistent@tenant1.com',
        ], 'tenant');
        $this->assertDatabaseMissing('contacts', [
            'name' => 'Persistent Contact',
        ], 'tenant');
    }
}

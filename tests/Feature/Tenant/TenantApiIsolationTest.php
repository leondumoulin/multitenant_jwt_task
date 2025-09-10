<?php

namespace Tests\Feature\Tenant;

use Tests\TestCase;
use App\Models\Tenant\User;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Deal;
use App\Models\Tenant\Activity;

class TenantApiIsolationTest extends TestCase
{
    /**
     * Test that tenant API endpoints are isolated between tenants
     */
    public function test_tenant_api_endpoints_are_isolated_between_tenants(): void
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

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

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

        // Test contacts endpoint isolation
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/contacts');

        $response1->assertStatus(200);
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonPath('data.0.name', 'Contact Tenant 1');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/contacts');

        $response2->assertStatus(200);
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonPath('data.0.name', 'Contact Tenant 2');
    }

    /**
     * Test that tenant users cannot access other tenants' data via API
     */
    public function test_tenant_users_cannot_access_other_tenants_data_via_api(): void
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

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // Create contacts in different tenants
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
        ]);

        // User 1 tries to access User 2's contact (should fail)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson("/api/tenant/contacts/{$contact2->id}");

        $response->assertStatus(404); // Contact not found in tenant 1's database

        // User 2 tries to access User 1's contact (should fail)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson("/api/tenant/contacts/{$contact1->id}");

        $response->assertStatus(404); // Contact not found in tenant 2's database
    }

    /**
     * Test that tenant users cannot modify other tenants' data via API
     */
    public function test_tenant_users_cannot_modify_other_tenants_data_via_api(): void
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

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // Create contacts in different tenants
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
        ]);

        // User 1 tries to update User 2's contact (should fail)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->putJson("/api/tenant/contacts/{$contact2->id}", [
            'name' => 'Hacked Contact',
            'email' => 'hacked@tenant2.com',
        ]);

        $response->assertStatus(404); // Contact not found in tenant 1's database

        // User 2 tries to update User 1's contact (should fail)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->putJson("/api/tenant/contacts/{$contact1->id}", [
            'name' => 'Hacked Contact',
            'email' => 'hacked@tenant1.com',
        ]);

        $response->assertStatus(404); // Contact not found in tenant 2's database

        // Verify original contacts are unchanged
        $this->switchToTenant($this->tenant1);
        $contact1->refresh();
        $this->assertEquals('Contact Tenant 1', $contact1->name);

        $this->switchToTenant($this->tenant2);
        $contact2->refresh();
        $this->assertEquals('Contact Tenant 2', $contact2->name);
    }

    /**
     * Test that tenant users cannot delete other tenants' data via API
     */
    public function test_tenant_users_cannot_delete_other_tenants_data_via_api(): void
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

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // Create contacts in different tenants
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
        ]);

        // User 1 tries to delete User 2's contact (should fail)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->deleteJson("/api/tenant/contacts/{$contact2->id}");

        $response->assertStatus(404); // Contact not found in tenant 1's database

        // User 2 tries to delete User 1's contact (should fail)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->deleteJson("/api/tenant/contacts/{$contact1->id}");

        $response->assertStatus(404); // Contact not found in tenant 2's database

        // Verify contacts still exist
        $this->switchToTenant($this->tenant1);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact1->id,
            'name' => 'Contact Tenant 1',
        ], 'tenant');

        $this->switchToTenant($this->tenant2);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact2->id,
            'name' => 'Contact Tenant 2',
        ], 'tenant');
    }

    /**
     * Test that deals API endpoints are isolated between tenants
     */
    public function test_deals_api_endpoints_are_isolated_between_tenants(): void
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

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // Create contacts and deals in different tenants
        $contact1 = $this->createTenantContact($this->tenant1, ['user_id' => $user1->id]);
        $contact2 = $this->createTenantContact($this->tenant2, ['user_id' => $user2->id]);

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

        // Test deals endpoint isolation
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/deals');

        $response1->assertStatus(200);
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonPath('data.0.title', 'Deal Tenant 1');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/deals');

        $response2->assertStatus(200);
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonPath('data.0.title', 'Deal Tenant 2');
    }

    /**
     * Test that activities API endpoints are isolated between tenants
     */
    public function test_activities_api_endpoints_are_isolated_between_tenants(): void
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

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // Create contacts and activities in different tenants
        $contact1 = $this->createTenantContact($this->tenant1, ['user_id' => $user1->id]);
        $contact2 = $this->createTenantContact($this->tenant2, ['user_id' => $user2->id]);

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

        // Test activities endpoint isolation
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/activities');

        $response1->assertStatus(200);
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonPath('data.0.description', 'Activity Tenant 1');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/activities');

        $response2->assertStatus(200);
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonPath('data.0.description', 'Activity Tenant 2');
    }

    /**
     * Test that role management API endpoints are isolated between tenants
     */
    public function test_role_management_api_endpoints_are_isolated_between_tenants(): void
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

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // Test role endpoints isolation
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/roles');

        $response1->assertStatus(200);
        $roles1 = $response1->json('data');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/roles');

        $response2->assertStatus(200);
        $roles2 = $response2->json('data');

        // Both tenants should have the same roles (they're seeded)
        $this->assertEquals($roles1, $roles2);

        // Test user permissions endpoint isolation
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/roles/user-permissions');

        $response1->assertStatus(200);
        $response1->assertJsonPath('data.user.email', 'user1@tenant1.com');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/roles/user-permissions');

        $response2->assertStatus(200);
        $response2->assertJsonPath('data.user.email', 'user2@tenant2.com');
    }

    /**
     * Test that audit logs API endpoints are isolated between tenants
     */
    public function test_audit_logs_api_endpoints_are_isolated_between_tenants(): void
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

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // Create contacts to generate audit logs
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
        ]);

        // Test audit logs endpoint isolation
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/audit-logs/my-logs');

        $response1->assertStatus(200);
        $logs1 = $response1->json('data');
        $this->assertGreaterThan(0, count($logs1));
        $this->assertTrue(collect($logs1)->every(fn($log) => $log['user_id'] === $user1->id));

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/audit-logs/my-logs');

        $response2->assertStatus(200);
        $logs2 = $response2->json('data');
        $this->assertGreaterThan(0, count($logs2));
        $this->assertTrue(collect($logs2)->every(fn($log) => $log['user_id'] === $user2->id));

        // Verify no cross-tenant data
        $user1Ids = collect($logs1)->pluck('user_id')->unique();
        $user2Ids = collect($logs2)->pluck('user_id')->unique();

        $this->assertFalse($user1Ids->contains($user2->id));
        $this->assertFalse($user2Ids->contains($user1->id));
    }

    /**
     * Test that concurrent API requests maintain tenant isolation
     */
    public function test_concurrent_api_requests_maintain_tenant_isolation(): void
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

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // Create contacts in different tenants
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
        ]);

        // Simulate concurrent requests
        $responses = [];

        // Make multiple requests from both tenants
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token1,
            ])->getJson('/api/tenant/contacts');

            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token2,
            ])->getJson('/api/tenant/contacts');
        }

        // Verify all responses are successful and isolated
        foreach ($responses as $index => $response) {
            $response->assertStatus(200);

            if ($index % 2 === 0) {
                // Even indices are tenant 1 requests
                $response->assertJsonPath('data.0.name', 'Contact Tenant 1');
            } else {
                // Odd indices are tenant 2 requests
                $response->assertJsonPath('data.0.name', 'Contact Tenant 2');
            }
        }
    }
}

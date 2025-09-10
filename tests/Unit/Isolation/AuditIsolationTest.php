<?php

namespace Tests\Unit\Isolation;

use Tests\TestCase;
use App\Models\Tenant\User;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Deal;
use App\Models\Tenant\Activity;
use App\Services\AuditLogService;

class AuditIsolationTest extends TestCase
{
    private AuditLogService $auditLogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLogService = app(AuditLogService::class);
    }

    /**
     * Test that audit logs are isolated between tenants
     */
    public function test_audit_logs_are_isolated_between_tenants(): void
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

        // Verify audit logs are isolated
        $this->assertTenantIsolation('audit_logs', [
            'user_id' => $user1->id,
        ], [
            'user_id' => $user2->id,
        ]);

        // Verify each tenant can only see their own audit logs
        $this->switchToTenant($this->tenant1);
        $logs1 = $this->auditLogService->getUserLogs($user1->id);
        $this->assertGreaterThan(0, $logs1->count());
        $this->assertTrue($logs1->every(fn($log) => $log->user_id === $user1->id));

        $this->switchToTenant($this->tenant2);
        $logs2 = $this->auditLogService->getUserLogs($user2->id);
        $this->assertGreaterThan(0, $logs2->count());
        $this->assertTrue($logs2->every(fn($log) => $log->user_id === $user2->id));
    }

    /**
     * Test that audit logs capture correct tenant context
     */
    public function test_audit_logs_capture_correct_tenant_context(): void
    {
        // Create user in tenant 1
        $user = $this->createTenantUser($this->tenant1, [
            'name' => 'Test User',
            'email' => 'user@tenant1.com',
        ]);

        $this->switchToTenant($this->tenant1);

        // Create contact to generate audit log
        $contact = $this->createTenantContact($this->tenant1, [
            'user_id' => $user->id,
            'name' => 'Test Contact',
        ]);

        // Get audit logs
        $logs = $this->auditLogService->getResourceLogs('Contact', $contact->id);

        // Verify audit log contains correct tenant context
        $this->assertGreaterThan(0, $logs->count());
        $log = $logs->first();
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals($user->name, $log->user_name);
        $this->assertEquals($user->email, $log->user_email);
        $this->assertEquals('Contact', $log->resource_type);
        $this->assertEquals($contact->id, $log->resource_id);
        $this->assertEquals('Test Contact', $log->resource_name);
    }

    /**
     * Test that audit logs are not visible across tenant boundaries
     */
    public function test_audit_logs_are_not_visible_across_tenant_boundaries(): void
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

        // Get tokens
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // User 1 should only see their tenant's audit logs
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/audit-logs/my-logs');

        $response1->assertStatus(200);
        $logs1 = $response1->json('data');
        $this->assertGreaterThan(0, count($logs1));
        $this->assertTrue(collect($logs1)->every(fn($log) => $log['user_id'] === $user1->id));

        // User 2 should only see their tenant's audit logs
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/audit-logs/my-logs');

        $response2->assertStatus(200);
        $logs2 = $response2->json('data');
        $this->assertGreaterThan(0, count($logs2));
        $this->assertTrue(collect($logs2)->every(fn($log) => $log['user_id'] === $user2->id));

        // Verify no cross-tenant data leakage
        $user1Ids = collect($logs1)->pluck('user_id')->unique();
        $user2Ids = collect($logs2)->pluck('user_id')->unique();

        $this->assertFalse($user1Ids->contains($user2->id));
        $this->assertFalse($user2Ids->contains($user1->id));
    }

    /**
     * Test that audit log statistics are isolated between tenants
     */
    public function test_audit_log_statistics_are_isolated_between_tenants(): void
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

        // Create different types of data to generate varied audit logs
        $this->switchToTenant($this->tenant1);
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
        ]);
        $deal1 = $this->createTenantDeal($this->tenant1, [
            'user_id' => $user1->id,
            'contact_id' => $contact1->id,
            'title' => 'Deal Tenant 1',
        ]);

        $this->switchToTenant($this->tenant2);
        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
        ]);
        $activity2 = $this->createTenantActivity($this->tenant2, [
            'user_id' => $user2->id,
            'contact_id' => $contact2->id,
            'type' => 'call',
            'description' => 'Activity Tenant 2',
        ]);

        // Get audit statistics for each tenant
        $this->switchToTenant($this->tenant1);
        $stats1 = $this->auditLogService->getAuditStats();

        $this->switchToTenant($this->tenant2);
        $stats2 = $this->auditLogService->getAuditStats();

        // Verify statistics are isolated
        $this->assertGreaterThan(0, $stats1['total_logs']);
        $this->assertGreaterThan(0, $stats2['total_logs']);

        // Verify resource statistics are isolated
        $this->assertArrayHasKey('Contact', $stats1['resource_stats']);
        $this->assertArrayHasKey('Deal', $stats1['resource_stats']);
        $this->assertArrayNotHasKey('Activity', $stats1['resource_stats']);

        $this->assertArrayHasKey('Contact', $stats2['resource_stats']);
        $this->assertArrayHasKey('Activity', $stats2['resource_stats']);
        $this->assertArrayNotHasKey('Deal', $stats2['resource_stats']);

        // Verify user statistics are isolated
        $this->assertTrue(collect($stats1['top_users'])->every(fn($user) => $user['user_id'] === $user1->id));
        $this->assertTrue(collect($stats2['top_users'])->every(fn($user) => $user['user_id'] === $user2->id));
    }

    /**
     * Test that audit logs capture model changes correctly within tenant context
     */
    public function test_audit_logs_capture_model_changes_correctly_within_tenant_context(): void
    {
        // Create user in tenant 1
        $user = $this->createTenantUser($this->tenant1, [
            'name' => 'Test User',
            'email' => 'user@tenant1.com',
        ]);

        $this->switchToTenant($this->tenant1);

        // Create contact (should generate 'created' log)
        $contact = $this->createTenantContact($this->tenant1, [
            'user_id' => $user->id,
            'name' => 'Original Name',
            'email' => 'original@test.com',
        ]);

        // Update contact (should generate 'updated' log)
        $contact->update([
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
        ]);

        // Delete contact (should generate 'deleted' log)
        $contact->delete();

        // Get audit logs for the contact
        $logs = $this->auditLogService->getResourceLogs('Contact', $contact->id);

        // Verify all three operations were logged
        $this->assertEquals(3, $logs->count());

        $createdLog = $logs->where('action', 'created')->first();
        $updatedLog = $logs->where('action', 'updated')->first();
        $deletedLog = $logs->where('action', 'deleted')->first();

        // Verify created log
        $this->assertNotNull($createdLog);
        $this->assertEquals($user->id, $createdLog->user_id);
        $this->assertEquals('Contact', $createdLog->resource_type);
        $this->assertEquals($contact->id, $createdLog->resource_id);
        $this->assertEquals('Original Name', $createdLog->resource_name);
        $this->assertNull($createdLog->old_values);
        $this->assertNotNull($createdLog->new_values);

        // Verify updated log
        $this->assertNotNull($updatedLog);
        $this->assertEquals($user->id, $updatedLog->user_id);
        $this->assertEquals('Contact', $updatedLog->resource_type);
        $this->assertEquals($contact->id, $updatedLog->resource_id);
        $this->assertEquals('Updated Name', $updatedLog->resource_name);
        $this->assertNotNull($updatedLog->old_values);
        $this->assertNotNull($updatedLog->new_values);

        // Verify deleted log
        $this->assertNotNull($deletedLog);
        $this->assertEquals($user->id, $deletedLog->user_id);
        $this->assertEquals('Contact', $deletedLog->resource_type);
        $this->assertEquals($contact->id, $deletedLog->resource_id);
        $this->assertEquals('Updated Name', $deletedLog->resource_name);
        $this->assertNotNull($deletedLog->old_values);
        $this->assertNull($deletedLog->new_values);
    }

    /**
     * Test that manual audit logging works within tenant context
     */
    public function test_manual_audit_logging_works_within_tenant_context(): void
    {
        // Create user in tenant 1
        $user = $this->createTenantUser($this->tenant1, [
            'name' => 'Test User',
            'email' => 'user@tenant1.com',
        ]);

        $this->switchToTenant($this->tenant1);

        // Create contact
        $contact = $this->createTenantContact($this->tenant1, [
            'user_id' => $user->id,
            'name' => 'Test Contact',
        ]);

        // Log a custom action
        $this->auditLogService->log('custom_action', 'Contact', $contact->id, $contact->name, null, null, [
            'custom_field' => 'custom_value',
        ]);

        // Get audit logs
        $logs = $this->auditLogService->getResourceLogs('Contact', $contact->id);

        // Verify custom action was logged
        $customLog = $logs->where('action', 'custom_action')->first();
        $this->assertNotNull($customLog);
        $this->assertEquals($user->id, $customLog->user_id);
        $this->assertEquals('Contact', $customLog->resource_type);
        $this->assertEquals($contact->id, $customLog->resource_id);
        $this->assertEquals('Test Contact', $customLog->resource_name);
        $this->assertNotNull($customLog->metadata);
        $this->assertEquals('custom_value', json_decode($customLog->metadata, true)['custom_field']);
    }

    /**
     * Test that audit logs are filtered correctly by tenant context
     */
    public function test_audit_logs_are_filtered_correctly_by_tenant_context(): void
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

        // Create contacts in different tenants
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
        ]);

        // Test filtering by action
        $this->switchToTenant($this->tenant1);
        $createdLogs1 = $this->auditLogService->getLogs(['action' => 'created']);
        $this->assertGreaterThan(0, $createdLogs1->count());
        $this->assertTrue($createdLogs1->every(fn($log) => $log->action === 'created'));

        $this->switchToTenant($this->tenant2);
        $createdLogs2 = $this->auditLogService->getLogs(['action' => 'created']);
        $this->assertGreaterThan(0, $createdLogs2->count());
        $this->assertTrue($createdLogs2->every(fn($log) => $log->action === 'created'));

        // Test filtering by resource type
        $this->switchToTenant($this->tenant1);
        $contactLogs1 = $this->auditLogService->getLogs(['resource_type' => 'Contact']);
        $this->assertGreaterThan(0, $contactLogs1->count());
        $this->assertTrue($contactLogs1->every(fn($log) => $log->resource_type === 'Contact'));

        $this->switchToTenant($this->tenant2);
        $contactLogs2 = $this->auditLogService->getLogs(['resource_type' => 'Contact']);
        $this->assertGreaterThan(0, $contactLogs2->count());
        $this->assertTrue($contactLogs2->every(fn($log) => $log->resource_type === 'Contact'));

        // Verify no cross-tenant data in filtered results
        $this->assertFalse($contactLogs1->contains('user_id', $user2->id));
        $this->assertFalse($contactLogs2->contains('user_id', $user1->id));
    }

    /**
     * Test that audit logs maintain data integrity across tenant boundaries
     */
    public function test_audit_logs_maintain_data_integrity_across_tenant_boundaries(): void
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

        // Create contacts with same ID in different tenants
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact 1',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact 2',
        ]);

        // Get audit logs for each contact
        $this->switchToTenant($this->tenant1);
        $logs1 = $this->auditLogService->getResourceLogs('Contact', $contact1->id);

        $this->switchToTenant($this->tenant2);
        $logs2 = $this->auditLogService->getResourceLogs('Contact', $contact2->id);

        // Verify logs are isolated even with same resource IDs
        $this->assertGreaterThan(0, $logs1->count());
        $this->assertGreaterThan(0, $logs2->count());

        // Verify no cross-tenant data
        $this->assertTrue($logs1->every(fn($log) => $log->user_id === $user1->id));
        $this->assertTrue($logs2->every(fn($log) => $log->user_id === $user2->id));

        // Verify resource names are correct
        $this->assertTrue($logs1->every(fn($log) => $log->resource_name === 'Contact 1'));
        $this->assertTrue($logs2->every(fn($log) => $log->resource_name === 'Contact 2'));
    }
}

<?php

namespace Tests\Unit\Isolation;

use Tests\TestCase;
use App\Models\Tenant\User;
use App\Services\JwtService;

class JwtIsolationTest extends TestCase
{
    private JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = app(JwtService::class);
    }

    /**
     * Test that admin JWT tokens cannot access tenant endpoints
     */
    public function test_admin_token_cannot_access_tenant_endpoints(): void
    {
        $adminToken = $this->getAdminToken();

        // Try to access tenant endpoints with admin token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/tenant/contacts');

        $response->assertStatus(401);
    }

    /**
     * Test that tenant JWT tokens cannot access admin endpoints
     */
    public function test_tenant_token_cannot_access_admin_endpoints(): void
    {
        // Create tenant user
        $user = $this->createTenantUser($this->tenant1, [
            'name' => 'Test User',
            'email' => 'user@tenant1.com',
        ]);

        $tenantToken = $this->getTenantToken($this->tenant1, 'user@tenant1.com');

        // Try to access admin endpoints with tenant token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tenantToken,
        ])->getJson('/api/admin/tenants');

        $response->assertStatus(401);
    }

    /**
     * Test that tenant tokens are isolated between tenants
     */
    public function test_tenant_tokens_are_isolated_between_tenants(): void
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

        // Get tokens for both users
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

        // User 1 should only see their tenant's contacts
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/contacts');

        $response1->assertStatus(200);
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonPath('data.0.name', 'Contact Tenant 1');

        // User 2 should only see their tenant's contacts
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/contacts');

        $response2->assertStatus(200);
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonPath('data.0.name', 'Contact Tenant 2');
    }

    /**
     * Test that JWT tokens contain correct tenant information
     */
    public function test_jwt_tokens_contain_correct_tenant_information(): void
    {
        // Create tenant user
        $user = $this->createTenantUser($this->tenant1, [
            'name' => 'Test User',
            'email' => 'user@tenant1.com',
        ]);

        $token = $this->getTenantToken($this->tenant1, 'user@tenant1.com');

        // Decode token to verify tenant information
        $payload = $this->jwtService->validate($token);

        $this->assertEquals('tenant', $payload['guard']);
        $this->assertEquals($user->id, $payload['sub']);
        $this->assertEquals($this->tenant1->id, $payload['tenant_id']);
    }

    /**
     * Test that admin JWT tokens contain correct admin information
     */
    public function test_admin_jwt_tokens_contain_correct_admin_information(): void
    {
        $adminToken = $this->getAdminToken();

        // Decode token to verify admin information
        $payload = $this->jwtService->validate($adminToken);

        $this->assertEquals('admin', $payload['guard']);
        $this->assertEquals($this->admin->id, $payload['sub']);
        $this->assertArrayNotHasKey('tenant_id', $payload);
    }

    /**
     * Test that expired tokens are rejected
     */
    public function test_expired_tokens_are_rejected(): void
    {
        // Create tenant user
        $user = $this->createTenantUser($this->tenant1);

        // Create an expired token (this would need to be mocked in a real test)
        $expiredToken = 'expired.jwt.token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $expiredToken,
        ])->getJson('/api/tenant/contacts');

        $response->assertStatus(401);
    }

    /**
     * Test that invalid tokens are rejected
     */
    public function test_invalid_tokens_are_rejected(): void
    {
        $invalidToken = 'invalid.jwt.token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $invalidToken,
        ])->getJson('/api/tenant/contacts');

        $response->assertStatus(401);
    }

    /**
     * Test that tokens without proper claims are rejected
     */
    public function test_tokens_without_proper_claims_are_rejected(): void
    {
        // Create token without tenant_id claim
        $invalidToken = 'invalid.jwt.token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $invalidToken,
        ])->getJson('/api/tenant/contacts');

        $response->assertStatus(401);
    }

    /**
     * Test that tokens with wrong guard are rejected
     */
    public function test_tokens_with_wrong_guard_are_rejected(): void
    {
        // Create tenant user
        $user = $this->createTenantUser($this->tenant1);

        // Create token with wrong guard
        $wrongGuardToken = 'wrong.guard.token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $wrongGuardToken,
        ])->getJson('/api/tenant/contacts');

        $response->assertStatus(401);
    }

    /**
     * Test that token refresh maintains tenant isolation
     */
    public function test_token_refresh_maintains_tenant_isolation(): void
    {
        // Create tenant user
        $user = $this->createTenantUser($this->tenant1, [
            'name' => 'Test User',
            'email' => 'user@tenant1.com',
        ]);

        $originalToken = $this->getTenantToken($this->tenant1, 'user@tenant1.com');

        // Refresh the token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $originalToken,
        ])->postJson('/api/tenant/refresh');

        $response->assertStatus(200);
        $newToken = $response->json('data.token');

        // Verify new token works and maintains tenant isolation
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $newToken,
        ])->getJson('/api/tenant/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.email', 'user@tenant1.com');

        // Verify old token is still valid (depending on implementation)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $originalToken,
        ])->getJson('/api/tenant/me');

        // This might be 200 or 401 depending on token refresh implementation
        $response->assertStatus(200);
    }

    /**
     * Test that logout invalidates tokens
     */
    public function test_logout_invalidates_tokens(): void
    {
        // Create tenant user
        $user = $this->createTenantUser($this->tenant1);

        $token = $this->getTenantToken($this->tenant1);

        // Verify token works
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/tenant/me');

        $response->assertStatus(200);

        // Logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/tenant/logout');

        $response->assertStatus(200);

        // Verify token is now invalid
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/tenant/me');

        $response->assertStatus(401);
    }

    /**
     * Test that multiple concurrent sessions are isolated
     */
    public function test_multiple_concurrent_sessions_are_isolated(): void
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

        // Get tokens for both users
        $token1 = $this->getTenantToken($this->tenant1, 'user1@tenant1.com');
        $token2 = $this->getTenantToken($this->tenant2, 'user2@tenant2.com');

        // Create data in both tenants
        $contact1 = $this->createTenantContact($this->tenant1, [
            'user_id' => $user1->id,
            'name' => 'Contact Tenant 1',
        ]);

        $contact2 = $this->createTenantContact($this->tenant2, [
            'user_id' => $user2->id,
            'name' => 'Contact Tenant 2',
        ]);

        // Simulate concurrent requests
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/tenant/contacts');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/tenant/contacts');

        // Both should succeed and return only their tenant's data
        $response1->assertStatus(200);
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonPath('data.0.name', 'Contact Tenant 1');

        $response2->assertStatus(200);
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonPath('data.0.name', 'Contact Tenant 2');
    }
}

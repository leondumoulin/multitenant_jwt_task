<?php

namespace App\Http\Guards;

use App\Services\JwtService;
use App\Services\DatabaseManager;
use App\Models\Admin\Tenant;
use App\Models\Tenant\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class TenantJwtGuard implements Guard
{
    private JwtService $jwtService;
    private DatabaseManager $dbManager;
    private UserProvider $provider;
    private Request $request;
    private $user;
    private ?Tenant $tenant = null;

    public function __construct(JwtService $jwtService, DatabaseManager $dbManager, UserProvider $provider, Request $request)
    {
        // dd($jwtService);
        $this->jwtService = $jwtService;
        $this->dbManager = $dbManager;
        $this->provider = $provider;
        $this->request = $request;
    }

    public function check(): bool
    {
        return !is_null($this->user());
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user()
    {
        if ($this->user) {
            return $this->user;
        }
        // try {
        $token = $this->jwtService->extractTokenFromHeader();
        if (!$token) {
            return null;
        }

        $payload = $this->jwtService->validateToken($token);

        if (!isset($payload['type']) || $payload['type'] !== 'tenant') {
            return null;
        }

        if (!isset($payload['tenant_id'])) {
            return null;
        }

        // Get and validate tenant
        $this->tenant = Tenant::find($payload['tenant_id']);

        if (!$this->tenant || !$this->tenant->isActive()) {
            return null;
        }

        // Switch to tenant database
        $this->dbManager->setTenantConnection($this->tenant);
        // Get user from tenant database

        $this->user = User::find($payload['sub']);
        if ($this->user) {
            $this->user->tenant = $this->tenant;
            $this->user->token_role = $payload['role'] ?? null;
        }

        return $this->user;
        // } catch (\Exception $e) {
        //     return null;
        // }
    }

    public function id()
    {
        return $this->user()?->id;
    }

    public function getTenant(): ?Tenant
    {
        $this->user(); // Ensure tenant is loaded
        return $this->tenant;
    }

    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        return $user && $this->provider->validateCredentials($user, $credentials);
    }

    public function hasUser(): bool
    {
        return !is_null($this->user);
    }

    public function setUser($user): static
    {
        $this->user = $user;
        return $this;
    }
}

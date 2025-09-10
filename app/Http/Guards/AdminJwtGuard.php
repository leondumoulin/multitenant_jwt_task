<?php

namespace App\Http\Guards;

use App\Services\JwtService;
use App\Models\Admin\Admin;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class AdminJwtGuard implements Guard
{
    private JwtService $jwtService;
    private UserProvider $provider;
    private Request $request;
    private $user;

    public function __construct(JwtService $jwtService, UserProvider $provider, Request $request)
    {
        $this->jwtService = $jwtService;
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

        try {
            $token = $this->jwtService->extractTokenFromHeader();
            if (!$token) {
                return null;
            }

            $payload = $this->jwtService->validateToken($token);

            if (!isset($payload['type']) || $payload['type'] !== 'admin') {
                return null;
            }

            $this->user = Admin::find($payload['sub']);
            return $this->user;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function id()
    {
        return $this->user()?->id;
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

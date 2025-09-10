<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Carbon\Carbon;

class JwtService
{
    private string $secretKey;
    private string $algorithm;

    public function __construct()
    {
        $this->secretKey = config('app.jwt_secret', env('JWT_SECRET', 'your-secret-key'));
        $this->algorithm = 'HS256';
    }

    public function generateToken(array $payload, int $expirationMinutes = 60): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + ($expirationMinutes * 60);

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    public function generateAdminToken(int $adminId): string
    {
        return $this->generateToken([
            'sub' => $adminId,
            'type' => 'admin',
        ], 480); // 8 hours
    }

    public function generateTenantToken(int $userId, int $tenantId): string
    {
        return $this->generateToken([
            'sub' => $userId,
            'tenant_id' => $tenantId,
            'type' => 'tenant',
        ], 480); // 8 hours
    }

    public function generateRefreshToken(int $userId, int $tenantId = null): string
    {
        $payload = [
            'sub' => $userId,
            'type' => 'refresh',
        ];

        if ($tenantId) {
            $payload['tenant_id'] = $tenantId;
        }

        return $this->generateToken($payload, 10080); // 7 days
    }

    public function validateToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new \Exception('Token has expired');
        } catch (SignatureInvalidException $e) {
            throw new \Exception('Token signature is invalid');
        } catch (\Exception $e) {
            throw new \Exception('Token is invalid');
        }
    }

    public function extractTokenFromHeader(string $header = null): ?string
    {
        if (!$header) {
            $header = request()->header('Authorization');
        }

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}

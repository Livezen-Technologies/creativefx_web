<?php

namespace Modules\Auth\Libraries;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use stdClass;

/**
 * Thin wrapper around firebase/php-jwt. Secret + TTL + issuer come from .env
 * (jwt.secret / jwt.ttl / jwt.issuer).
 */
class Jwt
{
    private string $secret;
    private int $ttl;
    private string $issuer;
    private string $alg = 'HS256';

    public function __construct()
    {
        $this->secret = (string) (env('jwt.secret') ?: 'insecure-dev-secret-change-me');
        $this->ttl    = (int) (env('jwt.ttl') ?: 3600);
        $this->issuer = (string) (env('jwt.issuer') ?: 'norlanka');
    }

    /**
     * Issue a signed token. Extra claims (e.g. roles) are merged in.
     *
     * @param array<string, mixed> $claims
     */
    public function issue(int $userId, array $claims = []): string
    {
        $now     = time();
        $payload = array_merge($claims, [
            'iss' => $this->issuer,
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ]);

        return FirebaseJWT::encode($payload, $this->secret, $this->alg);
    }

    /**
     * Decode + verify a token. Returns null when invalid/expired.
     */
    public function decode(string $token): ?stdClass
    {
        try {
            return FirebaseJWT::decode($token, new Key($this->secret, $this->alg));
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function ttl(): int
    {
        return $this->ttl;
    }
}

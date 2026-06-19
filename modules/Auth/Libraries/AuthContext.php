<?php

namespace Modules\Auth\Libraries;

use stdClass;

/**
 * Per-request holder for the authenticated identity decoded from the JWT.
 * Populated by JwtAuthFilter; read by API/admin controllers.
 */
class AuthContext
{
    private static ?int $userId = null;
    private static ?stdClass $claims = null;

    public static function set(?int $userId, ?stdClass $claims): void
    {
        self::$userId = $userId;
        self::$claims = $claims;
    }

    public static function userId(): ?int
    {
        return self::$userId;
    }

    public static function claims(): ?stdClass
    {
        return self::$claims;
    }

    public static function hasRole(string $slug): bool
    {
        $roles = self::$claims->roles ?? [];
        return in_array($slug, (array) $roles, true);
    }
}

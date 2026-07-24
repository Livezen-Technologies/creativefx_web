<?php

/**
 * Admin helpers.
 */

if (! function_exists('admin_can')) {
    /**
     * Role-based permission check for the logged-in admin.
     * Super-admins pass everything; others need the permission slug granted to
     * one of their roles (permissions → permission_role → role_user).
     */
    function admin_can(string $slug): bool
    {
        static $cache = null;

        $user = session()->get('admin_user');
        if (! is_array($user) || empty($user['id'])) {
            return false;
        }

        if ($cache === null) {
            $cache = ['super' => false, 'slugs' => []];
            try {
                $db    = db_connect();
                $roles = $db->table('role_user')
                    ->select('roles.id, roles.slug')
                    ->join('roles', 'roles.id = role_user.role_id')
                    ->where('role_user.user_id', (int) $user['id'])
                    ->get()->getResultArray();

                $roleIds = [];
                foreach ($roles as $r) {
                    $roleIds[] = (int) $r['id'];
                    if ($r['slug'] === 'super-admin') {
                        $cache['super'] = true;
                    }
                }

                if (! $cache['super'] && $roleIds !== []) {
                    $rows = $db->table('permission_role')
                        ->select('permissions.slug')
                        ->join('permissions', 'permissions.id = permission_role.permission_id')
                        ->whereIn('permission_role.role_id', $roleIds)
                        ->get()->getResultArray();
                    $cache['slugs'] = array_column($rows, 'slug');
                }
            } catch (\Throwable $e) {
                // On lookup failure, fail closed for non-super users.
            }
        }

        return $cache['super'] || in_array($slug, $cache['slugs'], true);
    }
}

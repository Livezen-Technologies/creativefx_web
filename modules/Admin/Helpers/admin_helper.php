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

if (! function_exists('admin_icon')) {
    /**
     * A Lucide-style stroke icon by name.
     *
     * Was a closure defined inside the dashboard view, which worked for exactly
     * as long as the whole dashboard was one file. Split into per-widget
     * partials it stopped working immediately: a closure is a local variable,
     * and $this->include() shares view data, not the including file's scope.
     * A helper is reachable from every admin view without being passed.
     */
    function admin_icon(string $key, string $class = 'h-5 w-5'): string
    {
        static $paths = [
            'users'     => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2m22 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
            'inbox'     => 'M22 12h-6l-2 3h-4l-2-3H2m3.5-7 -3.5 7v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-7a2 2 0 0 0-1.8-1H7.3a2 2 0 0 0-1.8 1Z',
            'target'    => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Zm0-6a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
            'briefcase' => 'M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Zm0 4h18',
            'news'      => 'M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0V9m14-3h-6m6 4h-6m6 4H8m10 4H8',
            'image'     => 'M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2ZM9 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm12 5-3.5-3.5a2 2 0 0 0-3 0L6 21',
            'file'      => 'M14 3v5h5M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z',
            'bed'       => 'M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8M2 16h20M6 10V7a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3',
            'pin'       => 'M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Zm-8 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
            'plus'      => 'M12 5v14M5 12h14',
            'upload'    => 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12',
            'edit'      => 'M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3Z',
        ];

        return '<svg class="' . esc($class, 'attr') . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="'
            . ($paths[$key] ?? $paths['file']) . '"/></svg>';
    }
}

if (! function_exists('admin_chip')) {
    /** A status pill, coloured by what the status means rather than by name. */
    function admin_chip(string $status): string
    {
        static $map = [
            'new'      => 'bg-brand-red/15 text-brand-red',
            'read'     => 'bg-white/10 text-white/60',
            'replied'  => 'bg-emerald-500/15 text-emerald-300',
            'archived' => 'bg-white/8 text-white/40',
        ];

        return '<span class="rounded-full ' . ($map[$status] ?? 'bg-white/10 text-white/60')
            . ' px-2.5 py-0.5 text-[11px] font-semibold capitalize">' . esc($status) . '</span>';
    }
}

if (! function_exists('admin_bytes')) {
    /** Storage size in the largest unit that leaves a number worth reading. */
    function admin_bytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        return number_format(max($bytes, 0) / 1024, 0) . ' KB';
    }
}


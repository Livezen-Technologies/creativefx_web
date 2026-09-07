<?php

namespace Modules\Auth\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table          = 'users';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    /**
     * The whitelist is the whole story here, and it is a trap worth naming:
     * CodeIgniter silently DROPS any key not on this list. `insert(['email' =>
     * …, 'verify_token' => …])` writes the row, discards the token, returns an
     * id and reports success — and the verification email then carries a link
     * that can never match anything. Adding a column to `users` is two edits,
     * the migration and this line, and forgetting the second one fails without
     * a word.
     */
    protected $allowedFields  = [
        'email', 'username', 'password_hash', 'first_name', 'last_name',
        'status', 'last_login_at',
        // The learner columns, from ExtendUsersForLearners.
        'phone', 'country', 'timezone', 'locale', 'company', 'job_title',
        'marketing_opt_in', 'consent_at', 'email_verified_at',
        'verify_token', 'verify_expires_at', 'reset_token', 'reset_expires_at',
        'remember_token', 'avatar',
    ];

    /**
     * Return the role slugs assigned to a user.
     *
     * @return list<string>
     */
    public function roleSlugs(int $userId): array
    {
        $rows = $this->db->table('role_user')
            ->select('roles.slug')
            ->join('roles', 'roles.id = role_user.role_id')
            ->where('role_user.user_id', $userId)
            ->get()
            ->getResultArray();

        return array_column($rows, 'slug');
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Whether this user holds any of the roles that may reach the console.
     *
     * Asked at the admin sign-in rather than only by a filter, so a customer's
     * password is never a way in even for the length of one redirect.
     */
    public function isStaff(int $userId): bool
    {
        return array_intersect(
            $this->roleSlugs($userId),
            \Modules\Auth\Database\Seeds\RoleSeeder::STAFF
        ) !== [];
    }
}

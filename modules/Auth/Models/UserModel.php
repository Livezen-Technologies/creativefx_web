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
    protected $allowedFields  = [
        'email', 'username', 'password_hash', 'first_name', 'last_name', 'office_id', 'status', 'last_login_at',
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
}

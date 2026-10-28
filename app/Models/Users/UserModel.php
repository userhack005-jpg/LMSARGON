<?php

namespace App\Models\Users;

use CodeIgniter\Model;
use Config\Database;
use Throwable;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'role_id',
        'role_slug',
        'parent_user_id',
        'full_name',
        'email',
        'password_hash',
        'status',
        'last_login',
        'created_at',
    ];

    public function ensureRolesAndUsersIntegrity(): void
    {
        static $syncedThisRequest = false;
        if ($syncedThisRequest) {
            return;
        }
        $syncedThisRequest = true;

        try {
            $db = Database::connect();
            if (! $db->tableExists('roles') || ! $db->tableExists('users')) {
                return;
            }

            $uCols = $db->getFieldNames('users');
            if (! in_array('role_slug', $uCols, true)) {
                $db->query("ALTER TABLE `users` ADD COLUMN `role_slug` VARCHAR(64) NOT NULL DEFAULT 'student'");
            }
            if (! in_array('parent_user_id', $uCols, true)) {
                $db->query("ALTER TABLE `users` ADD COLUMN `parent_user_id` INT UNSIGNED NULL DEFAULT NULL");
            }

            if ($db->tableExists('cms_roles')) {
                $cmsRoles   = $db->table('cms_roles')->orderBy('id', 'ASC')->get()->getResultArray();
                $coreRoles  = $db->table('roles')->get()->getResultArray();
                $coreBySlug = [];
                foreach ($coreRoles as $cr) {
                    $coreBySlug[strtolower(trim((string) $cr['role_slug']))] = (int) $cr['id'];
                }

                foreach ($cmsRoles as $cm) {
                    $cSlug = strtolower(trim((string) ($cm['role_slug'] ?? '')));
                    if ($cSlug !== '' && ! isset($coreBySlug[$cSlug])) {
                        $db->table('roles')->insert([
                            'role_slug'   => $cSlug,
                            'role_name'   => $cm['role_name'] ?? ucfirst($cSlug),
                            'description' => $cm['description'] ?? '',
                            'created_at'  => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            $allRoles = $db->table('roles')->get()->getResultArray();
            $idToSlug = [];
            $slugToId = [];
            foreach ($allRoles as $r) {
                $rid              = (int) $r['id'];
                $rSlug            = strtolower(trim((string) $r['role_slug']));
                $idToSlug[$rid]   = $rSlug;
                $slugToId[$rSlug] = $rid;
            }

            $users = $db->table('users')->select('id, role_id, role_slug')->get()->getResultArray();
            foreach ($users as $u) {
                $uid   = (int) $u['id'];
                $rid   = (int) $u['role_id'];
                $uSlug = strtolower(trim((string) ($u['role_slug'] ?? '')));

                if ($uid === 1) {
                    if ($rid !== 1 || $uSlug !== 'super_admin') {
                        $db->table('users')->where('id', 1)->update(['role_id' => 1, 'role_slug' => 'super_admin']);
                    }
                    continue;
                }

                if (isset($idToSlug[$rid])) {
                    $expectedSlug = $idToSlug[$rid];
                    if ($uSlug === '' || ($uSlug === 'student' && $expectedSlug !== 'student')) {
                        $db->table('users')->where('id', $uid)->update(['role_slug' => $expectedSlug]);
                    } elseif ($uSlug !== $expectedSlug && isset($slugToId[$uSlug])) {
                        $db->table('users')->where('id', $uid)->update(['role_id' => $slugToId[$uSlug]]);
                    }
                } elseif (isset($slugToId[$uSlug])) {
                    $db->table('users')->where('id', $uid)->update(['role_id' => $slugToId[$uSlug]]);
                }
            }
        } catch (Throwable $e) {
        }
    }

    public function syncUserRolesIntegrity(): void
    {
        $this->ensureRolesAndUsersIntegrity();
    }

    public function getRolePermissionsData(string $roleSlug): array
    {
        $roleSlug = strtolower(trim($roleSlug)) ?: 'student';
        if ($roleSlug === 'super_admin') {
            return ['permissions' => ['*'], 'portal_type' => 'backend'];
        }

        $cacheFile = WRITEPATH . 'addons/rbac_permissions_cache.json';
        if (is_file($cacheFile)) {
            $cached = json_decode((string) @file_get_contents($cacheFile), true);
            if (isset($cached['roles'][$roleSlug])) {
                return [
                    'permissions' => $cached['roles'][$roleSlug]['permissions'] ?? [],
                    'portal_type' => $cached['roles'][$roleSlug]['portal_type'] ?? 'frontend',
                ];
            }
        }

        try {
            $db = Database::connect();
            if ($db->tableExists('cms_roles')) {
                $row = $db->table('cms_roles')->where('role_slug', $roleSlug)->get()->getRowArray();
                if ($row) {
                    $perms = json_decode((string) ($row['permissions_json'] ?? '[]'), true);
                    return [
                        'permissions' => is_array($perms) ? $perms : [],
                        'portal_type' => $row['portal_type'] ?? 'frontend',
                    ];
                }
            }
        } catch (Throwable $e) {
        }

        return ['permissions' => ['dashboard.view'], 'portal_type' => 'frontend'];
    }

    public function verifyUserCredentials(string $email, string $password): ?array
    {
        $this->ensureRolesAndUsersIntegrity();

        try {
            $db   = Database::connect();
            $user = $db->table('users u')
                ->select('u.*, r.role_slug AS core_role_slug, r.role_name')
                ->join('roles r', 'r.id = u.role_id', 'left')
                ->where('u.email', $email)
                ->where('u.status', 'active')
                ->get()
                ->getRowArray();

            if ($user && password_verify($password, $user['password_hash'])) {
                $db->table('users')->where('id', $user['id'])->update(['last_login' => date('Y-m-d H:i:s')]);

                $effectiveSlug = ! empty($user['role_slug']) && $user['role_slug'] !== 'student'
                    ? $user['role_slug']
                    : ($user['core_role_slug'] ?: 'student');

                if ((int) $user['id'] === 1 || (int) $user['role_id'] === 1) {
                    $effectiveSlug = 'super_admin';
                }

                $user['role_slug'] = $effectiveSlug;
                if (empty($user['role_name'])) {
                    $user['role_name'] = ucwords(str_replace('_', ' ', $effectiveSlug));
                }

                $rbacData            = $this->getRolePermissionsData($effectiveSlug);
                $user['permissions'] = $rbacData['permissions'];
                $user['portal_type'] = $rbacData['portal_type'];

                return $user;
            }
            return null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getAvailableRoles(): array
    {
        $this->ensureRolesAndUsersIntegrity();
        try {
            $db = Database::connect();
            return $db->table('roles')->orderBy('id', 'ASC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getAllRoles(): array
    {
        return $this->getAvailableRoles();
    }

    public function getRoles(): array
    {
        return $this->getAvailableRoles();
    }

    public function getRoleById(int $roleId): ?array
    {
        try {
            $db  = Database::connect();
            $row = $db->table('roles')->where('id', $roleId)->get()->getRowArray();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getRoleBySlug(string $roleSlug): ?array
    {
        try {
            $db  = Database::connect();
            $row = $db->table('roles')->where('role_slug', strtolower(trim($roleSlug)))->get()->getRowArray();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getAllUsersWithRoles(): array
    {
        $this->ensureRolesAndUsersIntegrity();
        try {
            $db = Database::connect();
            return $db->table('users u')
                ->select('u.id, u.role_id, u.role_slug, u.parent_user_id, u.full_name, u.email, u.status, u.last_login, u.created_at, r.role_name')
                ->join('roles r', 'r.id = u.role_id', 'left')
                ->orderBy('u.id', 'DESC')
                ->get()
                ->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getUsersWithRoles(): array
    {
        return $this->getAllUsersWithRoles();
    }

    public function getRoleDistributionStats(): array
    {
        $this->ensureRolesAndUsersIntegrity();
        $stats = [
            'total'       => 0,
            'super_admin' => 0,
            'admin'       => 0,
            'teacher'     => 0,
            'parent'      => 0,
            'student'     => 0,
        ];

        try {
            $db   = Database::connect();
            $rows = $db->table('users u')
                ->select('u.role_slug, COUNT(u.id) as total_count')
                ->groupBy('u.role_slug')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $slug  = strtolower((string) ($r['role_slug'] ?? 'student'));
                $count = (int) $r['total_count'];
                $stats['total'] += $count;
                if (isset($stats[$slug])) {
                    $stats[$slug] += $count;
                } else {
                    $stats[$slug] = $count;
                }
            }
            return $stats;
        } catch (Throwable $e) {
            return $stats;
        }
    }

    public function getRoleStats(): array
    {
        return $this->getRoleDistributionStats();
    }

    public function insertNewUser(array $data): array
    {
        $this->ensureRolesAndUsersIntegrity();
        try {
            $db = Database::connect();

            $exists = $db->table('users')->where('email', $data['email'])->countAllResults();
            if ($exists > 0) {
                return [
                    'status'  => 'error',
                    'message' => 'A user with this email address already exists.',
                ];
            }

            $roleId   = (int) ($data['role_id'] ?? 0);
            $roleSlug = strtolower(trim((string) ($data['role_slug'] ?? '')));

            if ($roleId > 0 && $roleSlug === '') {
                $rRow     = $this->getRoleById($roleId);
                $roleSlug = $rRow['role_slug'] ?? 'student';
            } elseif ($roleSlug !== '' && $roleId <= 0) {
                $rRow   = $this->getRoleBySlug($roleSlug);
                $roleId = (int) ($rRow['id'] ?? 4);
            }
            if ($roleId <= 0) {
                $roleId   = 4;
                $roleSlug = 'student';
            }

            $db->table('users')->insert([
                'role_id'       => $roleId,
                'role_slug'     => $roleSlug,
                'full_name'     => $data['full_name'],
                'email'         => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
                'status'        => $data['status'] ?? 'active',
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            return [
                'status'  => 'success',
                'message' => 'User account provisioned and role (' . $roleSlug . ') assigned successfully!',
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'error',
                'message' => 'Database Error: ' . $e->getMessage(),
            ];
        }
    }

    public function createUser(array $data): array
    {
        return $this->insertNewUser($data);
    }

    public function changeUserRole(int $userId, $roleIdOrSlug): bool
    {
        $this->ensureRolesAndUsersIntegrity();
        try {
            $db = Database::connect();
            if (is_numeric($roleIdOrSlug) && (int) $roleIdOrSlug > 0) {
                $roleRow = $this->getRoleById((int) $roleIdOrSlug);
            } else {
                $roleRow = $this->getRoleBySlug((string) $roleIdOrSlug);
            }

            if (! $roleRow) {
                return false;
            }

            $roleId   = (int) $roleRow['id'];
            $roleSlug = (string) $roleRow['role_slug'];

            return (bool) $db->table('users')
                ->where('id', $userId)
                ->update([
                    'role_id'   => $roleId,
                    'role_slug' => $roleSlug,
                ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function updateUserRole(int $userId, $roleIdOrSlug): bool
    {
        return $this->changeUserRole($userId, $roleIdOrSlug);
    }

    public function flipUserStatus(int $userId): array
    {
        try {
            $db   = Database::connect();
            $user = $db->table('users')->where('id', $userId)->get()->getRowArray();

            if (! $user) {
                return ['status' => 'error', 'message' => 'User not found.'];
            }

            $newStatus = ($user['status'] === 'active') ? 'suspended' : 'active';
            $db->table('users')->where('id', $userId)->update(['status' => $newStatus]);

            return [
                'status'     => 'success',
                'new_status' => $newStatus,
                'message'    => 'Account status changed to ' . strtoupper($newStatus) . '.',
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Could not update status.'];
        }
    }

    public function toggleUserStatus(int $userId): array
    {
        return $this->flipUserStatus($userId);
    }

    public function removeUserById(int $userId): bool
    {
        try {
            $db = Database::connect();
            return (bool) $db->table('users')->where('id', $userId)->delete();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function deleteUserById(int $userId): bool
    {
        return $this->removeUserById($userId);
    }

    public function deleteUser(int $userId): bool
    {
        return $this->removeUserById($userId);
    }
}
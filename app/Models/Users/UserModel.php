<?php

namespace App\Models\Users;

use CodeIgniter\Exceptions\BadMethodCallException;
use CodeIgniter\Model;
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
        'name',
        'username',
        'email',
        'password',
        'password_hash',
        'status',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Called by DashboardController::index(), AuthController & UserController
     * Ensures `roles` and `users` schema integrity, default system roles, and Root Admin (#1) alignment.
     */
    public function ensureRolesAndUsersIntegrity(): void
    {
        try {
            if (! $this->db->tableExists('users')) {
                return;
            }

            $uCols = $this->db->getFieldNames('users');
            if (! in_array('role_slug', $uCols, true)) {
                $this->db->query("ALTER TABLE `users` ADD COLUMN `role_slug` VARCHAR(64) NOT NULL DEFAULT 'student'");
            }
            if (! in_array('parent_user_id', $uCols, true)) {
                $this->db->query("ALTER TABLE `users` ADD COLUMN `parent_user_id` INT UNSIGNED NULL DEFAULT NULL");
            }
            if (! in_array('full_name', $uCols, true)) {
                $this->db->query("ALTER TABLE `users` ADD COLUMN `full_name` VARCHAR(140) NOT NULL DEFAULT ''");
            }
            if (! in_array('status', $uCols, true)) {
                $this->db->query("ALTER TABLE `users` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active'");
            }

            if ($this->db->tableExists('roles')) {
                $rCols = $this->db->getFieldNames('roles');
                if (! in_array('role_slug', $rCols, true)) {
                    $this->db->query("ALTER TABLE `roles` ADD COLUMN `role_slug` VARCHAR(64) NOT NULL DEFAULT 'student'");
                }
                if (! in_array('role_name', $rCols, true)) {
                    $this->db->query("ALTER TABLE `roles` ADD COLUMN `role_name` VARCHAR(120) NOT NULL DEFAULT 'Student'");
                }

                $defaultRoles = [
                    1 => ['slug' => 'super_admin', 'name' => 'Super Administrator'],
                    2 => ['slug' => 'admin',       'name' => 'Administrator'],
                    3 => ['slug' => 'teacher',     'name' => 'Teacher / Instructor'],
                    4 => ['slug' => 'parent',      'name' => 'Parent / Guardian'],
                    5 => ['slug' => 'student',     'name' => 'Student (Learner)'],
                ];

                foreach ($defaultRoles as $defId => $rInfo) {
                    $ex = $this->db->table('roles')->where('role_slug', $rInfo['slug'])->get()->getRowArray();
                    if (! $ex) {
                        $byId = $this->db->table('roles')->where('id', $defId)->get()->getRowArray();
                        if ($byId) {
                            $this->db->table('roles')->where('id', $defId)->update([
                                'role_slug' => $rInfo['slug'],
                                'role_name' => $rInfo['name'],
                            ]);
                        } else {
                            $this->db->table('roles')->insert([
                                'role_slug' => $rInfo['slug'],
                                'role_name' => $rInfo['name'],
                            ]);
                        }
                    }
                }
            }

            // Guarantee Root User #1 is always active Super Admin
            $rootUser = $this->db->table('users')->where('id', 1)->get()->getRowArray();
            if ($rootUser) {
                if ((int) ($rootUser['role_id'] ?? 0) !== 1 || ($rootUser['role_slug'] ?? '') !== 'super_admin' || ($rootUser['status'] ?? '') !== 'active') {
                    $this->db->table('users')->where('id', 1)->update([
                        'role_id'   => 1,
                        'role_slug' => 'super_admin',
                        'status'    => 'active',
                    ]);
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'UserModel::ensureRolesAndUsersIntegrity error: ' . $e->getMessage());
        }
    }

    /**
     * Used by AuthController::authenticate() (`POST login/auth`)
     */
    public function verifyUserCredentials(string $identity, string $rawPassword): ?array
    {
        $identity = trim($identity);
        if ($identity === '' || $rawPassword === '' || ! $this->db->tableExists('users')) {
            return null;
        }

        $uCols   = $this->db->getFieldNames('users');
        $builder = $this->db->table('users');

        $builder->groupStart()
            ->where('email', strtolower($identity));
        if (in_array('username', $uCols, true)) {
            $builder->orWhere('username', $identity);
        }
        $builder->groupEnd();

        $user = $builder->get()->getRowArray();
        if (! $user) {
            return null;
        }

        if ((int) ($user['id'] ?? 0) !== 1 && isset($user['status']) && strtolower((string) $user['status']) !== 'active') {
            return null;
        }

        $storedHash = (string) ($user['password_hash'] ?? ($user['password'] ?? ''));
        $isValid    = false;

        if ($storedHash !== '') {
            if (password_verify($rawPassword, $storedHash)) {
                $isValid = true;
            } elseif ($storedHash === $rawPassword || $storedHash === md5($rawPassword)) {
                $isValid = true;
                $colName = in_array('password_hash', $uCols, true) ? 'password_hash' : 'password';
                $this->db->table('users')->where('id', $user['id'])->update([
                    $colName => password_hash($rawPassword, PASSWORD_DEFAULT),
                ]);
            }
        }

        if (! $isValid) {
            return null;
        }

        if (in_array('last_login_at', $uCols, true)) {
            $this->db->table('users')->where('id', $user['id'])->update([
                'last_login_at' => gmdate('Y-m-d H:i:s'),
            ]);
        }

        return $this->enrichUserRoleAndPermissions($user);
    }

    public function authenticateUser(string $identity, string $rawPassword): ?array
    {
        return $this->verifyUserCredentials($identity, $rawPassword);
    }

    public function verifyCredentials(string $identity, string $rawPassword): ?array
    {
        return $this->verifyUserCredentials($identity, $rawPassword);
    }

    public function findByEmail(string $email): ?array
    {
        if (! $this->db->tableExists('users')) {
            return null;
        }
        $row = $this->db->table('users')->where('email', strtolower(trim($email)))->get()->getRowArray();
        return $row ? $this->enrichUserRoleAndPermissions($row) : null;
    }

    public function getUserByEmail(string $email): ?array
    {
        return $this->findByEmail($email);
    }

    public function getUserWithRole(int $userId): ?array
    {
        if (! $this->db->tableExists('users') || $userId <= 0) {
            return null;
        }
        $row = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
        return $row ? $this->enrichUserRoleAndPermissions($row) : null;
    }

    public function getUserById(int $userId): ?array
    {
        return $this->getUserWithRole($userId);
    }

    public function enrichUserRoleAndPermissions(array $user): array
    {
        $uid   = (int) ($user['id'] ?? 0);
        $rid   = (int) ($user['role_id'] ?? 4);
        $rSlug = strtolower(trim((string) ($user['role_slug'] ?? '')));

        $rolesById   = [];
        $rolesBySlug = [];
        foreach ($this->getAllRoles() as $r) {
            $id   = (int) ($r['id'] ?? 0);
            $slug = strtolower(trim((string) ($r['role_slug'] ?? ($r['name'] ?? 'student'))));
            $rolesById[$id]     = $r;
            $rolesBySlug[$slug] = $r;
        }

        if ($uid === 1) {
            $rSlug = 'super_admin';
            $rid   = 1;
        } elseif ($rSlug === '' && isset($rolesById[$rid])) {
            $rSlug = strtolower(trim((string) ($rolesById[$rid]['role_slug'] ?? 'student')));
        } elseif ($rSlug === '') {
            $rSlug = 'student';
        }

        $roleRow = $rolesBySlug[$rSlug] ?? ($rolesById[$rid] ?? []);
        $perms   = [];
        if ($rSlug === 'super_admin') {
            $perms = ['*'];
        } elseif (! empty($roleRow['permissions_json'])) {
            $decoded = json_decode((string) $roleRow['permissions_json'], true);
            if (is_array($decoded)) {
                $perms = $decoded;
            }
        }

        $cacheFile = WRITEPATH . 'addons/rbac_permissions_cache.json';
        if (is_file($cacheFile)) {
            $c = json_decode((string) @file_get_contents($cacheFile), true);
            if (! empty($c['roles'][$rSlug]['permissions'])) {
                $perms = $c['roles'][$rSlug]['permissions'];
            }
        }

        $countryCode  = 'IN';
        $currencyCode = 'INR';
        $timezone     = 'Asia/Kolkata';
        try {
            if ($this->db->tableExists('cms_user_meta')) {
                $meta = $this->db->table('cms_user_meta')->where('user_id', $uid)->get()->getRowArray();
                if ($meta) {
                    $countryCode  = $meta['country_code'] ?: $countryCode;
                    $currencyCode = $meta['currency_code'] ?: $currencyCode;
                    $timezone     = $meta['timezone'] ?: $timezone;
                }
            }
        } catch (Throwable $e) {
        }

        $fullName = $user['full_name'] ?? ($user['name'] ?? ($user['username'] ?? ($user['email'] ?? 'User')));

        $user['role_id']          = $rid;
        $user['role_slug']        = $rSlug;
        $user['role']             = $rSlug;
        $user['role_name']        = $roleRow['role_name'] ?? ($roleRow['name'] ?? ucfirst(str_replace('_', ' ', $rSlug)));
        $user['portal_type']      = $roleRow['portal_type'] ?? (in_array($rSlug, ['super_admin', 'admin', 'teacher'], true) ? 'backend' : 'frontend');
        $user['full_name']        = $fullName;
        $user['name']             = $fullName;
        $user['permissions']      = $perms;
        $user['role_permissions'] = $perms;
        $user['user_country']     = $countryCode;
        $user['user_currency']    = $currencyCode;
        $user['user_timezone']    = $timezone;

        return $user;
    }

    public function getAllRoles(): array
    {
        $roles = [];
        try {
            if ($this->db->tableExists('cms_roles')) {
                $roles = $this->db->table('cms_roles')->orderBy('id', 'ASC')->get()->getResultArray();
            } elseif ($this->db->tableExists('roles')) {
                $roles = $this->db->table('roles')->orderBy('id', 'ASC')->get()->getResultArray();
            }
        } catch (Throwable $e) {
        }
        return $roles;
    }

    public function getAllUsersWithRoles(): array
    {
        if (! $this->db->tableExists('users')) {
            return [];
        }
        $rolesBySlug = [];
        $rolesById   = [];
        foreach ($this->getAllRoles() as $r) {
            $rid   = (int) ($r['id'] ?? 0);
            $rSlug = strtolower(trim((string) ($r['role_slug'] ?? ($r['name'] ?? 'student'))));
            $rName = $r['role_name'] ?? ($r['name'] ?? ucfirst($rSlug));
            $rolesById[$rid]     = ['slug' => $rSlug, 'name' => $rName];
            $rolesBySlug[$rSlug] = ['id' => $rid, 'name' => $rName];
        }

        $rows = $this->db->table('users')->orderBy('id', 'ASC')->get()->getResultArray();
        $byId = [];
        foreach ($rows as $u) {
            $byId[(int) $u['id']] = $u['full_name'] ?? ($u['name'] ?? ($u['email'] ?? 'User'));
        }

        foreach ($rows as &$u) {
            $rid   = (int) ($u['role_id'] ?? 4);
            $rSlug = strtolower(trim((string) ($u['role_slug'] ?? ($rolesById[$rid]['slug'] ?? 'student'))));
            if ((int) $u['id'] === 1) {
                $rSlug = 'super_admin';
            }
            $u['full_name']   = $u['full_name'] ?? ($u['name'] ?? ($u['username'] ?? 'User'));
            $u['role_slug']   = $rSlug;
            $u['role_name']   = $rolesBySlug[$rSlug]['name'] ?? ($rolesById[$rid]['name'] ?? ucfirst(str_replace('_', ' ', $rSlug)));
            $pid              = (int) ($u['parent_user_id'] ?? 0);
            $u['parent_name'] = ($pid > 0 && isset($byId[$pid])) ? $byId[$pid] : '';
        }
        unset($u);
        return $rows;
    }

    public function getRoleCounts(): array
    {
        $counts = [
            'total'       => 0,
            'super_admin' => 0,
            'admin'       => 0,
            'teacher'     => 0,
            'parent'      => 0,
            'student'     => 0,
            'active'      => 0,
            'inactive'    => 0,
        ];
        if (! $this->db->tableExists('users')) {
            return $counts;
        }
        foreach ($this->getAllUsersWithRoles() as $u) {
            $counts['total']++;
            $st = strtolower((string) ($u['status'] ?? 'active'));
            if ($st === 'active') {
                $counts['active']++;
            } else {
                $counts['inactive']++;
            }
            $rs = $u['role_slug'] ?? 'student';
            if (isset($counts[$rs])) {
                $counts[$rs]++;
            } else {
                $counts['student']++;
            }
        }
        return $counts;
    }

    public function getDashboardStats(): array
    {
        return $this->getRoleCounts();
    }

    public function createNewUser(array $post): array
    {
        $fullName = trim((string) ($post['full_name'] ?? ($post['name'] ?? '')));
        $email    = strtolower(trim((string) ($post['email'] ?? '')));
        $password = (string) ($post['password'] ?? '');
        $roleSlug = strtolower(trim((string) ($post['role_slug'] ?? 'student')));
        $roleId   = (int) ($post['role_id'] ?? 0);

        if ($fullName === '' || $email === '' || strlen($password) < 4) {
            return ['status' => 'error', 'message' => 'Full Name, Email, and Password (min 4 chars) are required.'];
        }

        $dup = $this->db->table('users')->where('email', $email)->get()->getRowArray();
        if ($dup) {
            return ['status' => 'error', 'message' => 'An account with this email already exists.'];
        }

        foreach ($this->getAllRoles() as $r) {
            $rs = strtolower(trim((string) ($r['role_slug'] ?? '')));
            if (($roleId > 0 && (int) $r['id'] === $roleId) || ($rs !== '' && $rs === $roleSlug)) {
                $roleId   = (int) $r['id'];
                $roleSlug = $rs ?: $roleSlug;
                break;
            }
        }
        if ($roleId <= 0) {
            $roleId = 4;
        }

        $now    = gmdate('Y-m-d H:i:s');
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $uCols  = array_flip($this->db->getFieldNames('users'));
        $row    = array_intersect_key([
            'role_id'       => $roleId,
            'role_slug'     => $roleSlug,
            'full_name'     => $fullName,
            'name'          => $fullName,
            'username'      => explode('@', $email)[0],
            'email'         => $email,
            'password'      => $hashed,
            'password_hash' => $hashed,
            'status'        => 'active',
            'created_at'    => $now,
            'updated_at'    => $now,
        ], $uCols);

        $this->db->table('users')->insert($row);
        return ['status' => 'success', 'message' => 'User "' . $fullName . '" created successfully!'];
    }

    public function registerUser(array $post): array
    {
        return $this->createNewUser($post);
    }

    public function assignRole(int $userId, int $roleId, string $roleSlug = ''): array
    {
        if ($userId <= 1) {
            return ['status' => 'error', 'message' => 'Root Super Admin role cannot be modified.'];
        }
        foreach ($this->getAllRoles() as $r) {
            $rs = strtolower(trim((string) ($r['role_slug'] ?? '')));
            if (($roleId > 0 && (int) $r['id'] === $roleId) || ($roleSlug !== '' && $rs === $roleSlug)) {
                $roleId   = (int) $r['id'];
                $roleSlug = $rs ?: $roleSlug;
                break;
            }
        }

        $uCols = array_flip($this->db->getFieldNames('users'));
        $upd   = ['updated_at' => gmdate('Y-m-d H:i:s')];
        if ($roleId > 0 && isset($uCols['role_id'])) $upd['role_id'] = $roleId;
        if ($roleSlug !== '' && isset($uCols['role_slug'])) $upd['role_slug'] = $roleSlug;
        if (in_array($roleSlug, ['super_admin', 'admin', 'teacher', 'parent'], true) && isset($uCols['parent_user_id'])) {
            $upd['parent_user_id'] = null;
        }

        $this->db->table('users')->where('id', $userId)->update($upd);
        return ['status' => 'success', 'message' => 'User role updated successfully!'];
    }

    public function updateUserRole(int $userId, int $roleId, string $roleSlug = ''): array
    {
        return $this->assignRole($userId, $roleId, $roleSlug);
    }

    public function toggleStatus(int $userId): array
    {
        if ($userId <= 1) {
            return ['status' => 'error', 'message' => 'Root Super Admin cannot be deactivated.'];
        }
        $u = $this->find($userId);
        if (! $u) {
            return ['status' => 'error', 'message' => 'User not found.'];
        }
        $newStatus = (($u['status'] ?? 'active') === 'active') ? 'inactive' : 'active';
        $this->db->table('users')->where('id', $userId)->update(['status' => $newStatus]);
        return ['status' => 'success', 'message' => 'User status set to ' . strtoupper($newStatus) . '.'];
    }

    public function toggleUserStatus(int $userId): array
    {
        return $this->toggleStatus($userId);
    }

    public function removeUser(int $userId): array
    {
        if ($userId <= 1) {
            return ['status' => 'error', 'message' => 'Root Super Admin cannot be deleted.'];
        }
        $this->db->table('users')->where('id', $userId)->delete();
        return ['status' => 'success', 'message' => 'User account deleted.'];
    }

    public function deleteUser(int $userId): array
    {
        return $this->removeUser($userId);
    }

    /**
     * Safety net: Prevents fatal BadMethodCallException if any legacy controller calls a custom helper method
     */
    public function __call(string $name, array $params)
    {
        try {
            return parent::__call($name, $params);
        } catch (BadMethodCallException $e) {
            log_message('warning', 'UserModel handled unmapped method call: ' . $name);
            return [];
        }
    }
}
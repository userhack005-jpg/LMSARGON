<?php

namespace App\Models\Users;

use CodeIgniter\Model;
use Config\Database;
use Throwable;

class UserModel extends Model
{
    /**
     * Authenticate User and join Role metadata
     */
    public function verifyUserCredentials(string $email, string $password): ?array
    {
        try {
            $db   = Database::connect();
            $user = $db->table('users u')
                ->select('u.*, r.role_slug, r.role_name')
                ->join('roles r', 'r.id = u.role_id', 'left')
                ->where('u.email', $email)
                ->where('u.status', 'active')
                ->get()
                ->getRowArray();

            if ($user && password_verify($password, $user['password_hash'])) {
                $db->table('users')->where('id', $user['id'])->update(['last_login' => date('Y-m-d H:i:s')]);
                return $user;
            }
            return null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Fetch all pre-seeded RBAC roles
     */
    public function getAvailableRoles(): array
    {
        try {
            $db = Database::connect();
            return $db->table('roles')->orderBy('id', 'ASC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Fetch single role by ID
     */
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

    /**
     * Fetch all users joined with their assigned role metadata
     */
    public function getAllUsersWithRoles(): array
    {
        try {
            $db = Database::connect();
            return $db->table('users u')
                ->select('u.id, u.role_id, u.full_name, u.email, u.status, u.last_login, u.created_at, r.role_slug, r.role_name')
                ->join('roles r', 'r.id = u.role_id', 'left')
                ->orderBy('u.id', 'DESC')
                ->get()
                ->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get live count of users per role
     */
    public function getRoleDistributionStats(): array
    {
        $stats = [
            'total'       => 0,
            'super_admin' => 0,
            'admin'       => 0,
            'teacher'     => 0,
            'student'     => 0,
        ];

        try {
            $db   = Database::connect();
            $rows = $db->table('users u')
                ->select('r.role_slug, COUNT(u.id) as total_count')
                ->join('roles r', 'r.id = u.role_id', 'left')
                ->groupBy('r.role_slug')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $slug  = $r['role_slug'] ?? '';
                $count = (int) $r['total_count'];
                $stats['total'] += $count;
                if (isset($stats[$slug])) {
                    $stats[$slug] = $count;
                }
            }
            return $stats;
        } catch (Throwable $e) {
            return $stats;
        }
    }

    /**
     * Insert a new user with an assigned role
     */
    public function insertNewUser(array $data): array
    {
        try {
            $db = Database::connect();

            $exists = $db->table('users')->where('email', $data['email'])->countAllResults();
            if ($exists > 0) {
                return [
                    'status'  => 'error',
                    'message' => 'A user with this email address already exists.',
                ];
            }

            $db->table('users')->insert([
                'role_id'       => (int) $data['role_id'],
                'full_name'     => $data['full_name'],
                'email'         => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
                'status'        => $data['status'],
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            return [
                'status'  => 'success',
                'message' => 'User account provisioned and role assigned successfully!',
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'error',
                'message' => 'Database Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reassign a user's role
     */
    public function changeUserRole(int $userId, int $roleId): bool
    {
        try {
            $db = Database::connect();
            return (bool) $db->table('users')
                ->where('id', $userId)
                ->update(['role_id' => $roleId]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Toggle user status between 'active' and 'suspended'
     */
    public function flipUserStatus(int $userId): array
    {
        try {
            $db   = Database::connect();
            $user = $db->table('users')->where('id', $userId)->get()->getRowArray();

            if (! $user) {
                return [
                    'status'  => 'error',
                    'message' => 'User not found.',
                ];
            }

            $newStatus = ($user['status'] === 'active') ? 'suspended' : 'active';
            $db->table('users')->where('id', $userId)->update(['status' => $newStatus]);

            return [
                'status'     => 'success',
                'new_status' => $newStatus,
                'message'    => 'Account status changed to ' . strtoupper($newStatus) . '.',
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'error',
                'message' => 'Could not update status.',
            ];
        }
    }

    /**
     * Delete user by ID
     */
    public function removeUserById(int $userId): bool
    {
        try {
            $db = Database::connect();
            return (bool) $db->table('users')->where('id', $userId)->delete();
        } catch (Throwable $e) {
            return false;
        }
    }
}
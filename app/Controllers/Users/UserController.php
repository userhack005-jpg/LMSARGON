<?php

namespace App\Controllers\Users;

use App\Controllers\BaseController;
use App\Models\Settings\SettingModel;
use App\Models\Users\UserModel;

class UserController extends BaseController
{
    protected SettingModel $settingModel;
    protected UserModel $userModel;
    protected string $lockFile;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->settingModel = new SettingModel();
        $this->userModel    = new UserModel();
        $this->lockFile     = WRITEPATH . 'installed.lock';
    }

    private function canManageUsers(): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        $role = (string) session()->get('role_slug');
        return in_array($role, ['super_admin', 'admin'], true);
    }

    /**
     * Sidebar Module View: Add Users & Role Directory
     */
    public function index()
    {
        if (! is_file($this->lockFile)) {
            return redirect()->to(base_url('install'));
        }

        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $settings = $this->settingModel->getSystemSettings();
        $roles    = $this->userModel->getAvailableRoles();
        $users    = $this->userModel->getAllUsersWithRoles();
        $stats    = $this->userModel->getRoleDistributionStats();

        return view('users/manage_view', [
            'activeMenu' => 'users',
            'settings'   => $settings,
            'roles'      => $roles,
            'users'      => $users,
            'stats'      => $stats,
            'user'       => session()->get(),
            'pageTitle'  => ($settings['site_title'] ?? 'System') . ' — User & Role Management',
        ]);
    }

    /**
     * AJAX Endpoint: Create New User
     */
    public function createUser()
    {
        if (! $this->canManageUsers()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized: Only Super Admin or Admin can provision users.',
            ]);
        }

        $fullName = trim((string) $this->request->getPost('full_name'));
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $roleId   = (int) $this->request->getPost('role_id');
        $status   = (string) ($this->request->getPost('status') ?: 'active');

        if ($fullName === '' || $email === '' || $password === '' || $roleId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'All fields (Full Name, Email, Password, and Assigned Role) are required.',
            ]);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Please enter a valid email address.',
            ]);
        }

        if (strlen($password) < 6) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Password must be at least 6 characters long.',
            ]);
        }

        $targetRole = $this->userModel->getRoleById($roleId);
        if (! $targetRole) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Selected role does not exist.',
            ]);
        }

        if ($targetRole['role_slug'] === 'super_admin' && session()->get('role_slug') !== 'super_admin') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Only a Super Admin can assign the Super Admin role.',
            ]);
        }

        $result = $this->userModel->insertNewUser([
            'role_id'   => $roleId,
            'full_name' => $fullName,
            'email'     => $email,
            'password'  => $password,
            'status'    => in_array($status, ['active', 'suspended'], true) ? $status : 'active',
        ]);

        return $this->response->setJSON($result);
    }

    /**
     * AJAX Endpoint: Reassign User Role
     */
    public function updateUserRole()
    {
        if (! $this->canManageUsers()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized action.',
            ]);
        }

        $targetUserId = (int) $this->request->getPost('user_id');
        $newRoleId    = (int) $this->request->getPost('role_id');
        $currentId    = (int) session()->get('user_id');

        if ($targetUserId === $currentId) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Safety Lock: You cannot demote or alter your own active role.',
            ]);
        }

        $targetRole = $this->userModel->getRoleById($newRoleId);
        if (! $targetRole) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid role selected.',
            ]);
        }

        if ($targetRole['role_slug'] === 'super_admin' && session()->get('role_slug') !== 'super_admin') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Only a Super Admin can promote users to Super Admin.',
            ]);
        }

        $updated = $this->userModel->changeUserRole($targetUserId, $newRoleId);

        if ($updated) {
            return $this->response->setJSON([
                'status'    => 'success',
                'message'   => 'User role updated to ' . $targetRole['role_name'] . '.',
                'role_slug' => $targetRole['role_slug'],
                'role_name' => $targetRole['role_name'],
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Failed to update role.',
        ]);
    }

    /**
     * AJAX Endpoint: Toggle User Status
     */
    public function toggleUserStatus()
    {
        if (! $this->canManageUsers()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized action.',
            ]);
        }

        $targetUserId = (int) $this->request->getPost('user_id');
        $currentId    = (int) session()->get('user_id');

        if ($targetUserId === $currentId) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Safety Lock: You cannot suspend your own active account.',
            ]);
        }

        $result = $this->userModel->flipUserStatus($targetUserId);
        return $this->response->setJSON($result);
    }

    /**
     * AJAX Endpoint: Delete User
     */
    public function deleteUser()
    {
        if (! $this->canManageUsers()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized action.',
            ]);
        }

        $targetUserId = (int) $this->request->getPost('user_id');
        $currentId    = (int) session()->get('user_id');

        if ($targetUserId === $currentId) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Safety Lock: You cannot delete your own logged-in account.',
            ]);
        }

        $deleted = $this->userModel->removeUserById($targetUserId);

        if ($deleted) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'User account permanently removed.',
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Unable to remove user.',
        ]);
    }
}
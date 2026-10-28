<?php

namespace App\Controllers\Users;

use App\Controllers\BaseController;
use App\Models\Settings\SettingModel;
use App\Models\Users\UserModel;

class UserController extends BaseController
{
    protected UserModel $userModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->userModel    = new UserModel();
        $this->settingModel = new SettingModel();
    }

    private function checkPerm($permKeys): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        if (function_exists('cms_has_permission')) {
            return cms_has_permission($permKeys);
        }
        return (int) session()->get('role_id') === 1 || (string) session()->get('role_slug') === 'super_admin';
    }

    public function index()
    {
        if (! is_file(WRITEPATH . 'installed.lock')) {
            return redirect()->to(base_url('install'));
        }
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->checkPerm(['users.view', 'users.create', 'users.edit', 'users.delete'])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied: Missing users.view permission.');
        }

        $this->userModel->ensureRolesAndUsersIntegrity();

        $settings = $this->settingModel->getSystemSettings();
        $users    = $this->userModel->getUsersWithRoles();
        $roles    = $this->userModel->getAllRoles();
        $stats    = $this->userModel->getRoleDistributionStats();

        return view('users/manage_view', [
            'activeMenu' => 'users',
            'settings'   => $settings,
            'users'      => $users,
            'roles'      => $roles,
            'stats'      => $stats,
            'user'       => session()->get(),
            'pageTitle'  => 'User Directory & Role Management',
        ]);
    }

    public function createUser()
    {
        if (! $this->checkPerm('users.create')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Access denied: Missing users.create permission.']);
        }

        $fullName = trim((string) $this->request->getPost('full_name'));
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $roleId   = (int) $this->request->getPost('role_id');
        $roleSlug = strtolower(trim((string) $this->request->getPost('role_slug')));
        $status   = (string) ($this->request->getPost('status') ?: 'active');

        if ($fullName === '' || $email === '' || strlen($password) < 6) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Please enter Full Name, valid Email, and Password (min. 6 characters).',
            ]);
        }

        $result = $this->userModel->insertNewUser([
            'full_name' => $fullName,
            'email'     => $email,
            'password'  => $password,
            'role_id'   => $roleId,
            'role_slug' => $roleSlug,
            'status'    => in_array($status, ['active', 'suspended'], true) ? $status : 'active',
        ]);

        return $this->response->setJSON($result);
    }

    public function updateUserRole()
    {
        if (! $this->checkPerm('users.edit')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Access denied: Missing users.edit permission.']);
        }

        $userId       = (int) ($this->request->getPost('user_id') ?: $this->request->getPost('id'));
        $roleIdOrSlug = $this->request->getPost('role_id') ?: $this->request->getPost('role_slug');

        if ($userId <= 0 || empty($roleIdOrSlug)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid user or role specified.']);
        }

        if ($userId === 1 && (string) session()->get('role_slug') !== 'super_admin') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Primary Super Admin account is protected.']);
        }

        $ok = $this->userModel->changeUserRole($userId, $roleIdOrSlug);

        return $this->response->setJSON([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'User role updated & synchronized!' : 'Could not update user role.',
        ]);
    }

    public function toggleUserStatus()
    {
        if (! $this->checkPerm('users.edit')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Access denied: Missing users.edit permission.']);
        }

        $userId = (int) ($this->request->getPost('user_id') ?: $this->request->getPost('id'));
        if ($userId === (int) session()->get('user_id') || $userId === 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cannot suspend your own or Super Admin account.']);
        }

        return $this->response->setJSON($this->userModel->flipUserStatus($userId));
    }

    public function deleteUser()
    {
        if (! $this->checkPerm('users.delete')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Access denied: Missing users.delete permission.']);
        }

        $userId = (int) ($this->request->getPost('user_id') ?: $this->request->getPost('id'));
        if ($userId === (int) session()->get('user_id') || $userId === 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cannot delete your own or Super Admin account.']);
        }

        $ok = $this->userModel->removeUserById($userId);

        return $this->response->setJSON([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'User deleted permanently.' : 'Failed to delete user.',
        ]);
    }
}
<?php

namespace App\Controllers\Addons\Rbac;

use App\Controllers\BaseController;
use App\Models\Addons\Rbac\RbacModel;

class RbacController extends BaseController
{
    protected ?RbacModel $rbacModel = null;
    protected string $addonPath;

    public function __construct()
    {
        $this->addonPath = WRITEPATH . 'addons/extracted/rbac';
    }

    private function getModel(): RbacModel
    {
        if ($this->rbacModel === null) {
            $modelFile = $this->addonPath . '/Models/Addons/Rbac/RbacModel.php';
            if (! class_exists(RbacModel::class) && is_file($modelFile)) {
                require_once $modelFile;
            }
            $this->rbacModel = new RbacModel();
        }
        return $this->rbacModel;
    }

    private function hasPermission(string $permissionKey): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        if (function_exists('has_permission')) {
            return (bool) has_permission($permissionKey);
        }
        $roleSlug = (string) session()->get('role_slug');
        if ($roleSlug === 'super_admin') {
            return true;
        }
        $perms = session()->get('permissions') ?? session()->get('role_permissions');
        if (is_array($perms)) {
            if (in_array($permissionKey, $perms, true)) {
                return true;
            }
            if (in_array('*', $perms, true)) {
                return true;
            }
            return false;
        }
        return $roleSlug === 'admin';
    }

    private function renderAddonView(string $viewFile, array $data = []): string
    {
        \Config\Services::autoloader()->addNamespace('AddonRbac', $this->addonPath);
        \Config\Services::autoloader()->addNamespace('App', $this->addonPath);

        $fullViewPath = $this->addonPath . '/Views/' . ltrim($viewFile, '/') . '.php';
        if (is_file($fullViewPath)) {
            $renderer = \Config\Services::renderer($this->addonPath . '/Views', null, false);
            return $renderer->setData($data)->render($viewFile);
        }

        return view($viewFile, $data);
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('rbac.view')) {
            return redirect()->to('/dashboard')->with('error', 'Access denied. You do not have permission to view Role Management.');
        }

        $model = $this->getModel();
        $roles = $model->getAllRoles();

        $selectedRoleSlug = trim((string) ($this->request->getGet('role') ?? 'admin'));
        $activeRole       = null;
        foreach ($roles as $r) {
            if ($r['role_slug'] === $selectedRoleSlug) {
                $activeRole = $r;
                break;
            }
        }
        if (! $activeRole && ! empty($roles)) {
            $activeRole       = $roles[0];
            $selectedRoleSlug = $activeRole['role_slug'];
        }

        $groupedFeatures = $model->getGroupedFeatures();
        $usersList       = $model->getUsersWithRoles();

        return $this->renderAddonView('addons/rbac/index_view', [
            'pageTitle'        => 'Dynamic Role & Permission Matrix',
            'activeNav'        => 'rbac',
            'user'             => session()->get(),
            'roles'            => $roles,
            'activeRole'       => $activeRole,
            'selectedRoleSlug' => $selectedRoleSlug,
            'groupedFeatures'  => $groupedFeatures,
            'usersList'        => $usersList,
            'canCreate'        => $this->hasPermission('rbac.create'),
            'canEdit'          => $this->hasPermission('rbac.edit'),
            'canDelete'        => $this->hasPermission('rbac.delete'),
        ]);
    }

    public function saveRole()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0 && ! $this->hasPermission('rbac.edit')) {
            return redirect()->to('/addons/rbac')->with('error', 'Access denied.');
        }
        if ($id === 0 && ! $this->hasPermission('rbac.create')) {
            return redirect()->to('/addons/rbac')->with('error', 'Access denied.');
        }

        $res = $this->getModel()->saveRoleData($this->request->getPost());
        return redirect()->to('/addons/rbac')->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteRole()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('rbac.delete')) {
            return redirect()->to('/addons/rbac')->with('error', 'Access denied.');
        }

        $id  = (int) ($this->request->getPost('id') ?? 0);
        $res = $this->getModel()->deleteCustomRole($id);
        return redirect()->to('/addons/rbac')->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function saveMatrix()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('rbac.edit')) {
            return redirect()->to('/addons/rbac')->with('error', 'Access denied.');
        }

        $roleId   = (int) ($this->request->getPost('role_id') ?? 0);
        $roleSlug = trim((string) ($this->request->getPost('role_slug') ?? 'admin'));
        $perms    = $this->request->getPost('permissions');
        if (! is_array($perms)) {
            $perms = [];
        }

        $res = $this->getModel()->updateRolePermissions($roleId, $perms);

        if ((string) session()->get('role_slug') === $roleSlug) {
            session()->set([
                'permissions'      => $perms,
                'role_permissions' => $perms,
            ]);
        }

        return redirect()->to('/addons/rbac?role=' . urlencode($roleSlug))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function saveFeature()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('rbac.create')) {
            return redirect()->to('/addons/rbac')->with('error', 'Access denied.');
        }

        $key   = (string) ($this->request->getPost('perm_key') ?? '');
        $label = (string) ($this->request->getPost('perm_label') ?? '');
        $group = (string) ($this->request->getPost('module_group') ?? 'Custom Website Features');

        $res = $this->getModel()->addCustomFeature($key, $label, $group);
        return redirect()->to('/addons/rbac')->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteFeature()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('rbac.delete')) {
            return redirect()->to('/addons/rbac')->with('error', 'Access denied.');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $this->getModel()->deleteCustomFeature($id);
        return redirect()->to('/addons/rbac')->with('success', 'Custom permission key removed.');
    }

    public function assignUserRole()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('rbac.edit')) {
            return redirect()->to('/addons/rbac')->with('error', 'Access denied.');
        }

        $userId   = (int) ($this->request->getPost('user_id') ?? 0);
        $roleSlug = trim((string) ($this->request->getPost('role_slug') ?? 'student'));
        $parentId = (int) ($this->request->getPost('parent_user_id') ?? 0);

        $res = $this->getModel()->assignRoleToUser($userId, $roleSlug, $parentId > 0 ? $parentId : null);
        return redirect()->to('/addons/rbac')->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function syncAddons()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('rbac.edit')) {
            return redirect()->to('/addons/rbac')->with('error', 'Access denied.');
        }

        $model = $this->getModel();
        $model->syncCoreAndAddonPermissions();
        $model->rebuildStaticCache();

        return redirect()->to('/addons/rbac')->with('success', 'Auto-discovered all installed Add-on permissions & rebuilt 0ms cache!');
    }

    public function asset($file = '')
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        $safeFile = basename((string) $file);
        $fullPath = $this->addonPath . '/assets/' . $safeFile;
        if (! is_file($fullPath)) {
            return $this->response->setStatusCode(404)->setBody('/* Asset not found */');
        }

        $ext  = strtolower((string) pathinfo($safeFile, PATHINFO_EXTENSION));
        $mime = ($ext === 'css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody((string) file_get_contents($fullPath));
    }
}

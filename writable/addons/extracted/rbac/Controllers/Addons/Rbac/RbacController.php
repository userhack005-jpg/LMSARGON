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
        helper(['url', 'form']);
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

    private function hasPermission($permissionKeys): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        if (function_exists('cms_has_permission')) {
            return cms_has_permission($permissionKeys);
        }
        return (int) session()->get('role_id') === 1 || (string) session()->get('role_slug') === 'super_admin';
    }

    private function renderAddonView(string $viewRelPath, array $data = []): string
    {
        $cleanView = ltrim($viewRelPath, '/');
        if (is_file(APPPATH . 'Views/' . $cleanView . '.php')) {
            return view($cleanView, $data);
        }

        \Config\Services::autoloader()->addNamespace('AddonRbac', $this->addonPath);
        $nsView = 'AddonRbac\\Views\\' . str_replace('/', '\\', $cleanView);
        return view($nsView, $data);
    }

    private function buildCommonAdminData(string $activeSubTab, string $pageTitle): array
    {
        $model = $this->getModel();
        $model->mirrorAssets(false);
        $roles = $model->getAllRoles();

        $selectedRoleSlug = trim((string) ($this->request->getGet('role') ?? 'student'));
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

        return [
            'pageTitle'        => $pageTitle,
            'activeMenu'       => 'addon_rbac',
            'activeNav'        => 'rbac',
            'activeSubTab'     => $activeSubTab,
            'settings'         => $model->getGlobalSiteSettings(),
            'user'             => session()->get(),
            'roles'            => $roles,
            'activeRole'       => $activeRole,
            'selectedRoleSlug' => $selectedRoleSlug,
            'groupedFeatures'  => $model->getGroupedFeatures(),
            'navCatalog'       => $model->discoverActiveNavigationTree(),
            'canView'          => $this->hasPermission('rbac.view'),
            'canCreate'        => $this->hasPermission('rbac.create'),
            'canEdit'          => $this->hasPermission('rbac.edit'),
            'canDelete'        => $this->hasPermission('rbac.delete'),
        ];
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission('rbac.view')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied.');
        }

        $data = $this->buildCommonAdminData('overview', 'Roles & Live Sidebar Preview');
        return $this->renderAddonView('addons/rbac/index_view', $data);
    }

    public function permissionMatrix()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission('rbac.view')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied.');
        }

        $data = $this->buildCommonAdminData('matrix', 'Permission Switch Matrix & Role Builder');
        return $this->renderAddonView('addons/rbac/matrix_view', $data);
    }

    public function workspaceArchitect()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['rbac.edit', 'rbac.view'])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied.');
        }

        $data = $this->buildCommonAdminData('workspace', 'Sidebar Menu Renamer & Dashboard Widgets');
        return $this->renderAddonView('addons/rbac/workspace_view', $data);
    }

    /**
     * Redirects legacy `/addons/rbac/users` to the Single Source of Truth (`/users`)
     */
    public function redirectToCoreUsers()
    {
        return redirect()->to(base_url('users'));
    }

    public function saveRole()
    {
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0 && ! $this->hasPermission('rbac.edit')) {
            return redirect()->to(base_url('addons/rbac/matrix'))->with('error', 'Access denied.');
        }
        if ($id === 0 && ! $this->hasPermission('rbac.create')) {
            return redirect()->to(base_url('addons/rbac/matrix'))->with('error', 'Access denied.');
        }

        $res  = $this->getModel()->saveRoleData($this->request->getPost());
        $slug = $res['role_slug'] ?? 'admin';
        return redirect()->to(base_url('addons/rbac/matrix?role=' . urlencode((string) $slug)))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteRole()
    {
        if (! $this->hasPermission('rbac.delete')) {
            return redirect()->to(base_url('addons/rbac/matrix'))->with('error', 'Access denied.');
        }

        $id  = (int) ($this->request->getPost('id') ?? 0);
        $res = $this->getModel()->deleteCustomRole($id);
        return redirect()->to(base_url('addons/rbac/matrix'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function saveMatrix()
    {
        if (! $this->hasPermission('rbac.edit')) {
            return redirect()->to(base_url('addons/rbac/matrix'))->with('error', 'Access denied.');
        }

        $roleId   = (int) ($this->request->getPost('role_id') ?? 0);
        $roleSlug = trim((string) ($this->request->getPost('role_slug') ?? 'admin'));
        $perms    = $this->request->getPost('permissions');
        if (! is_array($perms)) {
            $perms = [];
        }

        $res = $this->getModel()->updateRolePermissions($roleId, $perms);
        session()->set('_rbac_sync_key', '');

        return redirect()->to(base_url('addons/rbac/matrix?role=' . urlencode($roleSlug)))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function saveWorkspace()
    {
        if (! $this->hasPermission('rbac.edit')) {
            return redirect()->to(base_url('addons/rbac/workspace'))->with('error', 'Access denied.');
        }

        $roleId   = (int) ($this->request->getPost('role_id') ?? 0);
        $roleSlug = trim((string) ($this->request->getPost('role_slug') ?? 'admin'));

        $res = $this->getModel()->updateRoleWorkspaceConfig($roleId, $this->request->getPost());
        session()->set('_rbac_sync_key', '');

        return redirect()->to(base_url('addons/rbac/workspace?role=' . urlencode($roleSlug)))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function cloneRoleConfig()
    {
        if (! $this->hasPermission('rbac.edit')) {
            return redirect()->to(base_url('addons/rbac/matrix'))->with('error', 'Access denied.');
        }

        $targetRoleId   = (int) ($this->request->getPost('target_role_id') ?? 0);
        $targetRoleSlug = trim((string) ($this->request->getPost('target_role_slug') ?? 'admin'));
        $sourceRoleSlug = trim((string) ($this->request->getPost('source_role_slug') ?? ''));

        $res = $this->getModel()->cloneRoleConfiguration($targetRoleId, $sourceRoleSlug);
        session()->set('_rbac_sync_key', '');

        return redirect()->to(base_url('addons/rbac/workspace?role=' . urlencode($targetRoleSlug)))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function applyRecommendedPresets()
    {
        if (! $this->hasPermission('rbac.edit')) {
            return redirect()->to(base_url('addons/rbac'))->with('error', 'Access denied.');
        }

        $res = $this->getModel()->applySmartRolePresets();
        session()->set('_rbac_sync_key', '');

        return redirect()->back()->with('success', $res['message']);
    }

    public function saveFeature()
    {
        if (! $this->hasPermission('rbac.create')) {
            return redirect()->to(base_url('addons/rbac/matrix'))->with('error', 'Access denied.');
        }

        $key   = (string) ($this->request->getPost('perm_key') ?? '');
        $label = (string) ($this->request->getPost('perm_label') ?? '');
        $group = (string) ($this->request->getPost('module_group') ?? 'Custom Website Features');

        $res = $this->getModel()->addCustomFeature($key, $label, $group);
        return redirect()->to(base_url('addons/rbac/matrix'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteFeature()
    {
        if (! $this->hasPermission('rbac.delete')) {
            return redirect()->to(base_url('addons/rbac/matrix'))->with('error', 'Access denied.');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $this->getModel()->deleteCustomFeature($id);
        return redirect()->to(base_url('addons/rbac/matrix'))->with('success', 'Custom permission key removed.');
    }

    public function syncAddons()
    {
        if (! $this->hasPermission('rbac.edit')) {
            return redirect()->to(base_url('addons/rbac'))->with('error', 'Access denied.');
        }

        $model = $this->getModel();
        $model->syncCoreAndAddonPermissions();
        $model->rebuildStaticCache();
        session()->set('_rbac_sync_key', '');

        return redirect()->back()->with('success', 'Synced Active Add-ons, Sub-Views & Rebuilt 0ms Workspace Cache!');
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
            ->setHeader('Cache-Control', 'no-cache, must-revalidate')
            ->setBody((string) file_get_contents($fullPath));
    }
}

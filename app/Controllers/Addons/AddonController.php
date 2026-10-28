<?php

namespace App\Controllers\Addons;

use App\Controllers\BaseController;
use App\Models\Addons\AddonModel;
use App\Models\Settings\SettingModel;
use Throwable;

class AddonController extends BaseController
{
    protected AddonModel $addonModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form', 'filesystem']);
        $this->addonModel   = new AddonModel();
        $this->settingModel = new SettingModel();
    }

    private function isAuthorized(): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        if (function_exists('cms_has_permission')) {
            return cms_has_permission('addons.manage');
        }
        return (int) session()->get('role_id') === 1 || (string) session()->get('role_slug') === 'super_admin';
    }

    /**
     * Automatically rebuilds or deletes `rbac_permissions_cache.json` whenever any add-on is installed, toggled, or deleted!
     */
    private function syncRbacCacheAfterAddonChange(): void
    {
        $cacheFile = WRITEPATH . 'addons/rbac_permissions_cache.json';
        try {
            $activeAddons = $this->addonModel->getActiveAddons();
            $isRbacActive = false;
            foreach ($activeAddons as $a) {
                if (($a['slug'] ?? '') === 'rbac') {
                    $isRbacActive = true;
                    break;
                }
            }

            if (! $isRbacActive) {
                if (is_file($cacheFile)) {
                    @unlink($cacheFile);
                }
                return;
            }

            $rbacModelFile = WRITEPATH . 'addons/extracted/rbac/Models/Addons/Rbac/RbacModel.php';
            if (is_file($rbacModelFile)) {
                require_once $rbacModelFile;
                if (class_exists('\App\Models\Addons\Rbac\RbacModel')) {
                    (new \App\Models\Addons\Rbac\RbacModel())->rebuildStaticCache();
                    return;
                }
            }
        } catch (Throwable $e) {
        }

        if (is_file($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    private function respondOrRedirect(array $result)
    {
        if ($this->request->isAJAX() || strpos((string) $this->request->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->response->setJSON($result);
        }

        $flashKey = (($result['status'] ?? '') === 'success') ? 'success' : 'error';
        return redirect()->to(base_url('addons'))->with($flashKey, $result['message'] ?? 'Operation completed.');
    }

    public function index()
    {
        if (! is_file(WRITEPATH . 'installed.lock')) {
            return redirect()->to(base_url('install'));
        }
        if (! $this->isAuthorized()) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied: Missing addons.manage permission.');
        }

        return view('addons/index_view', [
            'activeMenu' => 'addons',
            'settings'   => $this->settingModel->getSystemSettings(),
            'addons'     => $this->addonModel->getAllAddons(),
            'user'       => session()->get(),
            'pageTitle'  => 'Modular Add-On Manager',
        ]);
    }

    public function uploadAndInstall()
    {
        if (! $this->isAuthorized()) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Unauthorized: Missing addons.manage permission.']);
        }

        $zipFile = $this->request->getFile('addon_zip');
        if (! $zipFile || ! $zipFile->isValid() || strtolower($zipFile->getClientExtension()) !== 'zip') {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Please upload a valid .zip package.']);
        }

        $pkgDir = WRITEPATH . 'addons/packages/';
        if (! is_dir($pkgDir)) {
            mkdir($pkgDir, 0775, true);
        }

        $tempName = 'tmp_' . time() . '.zip';
        $zipFile->move($pkgDir, $tempName);

        $result = $this->addonModel->installFromZip($pkgDir . $tempName);
        $this->syncRbacCacheAfterAddonChange();

        return $this->respondOrRedirect($result);
    }

    public function toggleAddon()
    {
        if (! $this->isAuthorized()) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Unauthorized: Missing addons.manage permission.']);
        }

        $id  = (int) ($this->request->getPost('addon_id') ?: $this->request->getPost('id'));
        $res = $this->addonModel->toggleStatus($id);
        $this->syncRbacCacheAfterAddonChange();

        return $this->respondOrRedirect($res);
    }

    public function deleteAddon()
    {
        if (! $this->isAuthorized()) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Unauthorized: Missing addons.manage permission.']);
        }

        $id  = (int) ($this->request->getPost('addon_id') ?: $this->request->getPost('id'));
        $res = $this->addonModel->uninstallAddon($id);
        $this->syncRbacCacheAfterAddonChange();

        return $this->respondOrRedirect($res);
    }

    public function downloadSampleGatewayZip()
    {
        return redirect()->to(base_url('addons'));
    }

    public function downloadPageBuilderZip()
    {
        return redirect()->to(base_url('addons'));
    }
}
<?php

namespace App\Controllers\Addons;

use App\Controllers\BaseController;
use App\Models\Addons\AddonModel;
use App\Models\Settings\SettingModel;

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
        return session()->get('isLoggedIn') && in_array((string) session()->get('role_slug'), ['super_admin', 'admin'], true);
    }

    public function index()
    {
        if (! is_file(WRITEPATH . 'installed.lock')) {
            return redirect()->to(base_url('install'));
        }
        if (! $this->isAuthorized()) {
            return redirect()->to(base_url('login'));
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
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized.']);
        }

        $zipFile = $this->request->getFile('addon_zip');
        if (! $zipFile || ! $zipFile->isValid() || strtolower($zipFile->getClientExtension()) !== 'zip') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Please upload a valid .zip package.']);
        }

        $pkgDir = WRITEPATH . 'addons/packages/';
        if (! is_dir($pkgDir)) {
            mkdir($pkgDir, 0775, true);
        }

        $tempName = 'tmp_' . time() . '.zip';
        $zipFile->move($pkgDir, $tempName);

        // Install & automatically delete the temporary .zip file
        $result = $this->addonModel->installFromZip($pkgDir . $tempName);

        return $this->response->setJSON($result);
    }

    public function toggleAddon()
    {
        if (! $this->isAuthorized()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized.']);
        }

        $id = (int) $this->request->getPost('addon_id');
        return $this->response->setJSON($this->addonModel->toggleStatus($id));
    }

    public function deleteAddon()
    {
        if (! $this->isAuthorized()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized.']);
        }

        $id = (int) $this->request->getPost('addon_id');
        return $this->response->setJSON($this->addonModel->uninstallAddon($id));
    }
}
<?php

namespace App\Controllers\Installer;

use App\Controllers\BaseController;
use App\Models\Installer\InstallerModel;

class InstallerController extends BaseController
{
    protected InstallerModel $installerModel;
    protected string $lockFile;

    public function __construct()
    {
        helper(['url', 'form', 'filesystem']);
        $this->installerModel = new InstallerModel();
        $this->lockFile       = WRITEPATH . 'installed.lock';
    }

    private function isInstalled(): bool
    {
        return is_file($this->lockFile);
    }

    /**
     * Render Step-by-Step Installer Wizard
     */
    public function index()
    {
        if ($this->isInstalled()) {
            return redirect()->to(base_url('login'));
        }

        return view('installer/install_view', [
            'pageTitle' => 'System Initialization — Setup Wizard',
        ]);
    }

    /**
     * AJAX Endpoint: Processes DB Creation, Schema Migration, .env Update & Super Admin
     */
    public function processInstall()
    {
        if ($this->isInstalled()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'System is already installed and locked.',
            ]);
        }

        $siteTitle  = trim((string) $this->request->getPost('site_title'));
        $dbHost     = trim((string) $this->request->getPost('db_host'));
        $dbPort     = (int) ($this->request->getPost('db_port') ?: 3306);
        $dbName     = trim((string) $this->request->getPost('db_name'));
        $dbUser     = trim((string) $this->request->getPost('db_user'));
        $dbPass     = (string) $this->request->getPost('db_pass');
        $adminName  = trim((string) $this->request->getPost('admin_name'));
        $adminEmail = trim((string) $this->request->getPost('admin_email'));
        $adminPass  = (string) $this->request->getPost('admin_pass');

        if ($siteTitle === '' || $dbHost === '' || $dbName === '' || $dbUser === '' || $adminEmail === '' || $adminPass === '') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Please fill in all required fields (Site Title, DB Details, and Super Admin credentials).',
            ]);
        }

        // Optional Logo Upload
        $logoPath  = null;
        $uploadDir = FCPATH . 'uploads/branding/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $logoFile = $this->request->getFile('site_logo');
        if ($logoFile && $logoFile->isValid() && ! $logoFile->hasMoved()) {
            $ext = strtolower($logoFile->getClientExtension());
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'], true)) {
                $newLogoName = 'logo_' . time() . '.' . $ext;
                $logoFile->move($uploadDir, $newLogoName);
                $logoPath = 'uploads/branding/' . $newLogoName;
            }
        }

        // Optional Favicon Upload
        $faviconPath = null;
        $favFile     = $this->request->getFile('site_favicon');
        if ($favFile && $favFile->isValid() && ! $favFile->hasMoved()) {
            $ext = strtolower($favFile->getClientExtension());
            if (in_array($ext, ['ico', 'png', 'svg'], true)) {
                $newFavName = 'favicon_' . time() . '.' . $ext;
                $favFile->move($uploadDir, $newFavName);
                $faviconPath = 'uploads/branding/' . $newFavName;
            }
        }

        $setupResult = $this->installerModel->initializeSystemDatabase([
            'host' => $dbHost,
            'port' => $dbPort,
            'name' => $dbName,
            'user' => $dbUser,
            'pass' => $dbPass,
        ], [
            'site_title'   => $siteTitle,
            'site_logo'    => $logoPath,
            'site_favicon' => $faviconPath,
            'admin_name'   => $adminName ?: 'Super Admin',
            'admin_email'  => $adminEmail,
            'admin_pass'   => $adminPass,
        ]);

        if (! $setupResult['success']) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $setupResult['message'],
            ]);
        }

        $this->installerModel->writeEnvironmentConfig([
            'baseURL'  => base_url(),
            'hostname' => $dbHost,
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass,
            'port'     => $dbPort,
        ]);

        file_put_contents($this->lockFile, json_encode([
            'installed_at' => date('Y-m-d H:i:s'),
            'site_title'   => $siteTitle,
        ], JSON_PRETTY_PRINT));

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Database provisioned and system configured! Redirecting to login...',
            'redirect' => base_url('login?installed=1'),
        ]);
    }
}
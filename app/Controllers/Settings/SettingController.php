<?php

namespace App\Controllers\Settings;

use App\Controllers\BaseController;
use App\Models\Settings\SettingModel;

class SettingController extends BaseController
{
    protected SettingModel $settingModel;
    protected string $lockFile;

    public function __construct()
    {
        helper(['url', 'form', 'filesystem']);
        $this->settingModel = new SettingModel();
        $this->lockFile     = WRITEPATH . 'installed.lock';
    }

    private function canManageSettings(): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        $role = (string) session()->get('role_slug');
        return in_array($role, ['super_admin', 'admin'], true);
    }

    /**
     * Render Site Settings, SEO, Analytics & Custom Code View
     */
    public function index()
    {
        if (! is_file($this->lockFile)) {
            return redirect()->to(base_url('install'));
        }

        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        if (! $this->canManageSettings()) {
            return redirect()->to(base_url('dashboard'));
        }

        // Auto-runs one-time schema check if not yet migrated
        $settings = $this->settingModel->getSystemSettings();

        return view('settings/index_view', [
            'activeMenu' => 'settings',
            'settings'   => $settings,
            'user'       => session()->get(),
            'pageTitle'  => ($settings['site_title'] ?? 'System') . ' — Global Site & SEO Settings',
        ]);
    }

    /**
     * AJAX Endpoint: Save Branding, SEO, Analytics & Custom Code
     */
    public function update()
    {
        if (! $this->canManageSettings()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized: Only Super Admin or Admin can modify system settings.',
            ]);
        }

        $siteTitle = trim((string) $this->request->getPost('site_title'));
        if ($siteTitle === '') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Website Title cannot be empty.',
            ]);
        }

        $currentSettings = $this->settingModel->getSystemSettings();
        $uploadDir       = FCPATH . 'uploads/branding/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        // 1. Handle Brand Logo Upload or Removal
        $logoPath = $currentSettings['site_logo'];
        if ($this->request->getPost('remove_logo') === '1') {
            $logoPath = null;
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

        // 2. Handle Favicon Upload or Removal
        $faviconPath = $currentSettings['site_favicon'];
        if ($this->request->getPost('remove_favicon') === '1') {
            $faviconPath = null;
        }
        $favFile = $this->request->getFile('site_favicon');
        if ($favFile && $favFile->isValid() && ! $favFile->hasMoved()) {
            $ext = strtolower($favFile->getClientExtension());
            if (in_array($ext, ['ico', 'png', 'svg'], true)) {
                $newFavName = 'favicon_' . time() . '.' . $ext;
                $favFile->move($uploadDir, $newFavName);
                $faviconPath = 'uploads/branding/' . $newFavName;
            }
        }

        // 3. Handle Social OpenGraph (OG) Image Upload
        $ogImagePath = $currentSettings['og_image'];
        $ogFile      = $this->request->getFile('og_image');
        if ($ogFile && $ogFile->isValid() && ! $ogFile->hasMoved()) {
            $ext = strtolower($ogFile->getClientExtension());
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                $newOgName = 'og_' . time() . '.' . $ext;
                $ogFile->move($uploadDir, $newOgName);
                $ogImagePath = 'uploads/branding/' . $newOgName;
            }
        }

        // 4. Collect All Text, SEO, Analytics & Script Fields
        $payload = [
            'site_title'               => $siteTitle,
            'site_tagline'             => trim((string) $this->request->getPost('site_tagline')),
            'contact_email'            => trim((string) $this->request->getPost('contact_email')),
            'site_logo'                => $logoPath,
            'site_favicon'             => $faviconPath,
            'og_image'                 => $ogImagePath,

            // SEO Fields
            'meta_title'               => trim((string) $this->request->getPost('meta_title')),
            'meta_description'         => trim((string) $this->request->getPost('meta_description')),
            'meta_keywords'            => trim((string) $this->request->getPost('meta_keywords')),
            'canonical_url'            => trim((string) $this->request->getPost('canonical_url')),
            'robots_txt'               => trim((string) $this->request->getPost('robots_txt')) ?: 'index, follow',

            // Analytics & Verification Codes
            'google_analytics_id'      => trim((string) $this->request->getPost('google_analytics_id')),
            'google_tag_manager_id'    => trim((string) $this->request->getPost('google_tag_manager_id')),
            'meta_pixel_id'            => trim((string) $this->request->getPost('meta_pixel_id')),
            'google_site_verification' => trim((string) $this->request->getPost('google_site_verification')),
            'bing_site_verification'   => trim((string) $this->request->getPost('bing_site_verification')),
            'meta_domain_verification' => trim((string) $this->request->getPost('meta_domain_verification')),

            // Custom CSS & Custom JS / Script Injections (Raw preserved)
            'custom_css'               => (string) $this->request->getPost('custom_css'),
            'header_scripts'           => (string) $this->request->getPost('header_scripts'),
            'body_scripts'             => (string) $this->request->getPost('body_scripts'),
            'footer_scripts'           => (string) $this->request->getPost('footer_scripts'),
        ];

        $saved = $this->settingModel->updateSettingsBatch($payload);

        if ($saved) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Global Site Settings, SEO Meta, Analytics & Custom Scripts updated successfully!',
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Database error occurred while saving settings.',
        ]);
    }

    /**
     * AJAX Endpoint: Manual Re-Initialize Schema (One-Click DB Schema Sync)
     */
    public function reinitializeSchema()
    {
        if (! $this->canManageSettings()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized action.',
            ]);
        }

        $done = $this->settingModel->forceReinitializeSchema();

        return $this->response->setJSON([
            'status'  => $done ? 'success' : 'error',
            'message' => $done
                ? 'Database schema & default setting keys verified and synced!'
                : 'Failed to re-initialize schema.',
        ]);
    }
}
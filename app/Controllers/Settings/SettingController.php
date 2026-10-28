<?php

namespace App\Controllers\Settings;

use App\Controllers\BaseController;
use App\Models\Settings\SettingModel;
use Config\Database;
use Throwable;

class SettingController extends BaseController
{
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form', 'filesystem']);
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

    /**
     * GET /settings
     */
    public function index()
    {
        if (! is_file(WRITEPATH . 'installed.lock')) {
            return redirect()->to(base_url('install'));
        }
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->checkPerm(['settings.view', 'settings.edit'])) {
            return redirect()->to(base_url('dashboard'))
                ->with('error', 'Access denied: Missing settings.view permission.');
        }

        $settings = $this->settingModel->getSystemSettings();

        return view('settings/index_view', [
            'activeMenu' => 'settings',
            'settings'   => $settings,
            'user'       => session()->get(),
            'pageTitle'  => ($settings['site_title'] ?? 'System') . ' — Global Site & SEO Settings',
        ]);
    }

    /**
     * POST /settings/update
     */
    public function update()
    {
        if (! $this->checkPerm('settings.edit')) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Access denied: Missing settings.edit permission.',
            ]);
        }

        try {
            $postData        = $this->request->getPost() ?: [];
            $currentSettings = $this->settingModel->getSystemSettings();
            $uploadDir       = FCPATH . 'uploads/branding/';

            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            // 1. Handle Primary Logo Upload / Removal
            $logoPath = $currentSettings['site_logo'] ?? null;
            if (! empty($postData['remove_logo'])) {
                if (! empty($logoPath) && is_file(FCPATH . $logoPath)) {
                    @unlink(FCPATH . $logoPath);
                }
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
            $postData['site_logo'] = $logoPath;

            // 2. Handle Browser Favicon Upload / Removal
            $favPath = $currentSettings['site_favicon'] ?? null;
            if (! empty($postData['remove_favicon'])) {
                if (! empty($favPath) && is_file(FCPATH . $favPath)) {
                    @unlink(FCPATH . $favPath);
                }
                $favPath = null;
            }
            $favFile = $this->request->getFile('site_favicon');
            if ($favFile && $favFile->isValid() && ! $favFile->hasMoved()) {
                $ext = strtolower($favFile->getClientExtension());
                if (in_array($ext, ['ico', 'png', 'svg'], true)) {
                    $newFavName = 'favicon_' . time() . '.' . $ext;
                    $favFile->move($uploadDir, $newFavName);
                    $favPath = 'uploads/branding/' . $newFavName;
                }
            }
            $postData['site_favicon'] = $favPath;

            // 3. Handle Social OpenGraph Image (`og_image`) Upload
            $ogPath = $currentSettings['og_image'] ?? null;
            $ogFile = $this->request->getFile('og_image');
            if ($ogFile && $ogFile->isValid() && ! $ogFile->hasMoved()) {
                $ext = strtolower($ogFile->getClientExtension());
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                    $newOgName = 'og_' . time() . '.' . $ext;
                    $ogFile->move($uploadDir, $newOgName);
                    $ogPath = 'uploads/branding/' . $newOgName;
                }
            }
            $postData['og_image'] = $ogPath;

            unset($postData['csrf_test_name'], $postData['remove_logo'], $postData['remove_favicon']);

            // Use SettingModel update method if available, otherwise perform direct safe DB upsert
            if (method_exists($this->settingModel, 'saveSystemSettings')) {
                $this->settingModel->saveSystemSettings($postData);
            } elseif (method_exists($this->settingModel, 'updateSettings')) {
                $this->settingModel->updateSettings($postData);
            } else {
                $db       = Database::connect();
                $groupMap = [
                    'site_title'               => 'general',
                    'site_tagline'             => 'general',
                    'contact_email'            => 'general',
                    'site_logo'                => 'general',
                    'site_favicon'             => 'general',
                    'meta_title'               => 'seo',
                    'meta_description'         => 'seo',
                    'meta_keywords'            => 'seo',
                    'canonical_url'            => 'seo',
                    'robots_txt'               => 'seo',
                    'og_image'                 => 'seo',
                    'google_analytics_id'      => 'analytics',
                    'google_tag_manager_id'    => 'analytics',
                    'meta_pixel_id'            => 'analytics',
                    'google_site_verification' => 'analytics',
                    'bing_site_verification'   => 'analytics',
                    'meta_domain_verification' => 'analytics',
                    'custom_css'               => 'scripts',
                    'header_scripts'           => 'scripts',
                    'body_scripts'             => 'scripts',
                    'footer_scripts'           => 'scripts',
					'system_environment_mode' => 'general',
                ];

                foreach ($postData as $k => $v) {
                    if (! is_scalar($v) && $v !== null) {
                        continue;
                    }
                    $grp = $groupMap[$k] ?? 'appearance';
                    $db->query("
                        INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            `setting_group` = VALUES(`setting_group`),
                            `setting_value` = VALUES(`setting_value`)
                    ", [(string) $k, $grp, $v !== null ? (string) $v : null]);
                }
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Global Site, SEO & Theme settings updated successfully!',
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to save settings: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /settings/reinitialize
     */
    public function reinitializeSchema()
    {
        if (! $this->checkPerm('settings.edit')) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Access denied: Missing settings.edit permission.',
            ]);
        }

        try {
            if (method_exists($this->settingModel, 'reinitializeSchema')) {
                $this->settingModel->reinitializeSchema();
            } elseif (method_exists($this->settingModel, 'ensureSchemaInitialized')) {
                $this->settingModel->ensureSchemaInitialized();
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Database settings schema verified & synchronized!',
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Schema sync error: ' . $e->getMessage(),
            ]);
        }
    }
}
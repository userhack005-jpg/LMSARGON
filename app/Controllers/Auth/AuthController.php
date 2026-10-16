<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\Settings\SettingModel;
use App\Models\Users\UserModel;

class AuthController extends BaseController
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

    private function isInstalled(): bool
    {
        return is_file($this->lockFile);
    }

    /**
     * Root Entry ('/')
     */
    public function index()
    {
       if (! $this->isInstalled()) {
            return redirect()->to(base_url('install'));
        }

        // If Page Builder Add-On is installed & a dynamic Homepage is set, render it at '/'!
        if (class_exists('\App\Models\Addons\PageBuilder\PageBuilderModel')) {
            $pbModel  = new \App\Models\Addons\PageBuilder\PageBuilderModel();
            $homePage = $pbModel->getHomepage();
            if ($homePage) {
                $pbCtrl = new \App\Controllers\Addons\PageBuilder\PageBuilderController();
                return $pbCtrl->renderFrontend($homePage['slug']);
            }
        }

        if (session()->get('isLoggedIn')) {
            return redirect()->to(base_url('dashboard'));
        }

        return redirect()->to(base_url('login'));
    }

    /**
     * Render Login Page
     */
    public function loginView()
    {
        if (! $this->isInstalled()) {
            return redirect()->to(base_url('install'));
        }

        if (session()->get('isLoggedIn')) {
            return redirect()->to(base_url('dashboard'));
        }

        $settings = $this->settingModel->getSystemSettings();

        return view('auth/login_view', [
            'settings'  => $settings,
            'pageTitle' => ($settings['site_title'] ?? 'Workspace') . ' — Sign In',
        ]);
    }

    /**
     * Authenticate Login POST Request
     */
    public function authenticate()
    {
        if (! $this->isInstalled()) {
            return redirect()->to(base_url('install'));
        }

        $email    = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');

        $user = $this->userModel->verifyUserCredentials($email, $password);

        if (! $user) {
            return redirect()->back()->with('auth_error', 'Invalid credentials or account is suspended.');
        }

        session()->set([
            'isLoggedIn' => true,
            'user_id'    => (int) $user['id'],
            'user_name'  => $user['full_name'],
            'user_email' => $user['email'],
            'role_id'    => (int) $user['role_id'],
            'role_slug'  => $user['role_slug'],
            'role_name'  => $user['role_name'],
        ]);

        return redirect()->to(base_url('dashboard'));
    }

    /**
     * Destroy Session & Logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('login'));
    }
}
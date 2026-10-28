<?php

namespace App\Controllers\Dashboard;

use App\Controllers\BaseController;
use App\Models\Settings\SettingModel;
use App\Models\Users\UserModel;

class DashboardController extends BaseController
{
    protected SettingModel $settingModel;
    protected UserModel $userModel;
    protected string $lockFile;

    public function __construct()
    {
        helper(['url']);
        $this->settingModel = new SettingModel();
        $this->userModel    = new UserModel();
        $this->lockFile     = WRITEPATH . 'installed.lock';
    }

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
        $stats    = $this->userModel->getRoleDistributionStats();

        return view('dashboard/index_view', [
            'activeMenu' => 'dashboard',
            'settings'   => $settings,
            'roles'      => $roles,
            'stats'      => $stats,
            'user'       => session()->get(),
            'pageTitle'  => ($settings['site_title'] ?? 'System') . ' — Dashboard Overview',
        ]);
    }
}
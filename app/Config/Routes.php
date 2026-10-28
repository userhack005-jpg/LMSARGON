<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->setPrioritize(true);

// ============================================================================
// 0. GLOBAL 0ms RBAC, WORKSPACE & LIVE ADD-ON VERIFICATION ENGINE
// ============================================================================
if (! function_exists('cms_get_active_addon_slugs')) {
    function cms_get_active_addon_slugs(): array
    {
        static $activeSlugs = null;
        if ($activeSlugs !== null) {
            return $activeSlugs;
        }
        $activeSlugs = [];
        try {
            if (class_exists('\App\Models\Addons\AddonModel')) {
                foreach ((new \App\Models\Addons\AddonModel())->getActiveAddons() as $row) {
                    $s = trim((string) ($row['slug'] ?? ''));
                    if ($s !== '') {
                        $activeSlugs[$s] = $row;
                    }
                }
            }
        } catch (\Throwable $e) {
        }
        return $activeSlugs;
    }
}

if (! function_exists('cms_get_rbac_cache')) {
    function cms_get_rbac_cache(): array
    {
        static $cacheData = null;
        if ($cacheData !== null) {
            return $cacheData;
        }

        $activeAddons = cms_get_active_addon_slugs();
        $cacheFile    = WRITEPATH . 'addons/rbac_permissions_cache.json';

        if (! isset($activeAddons['rbac'])) {
            if (is_file($cacheFile)) {
                @unlink($cacheFile);
            }
            return $cacheData = [];
        }

        if (is_file($cacheFile)) {
            $decoded = json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($decoded)) {
                return $cacheData = $decoded;
            }
        }
        return $cacheData = [];
    }
}

if (! function_exists('cms_get_effective_role')) {
    function cms_get_effective_role(): string
    {
        static $cachedEffectiveRole = null;
        if ($cachedEffectiveRole !== null) {
            return $cachedEffectiveRole;
        }

        $roleId   = (int) session()->get('role_id');
        $baseRole = strtolower(trim((string) (session()->get('role_slug') ?: 'student')));
        if ($roleId === 1) {
            return $cachedEffectiveRole = 'super_admin';
        }

        $viewMode      = (string) (session()->get('portal_view_mode') ?: 'default');
        $activeChildId = (int) (session()->get('active_student_id') ?: 0);
        if ($viewMode === 'student' && $activeChildId > 0) {
            return $cachedEffectiveRole = 'student';
        }

        return $cachedEffectiveRole = $baseRole;
    }
}

if (! function_exists('cms_get_role_workspace')) {
    function cms_get_role_workspace(?string $roleSlug = null): array
    {
        $roleSlug     = $roleSlug ?? cms_get_effective_role();
        $cache        = cms_get_rbac_cache();
        $activeAddons = cms_get_active_addon_slugs();

        if (isset($cache['roles'][$roleSlug])) {
            $roleData     = $cache['roles'][$roleSlug];
            $cleanSidebar = [];
            foreach (($roleData['compiled_sidebar'] ?? []) as $itemKey => $navItem) {
                if (strpos((string) $itemKey, 'addon_') === 0) {
                    $addonSlug = (string) ($navItem['slug'] ?? substr((string) $itemKey, 6));
                    if (! isset($activeAddons[$addonSlug])) {
                        continue;
                    }
                }
                $cleanSidebar[$itemKey] = $navItem;
            }
            $roleData['compiled_sidebar'] = $cleanSidebar;
            return $roleData;
        }

        $isSuper = ($roleSlug === 'super_admin');
        $isAdmin = in_array($roleSlug, ['super_admin', 'admin'], true);

        $fallbackSidebar = [
            'core_dashboard' => [
                'key'           => 'core_dashboard',
                'slug'          => 'core_dashboard',
                'display_label' => 'Dashboard Overview',
                'icon'          => '⊞',
                'route'         => 'dashboard',
                'section'       => 'MAIN WORKSPACE',
                'sub_views'     => [],
            ],
        ];

        if ($isAdmin) {
            $fallbackSidebar['core_users'] = [
                'key'           => 'core_users',
                'slug'          => 'core_users',
                'display_label' => 'User & Role Manager',
                'icon'          => '◈',
                'route'         => 'users',
                'section'       => 'ACCESS & RBAC MODULES',
                'sub_views'     => [],
            ];
            $fallbackSidebar['core_settings'] = [
                'key'           => 'core_settings',
                'slug'          => 'core_settings',
                'display_label' => 'Site & SEO Settings',
                'icon'          => '⚙',
                'route'         => 'settings',
                'section'       => 'CONFIGURATION & SEO',
                'sub_views'     => [],
            ];
            $fallbackSidebar['core_addons'] = [
                'key'           => 'core_addons',
                'slug'          => 'core_addons',
                'display_label' => 'Add-On Manager',
                'icon'          => '❖',
                'route'         => 'addons',
                'section'       => 'CONFIGURATION & SEO',
                'sub_views'     => [],
            ];
        }

        foreach ($activeAddons as $aSlug => $aRow) {
            $aRoute = trim((string) ($isAdmin ? ($aRow['admin_route'] ?: $aRow['frontend_route']) : ($aRow['frontend_route'] ?: $aRow['admin_route'])));
            if ($aRoute === '') {
                continue;
            }
            $fallbackSidebar['addon_' . $aSlug] = [
                'key'           => 'addon_' . $aSlug,
                'slug'          => $aSlug,
                'display_label' => $aRow['name'] ?? ucfirst($aSlug),
                'icon'          => '⚡',
                'route'         => $aRoute,
                'section'       => 'INSTALLED ADD-ON MODULES',
                'sub_views'     => [],
            ];
        }

        return [
            'role_slug'        => $roleSlug,
            'permissions'      => $isSuper ? ['*'] : ($isAdmin ? ['dashboard.view', 'users.view', 'users.create', 'users.edit', 'users.delete', 'settings.view', 'settings.edit', 'addons.manage'] : ['dashboard.view']),
            'workspace'        => [
                'show_child_switcher' => ($roleSlug === 'parent') ? 1 : 0,
                'landing_route'       => 'dashboard',
                'custom_labels'       => [],
                'hidden_items'        => [],
                'dashboard_widgets'   => ['kpi_overview', 'quick_modules'],
            ],
            'compiled_sidebar' => $fallbackSidebar,
        ];
    }
}

if (! function_exists('cms_has_permission')) {
    function cms_has_permission($requiredPerms): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }

        $effectiveRole = cms_get_effective_role();
        if ($effectiveRole === 'super_admin') {
            return true;
        }

        $roleData      = cms_get_role_workspace($effectiveRole);
        $resolvedPerms = $roleData['permissions'] ?? [];

        if (in_array('*', $resolvedPerms, true)) {
            return true;
        }

        $reqList = is_array($requiredPerms) ? $requiredPerms : [$requiredPerms];
        foreach ($reqList as $pKey) {
            if (in_array((string) $pKey, $resolvedPerms, true)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('has_permission')) {
    function has_permission($permissionKey): bool
    {
        return cms_has_permission($permissionKey);
    }
}

if (! function_exists('cms_is_sandbox_mode')) {
    function cms_is_sandbox_mode(): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        static $isSandbox = null;
        if ($isSandbox !== null) {
            return $isSandbox;
        }
        try {
            $db  = \Config\Database::connect();
            $row = $db->table('settings')->where('setting_key', 'system_environment_mode')->get()->getRowArray();
            $val = strtolower(trim((string) ($row['setting_value'] ?? 'testing')));
            return $isSandbox = ($val !== 'production');
        } catch (\Throwable $e) {
            return $isSandbox = true;
        }
    }
}

// ============================================================================
// URL-LEVEL GUARD (NEVER BLOCKS ADD-ON SUB-ROUTES WITH `addons.manage`!)
// ============================================================================
$rawUriPath = trim((string) service('uri')->getPath(), '/');
if (strpos($rawUriPath, 'index.php/') === 0) {
    $rawUriPath = trim(substr($rawUriPath, strlen('index.php/')), '/');
}

$isAssetRequest = (strpos($rawUriPath, '/assets/') !== false) || (strpos($rawUriPath, 'assets/') === 0);

if (! $isAssetRequest && is_file(WRITEPATH . 'installed.lock') && session()->get('isLoggedIn')) {
    $reqObj  = service('request');
    $uriPath = $rawUriPath;

    if ($reqObj->getGet('exit_child_mode') === '1') {
        session()->set([
            'active_student_id'   => 0,
            'portal_view_mode'    => 'parent',
            'effective_role_slug' => session()->get('role_slug'),
            '_rbac_sync_key'      => '',
        ]);
        @session_write_close();
        header('Location: ' . base_url('dashboard'));
        exit;
    }

    if (in_array($uriPath, ['addons/core-lms-auth/switch-student', 'portal/switch-student'], true)) {
        $postedStuId = (int) ($reqObj->getPost('student_id') ?? ($reqObj->getGet('student_id') ?? 0));
        if ($postedStuId > 0) {
            session()->set([
                'active_student_id'   => $postedStuId,
                'portal_view_mode'    => 'student',
                'effective_role_slug' => 'student',
                '_rbac_sync_key'      => '',
            ]);
        } else {
            session()->set([
                'active_student_id'   => 0,
                'portal_view_mode'    => 'parent',
                'effective_role_slug' => session()->get('role_slug'),
                '_rbac_sync_key'      => '',
            ]);
        }
    }

    if ($uriPath === 'addons/core-lms-auth/family-context-json') {
        $uid          = (int) session()->get('user_id');
        $activeStuId  = (int) session()->get('active_student_id');
        $viewMode     = (string) session()->get('portal_view_mode');
        $baseRoleSlug = strtolower(trim((string) session()->get('role_slug')));
        if ((int) session()->get('role_id') === 1) {
            $baseRoleSlug = 'super_admin';
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        $baseRoleData   = cms_get_role_workspace($baseRoleSlug);
        $switcherToggle = (int) ($baseRoleData['workspace']['show_child_switcher'] ?? ($baseRoleSlug === 'parent' ? 1 : 0));

        if ($switcherToggle === 0) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'disabled', 'siblings' => []]);
            exit;
        }

        try {
            $db       = \Config\Database::connect();
            $children = $db->table('users')
                ->select('id, full_name, email')
                ->where('parent_user_id', $uid)
                ->where('status', 'active')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'status'            => ! empty($children) ? 'success' : 'no_children',
                'active_student_id' => $activeStuId,
                'portal_view_mode'  => $viewMode,
                'siblings'          => $children,
            ]);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'error', 'siblings' => []]);
            exit;
        }
    }

    $isAjaxReq = $reqObj->isAJAX();
    $denyRoute = static function (string $missingPermLabel) use ($isAjaxReq): void {
        if ($isAjaxReq) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }
            header('Content-Type: application/json; charset=UTF-8', true, 403);
            echo json_encode([
                'status'  => 'error',
                'ok'      => false,
                'message' => 'RBAC Access Denied: Missing permission (' . $missingPermLabel . ').',
            ]);
            exit;
        }
        session()->setFlashdata('error', 'Access Denied: Your current profile does not have permission (' . $missingPermLabel . ').');
        @session_write_close();
        header('Location: ' . base_url('dashboard'));
        exit;
    };

    if ($uriPath === 'users' && ! cms_has_permission(['users.view', 'users.create', 'users.edit', 'users.delete'])) {
        $denyRoute('users.view');
    } elseif ($uriPath === 'users/create' && ! cms_has_permission('users.create')) {
        $denyRoute('users.create');
    } elseif (in_array($uriPath, ['users/update-role', 'users/toggle-status'], true) && ! cms_has_permission('users.edit')) {
        $denyRoute('users.edit');
    } elseif ($uriPath === 'users/delete' && ! cms_has_permission('users.delete')) {
        $denyRoute('users.delete');
    }

    if ($uriPath === 'settings' && ! cms_has_permission(['settings.view', 'settings.edit'])) {
        $denyRoute('settings.view');
    } elseif (in_array($uriPath, ['settings/update', 'settings/reinitialize'], true) && ! cms_has_permission('settings.edit')) {
        $denyRoute('settings.edit');
    }

    // ONLY these 4 exact Add-On Manager routes require `addons.manage`!
    // Individual add-on controllers (`addons/lms-timetable/*`, etc.) handle their own module permissions!
    if (in_array($uriPath, ['addons', 'addons/upload', 'addons/toggle', 'addons/delete'], true) && ! cms_has_permission('addons.manage')) {
        $denyRoute('addons.manage');
    }

    if ($uriPath === 'addons/page-builder' && ! cms_has_permission(['pages.view', 'pages.create', 'pages.edit', 'pages.delete'])) {
        $denyRoute('pages.view');
    } elseif (strpos($uriPath, 'addons/page-builder/editor') === 0 && ! cms_has_permission(['pages.create', 'pages.edit'])) {
        $denyRoute('pages.create / pages.edit');
    } elseif (in_array($uriPath, ['addons/page-builder/save', 'addons/page-builder/upload-media'], true) && ! cms_has_permission(['pages.create', 'pages.edit'])) {
        $denyRoute('pages.create / pages.edit');
    } elseif ($uriPath === 'addons/page-builder/delete' && ! cms_has_permission('pages.delete')) {
        $denyRoute('pages.delete');
    } elseif (in_array($uriPath, ['addons/page-builder/footer', 'addons/page-builder/save-footer', 'addons/page-builder/toggle-component'], true) && ! cms_has_permission('pages.edit')) {
        $denyRoute('pages.edit');
    }
}

// 1. Root Entry Point
$routes->get('/', 'Auth\AuthController::index');

// 2. Installer Module Routes
$routes->group('install', ['namespace' => 'App\Controllers\Installer'], static function ($routes) {
    $routes->get('/', 'InstallerController::index');
    $routes->post('process', 'InstallerController::processInstall');
});

// 3. Authentication Module Routes
$routes->get('login', 'Auth\AuthController::loginView');
$routes->post('login/auth', 'Auth\AuthController::authenticate');
$routes->get('logout', 'Auth\AuthController::logout');

// 4. Dashboard Overview Module
$routes->get('dashboard', 'Dashboard\DashboardController::index');

// 5. User & Role Management Module
$routes->group('users', ['namespace' => 'App\Controllers\Users'], static function ($routes) {
    $routes->get('/', 'UserController::index');
    $routes->post('create', 'UserController::createUser');
    $routes->post('update-role', 'UserController::updateUserRole');
    $routes->post('toggle-status', 'UserController::toggleUserStatus');
    $routes->post('delete', 'UserController::deleteUser');
});

// 6. Global Site Settings, SEO & Custom Code Module
$routes->group('settings', ['namespace' => 'App\Controllers\Settings'], static function ($routes) {
    $routes->get('/', 'SettingController::index');
    $routes->post('update', 'SettingController::update');
    $routes->post('reinitialize', 'SettingController::reinitializeSchema');
});

// 7. Modular Add-On & Plugin Manager Routes
$routes->group('addons', ['namespace' => 'App\Controllers\Addons'], static function ($routes) {
    $routes->get('/', 'AddonController::index');
    $routes->post('upload', 'AddonController::uploadAndInstall');
    $routes->post('toggle', 'AddonController::toggleAddon');
    $routes->post('delete', 'AddonController::deleteAddon');
    $routes->get('sample-gateway-zip', 'AddonController::downloadSampleGatewayZip');
    $routes->get('sample-pagebuilder-zip', 'AddonController::downloadPageBuilderZip');
});

// 8. Auto-Load Routes ONLY for ACTIVE Add-Ons
if (is_file(WRITEPATH . 'installed.lock')) {
    foreach (cms_get_active_addon_slugs() as $slug => $activeAddon) {
        $routeFile = WRITEPATH . 'addons/extracted/' . $slug . '/Routes.php';
        if (is_file($routeFile)) {
            require_once $routeFile;
        }
    }
}
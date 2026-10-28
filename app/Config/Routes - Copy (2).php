<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->setPrioritize(true);

// ============================================================================
// 0. GLOBAL 0ms RBAC PERMISSION SYNC & ROUTE GUARD ENGINE
// ============================================================================
if (! function_exists('cms_has_permission')) {
    function cms_has_permission($requiredPerms): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }

        $roleId   = (int) session()->get('role_id');
        $roleSlug = strtolower(trim((string) session()->get('role_slug')));
        if ($roleId === 1 || $roleSlug === 'super_admin') {
            return true;
        }

        // Sync live permissions from 0ms static cache file on every check
        static $resolvedPerms = null;
        if ($resolvedPerms === null) {
            $cacheFile = WRITEPATH . 'addons/rbac_permissions_cache.json';
            if (is_file($cacheFile)) {
                $cacheData = json_decode((string) @file_get_contents($cacheFile), true);
                if (is_array($cacheData) && isset($cacheData['roles'][$roleSlug]['permissions'])) {
                    $resolvedPerms = $cacheData['roles'][$roleSlug]['permissions'];
                    session()->set([
                        'permissions'      => $resolvedPerms,
                        'role_permissions' => $resolvedPerms,
                    ]);
                }
            }
            if ($resolvedPerms === null) {
                $sessPerms     = session()->get('permissions') ?? session()->get('role_permissions');
                $resolvedPerms = is_array($sessPerms) ? $sessPerms : [];
            }
        }

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

// Enforce URL-level RBAC Guard on Core & Add-on Backend Routes
if (is_file(WRITEPATH . 'installed.lock') && session()->get('isLoggedIn')) {
    $uriPath = trim((string) service('uri')->getPath(), '/');
    if (strpos($uriPath, 'index.php/') === 0) {
        $uriPath = trim(substr($uriPath, strlen('index.php/')), '/');
    }

    $isAjaxReq = service('request')->isAJAX();
    $denyRoute = static function (string $missingPermLabel) use ($isAjaxReq): void {
        if ($isAjaxReq) {
            header('Content-Type: application/json; charset=UTF-8', true, 403);
            echo json_encode([
                'status'  => 'error',
                'ok'      => false,
                'message' => 'RBAC Access Denied: Missing permission (' . $missingPermLabel . ').',
            ]);
            exit;
        }
        session()->setFlashdata('error', 'You do not have permission (' . $missingPermLabel . ') to access that module.');
        header('Location: ' . base_url('dashboard'));
        exit;
    };

    // 1. Guard `/users`
    if ($uriPath === 'users' && ! cms_has_permission(['users.view', 'users.create', 'users.edit', 'users.delete'])) {
        $denyRoute('users.view');
    } elseif ($uriPath === 'users/create' && ! cms_has_permission('users.create')) {
        $denyRoute('users.create');
    } elseif (in_array($uriPath, ['users/update-role', 'users/toggle-status'], true) && ! cms_has_permission('users.edit')) {
        $denyRoute('users.edit');
    } elseif ($uriPath === 'users/delete' && ! cms_has_permission('users.delete')) {
        $denyRoute('users.delete');
    }

    // 2. Guard `/settings`
    if ($uriPath === 'settings' && ! cms_has_permission(['settings.view', 'settings.edit'])) {
        $denyRoute('settings.view');
    } elseif (in_array($uriPath, ['settings/update', 'settings/reinitialize'], true) && ! cms_has_permission('settings.edit')) {
        $denyRoute('settings.edit');
    }

    // 3. Guard `/addons` (Core Add-On Manager)
    if (in_array($uriPath, ['addons', 'addons/upload', 'addons/toggle', 'addons/delete'], true) && ! cms_has_permission('addons.manage')) {
        $denyRoute('addons.manage');
    }

    // 4. Guard `/addons/page-builder`
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

// 2. Installer Module Routes (Folder: Controllers/Installer)
$routes->group('install', ['namespace' => 'App\Controllers\Installer'], static function ($routes) {
    $routes->get('/', 'InstallerController::index');
    $routes->post('process', 'InstallerController::processInstall');
});

// 3. Authentication Module Routes (Folder: Controllers/Auth)
$routes->get('login', 'Auth\AuthController::loginView');
$routes->post('login/auth', 'Auth\AuthController::authenticate');
$routes->get('logout', 'Auth\AuthController::logout');

// 4. Dashboard Overview Module (Folder: Controllers/Dashboard)
$routes->get('dashboard', 'Dashboard\DashboardController::index');

// 5. User & Role Management Module - Sidebar Feature (Folder: Controllers/Users)
$routes->group('users', ['namespace' => 'App\Controllers\Users'], static function ($routes) {
    $routes->get('/', 'UserController::index');
    $routes->post('create', 'UserController::createUser');
    $routes->post('update-role', 'UserController::updateUserRole');
    $routes->post('toggle-status', 'UserController::toggleUserStatus');
    $routes->post('delete', 'UserController::deleteUser');
});

// 6. Global Site Settings, SEO & Custom Code Module (Folder: Controllers/Settings)
$routes->group('settings', ['namespace' => 'App\Controllers\Settings'], static function ($routes) {
    $routes->get('/', 'SettingController::index');
    $routes->post('update', 'SettingController::update');
    $routes->post('reinitialize', 'SettingController::reinitializeSchema');
});

// 7. Modular Add-On & Plugin Manager Routes (Folder: Controllers/Addons)
$routes->group('addons', ['namespace' => 'App\Controllers\Addons'], static function ($routes) {
    $routes->get('/', 'AddonController::index');
    $routes->post('upload', 'AddonController::uploadAndInstall');
    $routes->post('toggle', 'AddonController::toggleAddon');
    $routes->post('delete', 'AddonController::deleteAddon');
    $routes->get('sample-gateway-zip', 'AddonController::downloadSampleGatewayZip');
    $routes->get('sample-pagebuilder-zip', 'AddonController::downloadPageBuilderZip');
});

// 8. Auto-Load Routes ONLY for ACTIVE Add-Ons (Uses Request-Level Static Cache)
if (is_file(WRITEPATH . 'installed.lock') && class_exists('\App\Models\Addons\AddonModel')) {
    $activeAddons = (new \App\Models\Addons\AddonModel())->getActiveAddons();
    foreach ($activeAddons as $activeAddon) {
        $slug = trim((string) ($activeAddon['slug'] ?? ''));
        if ($slug !== '') {
            $routeFile = WRITEPATH . 'addons/extracted/' . $slug . '/Routes.php';
            if (is_file($routeFile)) {
                require_once $routeFile;
            }
        }
    }
}
<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/Rbac/RbacModel.php')) {
    require_once $addonDir . '/Models/Addons/Rbac/RbacModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/Rbac/RbacController.php')) {
    require_once $addonDir . '/Controllers/Addons/Rbac/RbacController.php';
}

\Config\Services::autoloader()->addNamespace('AddonRbac', $addonDir);

// Lightweight 0ms Session Permission Sync from Static Cache (Zero DB Queries!)
if (session()->get('isLoggedIn')) {
    $cacheFile  = WRITEPATH . 'addons/rbac_permissions_cache.json';
    $roleSlug   = (string) (session()->get('role_slug') ?: 'website_user');$cacheMtime = is_file($cacheFile) ? (int) @filemtime($cacheFile) : 0;

    if ($cacheMtime > 0 && (int) session()->get('_rbac_sync_mtime') !==$cacheMtime) {
        $cacheData = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($cacheData) && isset($cacheData['roles'][$roleSlug])) {$roleInfo = $cacheData['roles'][$roleSlug];
            session()->set([
                'permissions'      => $roleInfo['permissions'] ?? [],
                'role_permissions' => $roleInfo['permissions'] ?? [],
                'role_portal'      => $roleInfo['portal_type'] ?? 'frontend',
                '_rbac_sync_mtime' => $cacheMtime,
            ]);
        }
    }
}

$routes->group('addons/rbac', ['namespace' => 'App\Controllers\Addons\Rbac'], static function ($routes) {$routes->get('/', 'RbacController::index');
    $routes->post('save-role', 'RbacController::saveRole');$routes->post('delete-role', 'RbacController::deleteRole');
    $routes->post('save-matrix', 'RbacController::saveMatrix');$routes->post('save-feature', 'RbacController::saveFeature');
    $routes->post('delete-feature', 'RbacController::deleteFeature');$routes->post('assign-user-role', 'RbacController::assignUserRole');
    $routes->post('sync-addons', 'RbacController::syncAddons');$routes->get('assets/(:any)', 'RbacController::asset/$1');
});

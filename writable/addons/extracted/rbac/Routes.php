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

// ============================================================================
// 0ms SESSION & CHILD-SANDBOX LOCK SYNC (Bypasses `/assets/` to Prevent Lock!)
// ============================================================================
$rbacRawUri  = trim((string) service('uri')->getPath(), '/');
$rbacIsAsset = (strpos($rbacRawUri, '/assets/') !== false) || (strpos($rbacRawUri, 'assets/') === 0);

if (! $rbacIsAsset && session()->get('isLoggedIn')) {
    $cacheFile = WRITEPATH . 'addons/rbac_permissions_cache.json';
    $baseRole  = strtolower(trim((string) (session()->get('role_slug') ?: 'student')));
    if ((int) session()->get('role_id') === 1) {
        $baseRole = 'super_admin';
    }

    // STRICT CHILD SANDBOX RULE:
    // When a Parent switches into a Child profile (`portal_view_mode === 'student'` and `active_student_id > 0`),
    // their EFFECTIVE role is strictly 'student' until they return to Parent Account!
    $viewMode      = (string) (session()->get('portal_view_mode') ?: 'default');
    $activeChildId = (int) (session()->get('active_student_id') ?: 0);
    $effectiveRole = ($viewMode === 'student' && $activeChildId > 0 && $baseRole !== 'super_admin')
        ? 'student'
        : $baseRole;

    $cacheMtime = is_file($cacheFile) ? (int) @filemtime($cacheFile) : 0;
    $syncKey    = $cacheMtime . '_' . $effectiveRole;

    if ($cacheMtime > 0 && ((string) session()->get('_rbac_sync_key') !== $syncKey || (string) session()->get('effective_role_slug') !== $effectiveRole)) {
        $cacheData = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($cacheData) && isset($cacheData['roles'][$effectiveRole])) {
            $roleInfo = $cacheData['roles'][$effectiveRole];
            session()->set([
                'effective_role_slug' => $effectiveRole,
                'permissions'         => $roleInfo['permissions'] ?? [],
                'role_permissions'    => $roleInfo['permissions'] ?? [],
                'role_portal'         => $roleInfo['portal_type'] ?? 'frontend',
                'workspace_config'    => $roleInfo['workspace'] ?? [],
                '_rbac_sync_key'      => $syncKey,
            ]);
        }
    }
}

$routes->group('addons/rbac', ['namespace' => 'App\Controllers\Addons\Rbac'], static function ($routes) {
    $routes->get('/', 'RbacController::index');
    $routes->get('matrix', 'RbacController::permissionMatrix');
    $routes->get('workspace', 'RbacController::workspaceArchitect');
    $routes->get('users', 'RbacController::redirectToCoreUsers');

    $routes->post('save-role', 'RbacController::saveRole');
    $routes->post('delete-role', 'RbacController::deleteRole');
    $routes->post('save-matrix', 'RbacController::saveMatrix');
    $routes->post('save-workspace', 'RbacController::saveWorkspace');
    $routes->post('clone-role-config', 'RbacController::cloneRoleConfig');
    $routes->post('apply-presets', 'RbacController::applyRecommendedPresets');
    $routes->post('save-feature', 'RbacController::saveFeature');
    $routes->post('delete-feature', 'RbacController::deleteFeature');
    $routes->post('sync-addons', 'RbacController::syncAddons');
    $routes->get('assets/(:any)', 'RbacController::asset/$1');
});

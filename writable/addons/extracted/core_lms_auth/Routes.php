<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/CoreLmsAuth/CoreLmsAuthModel.php')) {
    require_once $addonDir . '/Models/Addons/CoreLmsAuth/CoreLmsAuthModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/CoreLmsAuth/CoreLmsAuthController.php')) {
    require_once $addonDir . '/Controllers/Addons/CoreLmsAuth/CoreLmsAuthController.php';
}

\Config\Services::autoloader()->addNamespace('AddonCoreLmsAuth', $addonDir);

// Admin Dedicated Single-Purpose Routes
$routes->group('addons/core-lms-auth', ['namespace' => 'App\Controllers\Addons\CoreLmsAuth'], static function ($routes) {
    $routes->get('/', 'CoreLmsAuthController::index');
    $routes->get('directory', 'CoreLmsAuthController::familyDirectoryView');
    $routes->get('linker', 'CoreLmsAuthController::childLinkerView');
    $routes->get('role-mapper', 'CoreLmsAuthController::roleMapperView');
    $routes->get('countries', 'CoreLmsAuthController::countriesView');
    $routes->get('settings', 'CoreLmsAuthController::countriesView');

    $routes->post('save-role-map', 'CoreLmsAuthController::saveRoleMap');
    $routes->post('sync-fk-roles', 'CoreLmsAuthController::syncFkRoles');
    $routes->post('save-countries', 'CoreLmsAuthController::saveCountries');
    $routes->post('save-child-link', 'CoreLmsAuthController::saveChildLink');
    $routes->post('delete-child-link', 'CoreLmsAuthController::deleteChildLink');
    $routes->post('switch-student', 'CoreLmsAuthController::switchActiveStudent');
    $routes->get('family-context-json', 'CoreLmsAuthController::familyContextJson');
    $routes->get('assets/(:any)', 'CoreLmsAuthController::asset/$1');
});

// Parent & Student Clean Portal Routes (3 Separate Views: Children Hub, Add Child, Profile Settings)
$routes->group('portal', ['namespace' => 'App\Controllers\Addons\CoreLmsAuth'], static function ($routes) {
    $routes->get('family', 'CoreLmsAuthController::portalDashboard');
    $routes->get('family/add-child', 'CoreLmsAuthController::portalAddChildView');
    $routes->post('family/create-child', 'CoreLmsAuthController::portalCreateChild');
    $routes->get('family/profile', 'CoreLmsAuthController::portalProfileView');
    $routes->post('family/update-profile', 'CoreLmsAuthController::portalUpdateProfile');
    $routes->post('switch-student', 'CoreLmsAuthController::switchActiveStudent');
});

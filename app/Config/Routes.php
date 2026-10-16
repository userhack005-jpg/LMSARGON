<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

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
    $routes->get('sample-gateway-zip', 'AddonController::downloadSampleGatewayZip');
});

// 8. Auto-Load Routes from All Extracted Add-On Modules
$extractedAddonsDir = WRITEPATH . 'addons/extracted/';
if (is_dir($extractedAddonsDir)) {
    foreach (glob($extractedAddonsDir . '*/Routes.php') as $addonRouteFile) {
        require $addonRouteFile;
    }
}
<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/AbacusProSuite/AbacusProSuiteModel.php')) {
    require_once $addonDir . '/Models/Addons/AbacusProSuite/AbacusProSuiteModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/AbacusProSuite/AbacusProSuiteController.php')) {
    require_once $addonDir . '/Controllers/Addons/AbacusProSuite/AbacusProSuiteController.php';
}

\Config\Services::autoloader()->addNamespace('AddonAbacusProSuite', $addonDir);

$routes->group('addons/abacus-pro-suite', ['namespace' => 'App\Controllers\Addons\AbacusProSuite'], static function ($routes) {
    $routes->get('/', 'AbacusProSuiteController::index');
    $routes->get('analytics', 'AbacusProSuiteController::batchAnalyticsView');
    $routes->get('settings', 'AbacusProSuiteController::settingsView');
    $routes->get('arena', 'AbacusProSuiteController::arenaView');
    $routes->get('anzan', 'AbacusProSuiteController::anzanView');
    $routes->get('my-progress', 'AbacusProSuiteController::myProgressView');

    $routes->post('save-settings', 'AbacusProSuiteController::saveSettings');
    $routes->post('delete-attempt', 'AbacusProSuiteController::deleteAttempt');
    $routes->post('log-attempt', 'AbacusProSuiteController::logAttemptAjax');
    $routes->get('assets/(:any)', 'AbacusProSuiteController::asset/$1');
});

// Public / Guest & Student Practice Routes (Works WITH or WITHOUT Login!)
$routes->group('abacus', ['namespace' => 'App\Controllers\Addons\AbacusProSuite'], static function ($routes) {
    $routes->get('/', 'AbacusProSuiteController::arenaView');
    $routes->get('arena', 'AbacusProSuiteController::arenaView');
    $routes->get('anzan', 'AbacusProSuiteController::anzanView');
    $routes->get('my-progress', 'AbacusProSuiteController::myProgressView');
    $routes->post('log-attempt', 'AbacusProSuiteController::logAttemptAjax');
});

<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/Faqs/FaqModel.php')) {
    require_once $addonDir . '/Models/Addons/Faqs/FaqModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/Faqs/FaqController.php')) {
    require_once $addonDir . '/Controllers/Addons/Faqs/FaqController.php';
}

\Config\Services::autoloader()->addNamespace('AddonFaqs', $addonDir);

$routes->group('addons/faqs', ['namespace' => 'App\Controllers\Addons\Faqs'], static function ($routes) {
    $routes->get('/', 'FaqController::index');
    $routes->post('save', 'FaqController::save');
    $routes->post('toggle', 'FaqController::toggle');
    $routes->post('delete', 'FaqController::delete');
    $routes->get('assets/(:any)', 'FaqController::asset/$1');
});
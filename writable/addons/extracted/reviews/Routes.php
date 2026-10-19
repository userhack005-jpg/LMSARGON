<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/Reviews/ReviewModel.php')) {
    require_once $addonDir . '/Models/Addons/Reviews/ReviewModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/Reviews/ReviewController.php')) {
    require_once $addonDir . '/Controllers/Addons/Reviews/ReviewController.php';
}

\Config\Services::autoloader()->addNamespace('AddonReviews', $addonDir);

$routes->group('addons/reviews', ['namespace' => 'App\Controllers\Addons\Reviews'], static function ($routes) {
    $routes->get('/', 'ReviewController::index');
    $routes->post('save', 'ReviewController::save');
    $routes->post('toggle', 'ReviewController::toggle');
    $routes->post('delete', 'ReviewController::delete');
    $routes->get('assets/(:any)', 'ReviewController::asset/$1');
});

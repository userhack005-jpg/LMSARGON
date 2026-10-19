<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/Announcements/AnnouncementModel.php')) {
    require_once $addonDir . '/Models/Addons/Announcements/AnnouncementModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/Announcements/AnnouncementController.php')) {
    require_once $addonDir . '/Controllers/Addons/Announcements/AnnouncementController.php';
}

\Config\Services::autoloader()->addNamespace('AddonAnnouncements', $addonDir);

$routes->group('addons/announcements', ['namespace' => 'App\Controllers\Addons\Announcements'], static function ($routes) {
    $routes->get('/', 'AnnouncementController::index');
    $routes->post('save', 'AnnouncementController::save');
    $routes->post('toggle', 'AnnouncementController::toggle');
    $routes->post('delete', 'AnnouncementController::delete');
    $routes->get('assets/(:any)', 'AnnouncementController::asset/$1');
});

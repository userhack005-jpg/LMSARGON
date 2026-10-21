<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/Newsletter/NewsletterModel.php')) {
    require_once $addonDir . '/Models/Addons/Newsletter/NewsletterModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/Newsletter/NewsletterController.php')) {
    require_once $addonDir . '/Controllers/Addons/Newsletter/NewsletterController.php';
}

\Config\Services::autoloader()->addNamespace('AddonNewsletter', $addonDir);

$routes->group('addons/newsletter', ['namespace' => 'App\Controllers\Addons\Newsletter'], static function ($routes) {$routes->get('/', 'NewsletterController::index');
    $routes->post('save', 'NewsletterController::save');$routes->post('toggle', 'NewsletterController::toggle');
    $routes->post('delete', 'NewsletterController::delete');$routes->post('subscribe', 'NewsletterController::subscribe');
    $routes->get('export-csv', 'NewsletterController::exportCsv');$routes->get('assets/(:any)', 'NewsletterController::asset/$1');
});

$routes->post('cms/subscribe-lead', '\App\Controllers\Addons\Newsletter\NewsletterController::subscribe');

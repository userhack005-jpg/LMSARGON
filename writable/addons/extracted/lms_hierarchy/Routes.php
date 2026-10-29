<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/LmsHierarchy/LmsHierarchyModel.php')) {
    require_once $addonDir . '/Models/Addons/LmsHierarchy/LmsHierarchyModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/LmsHierarchy/LmsHierarchyController.php')) {
    require_once $addonDir . '/Controllers/Addons/LmsHierarchy/LmsHierarchyController.php';
}

\Config\Services::autoloader()->addNamespace('AddonLmsHierarchy', $addonDir);

$routes->group('addons/lms-hierarchy', ['namespace' => 'App\Controllers\Addons\LmsHierarchy'], static function ($routes) {
    $routes->get('/', 'LmsHierarchyController::index');
    $routes->get('builder', 'LmsHierarchyController::treeBuilder');
    $routes->get('pricing', 'LmsHierarchyController::pricingMatrix');
    $routes->get('enrollments', 'LmsHierarchyController::enrollmentsManager');
    $routes->get('catalog', 'LmsHierarchyController::catalogExplorer');

    $routes->post('save-node', 'LmsHierarchyController::saveNode');
    $routes->post('delete-node', 'LmsHierarchyController::deleteNode');
    $routes->post('save-pricing', 'LmsHierarchyController::savePricing');
    $routes->post('enroll-student', 'LmsHierarchyController::enrollStudent');
    $routes->post('upgrade-student', 'LmsHierarchyController::upgradeStudent');
    $routes->post('delete-enrollment', 'LmsHierarchyController::deleteEnrollment');
    $routes->get('node-details-json/(:num)', 'LmsHierarchyController::nodeDetailsJson/$1');
    $routes->get('detect-country-json', 'LmsHierarchyController::detectVisitorCountryJson');
    $routes->get('assets/(:any)', 'LmsHierarchyController::asset/$1');
});

// Clean Public & Role-Specific Course Routes (Includes Dedicated Detail Page!)
$routes->get('courses/catalog', '\App\Controllers\Addons\LmsHierarchy\LmsHierarchyController::catalogExplorer');
$routes->get('courses/view/(:num)', '\App\Controllers\Addons\LmsHierarchy\LmsHierarchyController::courseDetailPage/$1');
$routes->get('courses/tree-json', '\App\Controllers\Addons\LmsHierarchy\LmsHierarchyController::publicTreeJson');

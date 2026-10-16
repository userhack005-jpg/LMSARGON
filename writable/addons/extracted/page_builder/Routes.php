<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

// 1. Backend Page Builder Admin Routes
$routes->group('addons/page-builder', ['namespace' => 'App\Controllers\Addons\PageBuilder'], static function ($routes) {
    $routes->get('/', 'PageBuilderController::index');
    $routes->get('editor', 'PageBuilderController::editor');
    $routes->get('editor/(:num)', 'PageBuilderController::editor/$1');
    $routes->post('save-page', 'PageBuilderController::savePage');
    $routes->post('delete-page', 'PageBuilderController::deletePage');
    $routes->post('toggle-component', 'PageBuilderController::toggleComponent');
    $routes->post('upload-media', 'PageBuilderController::uploadMedia');
});

// 2. Frontend Dynamic Page Routes (Supports both /about-us AND nested /courses/abacus)
$routes->group('', ['namespace' => 'App\Controllers\Addons\PageBuilder'], static function ($routes) {
    $routes->get('p/(:any)', 'PageBuilderController::renderFrontend/$1');
    $routes->get('(:any)', 'PageBuilderController::renderFrontend/$1', ['priority' => 999]);
});
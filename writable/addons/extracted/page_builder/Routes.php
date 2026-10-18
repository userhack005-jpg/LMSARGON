<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

// 1. Backend Page Builder & Content Hub Admin Routes
$routes->group('addons/page-builder', ['namespace' => 'App\Controllers\Addons\PageBuilder'], static function ($routes) {
    $routes->get('/', 'PageBuilderController::index');
    $routes->get('editor', 'PageBuilderController::editor');
    $routes->get('editor/(:num)', 'PageBuilderController::editor/$1');
    $routes->post('save-page', 'PageBuilderController::savePage');
    $routes->post('delete-page', 'PageBuilderController::deletePage');
    $routes->post('toggle-component', 'PageBuilderController::toggleComponent');
    $routes->post('upload-media', 'PageBuilderController::uploadMedia');

    // Footer Builder Routes
    $routes->get('footer', 'PageBuilderController::footerEditor');
    $routes->post('save-footer', 'PageBuilderController::saveFooter');

    // Dedicated Content Hub (Create + Edit + Delete for Announcements, FAQs & Reviews)
    $routes->get('content-hub', 'PageBuilderController::contentHub');
    $routes->post('save-announcement', 'PageBuilderController::saveAnnouncement');
    $routes->post('delete-announcement', 'PageBuilderController::deleteAnnouncement');
    $routes->post('save-faq', 'PageBuilderController::saveFaq');
    $routes->post('delete-faq', 'PageBuilderController::deleteFaq');
    $routes->post('save-review', 'PageBuilderController::saveReview');
    $routes->post('delete-review', 'PageBuilderController::deleteReview');
});

// 2. Public Frontend Dynamic Page & Newsletter Lead Routes
$routes->group('', ['namespace' => 'App\Controllers\Addons\PageBuilder'], static function ($routes) {
    $routes->post('cms/subscribe-lead', 'PageBuilderController::subscribeLead');
    $routes->get('p/(:any)', 'PageBuilderController::renderFrontend/$1');
    $routes->get('(:any)', 'PageBuilderController::renderFrontend/$1', ['priority' => 999]);
});
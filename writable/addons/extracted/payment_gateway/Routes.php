<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

// Backend Admin Configuration Routes
$routes->group('addons/payment-gateway', ['namespace' => 'App\Controllers\Addons\PaymentGateway'], static function ($routes) {
    $routes->get('/', 'PaymentController::adminConfig');$routes->post('save', 'PaymentController::saveConfig');
});

// Frontend Website Checkout Routes
$routes->get('pay-checkout', 'App\Controllers\Addons\PaymentGateway\PaymentController::frontendCheckout');$routes->post('pay-checkout/process', 'App\Controllers\Addons\PaymentGateway\PaymentController::processDemoPayment');
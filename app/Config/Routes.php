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
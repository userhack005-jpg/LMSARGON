<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/LmsCommunication/LmsCommunicationModel.php')) {
    require_once $addonDir . '/Models/Addons/LmsCommunication/LmsCommunicationModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/LmsCommunication/LmsCommunicationController.php')) {
    require_once $addonDir . '/Controllers/Addons/LmsCommunication/LmsCommunicationController.php';
}

\Config\Services::autoloader()->addNamespace('AddonLmsCommunication', $addonDir);

// Admin Dedicated Sub-View Routes
$routes->group('addons/lms-communication', ['namespace' => 'App\Controllers\Addons\LmsCommunication'], static function ($routes) {
    $routes->get('/', 'LmsCommunicationController::index');
    $routes->get('chat-room', 'LmsCommunicationController::chatPortal');
    $routes->get('feedback', 'LmsCommunicationController::feedbackPortal');
    $routes->get('moderation', 'LmsCommunicationController::moderationQueueView');
    $routes->get('dlp-filters', 'LmsCommunicationController::dlpFiltersView');
    $routes->get('alerts', 'LmsCommunicationController::alertsLogView');

    $routes->post('approve-message', 'LmsCommunicationController::approveFlaggedMessage');
    $routes->post('delete-message', 'LmsCommunicationController::deleteFlaggedMessage');
    $routes->post('save-filter', 'LmsCommunicationController::saveDlpFilter');
    $routes->post('toggle-filter', 'LmsCommunicationController::toggleDlpFilter');
    $routes->post('delete-filter', 'LmsCommunicationController::deleteDlpFilter');
    $routes->post('trigger-test-alert', 'LmsCommunicationController::triggerTestAlert');
    $routes->get('assets/(:any)', 'LmsCommunicationController::asset/$1');
});

// Student, Teacher & Parent Clean Top-Level Chat & Feedback Routes
$routes->group('chat', ['namespace' => 'App\Controllers\Addons\LmsCommunication'], static function ($routes) {
    $routes->get('portal', 'LmsCommunicationController::chatPortal');
    $routes->get('feedback', 'LmsCommunicationController::feedbackPortal');
    $routes->post('submit-feedback', 'LmsCommunicationController::submitTeacherFeedback');
    $routes->post('send-message', 'LmsCommunicationController::sendMessageAjax');
    $routes->get('poll-messages-json', 'LmsCommunicationController::pollMessagesJson');
    $routes->get('widget-context-json', 'LmsCommunicationController::widgetContextJson');
});

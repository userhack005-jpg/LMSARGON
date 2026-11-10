<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/LmsOnboarding/LmsOnboardingModel.php')) {
    require_once $addonDir . '/Models/Addons/LmsOnboarding/LmsOnboardingModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/LmsOnboarding/LmsOnboardingController.php')) {
    require_once $addonDir . '/Controllers/Addons/LmsOnboarding/LmsOnboardingController.php';
}

\Config\Services::autoloader()->addNamespace('AddonLmsOnboarding', $addonDir);

$routes->group('addons/lms-onboarding', ['namespace' => 'App\Controllers\Addons\LmsOnboarding'], static function ($routes) {
    $routes->get('/', 'LmsOnboardingController::index');
    $routes->get('leads', 'LmsOnboardingController::leadsPipelineView');
    $routes->get('scheduler', 'LmsOnboardingController::demoSchedulerView');
    $routes->get('batches', 'LmsOnboardingController::demoSchedulerView');
    $routes->get('my-demos', 'LmsOnboardingController::teacherDemosView');

    // Admin Actions
    $routes->post('save-lead', 'LmsOnboardingController::submitDemoBooking');
    $routes->post('assign-lead-demo', 'LmsOnboardingController::assignLeadToDemoBatch');
    $routes->post('convert-lead', 'LmsOnboardingController::convertLeadToStudent');
    $routes->post('delete-lead', 'LmsOnboardingController::deleteLead');
    $routes->post('save-demo-batch', 'LmsOnboardingController::saveDemoBatch');
    $routes->post('delete-demo-batch', 'LmsOnboardingController::deleteDemoBatch');

    // Teacher Live Demo Lifecycle Actions
    $routes->post('teacher-start-demo', 'LmsOnboardingController::teacherStartDemoBatch');
    $routes->post('teacher-mark-attendance', 'LmsOnboardingController::teacherMarkLiveAttendance');
    $routes->post('teacher-end-demo', 'LmsOnboardingController::teacherEndDemoBatch');
    $routes->post('teacher-submit-eval', 'LmsOnboardingController::teacherEndDemoBatch');

    $routes->get('assets/(:any)', 'LmsOnboardingController::asset/$1');
});

$routes->group('demo', ['namespace' => 'App\Controllers\Addons\LmsOnboarding'], static function ($routes) {
    $routes->get('/', 'LmsOnboardingController::bookDemoPage');
    $routes->get('book', 'LmsOnboardingController::bookDemoPage');
    $routes->get('book/(:num)', 'LmsOnboardingController::bookDemoPage/$1');
    $routes->post('submit', 'LmsOnboardingController::submitDemoBooking');
    $routes->get('check-email-json', 'LmsOnboardingController::checkEmailExistsJson');
});

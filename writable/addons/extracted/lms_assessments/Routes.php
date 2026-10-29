<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/LmsAssessments/LmsAssessmentsModel.php')) {
    require_once $addonDir . '/Models/Addons/LmsAssessments/LmsAssessmentsModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/LmsAssessments/LmsAssessmentsController.php')) {
    require_once $addonDir . '/Controllers/Addons/LmsAssessments/LmsAssessmentsController.php';
}

\Config\Services::autoloader()->addNamespace('AddonLmsAssessments', $addonDir);

// Admin & Teacher Management Routes
$routes->group('addons/lms-assessments', ['namespace' => 'App\Controllers\Addons\LmsAssessments'], static function ($routes) {
    $routes->get('/', 'LmsAssessmentsController::index');
    $routes->get('question-bank', 'LmsAssessmentsController::questionBankManager');
    $routes->get('exams', 'LmsAssessmentsController::examsBuilder');
    $routes->get('worksheets', 'LmsAssessmentsController::worksheetsManager');
    $routes->get('analytics', 'LmsAssessmentsController::attemptsAndAnalytics');
    $routes->get('portal', 'LmsAssessmentsController::studentPortalExams');

    $routes->post('save-question', 'LmsAssessmentsController::saveQuestion');
    $routes->post('toggle-question', 'LmsAssessmentsController::toggleQuestionStatus');
    $routes->post('delete-question', 'LmsAssessmentsController::deleteQuestion');

    $routes->post('save-settings', 'LmsAssessmentsController::saveGlobalSettings');
    $routes->post('save-exam', 'LmsAssessmentsController::saveExam');
    $routes->post('toggle-exam', 'LmsAssessmentsController::toggleExamStatus');
    $routes->post('delete-exam', 'LmsAssessmentsController::deleteExam');
    $routes->post('save-worksheet', 'LmsAssessmentsController::saveWorksheet');
    $routes->post('toggle-worksheet', 'LmsAssessmentsController::toggleWorksheetStatus');
    $routes->post('delete-worksheet', 'LmsAssessmentsController::deleteWorksheet');
    $routes->post('submit-attempt', 'LmsAssessmentsController::submitExamAttempt');
    $routes->post('save-draft-answers', 'LmsAssessmentsController::saveDraftAnswers');
    $routes->get('certificate-pdf/(:num)', 'LmsAssessmentsController::downloadCertificatePdf/$1');
    $routes->get('verify-code-json', 'LmsAssessmentsController::verifyCertificateJson');
    $routes->get('assets/(:any)', 'LmsAssessmentsController::asset/$1');
});

// Student & Parent Clean 3-Sub-View Portal Routes
$routes->group('assessments', ['namespace' => 'App\Controllers\Addons\LmsAssessments'], static function ($routes) {
    $routes->get('portal', 'LmsAssessmentsController::studentPortalExams');
    $routes->get('portal/worksheets', 'LmsAssessmentsController::studentPortalWorksheets');
    $routes->get('portal/history', 'LmsAssessmentsController::studentPortalHistory');
    $routes->post('start-attempt', 'LmsAssessmentsController::startExamAttempt');
    $routes->post('submit-attempt', 'LmsAssessmentsController::submitExamAttempt');
    $routes->post('save-draft-answers', 'LmsAssessmentsController::saveDraftAnswers');
    $routes->get('certificate-pdf/(:num)', 'LmsAssessmentsController::downloadCertificatePdf/$1');
});

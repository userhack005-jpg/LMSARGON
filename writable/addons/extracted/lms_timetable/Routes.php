<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/LmsTimetable/LmsTimetableModel.php')) {
    require_once $addonDir . '/Models/Addons/LmsTimetable/LmsTimetableModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/LmsTimetable/LmsTimetableController.php')) {
    require_once $addonDir . '/Controllers/Addons/LmsTimetable/LmsTimetableController.php';
}

\Config\Services::autoloader()->addNamespace('AddonLmsTimetable', $addonDir);

$routes->group('addons/lms-timetable', ['namespace' => 'App\Controllers\Addons\LmsTimetable'], static function ($routes) {
    $routes->get('/', 'LmsTimetableController::index');
    $routes->get('cohorts', 'LmsTimetableController::cohortsManager');
    $routes->get('sessions', 'LmsTimetableController::sessionsManager');
    $routes->get('substitutes', 'LmsTimetableController::substitutesInbox');
    $routes->get('portal', 'LmsTimetableController::portalTimetable');

    $routes->post('save-settings', 'LmsTimetableController::saveSettings');
    $routes->post('save-cohort', 'LmsTimetableController::saveCohortBatch');
    $routes->post('assign-cohort-student', 'LmsTimetableController::assignStudentToCohort');
    $routes->post('remove-cohort-student', 'LmsTimetableController::removeStudentFromCohort');
    $routes->post('delete-cohort', 'LmsTimetableController::deleteCohortBatch');
    $routes->post('cleanup-orphans', 'LmsTimetableController::cleanupOrphanedSessions');
    $routes->post('generate-schedule', 'LmsTimetableController::generateSchedule');
    $routes->post('reschedule-session', 'LmsTimetableController::rescheduleSession');
    $routes->post('start-class', 'LmsTimetableController::startClassWithWizard');
    $routes->post('end-class', 'LmsTimetableController::endClass');
    $routes->post('mark-student-attendance', 'LmsTimetableController::markStudentAttendance');
    $routes->post('share-assignment', 'LmsTimetableController::shareAssignment');
    $routes->post('submit-sub-request', 'LmsTimetableController::submitSubstituteRequest');
    $routes->post('reply-sub-request', 'LmsTimetableController::replySubstituteRequest');
    $routes->post('assign-substitute', 'LmsTimetableController::assignSubstituteTeacher');
    $routes->post('add-guest-student', 'LmsTimetableController::addGuestStudent');
    $routes->post('delete-session', 'LmsTimetableController::deleteSession');
    $routes->get('live-sub-threads-json', 'LmsTimetableController::liveSubThreadsJson');
    $routes->get('assets/(:any)', 'LmsTimetableController::asset/$1');
});

$routes->group('timetable', ['namespace' => 'App\Controllers\Addons\LmsTimetable'], static function ($routes) {
    $routes->get('portal', 'LmsTimetableController::portalTimetable');
    $routes->post('start-class', 'LmsTimetableController::startClassWithWizard');
    $routes->post('end-class', 'LmsTimetableController::endClass');
    $routes->post('share-assignment', 'LmsTimetableController::shareAssignment');
    $routes->post('submit-sub-request', 'LmsTimetableController::submitSubstituteRequest');
    $routes->post('reply-sub-request', 'LmsTimetableController::replySubstituteRequest');
    $routes->get('live-sub-threads-json', 'LmsTimetableController::liveSubThreadsJson');
});

<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/LmsFinance/LmsFinanceModel.php')) {
    require_once $addonDir . '/Models/Addons/LmsFinance/LmsFinanceModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/LmsFinance/LmsFinanceController.php')) {
    require_once $addonDir . '/Controllers/Addons/LmsFinance/LmsFinanceController.php';
}

\Config\Services::autoloader()->addNamespace('AddonLmsFinance', $addonDir);

// 0ms Static Cache Check for Grace Period Auto-Lock on Active Child Profile
$finRawUri  = trim((string) service('uri')->getPath(), '/');
$finIsAsset = (strpos($finRawUri, '/assets/') !== false) || (strpos($finRawUri, 'assets/') === 0);

if (! $finIsAsset && session()->get('isLoggedIn')) {
    $lockCacheFile = WRITEPATH . 'addons/lms_finance_locks_cache.json';
    if (is_file($lockCacheFile)) {
        $activeStuId = (int) (session()->get('active_student_id') ?: (session()->get('user_id') ?: 0));
        $locksMap    = json_decode((string) @file_get_contents($lockCacheFile), true);
        $isLocked    = isset($locksMap['locked_students'][$activeStuId]);
        if ((int) session()->get('lms_child_fee_locked') !== ($isLocked ? 1 : 0)) {
            session()->set('lms_child_fee_locked', $isLocked ? 1 : 0);
        }

        if ($isLocked) {
            $restrictedPrefixes = ['timetable/portal', 'abacus/arena', 'addons/abacus-pro-suite', 'assessments/portal'];
            foreach ($restrictedPrefixes as $rp) {
                if ($finRawUri === $rp || strpos($finRawUri, $rp . '/') === 0) {
                    header('Location: ' . base_url('finance/portal?locked=1'));
                    exit;
                }
            }
        }
    }
}

// Admin Single-Purpose Sub-View Routes
$routes->group('addons/lms-finance', ['namespace' => 'App\Controllers\Addons\LmsFinance'], static function ($routes) {
    $routes->get('/', 'LmsFinanceController::index');
    $routes->get('invoices', 'LmsFinanceController::invoicesAndApprovalsView');
    $routes->get('collections', 'LmsFinanceController::invoicesAndApprovalsView');
    $routes->get('custom-billing', 'LmsFinanceController::customBillingView');
    $routes->get('payroll', 'LmsFinanceController::payrollView');
    $routes->get('expenses', 'LmsFinanceController::expensesView');
    $routes->get('gateways', 'LmsFinanceController::gatewaysAndReceiptView');

    $routes->post('save-settings', 'LmsFinanceController::saveSettings');
    $routes->post('save-gateway-config', 'LmsFinanceController::saveGatewayConfig');
    $routes->post('save-receipt-template', 'LmsFinanceController::saveReceiptTemplate');
    $routes->post('mark-paid-direct', 'LmsFinanceController::adminMarkPaidDirect');
    $routes->post('approve-offline-txn', 'LmsFinanceController::approveOfflineTransaction');
    $routes->post('reject-offline-txn', 'LmsFinanceController::rejectOfflineTransaction');
    $routes->post('create-custom-invoice', 'LmsFinanceController::createCustomInvoice');
    $routes->post('delete-invoice', 'LmsFinanceController::deleteInvoice');
    $routes->post('send-reminder', 'LmsFinanceController::sendPaymentReminder');
    $routes->post('run-grace-lock', 'LmsFinanceController::runGraceLockCheck');
    $routes->post('save-salary', 'LmsFinanceController::saveTeacherSalary');
    $routes->post('delete-salary', 'LmsFinanceController::deleteTeacherSalary');
    $routes->post('save-expense', 'LmsFinanceController::saveExpense');
    $routes->post('delete-expense', 'LmsFinanceController::deleteExpense');
    $routes->get('receipt-pdf/(:num)', 'LmsFinanceController::downloadReceiptPdf/$1');
    $routes->get('receipt-view/(:num)', 'LmsFinanceController::styledReceiptView/$1');
    $routes->get('assets/(:any)', 'LmsFinanceController::asset/$1');
});

// Parent, Student, Teacher & Direct Frontend Course Enrollment Checkout Routes
$routes->group('finance', ['namespace' => 'App\Controllers\Addons\LmsFinance'], static function ($routes) {
    $routes->get('portal', 'LmsFinanceController::parentFeePortal');
    $routes->get('my-payouts', 'LmsFinanceController::teacherSalaryPortal');
    $routes->get('enroll-checkout/(:num)', 'LmsFinanceController::directCourseEnrollCheckout/$1');
    $routes->post('process-enroll-checkout', 'LmsFinanceController::processDirectCourseEnrollCheckout');
    $routes->post('create-razorpay-order', 'LmsFinanceController::createRazorpayOrderJson');
    $routes->post('verify-razorpay-payment', 'LmsFinanceController::verifyRazorpayPayment');
    $routes->post('submit-offline-proof', 'LmsFinanceController::submitOfflinePaymentProof');
    $routes->get('receipt-pdf/(:num)', 'LmsFinanceController::downloadReceiptPdf/$1');
    $routes->get('receipt-view/(:num)', 'LmsFinanceController::styledReceiptView/$1');
});

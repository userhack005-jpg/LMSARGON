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

if (! defined('LMS_FINANCE_GLOBAL_GATE_BOUND')) {
    define('LMS_FINANCE_GLOBAL_GATE_BOUND', true);
    \CodeIgniter\Events\Events::on('post_controller_constructor', static function () {
        if (is_cli() || ! session()->get('isLoggedIn')) {
            return;
        }
        $roleId   = (int) (session()->get('role_id') ?? 0);
        $roleSlug = strtolower(trim((string) (session()->get('role_slug') ?: 'student')));
        if (in_array($roleId, [1, 2, 3], true) || in_array($roleSlug, ['super_admin', 'admin', 'teacher'], true)) {
            session()->set('lms_child_fee_locked', 0);
            return;
        }
        $finModel = new \App\Models\Addons\LmsFinance\LmsFinanceModel();
        $finModel->enforceStrictMonthlyFeeGate();
    });
}

$routes->group('addons/lms-finance', ['namespace' => 'App\Controllers\Addons\LmsFinance'], static function ($routes) {
    $routes->get('/', 'LmsFinanceController::index');
    $routes->get('invoices', 'LmsFinanceController::invoicesView');
    $routes->get('custom-billing', 'LmsFinanceController::customBillingView');
    $routes->get('payouts', 'LmsFinanceController::payoutsView');
    $routes->get('settings', 'LmsFinanceController::settingsView');
    $routes->get('portal', 'LmsFinanceController::portalFinance');

    $routes->post('save-subscription', 'LmsFinanceController::saveSubscription');
    $routes->post('toggle-lock', 'LmsFinanceController::toggleSubscriptionLock');
    $routes->post('delete-subscription', 'LmsFinanceController::deleteSubscription');
    $routes->post('save-custom-invoice', 'LmsFinanceController::saveCustomInvoice');
    $routes->post('delete-invoice', 'LmsFinanceController::deleteInvoice');
    $routes->post('verify-offline-proof', 'LmsFinanceController::verifyOfflineProof');
    $routes->post('delete-transaction', 'LmsFinanceController::deleteTransaction');
    $routes->post('save-payout', 'LmsFinanceController::savePayoutExpense');
    $routes->post('delete-payout', 'LmsFinanceController::deletePayoutExpense');
    $routes->post('save-razorpay-settings', 'LmsFinanceController::saveRazorpaySettings');
    $routes->post('save-receipt-template', 'LmsFinanceController::saveReceiptTemplate');
    $routes->get('assets/(:any)', 'LmsFinanceController::asset/$1');
});

$routes->group('finance', ['namespace' => 'App\Controllers\Addons\LmsFinance'], static function ($routes) {
    $routes->get('/', 'LmsFinanceController::portalFinance');
    $routes->get('portal', 'LmsFinanceController::portalFinance');
    $routes->get('my-payouts', 'LmsFinanceController::teacherPayoutsPortal');

    $routes->get('enroll-checkout/(:num)', 'LmsFinanceController::directCourseEnrollCheckout/$1');
    $routes->post('process-enroll-checkout', 'LmsFinanceController::processDirectCourseEnrollCheckout');

    $routes->post('create-razorpay-order', 'LmsFinanceController::createRazorpayOrderJson');
    $routes->post('verify-razorpay-payment', 'LmsFinanceController::verifyRazorpayPayment');
    $routes->post('submit-offline-proof', 'LmsFinanceController::submitOfflineProof');

    $routes->get('receipt-view/(:num)', 'LmsFinanceController::receiptView/$1');
    $routes->get('receipt-pdf/(:num)', 'LmsFinanceController::downloadReceiptPdf/$1');
});

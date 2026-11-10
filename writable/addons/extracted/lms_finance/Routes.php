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

        $rawUri = strtolower(trim((string) strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?'), '/'));
        if (
            preg_match('#(^|/)(logout|login|auth|core-lms-auth|switch-profile|switch|assets|finance|addons/lms-finance/portal|addons/lms-finance/assets)(/|$)#i', $rawUri)
        ) {
            return;
        }

        $finModel = new \App\Models\Addons\LmsFinance\LmsFinanceModel();
        $finModel->enforceStrictMonthlyFeeGate('/' . $rawUri);
    });

    // Inject clear Student Fee-Due Banner & Locked Feature Modal on `/dashboard` when `lms_child_fee_locked == 1`
    \CodeIgniter\Events\Events::on('post_system', static function () {
        if (is_cli() || ! session()->get('isLoggedIn')) {
            return;
        }
        if ((int) session()->get('lms_child_fee_locked') !== 1) {
            return;
        }

        $effectiveRole  = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));
        $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');
        $isStudentView  = ($effectiveRole === 'student') || ($portalViewMode === 'student' && $activeStuId > 0);

        if (! $isStudentView) {
            return;
        }

        $response = service('response');
        $body     = (string) $response->getBody();
        if ($body === '' || stripos($body, '</html>') === false || strpos($body, 'id="js-lfn-student-lock-banner"') !== false) {
            return;
        }

        $studentName = esc((string) (session()->get('active_student_name') ?: (session()->get('full_name') ?: 'Student')));
        $parentSwitchHint = ($activeStuId > 0)
            ? 'Switch back to your <strong>Parent Profile</strong> from the top bar and open <strong>Family Fee Portal</strong> to complete the due payment.'
            : 'Please ask your <strong>Parent / Guardian</strong> to log in and complete the pending course fee payment to unlock all features.';

        $lockWidgetHtml = <<<HTML
<style>
.lfn-stu-lock-banner {
    margin: 0 0 1.25rem 0;
    padding: 1rem 1.35rem;
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(244, 63, 94, 0.16), rgba(245, 158, 11, 0.12));
    border: 1.5px solid rgba(244, 63, 94, 0.55);
    color: var(--text-primary, #f8fafc);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    box-shadow: 0 10px 26px rgba(244, 63, 94, 0.14);
}
.lfn-stu-lock-left { display: flex; align-items: center; gap: 0.9rem; flex: 1 1 260px; min-width: 0; }
.lfn-stu-lock-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(244, 63, 94, 0.2); border: 1px solid rgba(244, 63, 94, 0.5);
    display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;
}
.lfn-stu-lock-title { font-weight: 800; font-size: 1rem; color: #fb7185; margin: 0 0 0.2rem 0; }
.lfn-stu-lock-sub { font-size: 0.85rem; color: var(--text-muted, #94a3b8); margin: 0; line-height: 1.45; }
.lfn-stu-lock-pill {
    padding: 0.4rem 0.85rem; border-radius: 999px; font-size: 0.76rem; font-weight: 800;
    background: rgba(244, 63, 94, 0.2); color: #fda4af; border: 1px solid rgba(244, 63, 94, 0.45);
    font-family: var(--font-mono, monospace); white-space: nowrap;
}
.lfn-stu-lock-modal-backdrop {
    position: fixed; inset: 0; background: rgba(6, 9, 18, 0.82); backdrop-filter: blur(6px);
    z-index: 99999; display: none; align-items: center; justify-content: center; padding: 1rem;
}
.lfn-stu-lock-modal-backdrop.is-open { display: flex; }
.lfn-stu-lock-modal {
    width: 100%; max-width: 460px; padding: 1.65rem; border-radius: 16px;
    background: var(--bg-surface, #11131c); border: 1.5px solid rgba(244, 63, 94, 0.6);
    color: var(--text-primary, #f8fafc); text-align: center; box-shadow: 0 24px 60px rgba(0, 0, 0, 0.55);
}
@media (max-width: 640px) {
    .lfn-stu-lock-banner { padding: 0.85rem 1rem; }
    .lfn-stu-lock-pill { width: 100%; text-align: center; }
}
</style>
<div id="js-lfn-student-lock-modal" class="lfn-stu-lock-modal-backdrop">
    <div class="lfn-stu-lock-modal">
        <div style="width:56px;height:56px;border-radius:16px;background:rgba(244,63,94,0.18);border:1px solid rgba(244,63,94,0.5);display:inline-flex;align-items:center;justify-content:center;font-size:1.65rem;margin-bottom:0.85rem;">🔒</div>
        <h3 style="margin:0 0 0.45rem;font-size:1.2rem;color:#fb7185;">Course Fee Due — Feature Locked</h3>
        <p style="font-size:0.9rem;color:var(--text-muted, #94a3b8);line-height:1.55;margin:0 0 1.1rem;">
            Live Classes, 3D Soroban Lab, Flash Anzan, Worksheets, and Teacher Chat are currently paused for <strong>{$studentName}</strong> because the batch tuition fee is due.
        </p>
        <div style="padding:0.75rem 1rem;border-radius:10px;background:var(--bg-canvas, #090a0f);border:1px solid var(--border-subtle, #232738);font-size:0.83rem;line-height:1.5;margin-bottom:1.2rem;">
            {$parentSwitchHint}
        </div>
        <button type="button" id="js-lfn-close-lock-modal" class="btn-primary" style="width:100%;justify-content:center;padding:0.7rem 1.2rem;">Got It</button>
    </div>
</div>
<script>
(function () {
    function mountStudentFeeLockUI() {
        var bannerHtml = '<div id="js-lfn-student-lock-banner" class="lfn-stu-lock-banner">' +
            '<div class="lfn-stu-lock-left">' +
            '  <div class="lfn-stu-lock-icon">🔒</div>' +
            '  <div>' +
            '    <div class="lfn-stu-lock-title">Course Access Paused — Batch Tuition Fee Due</div>' +
            '    <p class="lfn-stu-lock-sub">{$parentSwitchHint}</p>' +
            '  </div>' +
            '</div>' +
            '<span class="lfn-stu-lock-pill">FEE PAYMENT REQUIRED</span>' +
            '</div>';

        var targetContainer = document.querySelector('.app-content, main, .dashboard-wrapper, .content-wrapper') || document.body;
        if (targetContainer && !document.getElementById('js-lfn-student-lock-banner')) {
            targetContainer.insertAdjacentHTML('afterbegin', bannerHtml);
        }

        var modal = document.getElementById('js-lfn-student-lock-modal');
        var closeBtn = document.getElementById('js-lfn-close-lock-modal');
        if (closeBtn && modal) {
            closeBtn.addEventListener('click', function () { modal.classList.remove('is-open'); });
            modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('is-open'); });
        }

        if (window.location.search.indexOf('fee_locked=1') !== -1 && modal) {
            modal.classList.add('is-open');
        }

        document.addEventListener('click', function (e) {
            var link = e.target.closest('a[href]');
            if (!link) return;
            var href = (link.getAttribute('href') || '').toLowerCase();
            if (!href || href === '#' || href.indexOf('javascript:') === 0) return;

            var allowed = ['logout', 'login', 'switch', 'core-lms-auth', 'finance', 'addons/lms-finance'];
            for (var i = 0; i < allowed.length; i++) {
                if (href.indexOf(allowed[i]) !== -1) return;
            }

            var restricted = ['timetable', 'abacus', 'soroban', 'flash', 'assessments', 'courses', 'demo', 'communication', 'addons/lms-'];
            for (var j = 0; j < restricted.length; j++) {
                if (href.indexOf(restricted[j]) !== -1) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (modal) modal.classList.add('is-open');
                    return false;
                }
            }
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountStudentFeeLockUI);
    } else {
        mountStudentFeeLockUI();
    }
})();
</script>
HTML;

        $body = str_ireplace('</body>', $lockWidgetHtml . "\n</body>", $body);
        $response->setBody($body);
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
    $routes->post('log-failed-payment', 'LmsFinanceController::logFailedPaymentJson');
    $routes->post('submit-offline-proof', 'LmsFinanceController::submitOfflineProof');

    $routes->get('receipt-view/(:num)', 'LmsFinanceController::receiptView/$1');
    $routes->get('receipt-pdf/(:num)', 'LmsFinanceController::downloadReceiptPdf/$1');
});

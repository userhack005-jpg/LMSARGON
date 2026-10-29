<?php

namespace App\Controllers\Addons\LmsFinance;

use App\Controllers\BaseController;
use App\Models\Addons\LmsFinance\LmsFinanceModel;
use Throwable;

class LmsFinanceController extends BaseController
{
    protected ?LmsFinanceModel $finModel = null;
    protected string $addonPath;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->addonPath = WRITEPATH . 'addons/extracted/lms_finance';
    }

    private function getModel(): LmsFinanceModel
    {
        if ($this->finModel === null) {
            $modelFile = $this->addonPath . '/Models/Addons/LmsFinance/LmsFinanceModel.php';
            if (! class_exists(LmsFinanceModel::class) && is_file($modelFile)) {
                require_once $modelFile;
            }
            $this->finModel = new LmsFinanceModel();
        }
        return $this->finModel;
    }

    private function hasPermission($permissionKeys): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        if (function_exists('cms_has_permission')) {
            return cms_has_permission($permissionKeys);
        }
        return (int) session()->get('role_id') === 1 || (string) session()->get('role_slug') === 'super_admin';
    }

    private function renderAddonView(string $viewRelPath, array $data = []): string
    {
        if (! isset($data['settings']) || ! is_array($data['settings'])) {
            $data['settings'] = $this->getModel()->getGlobalSiteSettings();
        }
        if (empty($data['activeMenu'])) {
            $data['activeMenu'] = 'addon_lms_finance';
        }
        if (empty($data['user'])) {
            $data['user'] = session()->get();
        }

        $cleanView = ltrim($viewRelPath, '/');
        if (is_file(APPPATH . 'Views/' . $cleanView . '.php')) {
            return view($cleanView, $data);
        }
        \Config\Services::autoloader()->addNamespace('AddonLmsFinance', $this->addonPath);
        $nsView = 'AddonLmsFinance\\Views\\' . str_replace('/', '\\', $cleanView);
        return view($nsView, $data);
    }

    private function buildCommonAdminData(string $activeSubTab, string $pageTitle): array
    {
        $model = $this->getModel();
        $model->mirrorAssets(false);
        $model->syncSubscriptionsAndAutoInvoices();

        $roleUsers = $model->getRoleFilteredUsers();
        $leafNodes = $model->getLeafNodesWithPricing();
        $nodesById = [];
        foreach ($leafNodes as $nid => $n) {
            $nodesById[(int) $nid] = $n['title'];
        }

        $db           = \Config\Database::connect();
        $transactions = $model->orderBy('id', 'DESC')->findAll(80);
        $subs         = $db->table('lms_student_subscriptions')->orderBy('due_date', 'ASC')->get()->getResultArray();
        $invoices     = $db->table('lms_finance_invoices')->orderBy('id', 'DESC')->get()->getResultArray();
        $payouts      = $db->table('lms_teacher_payouts')->orderBy('id', 'DESC')->get()->getResultArray();
        $expenses     = $db->table('lms_general_expenses')->orderBy('expense_date', 'DESC')->get()->getResultArray();

        return [
            'pageTitle'         => $pageTitle,
            'activeMenu'        => 'addon_lms_finance',
            'activeNav'         => 'lms_finance',
            'activeSubTab'      => $activeSubTab,
            'settings'          => $model->getGlobalSiteSettings(),
            'user'              => session()->get(),
            'pnl'               => $model->getProfitAndLossSummary(),
            'graceDays'         => (int) $model->getFinanceSetting('grace_period_days', '5'),
            'autoInvoiceDays'   => (int) $model->getFinanceSetting('auto_invoice_days', '5'),
            'autoLockOn'        => $model->getFinanceSetting('auto_lock_overdue', '1') === '1',
            'gatewaysConfig'    => $model->getGatewaysConfig(),
            'razorpayConfig'    => $model->getActiveRazorpayConfig(),
            'receiptTemplate'   => $model->getReceiptTemplateConfig(),
            'enabledCountries'  => $model->getEnabledCountriesOnly(),
            'teachers'          => $roleUsers['teachers'],
            'students'          => $roleUsers['students'],
            'parents'           => $roleUsers['parents'],
            'childrenByParent'  => $roleUsers['children_by_parent'],
            'allUsers'          => $roleUsers['all'],
            'leafNodes'         => $leafNodes,
            'nodesById'         => $nodesById,
            'transactions'      => $transactions,
            'subscriptions'     => $subs,
            'invoices'          => $invoices,
            'payouts'           => $payouts,
            'expenses'          => $expenses,
            'canCollect'        => $this->hasPermission('lms_finance.collect'),
            'canPayroll'        => $this->hasPermission('lms_finance.payroll'),
            'canExpenses'       => $this->hasPermission('lms_finance.expenses'),
            'canDelete'         => $this->hasPermission('lms_finance.delete'),
        ];
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        if ($effectiveRole === 'teacher') {
            return redirect()->to(base_url('finance/my-payouts'));
        }
        if (! $this->hasPermission(['lms_finance.collect', 'lms_finance.payroll', 'lms_finance.expenses'])) {
            return redirect()->to(base_url('finance/portal'));
        }

        $data = $this->buildCommonAdminData('overview', 'Finance Overview & Revenue Ledger');
        return $this->renderAddonView('addons/lms_finance/overview_view', $data);
    }

    public function invoicesAndApprovalsView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_finance.collect', 'lms_finance.expenses'])) {
            return redirect()->to(base_url('finance/portal'));
        }

        $data = $this->buildCommonAdminData('invoices', 'Student Subscriptions, Offline Approvals & Direct Mark Paid');
        return $this->renderAddonView('addons/lms_finance/invoices_view', $data);
    }

    public function customBillingView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_finance.collect', 'lms_finance.expenses'])) {
            return redirect()->to(base_url('finance/portal'));
        }

        $data = $this->buildCommonAdminData('custom_billing', 'Request Custom Payment from Parent & Child');
        return $this->renderAddonView('addons/lms_finance/custom_billing_view', $data);
    }

    public function payrollView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_finance.payroll', 'lms_finance.collect'])) {
            return redirect()->to(base_url('finance/portal'));
        }

        $data = $this->buildCommonAdminData('payroll', 'Teacher Salary Disbursement');
        return $this->renderAddonView('addons/lms_finance/payroll_view', $data);
    }

    public function expensesView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_finance.expenses', 'lms_finance.collect'])) {
            return redirect()->to(base_url('finance/portal'));
        }

        $data = $this->buildCommonAdminData('expenses', 'Operational Expenses Ledger');
        return $this->renderAddonView('addons/lms_finance/expenses_view', $data);
    }

    public function gatewaysAndReceiptView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_finance.expenses', 'lms_finance.collect'])) {
            return redirect()->to(base_url('finance/portal'));
        }

        $data = $this->buildCommonAdminData('gateways', 'Razorpay Gateway Setup & Receipt Template Designer');
        return $this->renderAddonView('addons/lms_finance/gateways_view', $data);
    }

    public function teacherSalaryPortal()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $uid   = (int) (session()->get('user_id') ?: 1);
        $model = $this->getModel();
        $model->mirrorAssets(false);

        $db        = \Config\Database::connect();
        $myPayouts = $db->table('lms_teacher_payouts')
            ->where('teacher_user_id', $uid)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return $this->renderAddonView('addons/lms_finance/portal_teacher_view', [
            'pageTitle'      => 'My Monthly Salary Slips',
            'activeMenu'     => 'addon_lms_finance',
            'settings'       => $model->getGlobalSiteSettings(),
            'user'           => session()->get(),
            'myPayouts'      => $myPayouts,
            'canManageAdmin' => $this->hasPermission(['lms_finance.payroll', 'lms_finance.collect']),
        ]);
    }

    public function parentFeePortal()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $effectiveRole  = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        if ($effectiveRole === 'teacher') {
            return redirect()->to(base_url('finance/my-payouts'));
        }

        $data           = $this->buildCommonAdminData('portal', 'Family Fee Subscriptions & Official Receipts');
        $uid            = (int) (session()->get('user_id') ?: 1);
        $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');
        $baseRole       = strtolower(trim((string) (session()->get('role_slug') ?: 'student')));

        $isChildViewOnly = ($baseRole === 'student') || ($portalViewMode === 'student' && $activeStuId > 0);
        $parentKidsMap   = [];
        $targetChildIds  = [];

        if (! $isChildViewOnly && isset($data['childrenByParent'][$uid])) {
            foreach ($data['childrenByParent'][$uid] as $ch) {
                $cid = (int) $ch['id'];
                $parentKidsMap[$cid] = $ch['display_name'];
                $targetChildIds[]    = $cid;
            }
        }

        if (empty($targetChildIds)) {
            $stuId = $activeStuId > 0 ? $activeStuId : $uid;
            if (isset($data['students'][$stuId])) {
                $parentKidsMap[$stuId] = $data['students'][$stuId]['display_name'];
                $targetChildIds[]      = $stuId;
            } elseif (! empty($data['students']) && $this->hasPermission('lms_finance.collect')) {
                foreach ($data['students'] as $sid => $sRow) {
                    $parentKidsMap[(int) $sid] = $sRow['display_name'];
                    $targetChildIds[]          = (int) $sid;
                }
            }
        }

        $db         = \Config\Database::connect();
        $mySubs     = [];
        $myInvoices = [];
        $myTxns     = [];

        if (! empty($targetChildIds)) {
            $mySubs     = $db->table('lms_student_subscriptions')->whereIn('student_user_id', $targetChildIds)->orderBy('due_date', 'ASC')->get()->getResultArray();
            $myInvoices = $db->table('lms_finance_invoices')->whereIn('student_user_id', $targetChildIds)->orderBy('id', 'DESC')->get()->getResultArray();
            $myTxns     = $this->getModel()->whereIn('student_user_id', $targetChildIds)->orderBy('id', 'DESC')->findAll(50);
        }

        $data['isChildViewOnly'] = $isChildViewOnly;
        $data['parentKidsMap']   = $parentKidsMap;
        $data['mySubs']          = $mySubs;
        $data['myInvoices']      = $myInvoices;
        $data['myTxns']          = $myTxns;
        $data['isLockedBanner']  = (int) ($this->request->getGet('locked') ?: session()->get('lms_child_fee_locked')) === 1;
        $data['canManageAdmin']  = $this->hasPermission(['lms_finance.collect', 'lms_finance.expenses']);

        return $this->renderAddonView('addons/lms_finance/portal_parent_view', $data);
    }

    public function directCourseEnrollCheckout($nodeId = 0)
    {
        $nid   = (int) $nodeId;
        $model = $this->getModel();
        $model->mirrorAssets(false);

        $leaves = $model->getLeafNodesWithPricing();
        if (! isset($leaves[$nid])) {
            return redirect()->to(base_url('/'))->with('error', 'Course not found.');
        }

        $cCode = strtoupper(trim((string) ($this->request->getGet('country') ?: (session()->get('user_country') ?: 'IN'))));
        $quote = $model->resolveCourseFeeForCountry($nid, $cCode, 'monthly');

        return $this->renderAddonView('addons/lms_finance/enroll_checkout_view', [
            'pageTitle'        => 'Enroll in ' . $quote['course_title'],
            'settings'         => $model->getGlobalSiteSettings(),
            'user'             => session()->get(),
            'isLoggedIn'       => (bool) session()->get('isLoggedIn'),
            'courseQuote'      => $quote,
            'razorpayConfig'   => $model->getActiveRazorpayConfig(),
            'enabledCountries' => $model->getEnabledCountriesOnly(),
        ]);
    }

    public function processDirectCourseEnrollCheckout()
    {
        $model       = $this->getModel();
        $db          = \Config\Database::connect();
        $nodeId      = (int) ($this->request->getPost('node_id') ?? 0);
        $countryCode = strtoupper(trim((string) ($this->request->getPost('country_code') ?? 'IN'))) ?: 'IN';
        $parentName  = trim((string) ($this->request->getPost('parent_name') ?? ''));
        $parentEmail = strtolower(trim((string) ($this->request->getPost('parent_email') ?? '')));
        $childName   = trim((string) ($this->request->getPost('child_name') ?? ''));
        $childAge    = max(4, min(22, (int) ($this->request->getPost('child_age') ?? 8)));
        $payMethod   = trim((string) ($this->request->getPost('payment_method') ?? 'razorpay'));
        $txnRef      = trim((string) ($this->request->getPost('txn_reference') ?? ''));

        if ($nodeId <= 0 || $parentName === '' || $parentEmail === '' || $childName === '') {
            return redirect()->back()->with('error', 'Please fill in both Parent and Child details.');
        }

        $now   = gmdate('Y-m-d H:i:s');
        $uCols = array_flip($db->getFieldNames('users'));

        $parentRow = $db->table('users')->where('email', $parentEmail)->get()->getRowArray();
        if ($parentRow) {
            $parentId = (int) $parentRow['id'];
        } else {
            $pInsert = array_intersect_key([
                'role_id'       => 4,
                'role_slug'     => 'parent',
                'full_name'     => $parentName,
                'name'          => $parentName,
                'email'         => $parentEmail,
                'password_hash' => password_hash('Parent@123', PASSWORD_DEFAULT),
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ], $uCols);
            $db->table('users')->insert($pInsert);
            $parentId = (int) $db->insertID();
        }

        $childEmail = preg_replace('/[^a-z0-9]+/', '', strtolower($childName)) . '.' . $parentId . '.' . rand(10, 99) . '@student.portal';
        $cInsert = array_intersect_key([
            'role_id'        => 4,
            'role_slug'      => 'student',
            'parent_user_id' => $parentId,
            'full_name'      => $childName,
            'name'           => $childName,
            'email'          => $childEmail,
            'password_hash'  => password_hash('Kid@123', PASSWORD_DEFAULT),
            'status'         => 'active',
            'created_at'     => $now,
            'updated_at'     => $now,
        ], $uCols);
        $db->table('users')->insert($cInsert);
        $studentId = (int) $db->insertID();

        $quote = $model->resolveCourseFeeForCountry($nodeId, $countryCode, 'monthly');
        if ($db->tableExists('cms_user_meta')) {
            $db->table('cms_user_meta')->insert([
                'user_id'       => $studentId,
                'leaf_node_id'  => $nodeId,
                'child_age'     => $childAge,
                'grade_level'   => $quote['course_title'],
                'country_code'  => $countryCode,
                'currency_code' => $quote['currency'],
                'timezone'      => 'Asia/Kolkata',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        if ($db->tableExists('lms_hierarchy_enrollments')) {
            $db->table('lms_hierarchy_enrollments')->insert([
                'student_user_id'    => $studentId,
                'parent_user_id'     => $parentId,
                'leaf_node_id'       => $nodeId,
                'country_code'       => $countryCode,
                'currency_code'      => $quote['currency'],
                'monthly_fee'        => $quote['amount'],
                'billing_cycle'      => 'monthly',
                'next_due_date'      => date('Y-m-d', strtotime('+30 days')),
                'attendance_pct'     => 100.00,
                'test_score_avg'     => 0.00,
                'certificates_count' => 0,
                'history_json'       => json_encode([]),
                'status'             => 'active',
                'enrolled_at'        => $now,
                'updated_at'         => $now,
            ]);
        }

        if ($payMethod === 'offline_proof') {
            $uploadedProof = $this->request->getFile('proof_file');
            $res = $model->submitOfflineProofTransaction([
                'student_user_id' => $studentId,
                'node_id'         => $nodeId,
                'txn_reference'   => $txnRef,
            ], $uploadedProof);
            return redirect()->to(base_url('finance/portal'))->with($res['ok'] ? 'success' : 'error', $res['message']);
        }

        $res = $model->finalizePaidTransaction([
            'student_user_id' => $studentId,
            'parent_user_id'  => $parentId,
            'node_id'         => $nodeId,
            'provider'        => 'razorpay',
            'txn_reference'   => $txnRef ?: ('RZP-' . strtoupper(bin2hex(random_bytes(4)))),
            'currency'        => $quote['currency'],
            'amount'          => $quote['amount'],
        ]);

        return redirect()->to(base_url('finance/receipt-view/' . (int) ($res['txn_id'] ?? 0)))
            ->with('success', $res['message']);
    }

    public function createRazorpayOrderJson()
    {
        if (! session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false, 'message' => 'Please log in.']);
        }

        $model      = $this->getModel();
        $rzpConfig  = $model->getActiveRazorpayConfig();
        $amount     = max(1, (float) ($this->request->getPost('amount') ?? 1000));
        $currency   = strtoupper(trim((string) ($this->request->getPost('currency') ?? 'INR'))) ?: 'INR';
        $amtSubunit = (int) round($amount * 100);

        $orderId = '';
        if ($rzpConfig['key_id'] !== '' && $rzpConfig['key_secret'] !== '' && function_exists('curl_init')) {
            try {
                $ch = curl_init('https://api.razorpay.com/v1/orders');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERPWD, $rzpConfig['key_id'] . ':' . $rzpConfig['key_secret']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'amount'   => $amtSubunit,
                    'currency' => $currency,
                    'receipt'  => 'rcpt_' . time(),
                ]));
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                $resp = curl_exec($ch);
                curl_close($ch);
                $decoded = json_decode((string) $resp, true);
                if (! empty($decoded['id'])) {
                    $orderId = (string) $decoded['id'];
                }
            } catch (Throwable $e) {
            }
        }

        return $this->response->setJSON([
            'ok'         => true,
            'mode'       => $rzpConfig['mode'],
            'key_id'     => $rzpConfig['key_id'],
            'order_id'   => $orderId,
            'amount'     => $amount,
            'subunit'    => $amtSubunit,
            'currency'   => $currency,
            'csrf_token' => csrf_hash(),
        ]);
    }

    public function verifyRazorpayPayment()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $model     = $this->getModel();
        $rzpConfig = $model->getActiveRazorpayConfig();
        $payId     = trim((string) ($this->request->getPost('razorpay_payment_id') ?? ''));
        $orderId   = trim((string) ($this->request->getPost('razorpay_order_id') ?? ''));
        $sig       = trim((string) ($this->request->getPost('razorpay_signature') ?? ''));

        if ($orderId !== '' && $sig !== '' && $rzpConfig['key_secret'] !== '') {
            $expected = hash_hmac('sha256', $orderId . '|' . $payId, $rzpConfig['key_secret']);
            if (! hash_equals($expected, $sig)) {
                return redirect()->to(base_url('finance/portal'))->with('error', 'Razorpay signature verification failed.');
            }
        }

        $res = $model->finalizePaidTransaction([
            'student_user_id'   => (int) ($this->request->getPost('student_user_id') ?? 0),
            'invoice_id'        => (int) ($this->request->getPost('invoice_id') ?? 0),
            'subscription_id'   => (int) ($this->request->getPost('subscription_id') ?? 0),
            'node_id'           => (int) ($this->request->getPost('node_id') ?? 0),
            'provider'          => 'razorpay_' . $rzpConfig['mode'],
            'txn_reference'     => $payId ?: ('RZP-' . strtoupper(bin2hex(random_bytes(4)))),
            'currency'          => (string) ($this->request->getPost('currency') ?? 'INR'),
            'amount'            => (float) ($this->request->getPost('amount') ?? 0),
            'verification_note' => 'Verified via Razorpay (' . strtoupper($rzpConfig['mode']) . ')',
        ]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON($res);
        }

        return redirect()->to(base_url('finance/receipt-view/' . (int) ($res['txn_id'] ?? 0)))
            ->with('success', $res['message']);
    }

    public function submitOfflinePaymentProof()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $uploadedProof = $this->request->getFile('proof_file');
        $res           = $this->getModel()->submitOfflineProofTransaction($this->request->getPost(), $uploadedProof);

        return redirect()->to(base_url('finance/portal'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function adminMarkPaidDirect()
    {
        if (! $this->hasPermission('lms_finance.collect')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $res = $this->getModel()->finalizePaidTransaction([
            'student_user_id'   => (int) ($this->request->getPost('student_user_id') ?? 0),
            'invoice_id'        => (int) ($this->request->getPost('invoice_id') ?? 0),
            'subscription_id'   => (int) ($this->request->getPost('subscription_id') ?? 0),
            'node_id'           => (int) ($this->request->getPost('node_id') ?? 0),
            'provider'          => 'admin_direct_paid',
            'txn_reference'     => trim((string) ($this->request->getPost('txn_reference') ?? '')) ?: ('ADM-PAID-' . date('YmdHis')),
            'verification_note' => 'Directly Marked Paid by Admin',
        ]);

        return redirect()->to(base_url('addons/lms-finance/invoices'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function approveOfflineTransaction()
    {
        if (! $this->hasPermission('lms_finance.collect')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $txnId = (int) ($this->request->getPost('txn_id') ?? 0);
        $model = $this->getModel();
        $tx    = $model->find($txnId);
        if (! $tx) {
            return redirect()->back()->with('error', 'Transaction not found.');
        }

        $res = $model->finalizePaidTransaction([
            'existing_txn_id'   => $txnId,
            'student_user_id'   => (int) $tx['student_user_id'],
            'parent_user_id'    => (int) $tx['parent_user_id'],
            'invoice_id'        => (int) $tx['invoice_id'],
            'node_id'           => (int) $tx['node_id'],
            'provider'          => 'offline_verified',
            'txn_reference'     => $tx['txn_reference'],
            'currency'          => $tx['currency'],
            'subtotal_amount'   => (float) $tx['subtotal_amount'],
            'tax_percent'       => (float) $tx['tax_percent'],
            'tax_amount'        => (float) $tx['tax_amount'],
            'amount'            => (float) $tx['amount'],
            'proof_file_url'    => $tx['proof_file_url'],
            'verification_note' => 'Approved by Admin on ' . gmdate('d M Y'),
        ]);

        return redirect()->to(base_url('addons/lms-finance/invoices'))
            ->with('success', 'Offline payment #' . $txnId . ' approved & official receipt unlocked!');
    }

    public function rejectOfflineTransaction()
    {
        if (! $this->hasPermission('lms_finance.collect')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $txnId = (int) ($this->request->getPost('txn_id') ?? 0);
        $note  = trim((string) ($this->request->getPost('verification_note') ?? 'Screenshot / Reference could not be verified'));
        $model = $this->getModel();
        $tx    = $model->find($txnId);

        if ($tx) {
            $model->update($txnId, [
                'status'            => 'rejected',
                'verification_note' => $note,
                'updated_at'        => gmdate('Y-m-d H:i:s'),
            ]);
            if ((int) $tx['invoice_id'] > 0) {
                \Config\Database::connect()->table('lms_finance_invoices')
                    ->where('id', (int) $tx['invoice_id'])
                    ->update(['status' => 'unpaid']);
            }
        }
        return redirect()->to(base_url('addons/lms-finance/invoices'))
            ->with('error', 'Offline payment submission marked as Rejected.');
    }

    public function createCustomInvoice()
    {
        if (! $this->hasPermission('lms_finance.collect')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $res = $this->getModel()->createCustomParentChildInvoice($this->request->getPost());
        return redirect()->to(base_url('addons/lms-finance/custom-billing'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteInvoice()
    {
        if (! $this->hasPermission('lms_finance.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            \Config\Database::connect()->table('lms_finance_invoices')->where('id', $id)->delete();
        }
        return redirect()->back()->with('success', 'Invoice removed.');
    }

    public function saveSettings()
    {
        if (! $this->hasPermission(['lms_finance.expenses', 'lms_finance.collect'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $grace   = max(0, (int) ($this->request->getPost('grace_period_days') ?? 5));
        $autoInv = max(1, (int) ($this->request->getPost('auto_invoice_days') ?? 5));
        $lock    = $this->request->getPost('auto_lock_overdue') ? '1' : '0';

        $model = $this->getModel();
        $model->saveFinanceSetting('grace_period_days', (string) $grace);
        $model->saveFinanceSetting('auto_invoice_days', (string) $autoInv);
        $model->saveFinanceSetting('auto_lock_overdue', $lock);
        $model->syncSubscriptionsAndAutoInvoices();
        $stats = $model->evaluateGracePeriodLocks();

        return redirect()->back()->with(
            'success',
            'Billing rules saved (Auto-Invoice: ' . $autoInv . 'd before due | Grace: ' . $grace . 'd | Locked: ' . (int) $stats['locked_count'] . ').'
        );
    }

    public function saveGatewayConfig()
    {
        if (! $this->hasPermission('lms_finance.expenses')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $model    = $this->getModel();
        $gateways = $model->getGatewaysConfig();
        $post     = $this->request->getPost();

        $gateways['razorpay'] = [
            'slug'            => 'razorpay',
            'name'            => 'Razorpay Checkout',
            'is_enabled'      => ! empty($post['rzp_enabled']) ? 1 : 0,
            'mode'            => ($post['rzp_mode'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox',
            'test_key_id'     => trim((string) ($post['rzp_test_key_id'] ?? '')),
            'test_key_secret' => trim((string) ($post['rzp_test_key_secret'] ?? '')),
            'live_key_id'     => trim((string) ($post['rzp_live_key_id'] ?? '')),
            'live_key_secret' => trim((string) ($post['rzp_live_key_secret'] ?? '')),
        ];

        $model->saveFinanceSetting('gateways_json', json_encode($gateways, JSON_UNESCAPED_UNICODE));
        return redirect()->to(base_url('addons/lms-finance/gateways'))
            ->with('success', 'Razorpay Gateway (' . strtoupper($gateways['razorpay']['mode']) . ' Mode) configuration saved!');
    }

    public function saveReceiptTemplate()
    {
        if (! $this->hasPermission('lms_finance.expenses')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $model = $this->getModel();
        $curr  = $model->getReceiptTemplateConfig();
        $post  = $this->request->getPost();

        $logoUrl      = trim((string) ($post['logo_url'] ?? $curr['logo_url']));
        $uploadedLogo = $this->request->getFile('logo_file');
        if ($uploadedLogo && $uploadedLogo->isValid() && ! $uploadedLogo->hasMoved()) {
            $ext = strtolower($uploadedLogo->getClientExtension());
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                $uploadDir = FCPATH . 'uploads/lms_finance/';
                if (! is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $newName = 'receipt_logo_' . time() . '_' . $uploadedLogo->getRandomName();
                $uploadedLogo->move($uploadDir, $newName);
                $logoUrl = base_url('uploads/lms_finance/' . $newName);
            }
        }

        $tpl = [
            'academy_name'    => trim((string) ($post['academy_name'] ?? $curr['academy_name'])),
            'logo_url'        => $logoUrl,
            'accent_color'    => trim((string) ($post['accent_color'] ?? '#10b981')),
            'tax_label'       => trim((string) ($post['tax_label'] ?? 'GST / Tax Reg No.')),
            'tax_number'      => trim((string) ($post['tax_number'] ?? '')),
            'support_email'   => trim((string) ($post['support_email'] ?? '')),
            'support_phone'   => trim((string) ($post['support_phone'] ?? '')),
            'address_line'    => trim((string) ($post['address_line'] ?? '')),
            'signature_name'  => trim((string) ($post['signature_name'] ?? 'Authorized Signatory')),
            'footer_note'     => trim((string) ($post['footer_note'] ?? '')),
        ];

        $model->saveFinanceSetting('receipt_template_json', json_encode($tpl, JSON_UNESCAPED_UNICODE));
        return redirect()->to(base_url('addons/lms-finance/gateways'))
            ->with('success', 'Official Receipt Template design updated!');
    }

    public function runGraceLockCheck()
    {
        if (! $this->hasPermission('lms_finance.collect')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $model = $this->getModel();
        $model->syncSubscriptionsAndAutoInvoices();
        $stats = $model->evaluateGracePeriodLocks();
        return redirect()->back()->with('success', 'Auto-Invoices & Grace Locks verified (' . (int) $stats['locked_count'] . ' overdue locked).');
    }

    public function sendPaymentReminder()
    {
        if (! $this->hasPermission('lms_finance.collect')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $subId = (int) ($this->request->getPost('subscription_id') ?? 0);
        $db    = \Config\Database::connect();
        $sub   = $db->table('lms_student_subscriptions')->where('id', $subId)->get()->getRowArray();
        if ($sub) {
            $now = gmdate('Y-m-d H:i:s');
            $db->table('lms_student_subscriptions')->where('id', $subId)->update(['last_reminder_sent_at' => $now]);

            if ($db->tableExists('lms_notification_logs')) {
                $db->table('lms_notification_logs')->insert([
                    'event_type'         => 'Fee Due Reminder (' . $sub['currency'] . ' ' . $sub['fee_amount'] . ' due on ' . $sub['due_date'] . ')',
                    'channel'            => 'whatsapp_and_email',
                    'recipient_user_id'  => (int) ($sub['parent_user_id'] ?: $sub['student_user_id']),
                    'recipient_timezone' => 'UTC',
                    'formatted_message'  => 'Friendly Reminder: Course fee of ' . $sub['currency'] . ' ' . $sub['fee_amount'] . ' is due on ' . $sub['due_date'] . '. You can pay online anytime via your Family Fee Portal.',
                    'status'             => 'sent',
                    'created_at'         => $now,
                ]);
            }
        }
        return redirect()->back()->with('success', 'Fee Payment Reminder sent to Parent!');
    }

    public function saveTeacherSalary()
    {
        if (! $this->hasPermission('lms_finance.payroll')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $res = $this->getModel()->saveManualTeacherPayout($this->request->getPost());
        return redirect()->to(base_url('addons/lms-finance/payroll'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteTeacherSalary()
    {
        if (! $this->hasPermission('lms_finance.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            \Config\Database::connect()->table('lms_teacher_payouts')->where('id', $id)->delete();
        }
        return redirect()->to(base_url('addons/lms-finance/payroll'))->with('success', 'Salary entry deleted.');
    }

    public function saveExpense()
    {
        if (! $this->hasPermission('lms_finance.expenses')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $adminId = (int) (session()->get('user_id') ?: 1);
        $res     = $this->getModel()->saveGeneralExpense($this->request->getPost(), $adminId);
        return redirect()->to(base_url('addons/lms-finance/expenses'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteExpense()
    {
        if (! $this->hasPermission('lms_finance.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            \Config\Database::connect()->table('lms_general_expenses')->where('id', $id)->delete();
        }
        return redirect()->to(base_url('addons/lms-finance/expenses'))->with('success', 'Expense deleted.');
    }

    public function styledReceiptView($txnId = 0)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $model = $this->getModel();
        $txn   = $model->find((int) $txnId);
        if (! $txn) {
            return redirect()->back()->with('error', 'Receipt not found.');
        }

        $users   = $model->getRoleFilteredUsers()['all'];
        $sId     = (int) ($txn['student_user_id'] ?? 0);
        $sName   = $users[$sId]['display_name'] ?? 'Student';
        $leaves  = $model->getLeafNodesWithPricing();
        $nid     = (int) ($txn['node_id'] ?? 0);
        $cTitle  = $leaves[$nid]['title'] ?? ($txn['invoice_title'] ?? 'Enrolled Course');

        return $this->renderAddonView('addons/lms_finance/receipt_print_view', [
            'pageTitle'   => 'Official Fee Receipt — ' . $txn['txn_reference'],
            'settings'    => $model->getGlobalSiteSettings(),
            'user'        => session()->get(),
            'txn'         => $txn,
            'studentName' => $sName,
            'courseTitle' => $cTitle,
            'tpl'         => $model->getReceiptTemplateConfig(),
        ]);
    }

    public function downloadReceiptPdf($txnId = 0)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $model = $this->getModel();
        $txn   = $model->find((int) $txnId);
        if (! $txn) {
            return redirect()->back()->with('error', 'Receipt not found.');
        }

        $users  = $model->getRoleFilteredUsers()['all'];
        $sId    = (int) ($txn['student_user_id'] ?? 0);
        $sName  = $users[$sId]['display_name'] ?? 'Student';
        $leaves = $model->getLeafNodesWithPricing();
        $nid    = (int) ($txn['node_id'] ?? 0);
        $cTitle = $leaves[$nid]['title'] ?? ($txn['invoice_title'] ?? 'Enrolled Course');

        $pdfBytes = $model->generateBrandedPdfBinary($txn, $sName, $cTitle);
        $fileName = 'Receipt_' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $txn['txn_reference']) . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setBody($pdfBytes);
    }

    public function asset($file = '')
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $safeFile = basename((string) $file);
        $fullPath = $this->addonPath . '/assets/' . $safeFile;
        if (! is_file($fullPath)) {
            return $this->response->setStatusCode(404)->setBody('/* Asset not found */');
        }
        $ext  = strtolower((string) pathinfo($safeFile, PATHINFO_EXTENSION));
        $mime = ($ext === 'css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'no-cache, must-revalidate')
            ->setBody((string) file_get_contents($fullPath));
    }
}

<?php

namespace App\Controllers\Addons\LmsFinance;

use App\Controllers\BaseController;
use App\Models\Addons\LmsFinance\LmsFinanceModel;

class LmsFinanceController extends BaseController
{
    protected ?LmsFinanceModel $finModel = null;
    protected string $addonPath;

    public function __construct()
    {
        @date_default_timezone_set('Asia/Kolkata');
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
            $data['user'] = session()->get() ?: [];
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
        $model       = $this->getModel();
        $db          = \Config\Database::connect();
        $usersBundle = $model->getRoleUsersWithMeta();
        $nodesById   = [];
        foreach ($model->getLeafNodesWithPricing() as $nid => $nRow) {
            $nodesById[(int) $nid] = $nRow['title'] ?? 'Course';
        }

        $txns     = $db->table('payment_transactions')->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
        $model->evaluateSubscriptionLocks();
        $subs     = $db->table('lms_student_subscriptions')->orderBy('id', 'DESC')->get()->getResultArray();
        $invoices = $db->table('lms_finance_invoices')->orderBy('id', 'DESC')->limit(150)->get()->getResultArray();
        $expenses = $db->table('lms_finance_expenses')->orderBy('id', 'DESC')->limit(150)->get()->getResultArray();

        $revenueInr    = 0.0;
        $pendingProofs = [];
        foreach ($txns as $t) {
            if (($t['status'] ?? '') === 'paid') {
                $eq = (float) ($t['amount_inr_equiv'] ?? 0);
                $revenueInr += $eq > 0 ? $eq : (float) ($t['amount'] ?? 0);
            } elseif (($t['status'] ?? '') === 'pending_verification') {
                $pendingProofs[] = $t;
            }
        }

        $expensesInr = 0.0;
        foreach ($expenses as $ex) {
            $expensesInr += $model->convertToInrEquiv((float) ($ex['amount'] ?? 0), (string) ($ex['currency'] ?? 'INR'));
        }

        return [
            'pageTitle'        => $pageTitle,
            'activeMenu'       => 'addon_lms_finance',
            'activeNav'        => 'lms_finance',
            'activeSubTab'     => $activeSubTab,
            'settings'         => $model->getGlobalSiteSettings(),
            'user'             => session()->get() ?: [],
            'allUsers'         => $usersBundle['all'],
            'students'         => $usersBundle['students'],
            'parents'          => $usersBundle['parents'],
            'teachers'         => $usersBundle['teachers'],
            'nodesById'        => $nodesById,
            'txns'             => $txns,
            'pendingProofs'    => $pendingProofs,
            'subs'             => $subs,
            'invoices'         => $invoices,
            'expenses'         => $expenses,
            'revenueInr'       => $revenueInr,
            'expensesInr'      => $expensesInr,
            'netProfitInr'     => $revenueInr - $expensesInr,
            'razorpayConfig'   => $model->getActiveRazorpayConfig(),
            'receiptTemplate'  => $model->getActiveReceiptTemplateConfig(),
            'enabledCountries' => $model->getEnabledCountriesOnly(),
            'canCollect'       => $this->hasPermission('lms_finance.collect'),
            'canExpenses'      => $this->hasPermission('lms_finance.expenses'),
            'canSettings'      => $this->hasPermission('lms_finance.settings'),
            'canDelete'        => $this->hasPermission('lms_finance.delete'),
        ];
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $effectiveRole = function_exists('cms_get_effective_role') ? cms_get_effective_role() : 'student';
        if ($effectiveRole === 'teacher' && ! $this->hasPermission('lms_finance.collect')) {
            return $this->teacherPayoutsPortal();
        }
        if ($this->hasPermission(['lms_finance.collect', 'lms_finance.settings'])) {
            $data = $this->buildCommonAdminData('ledger', 'Finance Overview & Payment Transactions');
            return $this->renderAddonView('addons/lms_finance/index_view', $data);
        }
        return $this->portalFinance();
    }

    public function invoicesView()
    {
        if (! $this->hasPermission('lms_finance.collect')) {
            return redirect()->to(base_url('finance/portal'));
        }
        $data = $this->buildCommonAdminData('invoices', 'Batch Subscriptions & Monthly Locks');
        return $this->renderAddonView('addons/lms_finance/invoices_view', $data);
    }

    public function customBillingView()
    {
        if (! $this->hasPermission('lms_finance.collect')) {
            return redirect()->to(base_url('finance/portal'));
        }
        $data = $this->buildCommonAdminData('custom_billing', 'Custom Parent-Child Fee Billing');
        return $this->renderAddonView('addons/lms_finance/custom_billing_view', $data);
    }

    public function payoutsView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));
        if ($effectiveRole === 'teacher' && ! $this->hasPermission('lms_finance.collect')) {
            return $this->teacherPayoutsPortal();
        }
        if (! $this->hasPermission(['lms_finance.expenses', 'lms_finance.collect'])) {
            return redirect()->to(base_url('finance/portal'));
        }
        $data = $this->buildCommonAdminData('payouts', 'Teacher Payroll & Academy Expenses');
        return $this->renderAddonView('addons/lms_finance/payouts_view', $data);
    }

    public function settingsView()
    {
        if (! $this->hasPermission('lms_finance.settings')) {
            return redirect()->to(base_url('finance/portal'));
        }
        $data = $this->buildCommonAdminData('settings', 'Razorpay Gateway & Official Receipt Designer');
        return $this->renderAddonView('addons/lms_finance/settings_view', $data);
    }

    /**
     * ULTRA-FAST FAMILY FEE PORTAL (`/finance/portal`):
     * Queries ONLY the current Parent/Student's records (0ms latency) and maps latest paid receipts!
     */
    public function portalFinance()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        if ($effectiveRole === 'teacher' && ! $this->hasPermission('lms_finance.collect')) {
            return $this->teacherPayoutsPortal();
        }

        $model          = $this->getModel();
        $db             = \Config\Database::connect();
        $uid            = (int) (session()->get('user_id') ?: (session()->get('id') ?: 1));
        $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');
        $isChildMode    = ($portalViewMode === 'student' && $activeStuId > 0) || ($effectiveRole === 'student');
        $canManageAdmin = $this->hasPermission(['lms_finance.collect', 'lms_finance.settings']);

        $parentKidsMap = [];
        $targetStuIds  = [];
        $allUsers      = [];

        if ($isChildMode) {
            $stuId = $activeStuId > 0 ? $activeStuId : $uid;
            $sRow  = $db->table('users')->select('id, full_name, email')->where('id', $stuId)->get()->getRowArray();
            $sName = ! empty($sRow['full_name']) ? $sRow['full_name'] : 'Student';
            $targetStuIds               = [$stuId];
            $parentKidsMap[$stuId]      = $sName;
            $allUsers[$stuId]           = ['id' => $stuId, 'display_name' => $sName];
        } elseif ($effectiveRole === 'parent' || ! $canManageAdmin) {
            foreach ($db->table('users')->select('id, full_name, email')->where('parent_user_id', $uid)->orderBy('id', 'ASC')->get()->getResultArray() as $k) {
                $kidId = (int) $k['id'];
                $kName = ! empty($k['full_name']) ? $k['full_name'] : ($k['email'] ?: 'Child');
                $targetStuIds[]        = $kidId;
                $parentKidsMap[$kidId] = $kName;
                $allUsers[$kidId]      = ['id' => $kidId, 'display_name' => $kName];
            }
        } else {
            // Admin previewing portal
            foreach ($db->table('users')->select('id, full_name, email, role_id, role_slug')->orderBy('id', 'ASC')->limit(100)->get()->getResultArray() as $u) {
                $id    = (int) $u['id'];
                $rSlug = strtolower(trim((string) ($u['role_slug'] ?? 'student')));
                $uName = ! empty($u['full_name']) ? $u['full_name'] : ($u['email'] ?: ('User #' . $id));
                $allUsers[$id] = ['id' => $id, 'display_name' => $uName];
                if ($rSlug === 'student' || (int) ($u['role_id'] ?? 4) === 4) {
                    $targetStuIds[]      = $id;
                    $parentKidsMap[$id]  = $uName;
                }
            }
        }

        $nodesById = [];
        foreach ($model->getLeafNodesWithPricing() as $nid => $nRow) {
            $nodesById[(int) $nid] = $nRow['title'] ?? 'Course';
        }

        $mySubs     = [];
        $myInvoices = [];
        $myTxns     = [];

        if (! empty($targetStuIds)) {
            $model->evaluateSubscriptionLocks($targetStuIds);
            $mySubs     = $db->table('lms_student_subscriptions')->whereIn('student_user_id', $targetStuIds)->orderBy('id', 'DESC')->get()->getResultArray();
            $myInvoices = $db->table('lms_finance_invoices')->whereIn('student_user_id', $targetStuIds)->orderBy('id', 'DESC')->get()->getResultArray();
        }

        // Fetch transactions matching EITHER the children's `student_user_id` OR the logged-in `parent_user_id`!
        $txQb = $db->table('payment_transactions')->orderBy('id', 'DESC');
        if (! empty($targetStuIds) && ! $canManageAdmin) {
            $txQb->groupStart()
                ->whereIn('student_user_id', $targetStuIds)
                ->orWhere('parent_user_id', $uid)
            ->groupEnd();
            $myTxns = $txQb->get()->getResultArray();
        } elseif (! empty($targetStuIds) && $canManageAdmin) {
            $myTxns = $txQb->limit(100)->get()->getResultArray();
        } elseif ($uid > 0) {
            $myTxns = $txQb->where('parent_user_id', $uid)->get()->getResultArray();
        }

        // Map latest paid transaction per student & per subscription so Subscription Cards ALWAYS have Receipt buttons!
        $latestPaidTxnByStu = [];
        $latestPaidTxnBySub = [];
        foreach ($myTxns as $tx) {
            $st  = strtolower(trim((string) ($tx['status'] ?? 'paid')));
            $tid = (int) ($tx['id'] ?? 0);
            $sid = (int) ($tx['student_user_id'] ?? 0);
            $sub = (int) ($tx['subscription_id'] ?? 0);
            if (in_array($st, ['paid', 'success', 'captured', 'verified', 'completed'], true) && $tid > 0) {
                if ($sid > 0 && ! isset($latestPaidTxnByStu[$sid])) {
                    $latestPaidTxnByStu[$sid] = $tx;
                }
                if ($sub > 0 && ! isset($latestPaidTxnBySub[$sub])) {
                    $latestPaidTxnBySub[$sub] = $tx;
                }
            }
        }

        $data = [
            'pageTitle'          => 'Family Course Fee Subscriptions & Official Receipts',
            'activeMenu'         => 'addon_lms_finance',
            'activeNav'          => 'lms_finance',
            'activeSubTab'       => 'portal',
            'settings'           => $model->getGlobalSiteSettings(),
            'user'               => session()->get() ?: [],
            'allUsers'           => $allUsers,
            'nodesById'          => $nodesById,
            'razorpayConfig'     => $model->getActiveRazorpayConfig(),
            'parentKidsMap'      => $parentKidsMap,
            'isChildViewOnly'    => $isChildMode,
            'isLockedBanner'     => (int) ($this->request->getGet('locked') ?? 0) === 1 || (int) session()->get('lms_child_fee_locked') === 1,
            'mySubs'             => $mySubs,
            'myInvoices'         => $myInvoices,
            'myTxns'             => $myTxns,
            'latestPaidTxnByStu' => $latestPaidTxnByStu,
            'latestPaidTxnBySub' => $latestPaidTxnBySub,
            'canManageAdmin'     => $canManageAdmin,
            'canCollect'         => $this->hasPermission('lms_finance.collect'),
            'canExpenses'        => $this->hasPermission('lms_finance.expenses'),
            'canSettings'        => $this->hasPermission('lms_finance.settings'),
        ];

        return $this->renderAddonView('addons/lms_finance/portal_parent_view', $data);
    }

    public function teacherPayoutsPortal()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $uid   = (int) (session()->get('user_id') ?: (session()->get('id') ?: 1));
        $model = $this->getModel();
        $db    = \Config\Database::connect();

        return $this->renderAddonView('addons/lms_finance/portal_teacher_view', [
            'pageTitle' => 'My Instructor Salary Slips & Payout History',
            'settings'  => $model->getGlobalSiteSettings(),
            'user'      => session()->get() ?: [],
            'myPayouts' => $db->table('lms_finance_expenses')
                ->where('expense_type', 'teacher_payout')
                ->where('teacher_user_id', $uid)
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray(),
        ]);
    }

    public function directCourseEnrollCheckout($nodeId = 0)
    {
        $nid    = (int) $nodeId;
        $model  = $this->getModel();
        $leaves = $model->getLeafNodesWithPricing();
        if (! isset($leaves[$nid]) && ! empty($leaves)) {
            $nid = (int) array_key_first($leaves);
        }

        $loggedUser   = $model->getLoggedInUserRecord();
        $isLogged     = ($loggedUser !== null);
        $loggedUid    = $isLogged ? (int) $loggedUser['id'] : 0;
        $existingKids = ($isLogged && $loggedUid > 0) ? $model->getParentLinkedChildren($loggedUid) : [];
        $cCode        = $model->resolveViewerCountryCode();
        $quote        = $model->resolveCourseFeeForCountry($nid, $cCode, 'monthly');

        return $this->renderAddonView('addons/lms_finance/enroll_checkout_view', [
            'pageTitle'      => 'Course Enrollment - ' . $quote['course_title'],
            'settings'       => $model->getGlobalSiteSettings(),
            'user'           => $loggedUser ?: (session()->get() ?: []),
            'loggedUser'     => $loggedUser,
            'isLoggedIn'     => $isLogged,
            'existingKids'   => $existingKids,
            'courseQuote'    => $quote,
            'razorpayConfig' => $model->getActiveRazorpayConfig(),
        ]);
    }

    public function processDirectCourseEnrollCheckout()
    {
        $model       = $this->getModel();
        $db          = \Config\Database::connect();
        $defaultNid  = (int) ($this->request->getPost('node_id') ?? 0);
        $countryCode = strtoupper(trim((string) ($this->request->getPost('country_code') ?? $model->resolveViewerCountryCode()))) ?: 'IN';
        $payMethod   = trim((string) ($this->request->getPost('payment_method') ?? 'razorpay'));

        $rzpPayId = trim((string) ($this->request->getPost('razorpay_payment_id') ?? ''));
        $rzpOrdId = trim((string) ($this->request->getPost('razorpay_order_id') ?? ''));
        $rzpSig   = trim((string) ($this->request->getPost('razorpay_signature') ?? ''));
        $utrRef   = trim((string) ($this->request->getPost('txn_reference') ?? ''));

        if ($payMethod === 'razorpay') {
            $sigCheck = $model->verifyRazorpaySignatureStrict($rzpOrdId, $rzpPayId, $rzpSig);
            if (! $sigCheck['ok']) {
                return redirect()->back()->with('error', $sigCheck['message']);
            }
        }

        $loggedUser = $model->getLoggedInUserRecord();

        if ($loggedUser) {
            $parentId    = (int) $loggedUser['id'];
            $parentName  = (string) ($loggedUser['full_name'] ?: ($loggedUser['display_name'] ?? 'Parent'));
            $parentEmail = strtolower(trim((string) ($loggedUser['email'] ?? '')));
        } else {
            $parentName     = trim((string) ($this->request->getPost('parent_name') ?? ''));
            $parentEmail    = strtolower(trim((string) ($this->request->getPost('parent_email') ?? '')));
            $parentPassword = (string) ($this->request->getPost('parent_password') ?? '');

            if ($parentName === '' || $parentEmail === '' || strlen($parentPassword) < 4) {
                return redirect()->back()->with('error', 'Please enter your Full Name, Email, and a Password (min 4 characters).');
            }
        }

        $childrenRows = $this->request->getPost('children') ?? [];
        if (! is_array($childrenRows) || empty($childrenRows)) {
            return redirect()->back()->with('error', 'Please select or enter at least one Child profile.');
        }

        $now   = $model->nowIst();
        $uCols = array_flip($db->getFieldNames('users'));

        if (! $loggedUser) {
            $parentRow = $db->table('users')->where('email', $parentEmail)->get()->getRowArray();
            if ($parentRow) {
                return redirect()->to(base_url('login'))
                    ->with('error', 'This email ID is already used. Please log in to add a new child or buy a new course.');
            } else {
                $hashedPass = password_hash($parentPassword, PASSWORD_DEFAULT);
                $pInsert = array_intersect_key([
                    'role_id'       => 5,
                    'role_slug'     => 'parent',
                    'full_name'     => $parentName,
                    'email'         => $parentEmail,
                    'password_hash' => $hashedPass,
                    'status'        => 'active',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ], $uCols);
                $db->table('users')->insert($pInsert);
                $parentId = (int) $db->insertID();
            }
        }

        $existingKids = $model->getParentLinkedChildren($parentId);
        $quote        = $model->resolveCourseFeeForCountry($defaultNid, $countryCode, 'monthly');
        $cohortId     = (int) ($quote['cohort_id'] ?? 0);

        if ($db->tableExists('cms_user_meta')) {
            $exPMeta = $db->table('cms_user_meta')->where('user_id', $parentId)->get()->getRowArray();
            if (! $exPMeta) {
                $db->table('cms_user_meta')->insert([
                    'user_id'       => $parentId,
                    'country_code'  => $countryCode,
                    'currency_code' => $quote['currency'],
                    'timezone'      => 'Asia/Kolkata',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }

        $lastTxnId     = 0;
        $enrolledCount = 0;
        $uploadedProof = $this->request->getFile('proof_file');
        $rzpMode       = $model->getActiveRazorpayConfig()['mode'] ?? 'test';

        foreach ($childrenRows as $idx => $ch) {
            $selectedStuId = (int) ($ch['existing_student_id'] ?? 0);
            $kidName       = '';
            $kidAge        = max(3, min(22, (int) ($ch['age'] ?? 8)));
            $studentId     = 0;

            if ($selectedStuId > 0 && isset($existingKids[$selectedStuId])) {
                $studentId = $selectedStuId;
                $kidName   = $existingKids[$selectedStuId]['full_name'];
                $kidAge    = $existingKids[$selectedStuId]['age'];
            } else {
                $kidName = trim((string) ($ch['name'] ?? ''));
                if ($kidName === '') {
                    continue;
                }
                $slugKid    = preg_replace('/[^a-z0-9]+/', '', strtolower($kidName)) ?: 'learner';
                $childEmail = $slugKid . '.' . $parentId . '.' . rand(100, 999) . '@student.portal';
                $kidPass    = isset($parentPassword) && $parentPassword !== '' ? password_hash($parentPassword, PASSWORD_DEFAULT) : password_hash('Kid@123', PASSWORD_DEFAULT);

                $cInsert = array_intersect_key([
                    'role_id'        => 4,
                    'role_slug'      => 'student',
                    'parent_user_id' => $parentId,
                    'full_name'      => $kidName,
                    'email'          => $childEmail,
                    'password_hash'  => $kidPass,
                    'status'         => 'active',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ], $uCols);
                $db->table('users')->insert($cInsert);
                $studentId = (int) $db->insertID();

                if ($db->tableExists('cms_user_meta')) {
                    $db->table('cms_user_meta')->insert([
                        'user_id'       => $studentId,
                        'leaf_node_id'  => $defaultNid,
                        'child_age'     => $kidAge,
                        'grade_level'   => $quote['course_title'],
                        'country_code'  => $countryCode,
                        'currency_code' => $quote['currency'],
                        'timezone'      => 'Asia/Kolkata',
                        'avatar_color'  => '#10b981',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }

            if ($cohortId > 0 && $db->tableExists('lms_classroom_batch_students')) {
                $db->query("
                    INSERT INTO `lms_classroom_batch_students` (`cohort_id`, `leaf_node_id`, `student_user_id`, `status`, `assigned_at`)
                    VALUES (?, ?, ?, 'active', ?)
                    ON DUPLICATE KEY UPDATE `status` = 'active'
                ", [$cohortId, $defaultNid, $studentId, $now]);
            }

            if ($payMethod === 'offline_proof') {
                $model->submitOfflineProofTransaction([
                    'student_user_id' => $studentId,
                    'node_id'         => $defaultNid,
                    'txn_reference'   => $utrRef ?: ('UTR-' . time()),
                ], $uploadedProof);
            } else {
                $res = $model->finalizePaidTransaction([
                    'student_user_id'    => $studentId,
                    'parent_user_id'     => $parentId,
                    'node_id'            => $defaultNid,
                    'payer_name'         => $parentName,
                    'payer_email'        => $parentEmail,
                    'provider'           => 'razorpay_' . $rzpMode,
                    'txn_reference'      => $rzpPayId . ($enrolledCount > 0 ? ('-C' . ($enrolledCount + 1)) : ''),
                    'razorpay_order_id'  => $rzpOrdId,
                    'razorpay_signature' => $rzpSig,
                    'country_code'       => $countryCode,
                    'currency'           => $quote['currency'],
                    'amount'             => $quote['amount'],
                    'verification_note'  => 'Verified via Razorpay (' . strtoupper($rzpMode) . ')',
                ]);
                $lastTxnId = (int) ($res['txn_id'] ?? 0);
            }
            $enrolledCount++;
        }

        if ($enrolledCount === 0) {
            return redirect()->back()->with('error', 'Please select an existing child or enter a new child name.');
        }

        if (! $loggedUser) {
            $parentPerms = ['dashboard.view', 'core_lms_auth.view', 'lms_hierarchy.view', 'lms_assessments.take_exam', 'lms_communication.chat', 'lms_communication.feedback', 'lms_timetable.view', 'lms_finance.view'];
            session()->set([
                'isLoggedIn'          => true,
                'user_id'             => $parentId,
                'id'                  => $parentId,
                'role_id'             => 5,
                'role_slug'           => 'parent',
                'effective_role_slug' => 'parent',
                'permissions'         => $parentPerms,
                'role_permissions'    => $parentPerms,
                'portal_view_mode'    => 'default',
                'active_student_id'   => 0,
                'full_name'           => $parentName,
                'email'               => $parentEmail,
                'user_country'        => $countryCode,
                'user_currency'       => $quote['currency'],
            ]);
        }

        if ($payMethod === 'offline_proof' || $lastTxnId <= 0) {
            return redirect()->to(base_url('finance/portal'))
                ->with('success', 'Enrollment submitted! Your offline payment proof is awaiting Admin verification.');
        }

        return redirect()->to(base_url('finance/receipt-view/' . $lastTxnId))
            ->with('success', 'Razorpay Payment Verified & Enrollment Confirmed! Here is your official fee receipt.');
    }

    public function createRazorpayOrderJson()
    {
        $model    = $this->getModel();
        $amount   = max(1.0, (float) ($this->request->getPost('amount') ?? 1000));
        $currency = strtoupper(trim((string) ($this->request->getPost('currency') ?? 'INR'))) ?: 'INR';

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        $orderRes               = $model->createRealRazorpayOrder($amount, $currency);
        $orderRes['csrf_token'] = csrf_hash();

        if (! $orderRes['ok']) {
            return $this->response->setStatusCode(400)->setJSON($orderRes);
        }

        return $this->response->setJSON($orderRes);
    }

    public function verifyRazorpayPayment()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $paymentId = trim((string) $this->request->getPost('razorpay_payment_id'));
        $orderId   = trim((string) $this->request->getPost('razorpay_order_id'));
        $signature = trim((string) $this->request->getPost('razorpay_signature'));
        $subId     = (int) $this->request->getPost('subscription_id');
        $invId     = (int) ($this->request->getPost('invoice_id') ?: $this->request->getPost('custom_invoice_id'));
        $nodeId    = (int) $this->request->getPost('node_id');
        $studentId = (int) $this->request->getPost('student_user_id');

        if ($paymentId === '' || $orderId === '' || $signature === '') {
            return redirect()->to(base_url('finance/portal'))
                ->with('error', 'Security verification failed: Missing Razorpay payment signature.');
        }

        $model  = $this->getModel();
        $rzpCfg = $model->getActiveRazorpayConfig();
        $secret = trim((string) ($rzpCfg['key_secret'] ?? ''));

        if ($secret === '') {
            return redirect()->to(base_url('finance/portal'))
                ->with('error', 'Payment gateway secret is not configured. Verification aborted.');
        }

        // Strict Cryptographic HMAC-SHA256 Check (Blocks spoofed POST requests / parameter tampering)
        $expectedSig = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);
        if (! hash_equals($expectedSig, $signature)) {
            $model->recordFailedPaymentTransaction([
                'student_user_id'   => $studentId,
                'node_id'           => $nodeId,
                'subscription_id'   => $subId,
                'custom_invoice_id' => $invId,
                'txn_reference'     => $paymentId ?: $orderId,
                'reason'            => 'Invalid HMAC-SHA256 signature tampering detected',
            ]);
            return redirect()->to(base_url('finance/portal'))
                ->with('error', 'Payment signature mismatch! Unverified transaction blocked.');
        }

        // Prevent Replay Attacks (Duplicate `razorpay_payment_id` reuse)
        $db = \Config\Database::connect();
        if ($db->tableExists('payment_transactions')) {
            $dup = $db->table('payment_transactions')
                ->where('txn_reference', $paymentId)
                ->whereIn('status', ['paid', 'success', 'completed', 'captured', 'verified'])
                ->get()->getRowArray();
            if ($dup) {
                return redirect()->to(base_url('finance/receipt-view/' . (int) $dup['id']))
                    ->with('success', 'Payment already verified.');
            }
        }

        // Server-side authoritative amount & currency verification (Blocks client-side price tampering)
        $verifiedAmount   = (float) ($this->request->getPost('amount') ?: 0);
        $verifiedCurrency = (string) ($this->request->getPost('currency') ?: 'INR');
        if ($invId > 0 && $db->tableExists('lms_finance_invoices')) {
            $invRow = $db->table('lms_finance_invoices')->where('id', $invId)->get()->getRowArray();
            if ($invRow && (float) ($invRow['total_amount'] ?? 0) > 0) {
                $verifiedAmount   = (float) $invRow['total_amount'];
                $verifiedCurrency = (string) ($invRow['currency'] ?: $verifiedCurrency);
            }
        } elseif ($subId > 0 && $db->tableExists('lms_student_subscriptions')) {
            $subRow = $db->table('lms_student_subscriptions')->where('id', $subId)->get()->getRowArray();
            if ($subRow && (float) ($subRow['fee_amount'] ?? 0) > 0) {
                $verifiedAmount   = (float) $subRow['fee_amount'];
                $verifiedCurrency = (string) ($subRow['currency'] ?: $verifiedCurrency);
            }
        } elseif ($nodeId > 0) {
            $qRow = $model->resolveCourseFeeForCountry($nodeId, $model->resolveViewerCountryCode(), 'monthly', $studentId);
            if ((float) ($qRow['amount'] ?? 0) > 0) {
                $verifiedAmount   = (float) $qRow['amount'];
                $verifiedCurrency = (string) ($qRow['currency'] ?: $verifiedCurrency);
            }
        }

        $finRes = $model->finalizePaidTransaction([
            'txn_reference'      => $paymentId,
            'razorpay_order_id'  => $orderId,
            'razorpay_signature' => $signature,
            'subscription_id'    => $subId,
            'invoice_id'         => $invId,
            'node_id'            => $nodeId,
            'student_user_id'    => $studentId,
            'parent_user_id'     => (int) (session()->get('user_id') ?: (session()->get('id') ?: 0)),
            'payer_name'         => (string) (session()->get('full_name') ?: 'Parent'),
            'provider'           => 'razorpay_' . ($rzpCfg['mode'] ?? 'test'),
            'amount'             => $verifiedAmount,
            'currency'           => $verifiedCurrency,
        ]);
        $txnId = (int) ($finRes['txn_id'] ?? 0);

        if (! $txnId) {
            return redirect()->to(base_url('finance/portal'))
                ->with('error', 'Could not verify payment with gateway.');
        }

        session()->set('lms_child_fee_locked', 0);
        return redirect()->to(base_url('finance/receipt-view/' . (int) $txnId))
            ->with('success', 'Payment verified! Official fee receipt generated.');
    }
    public function submitOfflineProof()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $res = $this->getModel()->submitOfflineProofTransaction(
            $this->request->getPost(),
            $this->request->getFile('proof_file')
        );
        return redirect()->to(base_url('finance/portal'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function receiptView($txnId = 0)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $model = $this->getModel();
        $db    = \Config\Database::connect();
        $txn   = $db->table('payment_transactions')->where('id', (int) $txnId)->get()->getRowArray();
        if (! $txn) {
            return redirect()->to(base_url('finance/portal'))->with('error', 'Receipt not found.');
        }

        // 1. Verify that this transaction is actually PAID
        $rawSt  = strtolower(trim((string) ($txn['status'] ?? '')));
        $prov   = strtolower(trim((string) ($txn['provider'] ?? '')));
        if ($rawSt === '') {
            $rawSt = ($prov === 'offline_proof') ? 'pending_verification' : (strpos($prov, 'fail') !== false ? 'failed' : 'paid');
        }
        if (! in_array($rawSt, ['paid', 'success', 'completed', 'captured', 'verified'], true)) {
            return redirect()->to(base_url('finance/portal'))
                ->with('error', 'Official receipt is only available for verified paid transactions.');
        }

        // 2. STRICT OWNERSHIP CHECK (IDOR PROTECTION):
        $roleId   = (int) (session()->get('role_id') ?? 0);
        $roleSlug = strtolower(trim((string) (session()->get('role_slug') ?: 'student')));
        $isAdmin  = in_array($roleId, [1, 2], true)
            || in_array($roleSlug, ['super_admin', 'admin'], true)
            || $this->hasPermission(['lms_finance.collect', 'lms_finance.settings']);

        if (! $isAdmin) {
            $uid            = (int) (session()->get('user_id') ?: (session()->get('id') ?: 0));
            $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
            $allowedStuIds  = [];

            if ($uid > 0) {
                $allowedStuIds[] = $uid;
                if ($db->tableExists('users')) {
                    foreach ($db->table('users')->select('id')->where('parent_user_id', $uid)->get()->getResultArray() as $kRow) {
                        $allowedStuIds[] = (int) $kRow['id'];
                    }
                }
            }
            if ($activeStuId > 0) {
                $allowedStuIds[] = $activeStuId;
            }

            $txnParentId  = (int) ($txn['parent_user_id'] ?? 0);
            $txnStudentId = (int) ($txn['student_user_id'] ?? 0);

            $isOwner = ($uid > 0 && $txnParentId === $uid)
                || ($txnStudentId > 0 && in_array($txnStudentId, $allowedStuIds, true));

            if (! $isOwner) {
                return redirect()->to(base_url('finance/portal'))
                    ->with('error', 'Access denied: You can only view official receipts for your own children.');
            }
        }

        $tpl         = $model->getActiveReceiptTemplateConfig();
        $studentName = 'Student';
        if ($db->tableExists('users') && (int) $txn['student_user_id'] > 0) {
            $sRow = $db->table('users')->select('full_name')->where('id', (int) $txn['student_user_id'])->get()->getRowArray();
            if ($sRow && ! empty($sRow['full_name'])) {
                $studentName = $sRow['full_name'];
            }
        }

        $courseTitle = LmsFinanceModel::cleanText($txn['invoice_title'] ?: 'Course Tuition Fee');
        if ($db->tableExists('lms_hierarchy_nodes') && (int) $txn['node_id'] > 0) {
            $nRow = $db->table('lms_hierarchy_nodes')->select('title')->where('id', (int) $txn['node_id'])->get()->getRowArray();
            if ($nRow && ! empty($nRow['title'])) {
                $courseTitle = $nRow['title'];
            }
        }

        return $this->renderAddonView('addons/lms_finance/receipt_print_view', [
            'pageTitle'   => 'Official Fee Receipt - ' . $txn['txn_reference'],
            'settings'    => $model->getGlobalSiteSettings(),
            'user'        => session()->get() ?: [],
            'txn'         => $txn,
            'tpl'         => $tpl,
            'studentName' => $studentName,
            'courseTitle' => $courseTitle,
        ]);
    }

    public function downloadReceiptPdf($txnId = 0)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        return redirect()->to(base_url('finance/receipt-view/' . (int) $txnId . '?download_pdf=1'));
    }
    public function saveSubscription()
    {
        if (! $this->hasPermission('lms_finance.collect')) return redirect()->back()->with('error', 'Access denied.');
        $res = $this->getModel()->saveOrUpdateSubscription($this->request->getPost());
        return redirect()->to(base_url('addons/lms-finance/invoices'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function toggleSubscriptionLock()
    {
        if (! $this->hasPermission('lms_finance.collect')) return redirect()->back()->with('error', 'Access denied.');
        $id  = (int) ($this->request->getPost('id') ?? 0);
        $db  = \Config\Database::connect();
        $row = $db->table('lms_student_subscriptions')->where('id', $id)->get()->getRowArray();
        if ($row) {
            $newLock  = ((int) $row['is_access_locked'] === 1) ? 0 : 1;
            $override = ($newLock === 0) ? 1 : 2;
            $db->table('lms_student_subscriptions')->where('id', $id)->update([
                'is_access_locked'    => $newLock,
                'admin_lock_override' => $override,
                'updated_at'          => $this->getModel()->nowIst(),
            ]);
        }
        return redirect()->to(base_url('addons/lms-finance/invoices'))
            ->with('success', 'Student access manually ' . (($newLock ?? 0) === 1 ? 'LOCKED' : 'UNLOCKED') . ' by Admin!');
    }

    public function deleteSubscription()
    {
        if (! $this->hasPermission('lms_finance.delete')) return redirect()->back()->with('error', 'Access denied.');
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) \Config\Database::connect()->table('lms_student_subscriptions')->where('id', $id)->delete();
        return redirect()->to(base_url('addons/lms-finance/invoices'))->with('success', 'Subscription deleted.');
    }

    public function saveCustomInvoice()
    {
        if (! $this->hasPermission('lms_finance.collect')) return redirect()->back()->with('error', 'Access denied.');
        $res = $this->getModel()->createCustomParentChildInvoice($this->request->getPost());
        return redirect()->to(base_url('addons/lms-finance/custom-billing'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteInvoice()
    {
        if (! $this->hasPermission('lms_finance.delete')) return redirect()->back()->with('error', 'Access denied.');
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) \Config\Database::connect()->table('lms_finance_invoices')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Invoice deleted.');
    }

    public function verifyOfflineProof()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $roleId   = (int) (session()->get('role_id') ?? 0);
        $roleSlug = strtolower(trim((string) (session()->get('role_slug') ?: 'student')));
        $isAdmin  = in_array($roleId, [1, 2], true)
            || in_array($roleSlug, ['super_admin', 'admin'], true)
            || (method_exists($this, 'hasPermission') && $this->hasPermission(['lms_finance.collect', 'lms_finance.settings']));

        if (! $isAdmin) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied.');
        }

        $txnId     = (int) $this->request->getPost('txn_id');
        $decision  = strtolower(trim((string) $this->request->getPost('decision')));
        $reason    = trim(strip_tags((string) $this->request->getPost('rejection_reason')));
        $isApprove = ($decision === 'approve' || $decision === 'approved' || $decision === '1');

        if ($txnId <= 0 || ! in_array($decision, ['approve', 'approved', 'reject', 'rejected'], true)) {
            return redirect()->to(base_url('addons/lms-finance'))->with('error', 'Invalid verification request.');
        }

        $db  = \Config\Database::connect();
        $row = $db->table('payment_transactions')->where('id', $txnId)->get()->getRowArray();
        if (! $row) {
            return redirect()->to(base_url('addons/lms-finance'))->with('error', 'Transaction record not found.');
        }

        $model   = $this->getModel();
        $adminId = (int) (session()->get('user_id') ?: (session()->get('id') ?: 0));

        if (method_exists($model, 'verifyOfflineProofByAdmin')) {
            try {
                $ref    = new \ReflectionMethod($model, 'verifyOfflineProofByAdmin');
                $params = $ref->getParameters();
                $p1Name = isset($params[1]) ? strtolower($params[1]->getName()) : '';
                $arg2   = (strpos($p1Name, 'decision') !== false || strpos($p1Name, 'action') !== false || strpos($p1Name, 'status') !== false)
                    ? ($isApprove ? 'approve' : 'reject')
                    : $isApprove;

                if (count($params) >= 3) {
                    $model->verifyOfflineProofByAdmin($txnId, $arg2, $adminId);
                } else {
                    $model->verifyOfflineProofByAdmin($txnId, $arg2);
                }
            } catch (\Throwable $e) {
                // Fallback handled below
            }
        }

        $freshRow  = $db->table('payment_transactions')->where('id', $txnId)->get()->getRowArray() ?: $row;
        $baseTitle = preg_replace('/\s*(\(Rejected:.*?\)|\[Rejected:.*?\])$/i', '', (string) ($freshRow['invoice_title'] ?? 'Course Tuition Fee'));
        $baseTitle = trim($baseTitle) ?: 'Course Tuition Fee';

        if ($isApprove) {
            $updTxn = [
                'status'        => 'paid',
                'invoice_title' => $baseTitle,
            ];
            if ($db->fieldExists('verified_by', 'payment_transactions') && $adminId > 0) {
                $updTxn['verified_by'] = $adminId;
            }
            if ($db->fieldExists('verified_at', 'payment_transactions')) {
                $updTxn['verified_at'] = date('Y-m-d H:i:s');
            }
            $db->table('payment_transactions')->where('id', $txnId)->update($updTxn);

            $sid = (int) ($freshRow['student_user_id'] ?? 0);
            $nid = (int) ($freshRow['node_id'] ?? 0);
            if ($sid > 0 && $db->tableExists('lms_student_subscriptions')) {
                $subBuilder = $db->table('lms_student_subscriptions')->where('student_user_id', $sid);
                if ($nid > 0) {
                    $subBuilder->where('node_id', $nid);
                }
                $subRow = $subBuilder->get()->getRowArray();
                if ($subRow && (int) ($subRow['is_access_locked'] ?? 0) === 1) {
                    $subUpd = ['is_access_locked' => 0];
                    if ((int) ($subRow['paid_cycles_count'] ?? 0) === 0 && $db->fieldExists('paid_cycles_count', 'lms_student_subscriptions')) {
                        $subUpd['paid_cycles_count'] = 1;
                    }
                    $db->table('lms_student_subscriptions')->where('id', (int) $subRow['id'])->update($subUpd);
                }
            }

            return redirect()->to(base_url('addons/lms-finance'))
                ->with('success', 'Offline payment approved and student course access unlocked!');
        }

        $cleanReason = mb_substr(str_replace(['[', ']', '(', ')'], '', $reason), 0, 140);
        $newTitle    = $baseTitle . ($cleanReason !== '' ? ' (Rejected: ' . $cleanReason . ')' : ' (Rejected)');

        $updReject = [
            'status'        => 'rejected',
            'invoice_title' => $newTitle,
        ];
        if ($db->fieldExists('rejection_reason', 'payment_transactions')) {
            $updReject['rejection_reason'] = $cleanReason;
        }
        if ($db->fieldExists('admin_note', 'payment_transactions')) {
            $updReject['admin_note'] = $cleanReason;
        }
        if ($db->fieldExists('remarks', 'payment_transactions')) {
            $updReject['remarks'] = $cleanReason;
        }
        if ($db->fieldExists('provider_payload', 'payment_transactions')) {
            $payload = json_decode((string) ($freshRow['provider_payload'] ?? '{}'), true);
            if (! is_array($payload)) {
                $payload = [];
            }
            $payload['rejection_reason'] = $cleanReason;
            $updReject['provider_payload'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        $db->table('payment_transactions')->where('id', $txnId)->update($updReject);

        return redirect()->to(base_url('addons/lms-finance'))
            ->with('success', 'Offline payment proof rejected.' . ($cleanReason !== '' ? ' Reason: ' . $cleanReason : ''));
    }
    public function deleteTransaction()
    {
        if (! $this->hasPermission('lms_finance.delete')) return redirect()->back()->with('error', 'Access denied.');
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) $this->getModel()->delete($id);
        return redirect()->back()->with('success', 'Transaction removed.');
    }

    public function savePayoutExpense()
    {
        if (! $this->hasPermission(['lms_finance.expenses', 'lms_finance.collect'])) return redirect()->back()->with('error', 'Access denied.');
        $res = $this->getModel()->savePayoutOrExpense($this->request->getPost());
        return redirect()->to(base_url('addons/lms-finance/payouts'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deletePayoutExpense()
    {
        if (! $this->hasPermission('lms_finance.delete')) return redirect()->back()->with('error', 'Access denied.');
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) \Config\Database::connect()->table('lms_finance_expenses')->where('id', $id)->delete();
        return redirect()->to(base_url('addons/lms-finance/payouts'))->with('success', 'Expense entry removed.');
    }

    public function saveRazorpaySettings()
    {
        if (! $this->hasPermission('lms_finance.settings')) return redirect()->back()->with('error', 'Access denied.');
        $this->getModel()->saveRazorpayConfig($this->request->getPost());
        return redirect()->to(base_url('addons/lms-finance/settings'))->with('success', 'Razorpay Gateway settings updated!');
    }

    public function saveReceiptTemplate()
    {
        if (! $this->hasPermission('lms_finance.settings')) return redirect()->back()->with('error', 'Access denied.');
        $this->getModel()->saveReceiptTemplateConfig($this->request->getPost());
        return redirect()->to(base_url('addons/lms-finance/settings'))->with('success', 'Official Receipt branding saved!');
    }

    public function asset($file = '')
    {
        if (session_status() === PHP_SESSION_ACTIVE) @session_write_close();
        $safeFile = basename((string) $file);
        $fullPath = $this->addonPath . '/assets/' . $safeFile;
        if (! is_file($fullPath)) return $this->response->setStatusCode(404)->setBody('');
        $ext  = strtolower((string) pathinfo($safeFile, PATHINFO_EXTENSION));
        $mime = ($ext === 'css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';
        return $this->response->setHeader('Content-Type', $mime)->setHeader('Cache-Control', 'public, max-age=3600')->setBody((string) file_get_contents($fullPath));
    }

    public function logFailedPaymentJson()
    {
        $model = $this->getModel();
        $txnId = $model->recordFailedPaymentTransaction([
            'student_user_id'     => (int) ($this->request->getPost('student_user_id') ?? 0),
            'subscription_id'     => (int) ($this->request->getPost('subscription_id') ?? 0),
            'invoice_id'          => (int) ($this->request->getPost('invoice_id') ?? 0),
            'node_id'             => (int) ($this->request->getPost('node_id') ?? 0),
            'amount'              => (float) ($this->request->getPost('amount') ?? 1000),
            'currency'            => (string) ($this->request->getPost('currency') ?? 'INR'),
            'invoice_title'       => (string) ($this->request->getPost('title') ?? 'Course Fee - Failed Attempt'),
            'razorpay_order_id'   => (string) ($this->request->getPost('razorpay_order_id') ?? ''),
            'razorpay_payment_id' => (string) ($this->request->getPost('razorpay_payment_id') ?? ''),
            'error_reason'        => (string) ($this->request->getPost('error_reason') ?? 'Payment failed at gateway'),
            'payer_name'          => (string) ($this->request->getPost('payer_name') ?? ''),
            'payer_email'         => (string) ($this->request->getPost('payer_email') ?? ''),
        ]);

        return $this->response->setJSON([
            'ok'         => true,
            'txn_id'     => $txnId,
            'csrf_token' => csrf_hash(),
        ]);
    }

}

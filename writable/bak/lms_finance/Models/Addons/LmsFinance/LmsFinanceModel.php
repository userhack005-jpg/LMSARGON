<?php

namespace App\Models\Addons\LmsFinance;

use CodeIgniter\Model;
use Throwable;

class LmsFinanceModel extends Model
{
    protected $table            = 'payment_transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'subscription_id', 'invoice_id', 'student_user_id', 'parent_user_id', 'node_id',
        'payer_name', 'payer_email', 'invoice_title', 'billing_cycle',
        'batch_start_date', 'batch_end_date', 'next_due_date',
        'provider', 'txn_reference', 'razorpay_order_id', 'razorpay_signature',
        'proof_file_url', 'country_code', 'currency', 'subtotal_amount',
        'tax_percent', 'tax_amount', 'amount', 'amount_inr_equiv',
        'status', 'verification_note', 'created_at', 'updated_at',
    ];

    protected string $subsTable     = 'lms_student_subscriptions';
    protected string $invoicesTable = 'lms_finance_invoices';
    protected string $expensesTable = 'lms_finance_expenses';
    protected static ?array $globalSettingsCache = null;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchemaInitialized(): void
    {
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        try {
            if (! $this->db->tableExists('lms_student_subscriptions')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `lms_student_subscriptions` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `student_user_id` INT UNSIGNED NOT NULL,
                        `parent_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `billing_cycle` VARCHAR(30) NOT NULL DEFAULT 'Monthly',
                        `country_code` VARCHAR(10) NOT NULL DEFAULT 'IN',
                        `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
                        `fee_amount` DECIMAL(12,2) NOT NULL DEFAULT 1000.00,
                        `batch_start_date` DATE NULL,
                        `batch_end_date` DATE NULL,
                        `due_date` DATE NOT NULL,
                        `grace_period_days` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                        `is_access_locked` TINYINT(1) NOT NULL DEFAULT 0,
                        `status` VARCHAR(25) NOT NULL DEFAULT 'active',
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        KEY `idx_stu_status` (`student_user_id`, `status`),
                        KEY `idx_parent_status` (`parent_user_id`, `status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }

            if (! $this->db->tableExists('lms_finance_invoices')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `lms_finance_invoices` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `invoice_no` VARCHAR(60) NOT NULL,
                        `invoice_type` VARCHAR(30) NOT NULL DEFAULT 'custom_request',
                        `subscription_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `parent_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `student_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `title` VARCHAR(190) NOT NULL,
                        `remarks` VARCHAR(255) NOT NULL DEFAULT '',
                        `country_code` VARCHAR(10) NOT NULL DEFAULT 'IN',
                        `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
                        `subtotal_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                        `tax_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                        `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                        `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                        `billing_cycle` VARCHAR(30) NOT NULL DEFAULT 'One-Time',
                        `cycle_month_label` VARCHAR(40) NOT NULL DEFAULT '',
                        `due_date` DATE NULL,
                        `status` VARCHAR(30) NOT NULL DEFAULT 'unpaid',
                        `paid_txn_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }

            if (! $this->db->tableExists('payment_transactions')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `payment_transactions` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `subscription_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `invoice_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `student_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `parent_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `payer_name` VARCHAR(150) NOT NULL DEFAULT '',
                        `payer_email` VARCHAR(190) NOT NULL DEFAULT '',
                        `invoice_title` VARCHAR(190) NOT NULL DEFAULT 'Course Tuition Fee',
                        `billing_cycle` VARCHAR(30) NOT NULL DEFAULT 'Monthly',
                        `batch_start_date` DATE NULL,
                        `batch_end_date` DATE NULL,
                        `next_due_date` DATE NULL,
                        `provider` VARCHAR(40) NOT NULL DEFAULT 'razorpay',
                        `txn_reference` VARCHAR(120) NOT NULL,
                        `razorpay_order_id` VARCHAR(120) NOT NULL DEFAULT '',
                        `razorpay_signature` VARCHAR(255) NOT NULL DEFAULT '',
                        `proof_file_url` VARCHAR(255) NOT NULL DEFAULT '',
                        `country_code` VARCHAR(10) NOT NULL DEFAULT 'IN',
                        `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
                        `subtotal_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                        `tax_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                        `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                        `amount_inr_equiv` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                        `status` VARCHAR(30) NOT NULL DEFAULT 'paid',
                        `verification_note` VARCHAR(255) NOT NULL DEFAULT '',
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }

            if (! $this->db->tableExists('lms_finance_expenses')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `lms_finance_expenses` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `expense_type` VARCHAR(40) NOT NULL DEFAULT 'teacher_payout',
                        `teacher_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `title` VARCHAR(190) NOT NULL,
                        `month_year` VARCHAR(30) NOT NULL DEFAULT '',
                        `classes_taught` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                        `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
                        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                        `payment_mode` VARCHAR(40) NOT NULL DEFAULT 'Bank Transfer / UPI',
                        `reference_no` VARCHAR(90) NOT NULL DEFAULT '',
                        `status` VARCHAR(25) NOT NULL DEFAULT 'paid',
                        `notes` VARCHAR(255) NOT NULL DEFAULT '',
                        `created_at` DATETIME NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }

            $this->syncBatchAssignmentsAndEvaluateLocks();
            $this->mirrorAssets(false);
        } catch (Throwable $e) {
            log_message('error', 'LmsFinanceModel schema error: ' . $e->getMessage());
        }
    }

    /**
     * Resolves exact `start_date` and `end_date` for a student's assigned batch from `lms_classroom_batches`
     */
    public function resolveBatchDatesForStudentNode(int $studentId, int $nodeId): array
    {
        $startDate = date('Y-m-01');
        $endDate   = date('Y-m-t', strtotime($startDate . ' +3 months'));
        $cohortId  = 0;

        if ($this->db->tableExists('lms_classroom_batches')) {
            $batchRow = null;
            if ($studentId > 0 && $this->db->tableExists('lms_classroom_batch_students')) {
                $qb = $this->db->table('lms_classroom_batch_students bs')
                    ->select('b.id, b.leaf_node_id, b.start_date, b.end_date')
                    ->join('lms_classroom_batches b', 'b.id = bs.cohort_id', 'inner')
                    ->where('bs.student_user_id', $studentId);
                if ($nodeId > 0) {
                    $qb->where('bs.leaf_node_id', $nodeId);
                }
                $batchRow = $qb->orderBy('bs.id', 'DESC')->get()->getRowArray();
            }
            if (! $batchRow && $nodeId > 0) {
                $batchRow = $this->db->table('lms_classroom_batches')
                    ->where('leaf_node_id', $nodeId)
                    ->where('status', 'active')
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getRowArray();
            }
            if ($batchRow) {
                $cohortId = (int) ($batchRow['id'] ?? 0);
                if (! empty($batchRow['start_date']) && $batchRow['start_date'] !== '0000-00-00') {
                    $startDate = (string) $batchRow['start_date'];
                }
                if (! empty($batchRow['end_date']) && $batchRow['end_date'] !== '0000-00-00') {
                    $endDate = (string) $batchRow['end_date'];
                } else {
                    $endDate = date('Y-m-t', strtotime($startDate . ' +3 months'));
                }
            }
        }

        return [
            'cohort_id'        => $cohortId,
            'batch_start_date' => $startDate,
            'batch_end_date'   => $endDate,
        ];
    }

    /**
     * Auto-syncs students assigned to batches (`lms_classroom_batch_students` & `lms_hierarchy_enrollments`)
     * into `lms_student_subscriptions` and enforces the 1st-of-the-Month payment lock!
     */
    public function syncBatchAssignmentsAndEvaluateLocks(): void
    {
        try {
            if (! $this->db->tableExists($this->subsTable) || ! $this->db->tableExists('users')) {
                return;
            }

            $usersMap = [];
            foreach ($this->db->table('users')->select('id, parent_user_id, status')->get()->getResultArray() as $u) {
                $usersMap[(int) $u['id']] = $u;
            }

            // Ensure every student in `lms_classroom_batch_students` has a subscription record
            if ($this->db->tableExists('lms_classroom_batch_students') && $this->db->tableExists('lms_classroom_batches')) {
                $batchStudents = $this->db->table('lms_classroom_batch_students bs')
                    ->select('bs.student_user_id, bs.leaf_node_id, b.start_date, b.end_date')
                    ->join('lms_classroom_batches b', 'b.id = bs.cohort_id', 'inner')
                    ->where('bs.status', 'active')
                    ->get()
                    ->getResultArray();

                foreach ($batchStudents as $bs) {
                    $sid = (int) ($bs['student_user_id'] ?? 0);
                    $nid = (int) ($bs['leaf_node_id'] ?? 0);
                    if ($sid <= 0 || ! isset($usersMap[$sid])) {
                        continue;
                    }

                    $exSub = $this->db->table($this->subsTable)
                        ->where('student_user_id', $sid)
                        ->where('node_id', $nid)
                        ->get()
                        ->getRowArray();

                    $bStart = ! empty($bs['start_date']) && $bs['start_date'] !== '0000-00-00' ? $bs['start_date'] : date('Y-m-01');
                    $bEnd   = ! empty($bs['end_date']) && $bs['end_date'] !== '0000-00-00' ? $bs['end_date'] : date('Y-m-t', strtotime($bStart . ' +3 months'));

                    if (! $exSub) {
                        $pid     = (int) ($usersMap[$sid]['parent_user_id'] ?? 0);
                        $quote   = $this->resolveCourseFeeForCountry($nid, 'IN', 'monthly', $sid);
                        $paidCnt = (int) $this->db->table($this->table)
                            ->where('student_user_id', $sid)
                            ->where('node_id', $nid)
                            ->where('status', 'paid')
                            ->countAllResults();

                        $firstMonthFirstDay = date('Y-m-01', strtotime($bStart));
                        if ($firstMonthFirstDay < date('Y-m-01')) {
                            $firstMonthFirstDay = date('Y-m-01');
                        }
                        $initDue = $paidCnt > 0
                            ? date('Y-m-01', strtotime($firstMonthFirstDay . ' +' . $paidCnt . ' months'))
                            : date('Y-m-01');

                        $now = gmdate('Y-m-d H:i:s');
                        $this->db->table($this->subsTable)->insert([
                            'student_user_id'   => $sid,
                            'parent_user_id'    => $pid,
                            'node_id'           => $nid,
                            'billing_cycle'     => 'Monthly',
                            'country_code'      => $quote['country_code'],
                            'currency'          => $quote['currency'],
                            'fee_amount'        => $quote['amount'],
                            'batch_start_date'  => $bStart,
                            'batch_end_date'    => $bEnd,
                            'due_date'          => $initDue,
                            'grace_period_days' => 0,
                            'is_access_locked'  => ($paidCnt === 0) ? 1 : 0,
                            'status'            => 'active',
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ]);
                    } else {
                        // Sync batch start & end date from batch if missing or updated
                        $updDates = [];
                        if (empty($exSub['batch_start_date']) || $exSub['batch_start_date'] === '0000-00-00') {
                            $updDates['batch_start_date'] = $bStart;
                        }
                        if (empty($exSub['batch_end_date']) || $exSub['batch_end_date'] === '0000-00-00') {
                            $updDates['batch_end_date'] = $bEnd;
                        }
                        if (! empty($updDates)) {
                            $this->db->table($this->subsTable)->where('id', $exSub['id'])->update($updDates);
                        }
                    }
                }
            }

            // Evaluate 1st-of-the-Month Locks across all active subscriptions
            $todayYmd         = date('Y-m-d');
            $currentMonth1st  = date('Y-m-01');
            $subs             = $this->db->table($this->subsTable)->where('status', 'active')->get()->getResultArray();

            foreach ($subs as $s) {
                $subId    = (int) $s['id'];
                $stuId    = (int) $s['student_user_id'];
                $nodeId   = (int) $s['node_id'];
                $dueYmd   = (string) ($s['due_date'] ?? '');
                $bEndYmd  = (string) ($s['batch_end_date'] ?? '');

                // If batch has already ended and due_date is past batch_end_date, mark completed & unlocked
                if ($bEndYmd !== '' && $bEndYmd !== '0000-00-00' && $dueYmd > $bEndYmd) {
                    $this->db->table($this->subsTable)->where('id', $subId)->update([
                        'is_access_locked' => 0,
                        'status'           => 'completed',
                    ]);
                    continue;
                }

                // Check if due_date is <= today (meaning the 1st of the unpaid month has arrived!)
                $shouldLock = 0;
                if ($dueYmd === '' || $dueYmd === '0000-00-00' || $todayYmd >= $dueYmd) {
                    $shouldLock = 1;
                }

                if ((int) ($s['is_access_locked'] ?? 0) !== $shouldLock) {
                    $this->db->table($this->subsTable)->where('id', $subId)->update([
                        'is_access_locked' => $shouldLock,
                    ]);
                }
            }
        } catch (Throwable $e) {
        }
    }

    /**
     * GLOBAL STRICT MONTHLY FEE GATE:
     * - If Student (or Parent in Student View Mode) has an unpaid current month (`is_access_locked = 1` or `due_date <= today`):
     *   - Blocks ALL URLs except `/dashboard`, `/logout`, `/login`, and profile switch back to Parent.
     *   - Injects a strict DOM/JS lock on `/dashboard` so NO join links, popups, practice arenas, or demos can be opened.
     * - If Parent (in Parent View Mode) has an unpaid child subscription:
     *   - Blocks direct URL access to `/timetable/*`, `/abacus/*`, `/assessments/*`, `/demo/*`, `/courses/*` and redirects to `/finance/portal?locked=1`.
     */
    public function enforceStrictMonthlyFeeGate(): void
    {
        try {
            $this->syncBatchAssignmentsAndEvaluateLocks();

            $uid            = (int) (session()->get('user_id') ?: (session()->get('id') ?: 0));
            $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
            $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');
            $effectiveRole  = function_exists('cms_get_effective_role')
                ? cms_get_effective_role()
                : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

            $isStudentContext = ($effectiveRole === 'student') || ($portalViewMode === 'student' && $activeStuId > 0);
            $targetStudentIds = [];

            if ($isStudentContext) {
                $targetStudentIds = [$activeStuId > 0 ? $activeStuId : $uid];
            } elseif ($effectiveRole === 'parent' && $uid > 0 && $this->db->tableExists('users')) {
                foreach ($this->db->table('users')->select('id')->where('parent_user_id', $uid)->get()->getResultArray() as $k) {
                    $targetStudentIds[] = (int) $k['id'];
                }
            }

            if (empty($targetStudentIds) || ! $this->db->tableExists($this->subsTable)) {
                session()->set('lms_child_fee_locked', 0);
                return;
            }

            $todayYmd   = date('Y-m-d');
            $lockedSubs = $this->db->table($this->subsTable)
                ->whereIn('student_user_id', $targetStudentIds)
                ->where('status', 'active')
                ->groupStart()
                    ->where('is_access_locked', 1)
                    ->orWhere('due_date <=', $todayYmd)
                ->groupEnd()
                ->get()
                ->getResultArray();

            $isLocked = ! empty($lockedSubs);
            session()->set('lms_child_fee_locked', $isLocked ? 1 : 0);

            if (! $isLocked) {
                return;
            }

            $uriPath = strtolower(trim((string) service('uri')->getPath(), '/'));

            // Always allow Auth/Logout & Parent-Child Switcher endpoints
            $alwaysAllowedPrefixes = [
                'logout', 'login', 'auth', 'switch-profile', 'portal/switch',
                'addons/core-lms-auth', 'addons/lms-finance/assets', 'assets',
            ];
            foreach ($alwaysAllowedPrefixes as $p) {
                if ($uriPath === $p || strpos($uriPath, $p . '/') === 0) {
                    return;
                }
            }

            if ($isStudentContext) {
                // STUDENT CONTEXT: ONLY `/dashboard` is allowed! Everything else redirects to `/dashboard`!
                if ($uriPath !== 'dashboard' && $uriPath !== '') {
                    header('Location: ' . base_url('dashboard?fee_locked=1'));
                    exit;
                }
            } else {
                // PARENT CONTEXT: Allow `/dashboard` and `/finance/*`, block direct URLs to classes, practice, exams, demo, courses!
                $parentAllowed = ['dashboard', 'finance'];
                $allowedForParent = false;
                foreach ($parentAllowed as $pa) {
                    if ($uriPath === $pa || strpos($uriPath, $pa . '/') === 0) {
                        $allowedForParent = true;
                        break;
                    }
                }
                if (! $allowedForParent) {
                    header('Location: ' . base_url('finance/portal?locked=1'));
                    exit;
                }
            }
        } catch (Throwable $e) {
        }
    }

    public function mirrorAssets(bool $forceTouch = false): void
    {
        $srcDir = WRITEPATH . 'addons/extracted/lms_finance/assets';
        $dstDir = FCPATH . 'assets/addons/lms_finance';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['lms_finance.css', 'lms_finance.js'] as $f) {
                $sFile = $srcDir . '/' . $f;
                $dFile = $dstDir . '/' . $f;
                if (is_file($sFile) && ($forceTouch || ! is_file($dFile) || md5_file($sFile) !== md5_file($dFile))) {
                    @copy($sFile, $dFile);
                    @touch($dFile, time());
                }
            }
        }
    }

    public function getGlobalSiteSettings(): array
    {
        if (self::$globalSettingsCache !== null) {
            return self::$globalSettingsCache;
        }
        self::$globalSettingsCache = ['site_title' => 'Academy Portal', 'active_theme_mode' => 'dark', 'system_environment_mode' => 'testing'];
        try {
            if ($this->db->tableExists('settings')) {
                foreach ($this->db->table('settings')->get()->getResultArray() as $r) {
                    self::$globalSettingsCache[$r['setting_key']] = $r['setting_value'];
                }
            }
        } catch (Throwable $e) {
        }
        return self::$globalSettingsCache;
    }

    public function getEnabledCountriesOnly(): array
    {
        $authCache = WRITEPATH . 'addons/core_lms_auth_cache.json';
        if (is_file($authCache)) {
            $c = json_decode((string) @file_get_contents($authCache), true);
            if (! empty($c['enabled_countries']) && is_array($c['enabled_countries'])) {
                return $c['enabled_countries'];
            }
        }
        return [
            'IN' => ['code' => 'IN', 'name' => 'India',          'flag' => 'ðŸ‡®ðŸ‡³', 'currency_code' => 'INR', 'default_tz' => 'Asia/Kolkata'],
            'AE' => ['code' => 'AE', 'name' => 'UAE',            'flag' => 'ðŸ‡¦ðŸ‡ª', 'currency_code' => 'AED', 'default_tz' => 'Asia/Dubai'],
            'US' => ['code' => 'US', 'name' => 'United States',  'flag' => 'ðŸ‡ºðŸ‡¸', 'currency_code' => 'USD', 'default_tz' => 'America/New_York'],
            'GB' => ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => 'ðŸ‡¬ðŸ‡§', 'currency_code' => 'GBP', 'default_tz' => 'Europe/London'],
        ];
    }

    public function resolveViewerCountryCode(): string
    {
        $cCode = strtoupper(trim((string) (session()->get('user_country') ?: 'IN')));
        $uid   = (int) (session()->get('user_id') ?: (session()->get('id') ?: 0));
        if ($uid > 0 && $this->db->tableExists('cms_user_meta')) {
            $meta = $this->db->table('cms_user_meta')->select('country_code')->where('user_id', $uid)->get()->getRowArray();
            if (! empty($meta['country_code'])) {
                $cCode = strtoupper(trim((string) $meta['country_code']));
            }
        }
        return $cCode ?: 'IN';
    }

    /**
     * Strictly uses `full_name` from `users` table (NEVER `name`)
     */
    public function getLoggedInUserRecord(): ?array
    {
        $sessUser = is_array(session()->get('user')) ? session()->get('user') : [];
        $uid      = (int) (session()->get('user_id') ?: (session()->get('id') ?: ($sessUser['id'] ?? ($sessUser['user_id'] ?? 0))));
        if ($uid <= 0 && ! session()->get('isLoggedIn')) {
            return null;
        }
        if ($uid > 0 && $this->db->tableExists('users')) {
            $uRow = $this->db->table('users')->where('id', $uid)->get()->getRowArray();
            if ($uRow) {
                $uRow['display_name'] = $uRow['full_name'] ?: ($uRow['email'] ?: 'Parent');
                return $uRow;
            }
        }
        return null;
    }

    public function getParentLinkedChildren(int $parentId): array
    {
        if ($parentId <= 0 || ! $this->db->tableExists('users')) {
            return [];
        }
        $metaAge = [];
        if ($this->db->tableExists('cms_user_meta')) {
            foreach ($this->db->table('cms_user_meta')->get()->getResultArray() as $m) {
                $metaAge[(int) $m['user_id']] = (int) ($m['child_age'] ?? 8);
            }
        }

        $kids = [];
        foreach ($this->db->table('users')->where('parent_user_id', $parentId)->where('status', 'active')->orderBy('id', 'ASC')->get()->getResultArray() as $k) {
            $kidId = (int) $k['id'];
            $kids[$kidId] = [
                'id'        => $kidId,
                'full_name' => $k['full_name'] ?: ($k['email'] ?: 'Child'),
                'age'       => $metaAge[$kidId] ?? 8,
            ];
        }
        return $kids;
    }

    public function getActiveRazorpayConfig(): array
    {
        $s   = $this->getGlobalSiteSettings();
        $raw = $s['lms_finance__razorpay_json'] ?? '';
        $cfg = $raw !== '' ? (json_decode((string) $raw, true) ?: []) : [];

        $rawMode = strtolower(trim((string) ($cfg['mode'] ?? 'test')));
        $mode    = ($rawMode === 'live') ? 'live' : 'test';

        $testKeyId     = trim((string) ($cfg['test_key_id'] ?? ($cfg['sandbox_key_id'] ?? '')));
        $testKeySecret = trim((string) ($cfg['test_key_secret'] ?? ($cfg['sandbox_key_secret'] ?? '')));
        $liveKeyId     = trim((string) ($cfg['live_key_id'] ?? ''));
        $liveKeySecret = trim((string) ($cfg['live_key_secret'] ?? ''));
        $webhookSecret = trim((string) ($cfg['webhook_secret'] ?? ''));
        $isEnabled     = isset($cfg['is_enabled']) ? (int) $cfg['is_enabled'] : 1;

        return [
            'is_enabled'      => $isEnabled,
            'mode'            => $mode,
            'key_id'          => ($mode === 'live') ? $liveKeyId : $testKeyId,
            'key_secret'      => ($mode === 'live') ? $liveKeySecret : $testKeySecret,
            'test_key_id'     => $testKeyId,
            'test_key_secret' => $testKeySecret,
            'live_key_id'     => $liveKeyId,
            'live_key_secret' => $liveKeySecret,
            'webhook_secret'  => $webhookSecret,
        ];
    }

    public function saveRazorpayConfig(array $post): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }
        $rawMode = strtolower(trim((string) ($post['mode'] ?? 'test')));
        $mode    = ($rawMode === 'live') ? 'live' : 'test';

        $cfg = [
            'is_enabled'      => ! empty($post['is_enabled']) ? 1 : 0,
            'mode'            => $mode,
            'test_key_id'     => trim((string) ($post['test_key_id'] ?? '')),
            'test_key_secret' => trim((string) ($post['test_key_secret'] ?? '')),
            'live_key_id'     => trim((string) ($post['live_key_id'] ?? '')),
            'live_key_secret' => trim((string) ($post['live_key_secret'] ?? '')),
            'webhook_secret'  => trim((string) ($post['webhook_secret'] ?? '')),
        ];

        $this->db->query("
            INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`)
            VALUES ('lms_finance__razorpay_json', 'lms_finance', ?)
            ON DUPLICATE KEY UPDATE `setting_group` = 'lms_finance', `setting_value` = VALUES(`setting_value`)
        ", [json_encode($cfg, JSON_UNESCAPED_UNICODE)]);
        self::$globalSettingsCache = null;
    }

    public function createRealRazorpayOrder(float $amount, string $currency, string $receiptRef = ''): array
    {
        $rzp             = $this->getActiveRazorpayConfig();
        $mode            = $rzp['mode'];
        $activeKeyId     = $rzp['key_id'];
        $activeKeySecret = $rzp['key_secret'];

        if ($activeKeyId === '' || $activeKeySecret === '') {
            return [
                'ok'      => false,
                'message' => 'Razorpay ' . strtoupper($mode) . ' Key ID and Key Secret are not configured in Finance Settings.',
            ];
        }

        $currency = strtoupper(trim($currency)) ?: 'INR';
        $subunit  = (int) round(max(1.0, $amount) * 100);
        if ($receiptRef === '') {
            $receiptRef = 'rcpt_' . time() . '_' . rand(100, 999);
        }

        if (class_exists('\Razorpay\Api\Api')) {
            try {
                $sdkClass = '\Razorpay\Api\Api';
                $api      = new $sdkClass($activeKeyId, $activeKeySecret);
                $order    = $api->order->create([
                    'receipt'         => $receiptRef,
                    'amount'          => $subunit,
                    'currency'        => $currency,
                    'payment_capture' => 1,
                ]);
                $orderId = (string) ($order['id'] ?? '');
                if ($orderId !== '') {
                    return [
                        'ok'       => true,
                        'mode'     => $mode,
                        'key_id'   => $activeKeyId,
                        'order_id' => $orderId,
                        'amount'   => $amount,
                        'subunit'  => $subunit,
                        'currency' => $currency,
                    ];
                }
            } catch (Throwable $e) {
                return ['ok' => false, 'message' => 'Razorpay SDK Error: ' . $e->getMessage()];
            }
        }

        if (! function_exists('curl_init')) {
            return ['ok' => false, 'message' => 'PHP cURL extension is required for Razorpay Orders API.'];
        }

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $activeKeyId . ':' . $activeKeySecret,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode([
                'amount'          => $subunit,
                'currency'        => $currency,
                'receipt'         => $receiptRef,
                'payment_capture' => 1,
            ]),
            CURLOPT_TIMEOUT        => 20,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr      = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $curlErr !== '') {
            return ['ok' => false, 'message' => 'Razorpay Connection Error: ' . ($curlErr ?: 'Network error')];
        }

        $decoded = json_decode((string) $responseBody, true);
        if ($httpCode >= 200 && $httpCode < 300 && ! empty($decoded['id'])) {
            return [
                'ok'       => true,
                'mode'     => $mode,
                'key_id'   => $activeKeyId,
                'order_id' => (string) $decoded['id'],
                'amount'   => $amount,
                'subunit'  => $subunit,
                'currency' => $currency,
            ];
        }

        $apiErrDesc = $decoded['error']['description'] ?? ($decoded['error']['reason'] ?? ('HTTP ' . $httpCode));
        return ['ok' => false, 'message' => 'Razorpay API Error: ' . $apiErrDesc];
    }

    public function verifyRazorpaySignatureStrict(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): array
    {
        $razorpayOrderId   = trim($razorpayOrderId);
        $razorpayPaymentId = trim($razorpayPaymentId);
        $razorpaySignature = trim($razorpaySignature);

        if ($razorpayOrderId === '' || $razorpayPaymentId === '' || $razorpaySignature === '') {
            return ['ok' => false, 'message' => 'Missing Razorpay Order ID, Payment ID, or Signature.'];
        }

        $rzp             = $this->getActiveRazorpayConfig();
        $activeKeySecret = $rzp['key_secret'];

        if ($activeKeySecret === '') {
            return ['ok' => false, 'message' => 'Active Razorpay Key Secret is not configured.'];
        }

        $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $activeKeySecret);
        if (! hash_equals($expectedSignature, $razorpaySignature)) {
            return ['ok' => false, 'message' => 'Razorpay signature verification failed (HMAC-SHA256 mismatch).'];
        }

        return ['ok' => true, 'mode' => $rzp['mode']];
    }

    public function getActiveReceiptTemplateConfig(): array
    {
        $s   = $this->getGlobalSiteSettings();
        $raw = $s['lms_finance__receipt_template_json'] ?? '';
        $tpl = $raw !== '' ? (json_decode((string) $raw, true) ?: []) : [];

        return array_merge([
            'academy_name'   => $s['site_title'] ?? 'Online Abacus & Mental Math Academy',
            'address_line'   => 'Academic Headquarters â€¢ Online Live Classroom Portal',
            'support_email'  => 'billing@academy.portal',
            'support_phone'  => '+91 98765 43210',
            'tax_label'      => 'GSTIN / Tax ID',
            'tax_number'     => '',
            'accent_color'   => '#10b981',
            'logo_url'       => '',
            'signature_name' => 'Authorized Academic Signatory',
            'footer_note'    => 'Thank you for your payment! This is a computer-generated official fee receipt.',
        ], $tpl);
    }

    public function saveReceiptTemplateConfig(array $post): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }
        $curr = $this->getActiveReceiptTemplateConfig();
        $tpl  = [
            'academy_name'   => trim((string) ($post['academy_name'] ?? $curr['academy_name'])),
            'address_line'   => trim((string) ($post['address_line'] ?? $curr['address_line'])),
            'support_email'  => trim((string) ($post['support_email'] ?? $curr['support_email'])),
            'support_phone'  => trim((string) ($post['support_phone'] ?? $curr['support_phone'])),
            'tax_label'      => trim((string) ($post['tax_label'] ?? $curr['tax_label'])),
            'tax_number'     => trim((string) ($post['tax_number'] ?? $curr['tax_number'])),
            'accent_color'   => trim((string) ($post['accent_color'] ?? '#10b981')) ?: '#10b981',
            'logo_url'       => trim((string) ($post['logo_url'] ?? $curr['logo_url'])),
            'signature_name' => trim((string) ($post['signature_name'] ?? $curr['signature_name'])),
            'footer_note'    => trim((string) ($post['footer_note'] ?? $curr['footer_note'])),
        ];

        $this->db->query("
            INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`)
            VALUES ('lms_finance__receipt_template_json', 'lms_finance', ?)
            ON DUPLICATE KEY UPDATE `setting_group` = 'lms_finance', `setting_value` = VALUES(`setting_value`)
        ", [json_encode($tpl, JSON_UNESCAPED_UNICODE)]);
        self::$globalSettingsCache = null;
    }

    public function getLeafNodesWithPricing(): array
    {
        if (! $this->db->tableExists('lms_hierarchy_nodes')) {
            return [];
        }
        $rows = $this->db->table('lms_hierarchy_nodes')->where('status', 'active')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        $out  = [];
        foreach ($rows as $r) {
            $out[(int) $r['id']] = $r;
        }
        return $out;
    }

    public function resolveCourseFeeForCountry(int $nodeId, string $countryCode = '', string $cycle = 'monthly', int $studentId = 0): array
    {
        if ($countryCode === '') {
            $countryCode = $this->resolveViewerCountryCode();
        }
        $countryCode = strtoupper(trim($countryCode)) ?: 'IN';
        $courseTitle = 'Course Program';
        $nodeRow     = null;

        if ($this->db->tableExists('lms_hierarchy_nodes') && $nodeId > 0) {
            $nodeRow = $this->db->table('lms_hierarchy_nodes')->where('id', $nodeId)->get()->getRowArray();
            if ($nodeRow) {
                $courseTitle = $nodeRow['title'] ?: $courseTitle;
            }
        }

        $batchDates = $this->resolveBatchDatesForStudentNode($studentId, $nodeId);
        $batchStart = $batchDates['batch_start_date'];
        $batchEnd   = $batchDates['batch_end_date'];
        $cohortId   = $batchDates['cohort_id'];

        $countries   = $this->getEnabledCountriesOnly();
        $defaultCurr = $countries[$countryCode]['currency_code'] ?? (($countryCode === 'AE') ? 'AED' : (($countryCode === 'US') ? 'USD' : (($countryCode === 'GB') ? 'GBP' : 'INR')));
        $currency    = $defaultCurr;
        $amount      = 0.0;

        if ($this->db->tableExists('lms_hierarchy_pricing') && $nodeId > 0) {
            $currId = $nodeId;
            $guard  = 0;
            while ($currId > 0 && $guard < 10) {
                $pRow = $this->db->table('lms_hierarchy_pricing')
                    ->where('node_id', $currId)
                    ->where('country_code', $countryCode)
                    ->get()
                    ->getRowArray();
                if (! $pRow) {
                    $pRow = $this->db->table('lms_hierarchy_pricing')
                        ->where('node_id', $currId)
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getRowArray();
                }
                if ($pRow && (float) ($pRow['monthly_fee'] ?? 0) > 0) {
                    $currency = $pRow['currency_code'] ?: $currency;
                    $amount   = (float) $pRow['monthly_fee'];
                    if (stripos($cycle, 'quarter') !== false && (float) ($pRow['quarterly_fee'] ?? 0) > 0) {
                        $amount = (float) $pRow['quarterly_fee'];
                    } elseif ((stripos($cycle, 'full') !== false || stripos($cycle, 'year') !== false) && (float) ($pRow['full_course_fee'] ?? 0) > 0) {
                        $amount = (float) $pRow['full_course_fee'];
                    }
                    break;
                }
                $currNode = $this->db->table('lms_hierarchy_nodes')->select('parent_id')->where('id', $currId)->get()->getRowArray();
                $currId   = $currNode ? (int) ($currNode['parent_id'] ?? 0) : 0;
                $guard++;
            }
        }

        if ($amount <= 0) {
            $amount = 1000.00;
        }

        $symbols = ['INR' => 'â‚¹', 'AED' => 'AED ', 'USD' => '$', 'GBP' => 'Â£', 'EUR' => 'â‚¬'];
        return [
            'node_id'          => $nodeId,
            'cohort_id'        => $cohortId,
            'course_title'     => $courseTitle,
            'country_code'     => $countryCode,
            'currency'         => $currency,
            'symbol'           => $symbols[$currency] ?? ($currency . ' '),
            'amount'           => $amount,
            'billing_cycle'    => ucfirst($cycle),
            'batch_start_date' => $batchStart,
            'batch_end_date'   => $batchEnd,
        ];
    }

    public function convertToInrEquiv(float $amount, string $currency): float
    {
        $rates = ['INR' => 1.0, 'AED' => 22.8, 'USD' => 84.0, 'GBP' => 106.0, 'EUR' => 91.0];
        $curr  = strtoupper(trim($currency));
        return round($amount * ($rates[$curr] ?? 1.0), 2);
    }

    /**
     * Strictly uses `full_name` from `users` table (NEVER `name`) and maps country/currency from `cms_user_meta`
     * (inheriting Parent's country/currency for linked children if child meta is blank).
     */
    public function getRoleUsersWithMeta(): array
    {
        if (! $this->db->tableExists('users')) {
            return ['students' => [], 'parents' => [], 'teachers' => [], 'all' => []];
        }
        $metaByUid = [];
        if ($this->db->tableExists('cms_user_meta')) {
            foreach ($this->db->table('cms_user_meta')->get()->getResultArray() as $m) {
                $metaByUid[(int) $m['user_id']] = $m;
            }
        }

        $countries = $this->getEnabledCountriesOnly();
        $rows      = $this->db->table('users')->orderBy('id', 'ASC')->get()->getResultArray();
        $students  = [];
        $parents   = [];
        $teachers  = [];
        $all       = [];

        // First pass: map all users
        foreach ($rows as $r) {
            $id    = (int) $r['id'];
            $m     = $metaByUid[$id] ?? [];
            $cCode = strtoupper(trim((string) ($m['country_code'] ?? '')));
            $curr  = strtoupper(trim((string) ($m['currency_code'] ?? '')));

            if ($cCode !== '' && $curr === '') {
                $curr = $countries[$cCode]['currency_code'] ?? ($cCode === 'AE' ? 'AED' : ($cCode === 'US' ? 'USD' : ($cCode === 'GB' ? 'GBP' : 'INR')));
            }

            $r['display_name']  = ! empty($r['full_name']) ? $r['full_name'] : ($r['email'] ?? ('User #' . $id));
            $r['country_code']  = $cCode ?: 'IN';
            $r['currency_code'] = $curr ?: ($countries[$r['country_code']]['currency_code'] ?? 'INR');
            $r['_has_own_meta'] = ($cCode !== '');
            $all[$id]           = $r;
        }

        // Second pass: if a student doesn't have their own country/currency in `cms_user_meta`, inherit from their Parent!
        foreach ($all as $id => &$u) {
            $pid = (int) ($u['parent_user_id'] ?? 0);
            if (! $u['_has_own_meta'] && $pid > 0 && isset($all[$pid])) {
                $u['country_code']  = $all[$pid]['country_code'];
                $u['currency_code'] = $all[$pid]['currency_code'];
            }
            $rId   = (int) ($u['role_id'] ?? 4);
            $rSlug = strtolower(trim((string) ($u['role_slug'] ?? 'student')));

            if (in_array($rId, [1, 2], true) || in_array($rSlug, ['super_admin', 'admin'], true)) {
                continue;
            }
            if ($rId === 3 || $rSlug === 'teacher') {
                $teachers[$id] = $u;
            } elseif ($rSlug === 'parent') {
                $parents[$id] = $u;
            } else {
                $students[$id] = $u;
            }
        }
        unset($u);

        return ['students' => $students, 'parents' => $parents, 'teachers' => $teachers, 'all' => $all];
    }

    /**
     * FINALIZES PAID TRANSACTION & ADVANCES DUE DATE TO THE 1ST OF THE NEXT UNPAID MONTH:
     * - Syncs `batch_start_date` and `batch_end_date` from `lms_classroom_batches`.
     * - First payment covers the starting/current month and sets `due_date = YYYY-MM-01` of next month.
     * - Advance payments (paying when `due_date` is already in a future month, or multiple payments on the same day)
     *   increment `due_date` by **+1 month (`YYYY-MM-01`)** from the subscription's current `due_date` up to `batch_end_date`!
     */
    public function finalizePaidTransaction(array $payload): array
    {
        $studentId  = (int) ($payload['student_user_id'] ?? 0);
        $parentId   = (int) ($payload['parent_user_id'] ?? 0);
        $subId      = (int) ($payload['subscription_id'] ?? 0);
        $invId      = (int) ($payload['invoice_id'] ?? 0);
        $nodeId     = (int) ($payload['node_id'] ?? 0);
        $existingTx = (int) ($payload['existing_txn_id'] ?? 0);

        $users = $this->getRoleUsersWithMeta()['all'];
        if ($parentId <= 0 && isset($users[$studentId])) {
            $parentId = (int) ($users[$studentId]['parent_user_id'] ?? 0);
        }

        $payerUser  = $users[$parentId] ?? ($users[$studentId] ?? []);
        $payerName  = trim((string) ($payload['payer_name'] ?? ($payerUser['display_name'] ?? 'Parent')));
        $payerEmail = trim((string) ($payload['payer_email'] ?? ($payerUser['email'] ?? '')));
        $country    = strtoupper(trim((string) ($payload['country_code'] ?? ($payerUser['country_code'] ?? 'IN')))) ?: 'IN';

        $subRow = null;
        if ($subId > 0) {
            $subRow = $this->db->table($this->subsTable)->where('id', $subId)->get()->getRowArray();
        } elseif ($studentId > 0 && $nodeId > 0) {
            $subRow = $this->db->table($this->subsTable)->where('student_user_id', $studentId)->where('node_id', $nodeId)->get()->getRowArray();
        } elseif ($studentId > 0) {
            $subRow = $this->db->table($this->subsTable)->where('student_user_id', $studentId)->orderBy('id', 'DESC')->get()->getRowArray();
        }

        if ($subRow) {
            $subId  = (int) $subRow['id'];
            $nodeId = $nodeId > 0 ? $nodeId : (int) $subRow['node_id'];
        }

        $quote      = $this->resolveCourseFeeForCountry($nodeId, $country, 'monthly', $studentId);
        $cycle      = (string) ($payload['billing_cycle'] ?? ($subRow['billing_cycle'] ?? 'Monthly'));
        $batchStart = ! empty($subRow['batch_start_date']) && $subRow['batch_start_date'] !== '0000-00-00'
            ? (string) $subRow['batch_start_date']
            : (string) $quote['batch_start_date'];
        $batchEnd   = ! empty($subRow['batch_end_date']) && $subRow['batch_end_date'] !== '0000-00-00'
            ? (string) $subRow['batch_end_date']
            : (string) $quote['batch_end_date'];

        // =====================================================================
        // 1ST OF EVERY MONTH & ADVANCE PAYMENT INCREMENT ENGINE
        // =====================================================================
        $monthsToAdd = 1;
        if (stripos($cycle, 'quarter') !== false) {
            $monthsToAdd = 3;
        } elseif (stripos($cycle, 'year') !== false || stripos($cycle, 'full') !== false) {
            $monthsToAdd = 12;
        }

        $currentMonth1st = date('Y-m-01');
        $existingDueYmd  = ($subRow && ! empty($subRow['due_date']) && $subRow['due_date'] !== '0000-00-00')
            ? (string) $subRow['due_date']
            : '';

        // Count prior verified paid transactions for this subscription/course
        $priorPaidCount = (int) $this->db->table($this->table)
            ->where('student_user_id', $studentId)
            ->where('node_id', $nodeId)
            ->where('status', 'paid')
            ->countAllResults();
        if ($existingTx > 0) {
            $priorPaidCount = max(0, $priorPaidCount - 1);
        }

        if ($priorPaidCount === 0 || $existingDueYmd === '' || $existingDueYmd <= $currentMonth1st) {
            // Paying for the current active month (or first batch month if batch starts in the future)
            $baseMonth1st = date('Y-m-01', strtotime($batchStart));
            if ($baseMonth1st < $currentMonth1st) {
                $baseMonth1st = $currentMonth1st;
            }
            if ($existingDueYmd !== '' && $existingDueYmd > $baseMonth1st) {
                $baseMonth1st = date('Y-m-01', strtotime($existingDueYmd));
            }
        } else {
            // ADVANCE PAYMENT: Student already paid current month; `existingDueYmd` is the upcoming unpaid 1st of month!
            $baseMonth1st = date('Y-m-01', strtotime($existingDueYmd));
        }

        $coveredMonthLabel = date('M Y', strtotime($baseMonth1st));
        $nextDueYmd        = date('Y-m-01', strtotime($baseMonth1st . ' +' . $monthsToAdd . ' months'));

        // Cap against Batch End Date month so we don't bill past the batch end month
        $subStatus    = 'active';
        $batchEndLast = ($batchEnd !== '' && $batchEnd !== '0000-00-00') ? date('Y-m-t', strtotime($batchEnd)) : '';
        if ($batchEndLast !== '' && $nextDueYmd > $batchEndLast) {
            $nextDueYmd = $batchEnd;
            $subStatus  = 'completed';
        }

        $currency = strtoupper(trim((string) ($payload['currency'] ?? ($subRow['currency'] ?? $quote['currency'])))) ?: 'INR';
        $amount   = (float) ($payload['amount'] ?? ($subRow['fee_amount'] ?? $quote['amount']));
        $subtotal = (float) ($payload['subtotal_amount'] ?? $amount);
        $taxPct   = (float) ($payload['tax_percent'] ?? 0.00);
        $taxAmt   = (float) ($payload['tax_amount'] ?? 0.00);
        $inrEquiv = $this->convertToInrEquiv($amount, $currency);

        $baseTitle = trim((string) ($payload['invoice_title'] ?? ($quote['course_title'] . ' â€” ' . $cycle . ' Fee')));
        if (stripos($baseTitle, $coveredMonthLabel) === false && $invId === 0) {
            $invTitle = $baseTitle . ' (' . $coveredMonthLabel . ')';
        } else {
            $invTitle = $baseTitle;
        }

        $txnRef = trim((string) ($payload['txn_reference'] ?? ''));
        $now    = date('Y-m-d H:i:s');

        if ($subRow) {
            $this->db->table($this->subsTable)->where('id', $subId)->update([
                'batch_start_date' => $batchStart,
                'batch_end_date'   => $batchEnd,
                'due_date'         => $nextDueYmd,
                'is_access_locked' => 0,
                'status'           => $subStatus,
                'updated_at'       => $now,
            ]);
        } elseif ($studentId > 0) {
            $this->db->table($this->subsTable)->insert([
                'student_user_id'   => $studentId,
                'parent_user_id'    => $parentId,
                'node_id'           => $nodeId,
                'billing_cycle'     => $cycle,
                'country_code'      => $country,
                'currency'          => $currency,
                'fee_amount'        => $amount,
                'batch_start_date'  => $batchStart,
                'batch_end_date'    => $batchEnd,
                'due_date'          => $nextDueYmd,
                'grace_period_days' => 0,
                'is_access_locked'  => 0,
                'status'            => $subStatus,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            $subId = (int) $this->db->insertID();
        }

        if ($this->db->tableExists('lms_hierarchy_enrollments') && $studentId > 0 && $nodeId > 0) {
            $this->db->table('lms_hierarchy_enrollments')
                ->where('student_user_id', $studentId)
                ->where('leaf_node_id', $nodeId)
                ->update(['next_due_date' => $nextDueYmd, 'updated_at' => $now]);
        }

        $txData = [
            'subscription_id'    => $subId,
            'invoice_id'         => $invId,
            'student_user_id'    => $studentId,
            'parent_user_id'     => $parentId,
            'node_id'            => $nodeId,
            'payer_name'         => $payerName,
            'payer_email'        => $payerEmail,
            'invoice_title'      => $invTitle,
            'billing_cycle'      => $cycle,
            'batch_start_date'   => $batchStart,
            'batch_end_date'     => $batchEnd,
            'next_due_date'      => $nextDueYmd,
            'provider'           => (string) ($payload['provider'] ?? 'razorpay'),
            'txn_reference'      => $txnRef,
            'razorpay_order_id'  => (string) ($payload['razorpay_order_id'] ?? ''),
            'razorpay_signature' => (string) ($payload['razorpay_signature'] ?? ''),
            'country_code'       => $country,
            'currency'           => $currency,
            'subtotal_amount'    => $subtotal,
            'tax_percent'        => $taxPct,
            'tax_amount'         => $taxAmt,
            'amount'             => $amount,
            'amount_inr_equiv'   => $inrEquiv,
            'status'             => 'paid',
            'verification_note'  => (string) ($payload['verification_note'] ?? ('Paid for ' . $coveredMonthLabel)),
            'updated_at'         => $now,
        ];

        if ($existingTx > 0) {
            $this->db->table($this->table)->where('id', $existingTx)->update($txData);
            $txnId = $existingTx;
        } else {
            $txData['created_at'] = $now;
            $this->db->table($this->table)->insert($txData);
            $txnId = (int) $this->db->insertID();
        }

        if ($invId > 0 && $this->db->tableExists($this->invoicesTable)) {
            $this->db->table($this->invoicesTable)->where('id', $invId)->update([
                'status'      => 'paid',
                'paid_txn_id' => $txnId,
                'updated_at'  => $now,
            ]);
        }

        session()->set('lms_child_fee_locked', 0);

        return [
            'ok'            => true,
            'txn_id'        => $txnId,
            'next_due_date' => $nextDueYmd,
            'message'       => 'Payment verified for ' . $coveredMonthLabel . '! Next Due Date updated to ' . $nextDueYmd . '.',
        ];
    }

    public function submitOfflineProofTransaction(array $post, $proofFile = null): array
    {
        $studentId = (int) ($post['student_user_id'] ?? 0);
        $subId     = (int) ($post['subscription_id'] ?? 0);
        $invId     = (int) ($post['invoice_id'] ?? 0);
        $nodeId    = (int) ($post['node_id'] ?? 0);
        $utrRef    = trim((string) ($post['txn_reference'] ?? ''));

        if ($utrRef === '') {
            return ['ok' => false, 'message' => 'UTR / Bank Reference Number is compulsory.'];
        }

        $proofUrl = '';
        if ($proofFile && $proofFile->isValid() && ! $proofFile->hasMoved()) {
            $ext = strtolower($proofFile->getClientExtension());
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'pdf'], true)) {
                $dir = FCPATH . 'uploads/lms_finance/proofs/';
                if (! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $newName = 'proof_' . time() . '_' . $proofFile->getRandomName();
                $proofFile->move($dir, $newName);
                $proofUrl = 'uploads/lms_finance/proofs/' . $newName;
            }
        }

        $users      = $this->getRoleUsersWithMeta()['all'];
        $stuRow     = $users[$studentId] ?? [];
        $parentId   = (int) ($stuRow['parent_user_id'] ?? (session()->get('user_id') ?: 0));
        $parentRow  = $users[$parentId] ?? $stuRow;
        $country    = $parentRow['country_code'] ?? 'IN';
        $quote      = $this->resolveCourseFeeForCountry($nodeId, $country, 'monthly', $studentId);
        $batchStart = $quote['batch_start_date'];
        $nextDueYmd = date('Y-m-01', strtotime('first day of next month'));

        $amount   = (float) ($quote['amount'] ?? 1000.00);
        $currency = (string) ($quote['currency'] ?? 'INR');
        $title    = $quote['course_title'] . ' â€” Offline Payment Proof';

        if ($invId > 0 && $this->db->tableExists($this->invoicesTable)) {
            $inv = $this->db->table($this->invoicesTable)->where('id', $invId)->get()->getRowArray();
            if ($inv) {
                $amount   = (float) $inv['total_amount'];
                $currency = (string) $inv['currency'];
                $title    = (string) $inv['title'];
                $this->db->table($this->invoicesTable)->where('id', $invId)->update(['status' => 'pending_verification']);
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table($this->table)->insert([
            'subscription_id'   => $subId,
            'invoice_id'        => $invId,
            'student_user_id'   => $studentId,
            'parent_user_id'    => $parentId,
            'node_id'           => $nodeId,
            'payer_name'        => $parentRow['display_name'] ?? 'Parent',
            'payer_email'       => $parentRow['email'] ?? '',
            'invoice_title'     => $title,
            'billing_cycle'     => 'Monthly',
            'batch_start_date'  => $batchStart,
            'batch_end_date'    => $quote['batch_end_date'],
            'next_due_date'     => $nextDueYmd,
            'provider'          => 'offline_proof',
            'txn_reference'     => $utrRef,
            'proof_file_url'    => $proofUrl,
            'country_code'      => $country,
            'currency'          => $currency,
            'subtotal_amount'   => $amount,
            'amount'            => $amount,
            'amount_inr_equiv'  => $this->convertToInrEquiv($amount, $currency),
            'status'            => 'pending_verification',
            'verification_note' => 'Awaiting Admin UTR & Screenshot Verification',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        return ['ok' => true, 'message' => 'Payment proof (UTR: ' . $utrRef . ') submitted! Once verified by Admin, your due date will advance and receipt will unlock.'];
    }

    public function verifyOfflineProofByAdmin(int $txnId, string $decision, string $note = ''): array
    {
        $tx = $this->find($txnId);
        if (! $tx) {
            return ['ok' => false, 'message' => 'Transaction not found.'];
        }
        if ($decision === 'approve') {
            return $this->finalizePaidTransaction([
                'existing_txn_id'   => $txnId,
                'subscription_id'   => (int) $tx['subscription_id'],
                'invoice_id'        => (int) $tx['invoice_id'],
                'student_user_id'   => (int) $tx['student_user_id'],
                'parent_user_id'    => (int) $tx['parent_user_id'],
                'node_id'           => (int) $tx['node_id'],
                'payer_name'        => $tx['payer_name'],
                'payer_email'       => $tx['payer_email'],
                'invoice_title'     => $tx['invoice_title'],
                'billing_cycle'     => $tx['billing_cycle'],
                'provider'          => 'offline_verified',
                'txn_reference'     => $tx['txn_reference'],
                'country_code'      => $tx['country_code'],
                'currency'          => $tx['currency'],
                'subtotal_amount'   => (float) $tx['subtotal_amount'] ?: (float) $tx['amount'],
                'tax_percent'       => (float) $tx['tax_percent'],
                'tax_amount'        => (float) $tx['tax_amount'],
                'amount'            => (float) $tx['amount'],
                'verification_note' => $note !== '' ? $note : 'Verified UTR & Screenshot by Admin',
            ]);
        }

        $this->update($txnId, [
            'status'            => 'rejected',
            'verification_note' => $note !== '' ? $note : 'Proof rejected by Admin',
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
        if ((int) $tx['invoice_id'] > 0 && $this->db->tableExists($this->invoicesTable)) {
            $this->db->table($this->invoicesTable)->where('id', (int) $tx['invoice_id'])->update(['status' => 'unpaid']);
        }
        return ['ok' => true, 'message' => 'Offline payment proof marked as Rejected.'];
    }

    public function saveOrUpdateSubscription(array $post): array
    {
        $studentId  = (int) ($post['student_user_id'] ?? 0);
        $nodeId     = (int) ($post['node_id'] ?? 0);
        $cycle      = trim((string) ($post['billing_cycle'] ?? 'Monthly')) ?: 'Monthly';

        if ($studentId <= 0 || $nodeId <= 0) {
            return ['ok' => false, 'message' => 'Select a Student and a Course.'];
        }

        $users      = $this->getRoleUsersWithMeta()['all'];
        $stu        = $users[$studentId] ?? [];
        $parentId   = (int) ($stu['parent_user_id'] ?? 0);
        $country    = $stu['country_code'] ?? 'IN';
        $quote      = $this->resolveCourseFeeForCountry($nodeId, $country, $cycle, $studentId);

        $batchStart = ! empty($post['batch_start_date']) ? trim((string) $post['batch_start_date']) : $quote['batch_start_date'];
        $batchEnd   = ! empty($post['batch_end_date']) ? trim((string) $post['batch_end_date']) : $quote['batch_end_date'];
        $customFee  = isset($post['fee_amount']) && (float) $post['fee_amount'] > 0 ? (float) $post['fee_amount'] : (float) $quote['amount'];

        // Initial due date is 1st of the current/starting month (unpaid until first payment is made)
        $initDueYmd = date('Y-m-01', strtotime($batchStart));
        if ($initDueYmd < date('Y-m-01')) {
            $initDueYmd = date('Y-m-01');
        }
        $now = date('Y-m-d H:i:s');

        $this->db->table($this->subsTable)->insert([
            'student_user_id'   => $studentId,
            'parent_user_id'    => $parentId,
            'node_id'           => $nodeId,
            'billing_cycle'     => $cycle,
            'country_code'      => $country,
            'currency'          => $quote['currency'],
            'fee_amount'        => $customFee,
            'batch_start_date'  => $batchStart,
            'batch_end_date'    => $batchEnd,
            'due_date'          => $initDueYmd,
            'grace_period_days' => 0,
            'is_access_locked'  => ($initDueYmd <= date('Y-m-d')) ? 1 : 0,
            'status'            => 'active',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        return ['ok' => true, 'message' => 'Batch Subscription created (Batch: ' . $batchStart . ' to ' . $batchEnd . ' | Due Date: ' . $initDueYmd . ')!'];
    }

    public function createCustomParentChildInvoice(array $post): array
    {
        $parentId  = (int) ($post['parent_user_id'] ?? 0);
        $studentId = (int) ($post['student_user_id'] ?? 0);
        $title     = trim((string) ($post['title'] ?? ''));
        $remarks   = trim((string) ($post['remarks'] ?? ''));
        $currency  = strtoupper(trim((string) ($post['currency'] ?? 'INR'))) ?: 'INR';
        $country   = strtoupper(trim((string) ($post['country_code'] ?? 'IN'))) ?: 'IN';
        $subtotal  = max(1.0, (float) ($post['subtotal_amount'] ?? 0));
        $taxPct    = max(0.0, min(50.0, (float) ($post['tax_percent'] ?? 0)));
        $dueDate   = trim((string) ($post['due_date'] ?? date('Y-m-01', strtotime('first day of next month'))));

        if ($studentId <= 0 || $title === '' || $subtotal <= 0) {
            return ['ok' => false, 'message' => 'Select a Child, enter an Invoice Title, and enter a valid Amount.'];
        }

        if ($parentId <= 0) {
            $users    = $this->getRoleUsersWithMeta()['all'];
            $parentId = (int) ($users[$studentId]['parent_user_id'] ?? 0);
        }

        $taxAmt = round($subtotal * ($taxPct / 100), 2);
        $total  = round($subtotal + $taxAmt, 2);
        $now    = date('Y-m-d H:i:s');

        $this->db->table($this->invoicesTable)->insert([
            'invoice_no'        => 'INV-' . date('Ym') . '-' . $studentId . '-' . rand(10, 99),
            'invoice_type'      => 'custom_request',
            'parent_user_id'    => $parentId,
            'student_user_id'   => $studentId,
            'title'             => $title,
            'remarks'           => $remarks,
            'country_code'      => $country,
            'currency'          => $currency,
            'subtotal_amount'   => $subtotal,
            'tax_percent'       => $taxPct,
            'tax_amount'        => $taxAmt,
            'total_amount'      => $total,
            'billing_cycle'     => 'One-Time',
            'cycle_month_label' => date('M Y'),
            'due_date'          => $dueDate,
            'status'            => 'unpaid',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        return ['ok' => true, 'message' => 'Custom Fee Invoice (' . $currency . ' ' . number_format($total, 2) . ') sent to Parent Portal!'];
    }

    public function savePayoutOrExpense(array $post): array
    {
        $type      = in_array($post['expense_type'] ?? '', ['teacher_payout', 'operational_expense'], true) ? $post['expense_type'] : 'teacher_payout';
        $teacherId = (int) ($post['teacher_user_id'] ?? 0);
        $title     = trim((string) ($post['title'] ?? ''));
        $monthYear = trim((string) ($post['month_year'] ?? date('M Y')));
        $classes   = max(0, (int) ($post['classes_taught'] ?? 0));
        $currency  = strtoupper(trim((string) ($post['currency'] ?? 'INR'))) ?: 'INR';
        $amount    = max(1.0, (float) ($post['amount'] ?? 0));
        $mode      = trim((string) ($post['payment_mode'] ?? 'Bank Transfer / UPI'));
        $refNo     = trim((string) ($post['reference_no'] ?? ('PAY-' . date('ymd') . '-' . rand(10, 99))));
        $notes     = trim((string) ($post['notes'] ?? ''));

        if ($title === '' || $amount <= 0) {
            return ['ok' => false, 'message' => 'Enter a Title and valid Amount.'];
        }

        $this->db->table($this->expensesTable)->insert([
            'expense_type'    => $type,
            'teacher_user_id' => $teacherId,
            'title'           => $title,
            'month_year'      => $monthYear,
            'classes_taught'  => $classes,
            'currency'        => $currency,
            'amount'          => $amount,
            'payment_mode'    => $mode,
            'reference_no'    => $refNo,
            'status'          => 'paid',
            'notes'           => $notes,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'message' => 'Recorded ' . ($type === 'teacher_payout' ? 'Teacher Salary Slip' : 'Expense') . ' (' . $currency . ' ' . number_format($amount, 2) . ')!'];
    }
}

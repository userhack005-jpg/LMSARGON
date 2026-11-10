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
    protected static bool $schemaVerifiedInRequest = false;

    public function __construct()
    {
        @date_default_timezone_set('Asia/Kolkata');
        parent::__construct();
        if (! self::$schemaVerifiedInRequest) {
            self::$schemaVerifiedInRequest = true;
            $this->ensureSchemaFast();
        }
    }

    public function nowIst(): string
    {
        @date_default_timezone_set('Asia/Kolkata');
        return date('Y-m-d H:i:s');
    }

    public static function cleanText(?string $str): string
    {
        if ($str === null || $str === '') {
            return '';
        }
        return str_replace(
            ['â€”', 'â€“', 'â€¢', 'â†’', 'âž”', 'â‚¹', 'ðŸŽ“', 'ðŸ‘¨â€ðŸ‘©â€ðŸ‘§â€ðŸ‘¦'],
            [' - ', ' - ', ' | ', ' -> ', ' -> ', 'Rs. ', '', ''],
            $str
        );
    }

    public function ensureSchemaInitialized(): void
    {
        $this->ensureSchemaFast();
    }

    public function ensureSchema(): void
    {
        $this->ensureSchemaFast();
    }

    public function ensureSchemaFast(): void
    {
        $lockFile = WRITEPATH . 'addons/schema_addon_lms_finance_v160.lock';
        if (is_file($lockFile)) {
            return;
        }

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
                        `grace_period_days` SMALLINT UNSIGNED NOT NULL DEFAULT 5,
                        `paid_cycles_count` INT UNSIGNED NOT NULL DEFAULT 0,
                        `admin_lock_override` TINYINT(1) NOT NULL DEFAULT 0,
                        `is_access_locked` TINYINT(1) NOT NULL DEFAULT 1,
                        `status` VARCHAR(40) NOT NULL DEFAULT 'active',
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        KEY `idx_stu_status` (`student_user_id`, `status`),
                        KEY `idx_parent_status` (`parent_user_id`, `status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } else {
                $sCols = $this->db->getFieldNames('lms_student_subscriptions');
                $sAlters = [
                    'paid_cycles_count'   => "ADD COLUMN `paid_cycles_count` INT UNSIGNED NOT NULL DEFAULT 0",
                    'admin_lock_override' => "ADD COLUMN `admin_lock_override` TINYINT(1) NOT NULL DEFAULT 0",
                    'grace_period_days'   => "ADD COLUMN `grace_period_days` SMALLINT UNSIGNED NOT NULL DEFAULT 5",
                    'batch_start_date'    => "ADD COLUMN `batch_start_date` DATE NULL",
                    'batch_end_date'      => "ADD COLUMN `batch_end_date` DATE NULL",
                ];
                foreach ($sAlters as $col => $frag) {
                    if (! in_array($col, $sCols, true)) {
                        try {
                            $this->db->query("ALTER TABLE `lms_student_subscriptions` " . $frag);
                        } catch (Throwable $e) {
                        }
                    }
                }
                try {
                    $this->db->query("ALTER TABLE `lms_student_subscriptions` MODIFY COLUMN `status` VARCHAR(40) NOT NULL DEFAULT 'active'");
                } catch (Throwable $e) {
                }
            }

            if ($this->db->tableExists('payment_transactions')) {
                try {
                    $this->db->query("ALTER TABLE `payment_transactions` MODIFY COLUMN `status` VARCHAR(40) NOT NULL DEFAULT 'paid'");
                    $this->db->query("ALTER TABLE `payment_transactions` MODIFY COLUMN `provider` VARCHAR(50) NOT NULL DEFAULT 'razorpay'");
                } catch (Throwable $e) {
                }

                // Heal any blank status rows caused by old ENUM constraints
                $this->db->query("
                    UPDATE `payment_transactions`
                    SET `status` = 'pending_verification'
                    WHERE (`status` = '' OR `status` IS NULL)
                      AND (`provider` = 'offline_proof' OR `verification_note` LIKE '%Awaiting%')
                ");
                $this->db->query("
                    UPDATE `payment_transactions`
                    SET `status` = 'failed'
                    WHERE (`status` = '' OR `status` IS NULL)
                      AND (`provider` LIKE '%failed%' OR `verification_note` LIKE '%Failed%')
                ");
                $this->db->query("
                    UPDATE `payment_transactions`
                    SET `status` = 'rejected'
                    WHERE (`status` = '' OR `status` IS NULL)
                      AND `verification_note` LIKE '%Rejected%'
                ");
                $this->db->query("
                    UPDATE `payment_transactions`
                    SET `status` = 'paid'
                    WHERE (`status` = '' OR `status` IS NULL OR `status` = 'completed' OR `status` = 'captured' OR `status` = 'success')
                ");
                $this->db->query("
                    UPDATE `payment_transactions`
                    SET `invoice_title` = REPLACE(REPLACE(`invoice_title`, 'â€”', ' - '), 'â€¢', ' | '),
                        `verification_note` = REPLACE(REPLACE(`verification_note`, 'â€”', ' - '), 'â€¢', ' | ')
                ");
            }

            if ($this->db->tableExists('lms_finance_invoices')) {
                try {
                    $this->db->query("ALTER TABLE `lms_finance_invoices` MODIFY COLUMN `status` VARCHAR(40) NOT NULL DEFAULT 'unpaid'");
                } catch (Throwable $e) {
                }
            }

            // Sync all existing batch assignments & course enrollments into `lms_student_subscriptions`
            $this->syncBatchAssignmentsAndSubscriptions();
            $this->mirrorAssets(true);

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, $this->nowIst());
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
     * Auto-Syncs any student assigned to a Batch (`lms_classroom_batch_students`)
     * or Course (`lms_hierarchy_enrollments`) into `lms_student_subscriptions`!
     * If `paid_cycles_count == 0`, sets `is_access_locked = 1` so Parent sees the Pending Payment Card
     * on `/finance/portal` and the student cannot access classes/practice until the first fee is paid!
     */
    public function syncBatchAssignmentsAndSubscriptions(array $studentIdsFilter = []): void
    {
        try {
            if (! $this->db->tableExists($this->subsTable) || ! $this->db->tableExists('users')) {
                return;
            }

            $candidates = [];

            if ($this->db->tableExists('lms_classroom_batch_students')) {
                $qb1 = $this->db->table('lms_classroom_batch_students')
                    ->select('student_user_id, leaf_node_id')
                    ->where('status', 'active');
                if (! empty($studentIdsFilter)) {
                    $qb1->whereIn('student_user_id', array_map('intval', $studentIdsFilter));
                }
                foreach ($qb1->get()->getResultArray() as $r1) {
                    $sid = (int) ($r1['student_user_id'] ?? 0);
                    $nid = (int) ($r1['leaf_node_id'] ?? 0);
                    if ($sid > 0 && $nid > 0) {
                        $candidates[$sid . '_' . $nid] = ['student_user_id' => $sid, 'node_id' => $nid];
                    }
                }
            }

            if ($this->db->tableExists('lms_hierarchy_enrollments')) {
                $qb2 = $this->db->table('lms_hierarchy_enrollments')
                    ->select('student_user_id, leaf_node_id')
                    ->where('status', 'active');
                if (! empty($studentIdsFilter)) {
                    $qb2->whereIn('student_user_id', array_map('intval', $studentIdsFilter));
                }
                foreach ($qb2->get()->getResultArray() as $r2) {
                    $sid = (int) ($r2['student_user_id'] ?? 0);
                    $nid = (int) ($r2['leaf_node_id'] ?? 0);
                    if ($sid > 0 && $nid > 0) {
                        $candidates[$sid . '_' . $nid] = ['student_user_id' => $sid, 'node_id' => $nid];
                    }
                }
            }

            if (empty($candidates)) {
                return;
            }

            $exQb = $this->db->table($this->subsTable)->select('student_user_id, node_id');
            if (! empty($studentIdsFilter)) {
                $exQb->whereIn('student_user_id', array_map('intval', $studentIdsFilter));
            }
            foreach ($exQb->get()->getResultArray() as $ex) {
                $key = ((int) $ex['student_user_id']) . '_' . ((int) $ex['node_id']);
                unset($candidates[$key]);
            }

            if (empty($candidates)) {
                return;
            }

            $users = $this->getRoleUsersWithMeta()['all'];
            $now   = $this->nowIst();

            foreach ($candidates as $cand) {
                $sid = $cand['student_user_id'];
                $nid = $cand['node_id'];
                if (! isset($users[$sid])) {
                    continue;
                }

                $pid     = (int) ($users[$sid]['parent_user_id'] ?? 0);
                $country = $users[$sid]['country_code'] ?? 'IN';
                $quote   = $this->resolveCourseFeeForCountry($nid, $country, 'monthly', $sid);

                $paidCnt = (int) $this->db->table($this->table)
                    ->where('student_user_id', $sid)
                    ->where('node_id', $nid)
                    ->where('status', 'paid')
                    ->countAllResults();

                $bStart  = $quote['batch_start_date'];
                $bEnd    = $quote['batch_end_date'];
                $initDue = date('Y-m-01', strtotime($bStart));
                if ($initDue < date('Y-m-01')) {
                    $initDue = date('Y-m-01');
                }
                if ($paidCnt > 0) {
                    $initDue = date('Y-m-01', strtotime($initDue . ' +' . $paidCnt . ' months'));
                }

                $this->db->table($this->subsTable)->insert([
                    'student_user_id'     => $sid,
                    'parent_user_id'      => $pid,
                    'node_id'             => $nid,
                    'billing_cycle'       => $quote['billing_cycle'],
                    'country_code'        => $quote['country_code'],
                    'currency'            => $quote['currency'],
                    'fee_amount'          => $quote['amount'],
                    'batch_start_date'    => $bStart,
                    'batch_end_date'      => $bEnd,
                    'due_date'            => $initDue,
                    'grace_period_days'   => 5,
                    'paid_cycles_count'   => $paidCnt,
                    'admin_lock_override' => 0,
                    'is_access_locked'    => ($paidCnt === 0) ? 1 : 0,
                    'status'              => 'active',
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
            }
        } catch (Throwable $e) {
        }
    }

    /**
     * Evaluates Subscription Locks:
     * - First syncs any missing batch/course assignments into `lms_student_subscriptions`.
     * - Respects `admin_lock_override` (1 = Admin Unlocked, 2 = Admin Locked).
     * - If `paid_cycles_count == 0` -> `is_access_locked = 1` (Batch requires 1st payment before activation!).
     * - If `paid_cycles_count >= 1` -> Locks if `today > due_date + grace_period_days`.
     */
    public function evaluateSubscriptionLocks(array $studentIdsFilter = []): void
    {
        try {
            $this->syncBatchAssignmentsAndSubscriptions($studentIdsFilter);

            $qb = $this->db->table($this->subsTable)->where('status', 'active');
            if (! empty($studentIdsFilter)) {
                $qb->whereIn('student_user_id', array_map('intval', $studentIdsFilter));
            }
            $subs = $qb->get()->getResultArray();
            if (empty($subs)) {
                return;
            }

            $todayYmd = date('Y-m-d');
            foreach ($subs as $s) {
                $subId    = (int) $s['id'];
                $stuId    = (int) $s['student_user_id'];
                $nodeId   = (int) $s['node_id'];
                $override = (int) ($s['admin_lock_override'] ?? 0);

                if ($override === 1) {
                    if ((int) $s['is_access_locked'] !== 0) {
                        $this->db->table($this->subsTable)->where('id', $subId)->update(['is_access_locked' => 0]);
                    }
                    continue;
                }
                if ($override === 2) {
                    if ((int) $s['is_access_locked'] !== 1) {
                        $this->db->table($this->subsTable)->where('id', $subId)->update(['is_access_locked' => 1]);
                    }
                    continue;
                }

                $paidCnt = (int) ($s['paid_cycles_count'] ?? 0);
                if ($paidCnt === 0 && $this->db->tableExists($this->table)) {
                    $paidCnt = (int) $this->db->table($this->table)
                        ->where('student_user_id', $stuId)
                        ->where('node_id', $nodeId)
                        ->where('status', 'paid')
                        ->countAllResults();
                    if ($paidCnt > 0) {
                        $this->db->table($this->subsTable)->where('id', $subId)->update(['paid_cycles_count' => $paidCnt]);
                    }
                }

                $dueYmd  = (string) ($s['due_date'] ?? '');
                $bEndYmd = (string) ($s['batch_end_date'] ?? '');
                $grace   = max(0, (int) ($s['grace_period_days'] ?? 5));

                if ($bEndYmd !== '' && $bEndYmd !== '0000-00-00' && $paidCnt > 0 && $dueYmd > $bEndYmd) {
                    $this->db->table($this->subsTable)->where('id', $subId)->update([
                        'is_access_locked' => 0,
                        'status'           => 'completed',
                    ]);
                    continue;
                }

                if ($paidCnt === 0) {
                    $shouldLock = 1;
                } else {
                    $cutoffYmd  = ($dueYmd !== '' && $dueYmd !== '0000-00-00')
                        ? date('Y-m-d', strtotime($dueYmd . ' +' . $grace . ' days'))
                        : $todayYmd;
                    $shouldLock = ($todayYmd > $cutoffYmd) ? 1 : 0;
                }

                if ((int) ($s['is_access_locked'] ?? 0) !== $shouldLock) {
                    $this->db->table($this->subsTable)->where('id', $subId)->update(['is_access_locked' => $shouldLock]);
                }
            }
        } catch (Throwable $e) {
        }
    }

    public function enforceStrictMonthlyFeeGate(string $rawUri = ''): void
    {
        try {
            if ($rawUri === '') {
                $rawUri = strtolower(strtok((string) ($_SERVER['REQUEST_URI'] ?? service('uri')->getPath()), '?'));
            }

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
            } elseif ($effectiveRole === 'parent' && $uid > 0) {
                foreach ($this->db->table('users')->select('id')->where('parent_user_id', $uid)->get()->getResultArray() as $k) {
                    $targetStudentIds[] = (int) $k['id'];
                }
            }

            if (empty($targetStudentIds)) {
                session()->set('lms_child_fee_locked', 0);
                return;
            }

            $this->evaluateSubscriptionLocks($targetStudentIds);

            $lockedCount = (int) $this->db->table($this->subsTable)
                ->whereIn('student_user_id', $targetStudentIds)
                ->where('status', 'active')
                ->where('is_access_locked', 1)
                ->countAllResults();

            $isLocked = ($lockedCount > 0);
            session()->set('lms_child_fee_locked', $isLocked ? 1 : 0);

            if (! $isLocked) {
                return;
            }

            if (strpos($rawUri, 'dashboard') !== false) {
                return;
            }
            if (strpos($rawUri, 'finance') !== false) {
                if ($isStudentContext && strpos($rawUri, 'receipt') === false) {
                    header('Location: ' . base_url('dashboard?fee_locked=1'));
                    exit;
                }
                return;
            }

            $restrictedKeywords = ['timetable', 'abacus', 'assessments', 'courses', 'demo', 'communication', 'addons/lms-'];
            foreach ($restrictedKeywords as $rk) {
                if (strpos($rawUri, $rk) !== false) {
                    if ($isStudentContext) {
                        header('Location: ' . base_url('dashboard?fee_locked=1'));
                        exit;
                    } else {
                        header('Location: ' . base_url('finance/portal?locked=1'));
                        exit;
                    }
                }
            }
        } catch (Throwable $e) {
        }
    }

    public function mirrorAssets(bool $forceTouch = false): void
    {
        if (! $forceTouch) {
            return;
        }
        $srcDir = WRITEPATH . 'addons/extracted/lms_finance/assets';
        $dstDir = FCPATH . 'assets/addons/lms_finance';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['lms_finance.css', 'lms_finance.js'] as $f) {
                $sFile = $srcDir . '/' . $f;
                $dFile = $dstDir . '/' . $f;
                if (is_file($sFile)) {
                    @copy($sFile, $dFile);
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
            foreach ($this->db->table('settings')->get()->getResultArray() as $r) {
                self::$globalSettingsCache[$r['setting_key']] = $r['setting_value'];
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
            'IN' => ['code' => 'IN', 'name' => 'India',          'flag' => 'IN', 'currency_code' => 'INR', 'default_tz' => 'Asia/Kolkata'],
            'AE' => ['code' => 'AE', 'name' => 'UAE',            'flag' => 'AE', 'currency_code' => 'AED', 'default_tz' => 'Asia/Dubai'],
            'US' => ['code' => 'US', 'name' => 'United States',  'flag' => 'US', 'currency_code' => 'USD', 'default_tz' => 'America/New_York'],
            'GB' => ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => 'GB', 'currency_code' => 'GBP', 'default_tz' => 'Europe/London'],
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

    public function getLoggedInUserRecord(): ?array
    {
        $sessUser = is_array(session()->get('user')) ? session()->get('user') : [];
        $uid      = (int) (session()->get('user_id') ?: (session()->get('id') ?: ($sessUser['id'] ?? ($sessUser['user_id'] ?? 0))));
        if ($uid <= 0 && ! session()->get('isLoggedIn')) {
            return null;
        }
        if ($uid > 0) {
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
        if ($parentId <= 0) {
            return [];
        }
        $kids = [];
        foreach ($this->db->table('users')->where('parent_user_id', $parentId)->where('status', 'active')->orderBy('id', 'ASC')->get()->getResultArray() as $k) {
            $kidId = (int) $k['id'];
            $kids[$kidId] = [
                'id'        => $kidId,
                'full_name' => $k['full_name'] ?: ($k['email'] ?: 'Child'),
                'age'       => 8,
            ];
        }
        if (! empty($kids) && $this->db->tableExists('cms_user_meta')) {
            foreach ($this->db->table('cms_user_meta')->whereIn('user_id', array_keys($kids))->get()->getResultArray() as $m) {
                $uid = (int) $m['user_id'];
                if (isset($kids[$uid])) {
                    $kids[$uid]['age'] = (int) ($m['child_age'] ?? 8);
                }
            }
        }
        return $kids;
    }

    public function getActiveRazorpayConfig(): array
    {
        $s   = $this->getGlobalSiteSettings();
        $raw = $s['lms_finance__razorpay_json'] ?? '';
        $cfg = $raw !== '' ? (json_decode((string) $raw, true) ?: []) : [];

        $rawMode       = strtolower(trim((string) ($cfg['mode'] ?? 'test')));
        $mode          = ($rawMode === 'live') ? 'live' : 'test';
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
            'address_line'   => 'Academic Headquarters | Online Live Classroom Portal',
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
        $rows = $this->db->table('lms_hierarchy_nodes')->select('id, title, parent_id')->where('status', 'active')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
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

        if ($this->db->tableExists('lms_hierarchy_nodes') && $nodeId > 0) {
            $nodeRow = $this->db->table('lms_hierarchy_nodes')->select('id, title, parent_id')->where('id', $nodeId)->get()->getRowArray();
            if ($nodeRow) {
                $courseTitle = $nodeRow['title'] ?: $courseTitle;
            }
        }

        $batchDates  = $this->resolveBatchDatesForStudentNode($studentId, $nodeId);
        $batchStart  = $batchDates['batch_start_date'];
        $batchEnd    = $batchDates['batch_end_date'];
        $cohortId    = $batchDates['cohort_id'];

        $countries   = $this->getEnabledCountriesOnly();
        $defaultCurr = $countries[$countryCode]['currency_code'] ?? (($countryCode === 'AE') ? 'AED' : (($countryCode === 'US') ? 'USD' : (($countryCode === 'GB') ? 'GBP' : 'INR')));
        $currency    = $defaultCurr;
        $amount      = 0.0;
        $detectedCyc = ucfirst(strtolower(trim($cycle ?: 'Monthly')));

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
                if ($pRow) {
                    $currency = $pRow['currency_code'] ?: $currency;
                    $mFee     = (float) ($pRow['monthly_fee'] ?? 0);
                    $qFee     = (float) ($pRow['quarterly_fee'] ?? 0);
                    $fFee     = (float) ($pRow['full_course_fee'] ?? 0);

                    if (stripos($cycle, 'quarter') !== false && $qFee > 0) {
                        $amount      = $qFee;
                        $detectedCyc = 'Quarterly';
                        break;
                    } elseif ((stripos($cycle, 'year') !== false || stripos($cycle, 'full') !== false) && $fFee > 0) {
                        $amount      = $fFee;
                        $detectedCyc = 'Yearly';
                        break;
                    } elseif ($mFee > 0) {
                        $amount      = $mFee;
                        $detectedCyc = 'Monthly';
                        break;
                    } elseif ($qFee > 0) {
                        $amount      = $qFee;
                        $detectedCyc = 'Quarterly';
                        break;
                    } elseif ($fFee > 0) {
                        $amount      = $fFee;
                        $detectedCyc = 'Yearly';
                        break;
                    }
                }
                $currNode = $this->db->table('lms_hierarchy_nodes')->select('parent_id')->where('id', $currId)->get()->getRowArray();
                $currId   = $currNode ? (int) ($currNode['parent_id'] ?? 0) : 0;
                $guard++;
            }
        }

        if ($amount <= 0) {
            $amount = 1000.00;
        }

        $symbols = ['INR' => 'Rs. ', 'AED' => 'AED ', 'USD' => '$', 'GBP' => 'GBP ', 'EUR' => 'EUR '];
        return [
            'node_id'          => $nodeId,
            'cohort_id'        => $cohortId,
            'course_title'     => $courseTitle,
            'country_code'     => $countryCode,
            'currency'         => $currency,
            'symbol'           => $symbols[$currency] ?? ($currency . ' '),
            'amount'           => $amount,
            'billing_cycle'    => $detectedCyc,
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

    public function getRoleUsersWithMeta(): array
    {
        if (! $this->db->tableExists('users')) {
            return ['students' => [], 'parents' => [], 'teachers' => [], 'all' => []];
        }
        $metaByUid = [];
        if ($this->db->tableExists('cms_user_meta')) {
            foreach ($this->db->table('cms_user_meta')->select('user_id, country_code, currency_code')->get()->getResultArray() as $m) {
                $metaByUid[(int) $m['user_id']] = $m;
            }
        }

        $countries = $this->getEnabledCountriesOnly();
        $rows      = $this->db->table('users')->select('id, role_id, role_slug, parent_user_id, full_name, email, status')->orderBy('id', 'ASC')->get()->getResultArray();
        $students  = [];
        $parents   = [];
        $teachers  = [];
        $all       = [];

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

        $reqCycle   = (string) ($payload['billing_cycle'] ?? ($subRow['billing_cycle'] ?? 'Monthly'));
        $quote      = $this->resolveCourseFeeForCountry($nodeId, $country, $reqCycle, $studentId);
        $cycle      = $subRow['billing_cycle'] ?? $quote['billing_cycle'];
        $batchStart = ! empty($subRow['batch_start_date']) && $subRow['batch_start_date'] !== '0000-00-00'
            ? (string) $subRow['batch_start_date']
            : (string) $quote['batch_start_date'];
        $batchEnd   = ! empty($subRow['batch_end_date']) && $subRow['batch_end_date'] !== '0000-00-00'
            ? (string) $subRow['batch_end_date']
            : (string) $quote['batch_end_date'];

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

        $priorPaidCount = (int) ($subRow['paid_cycles_count'] ?? 0);
        if ($priorPaidCount === 0) {
            $priorPaidCount = (int) $this->db->table($this->table)
                ->where('student_user_id', $studentId)
                ->where('node_id', $nodeId)
                ->where('status', 'paid')
                ->countAllResults();
            if ($existingTx > 0) {
                $priorPaidCount = max(0, $priorPaidCount - 1);
            }
        }

        if ($priorPaidCount === 0 || $existingDueYmd === '' || $existingDueYmd <= $currentMonth1st) {
            $baseMonth1st = date('Y-m-01', strtotime($batchStart));
            if ($baseMonth1st < $currentMonth1st) {
                $baseMonth1st = $currentMonth1st;
            }
            if ($existingDueYmd !== '' && $existingDueYmd > $baseMonth1st) {
                $baseMonth1st = date('Y-m-01', strtotime($existingDueYmd));
            }
        } else {
            $baseMonth1st = date('Y-m-01', strtotime($existingDueYmd));
        }

        $coveredMonthLabel = date('M Y', strtotime($baseMonth1st));
        $nextDueYmd        = date('Y-m-01', strtotime($baseMonth1st . ' +' . $monthsToAdd . ' months'));

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

        $baseTitle = self::cleanText(trim((string) ($payload['invoice_title'] ?? ($quote['course_title'] . ' - ' . $cycle . ' Fee'))));
        $invTitle  = (stripos($baseTitle, $coveredMonthLabel) === false && $invId === 0)
            ? ($baseTitle . ' (' . $coveredMonthLabel . ')')
            : $baseTitle;

        $txnRef = trim((string) ($payload['txn_reference'] ?? ''));
        $now    = $this->nowIst();

        if ($subRow) {
            $this->db->table($this->subsTable)->where('id', $subId)->update([
                'batch_start_date'    => $batchStart,
                'batch_end_date'      => $batchEnd,
                'due_date'            => $nextDueYmd,
                'paid_cycles_count'   => $priorPaidCount + 1,
                'admin_lock_override' => 0,
                'is_access_locked'    => 0,
                'status'              => $subStatus,
                'updated_at'          => $now,
            ]);
        } elseif ($studentId > 0) {
            $this->db->table($this->subsTable)->insert([
                'student_user_id'     => $studentId,
                'parent_user_id'      => $parentId,
                'node_id'             => $nodeId,
                'billing_cycle'       => $cycle,
                'country_code'        => $country,
                'currency'            => $currency,
                'fee_amount'          => $amount,
                'batch_start_date'    => $batchStart,
                'batch_end_date'      => $batchEnd,
                'due_date'            => $nextDueYmd,
                'grace_period_days'   => 5,
                'paid_cycles_count'   => 1,
                'admin_lock_override' => 0,
                'is_access_locked'    => 0,
                'status'              => $subStatus,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
            $subId = (int) $this->db->insertID();
        }

        if ($this->db->tableExists('lms_hierarchy_enrollments') && $studentId > 0 && $nodeId > 0) {
            $this->db->table('lms_hierarchy_enrollments')
                ->where('student_user_id', $studentId)
                ->where('leaf_node_id', $nodeId)
                ->update(['next_due_date' => $nextDueYmd, 'status' => 'active', 'updated_at' => $now]);
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
            'verification_note'  => self::cleanText((string) ($payload['verification_note'] ?? ('Paid for ' . $coveredMonthLabel))),
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
            'message'       => 'Payment verified for ' . $coveredMonthLabel . '! Batch access is active & Next Due Date is ' . $nextDueYmd . '.',
        ];
    }

    public function recordFailedPaymentTransaction(array $payload): int
    {
        $studentId = (int) ($payload['student_user_id'] ?? 0);
        $parentId  = (int) ($payload['parent_user_id'] ?? 0);
        $subId     = (int) ($payload['subscription_id'] ?? 0);
        $invId     = (int) ($payload['invoice_id'] ?? ($payload['custom_invoice_id'] ?? 0));
        $nodeId    = (int) ($payload['node_id'] ?? 0);

        $users = $this->getRoleUsersWithMeta()['all'];
        if ($parentId <= 0 && isset($users[$studentId])) {
            $parentId = (int) ($users[$studentId]['parent_user_id'] ?? 0);
        }
        if ($parentId <= 0) {
            $parentId = (int) (session()->get('user_id') ?: (session()->get('id') ?: 0));
        }

        $payerUser  = $users[$parentId] ?? ($users[$studentId] ?? []);
        $payerName  = trim((string) ($payload['payer_name'] ?? ($payerUser['display_name'] ?? (session()->get('full_name') ?: 'Parent'))));
        $payerEmail = trim((string) ($payload['payer_email'] ?? ($payerUser['email'] ?? (session()->get('email') ?: ''))));
        $country    = strtoupper(trim((string) ($payload['country_code'] ?? ($payerUser['country_code'] ?? 'IN')))) ?: 'IN';

        $subRow = null;
        if ($subId > 0) {
            $subRow = $this->db->table($this->subsTable)->where('id', $subId)->get()->getRowArray();
        } elseif ($studentId > 0) {
            $subRow = $this->db->table($this->subsTable)->where('student_user_id', $studentId)->orderBy('id', 'DESC')->get()->getRowArray();
        }
        if ($subRow) {
            $subId  = (int) $subRow['id'];
            $nodeId = $nodeId > 0 ? $nodeId : (int) $subRow['node_id'];
        }

        $quote    = $this->resolveCourseFeeForCountry($nodeId, $country, 'monthly', $studentId);
        $amount   = (float) ($payload['amount'] ?? ($subRow['fee_amount'] ?? $quote['amount']));
        $currency = strtoupper(trim((string) ($payload['currency'] ?? ($subRow['currency'] ?? $quote['currency'])))) ?: 'INR';
        $title    = self::cleanText(trim((string) ($payload['invoice_title'] ?? ($quote['course_title'] . ' - Failed Payment Attempt'))));
        $reason   = self::cleanText(trim((string) ($payload['error_reason'] ?? ($payload['reason'] ?? 'Payment failed or cancelled at gateway'))));
        $ordId    = trim((string) ($payload['razorpay_order_id'] ?? ''));
        $payId    = trim((string) ($payload['razorpay_payment_id'] ?? ''));
        $baseRef  = $payId !== '' ? $payId : ($ordId !== '' ? $ordId : ('FAIL-' . date('ymdHis')));
        $txnRef   = $baseRef . '-' . rand(100, 999);
        $now      = $this->nowIst();

        $this->db->table($this->table)->insert([
            'subscription_id'    => $subId,
            'invoice_id'         => $invId,
            'student_user_id'    => $studentId,
            'parent_user_id'     => $parentId,
            'node_id'            => $nodeId,
            'payer_name'         => $payerName,
            'payer_email'        => $payerEmail,
            'invoice_title'      => $title,
            'billing_cycle'      => $subRow['billing_cycle'] ?? $quote['billing_cycle'],
            'batch_start_date'   => $subRow['batch_start_date'] ?? $quote['batch_start_date'],
            'batch_end_date'     => $subRow['batch_end_date'] ?? $quote['batch_end_date'],
            'next_due_date'      => $subRow['due_date'] ?? date('Y-m-01'),
            'provider'           => 'razorpay_failed',
            'txn_reference'      => $txnRef,
            'razorpay_order_id'  => $ordId,
            'razorpay_signature' => '',
            'country_code'       => $country,
            'currency'           => $currency,
            'subtotal_amount'    => $amount,
            'amount'             => $amount,
            'amount_inr_equiv'   => $this->convertToInrEquiv($amount, $currency),
            'status'             => 'failed',
            'verification_note'  => 'Failed Attempt: ' . $reason,
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);

        return (int) $this->db->insertID();
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
        $title    = $quote['course_title'] . ' - Offline Payment Proof';

        if ($invId > 0 && $this->db->tableExists($this->invoicesTable)) {
            $inv = $this->db->table($this->invoicesTable)->where('id', $invId)->get()->getRowArray();
            if ($inv) {
                $amount   = (float) $inv['total_amount'];
                $currency = (string) $inv['currency'];
                $title    = (string) $inv['title'];
                $this->db->table($this->invoicesTable)->where('id', $invId)->update(['status' => 'pending_verification']);
            }
        }

        $now = $this->nowIst();
        $this->db->table($this->table)->insert([
            'subscription_id'   => $subId,
            'invoice_id'        => $invId,
            'student_user_id'   => $studentId,
            'parent_user_id'    => $parentId,
            'node_id'           => $nodeId,
            'payer_name'        => $parentRow['display_name'] ?? 'Parent',
            'payer_email'       => $parentRow['email'] ?? '',
            'invoice_title'     => self::cleanText($title),
            'billing_cycle'     => $quote['billing_cycle'],
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

        return ['ok' => true, 'message' => 'Payment proof (UTR: ' . $utrRef . ') submitted! Once verified by Admin, your batch access & receipt will unlock.'];
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
            'updated_at'        => $this->nowIst(),
        ]);
        if ((int) $tx['invoice_id'] > 0 && $this->db->tableExists($this->invoicesTable)) {
            $this->db->table($this->invoicesTable)->where('id', (int) $tx['invoice_id'])->update(['status' => 'unpaid']);
        }
        return ['ok' => true, 'message' => 'Offline payment proof marked as Rejected.'];
    }

    public function saveOrUpdateSubscription(array $post): array
    {
        $studentId = (int) ($post['student_user_id'] ?? 0);
        $nodeId    = (int) ($post['node_id'] ?? 0);
        $cycle     = trim((string) ($post['billing_cycle'] ?? 'Monthly')) ?: 'Monthly';
        $graceDays = max(0, min(60, (int) ($post['grace_period_days'] ?? 5)));

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

        $initDueYmd = date('Y-m-01', strtotime($batchStart));
        if ($initDueYmd < date('Y-m-01')) {
            $initDueYmd = date('Y-m-01');
        }
        $now = $this->nowIst();

        $this->db->table($this->subsTable)->insert([
            'student_user_id'     => $studentId,
            'parent_user_id'      => $parentId,
            'node_id'             => $nodeId,
            'billing_cycle'       => $quote['billing_cycle'],
            'country_code'        => $country,
            'currency'            => $quote['currency'],
            'fee_amount'          => $customFee,
            'batch_start_date'    => $batchStart,
            'batch_end_date'      => $batchEnd,
            'due_date'            => $initDueYmd,
            'grace_period_days'   => $graceDays,
            'paid_cycles_count'   => 0,
            'admin_lock_override' => 0,
            'is_access_locked'    => 1,
            'status'              => 'active',
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        return ['ok' => true, 'message' => 'Subscription created (' . $quote['billing_cycle'] . ' | First Payment Due: ' . $initDueYmd . ')!'];
    }

    public function createCustomParentChildInvoice(array $post): array
    {
        $parentId  = (int) ($post['parent_user_id'] ?? 0);
        $studentId = (int) ($post['student_user_id'] ?? 0);
        $title     = self::cleanText(trim((string) ($post['title'] ?? '')));
        $remarks   = self::cleanText(trim((string) ($post['remarks'] ?? '')));
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
        $now    = $this->nowIst();

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

        return ['ok' => true, 'message' => 'Custom Fee Invoice (' . $currency . ' ' . number_format($total, 2) . ' | Due: ' . $dueDate . ') sent to Parent Portal!'];
    }

    public function savePayoutOrExpense(array $post): array
    {
        $type      = in_array($post['expense_type'] ?? '', ['teacher_payout', 'operational_expense'], true) ? $post['expense_type'] : 'teacher_payout';
        $teacherId = (int) ($post['teacher_user_id'] ?? 0);
        $title     = self::cleanText(trim((string) ($post['title'] ?? '')));
        $monthYear = trim((string) ($post['month_year'] ?? date('M Y')));
        $classes   = max(0, (int) ($post['classes_taught'] ?? 0));
        $currency  = strtoupper(trim((string) ($post['currency'] ?? 'INR'))) ?: 'INR';
        $amount    = max(1.0, (float) ($post['amount'] ?? 0));
        $mode      = trim((string) ($post['payment_mode'] ?? 'Bank Transfer / UPI'));
        $refNo     = trim((string) ($post['reference_no'] ?? ('PAY-' . date('ymd') . '-' . rand(10, 99))));
        $notes     = self::cleanText(trim((string) ($post['notes'] ?? '')));

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
            'created_at'      => $this->nowIst(),
        ]);

        return ['ok' => true, 'message' => 'Recorded ' . ($type === 'teacher_payout' ? 'Teacher Salary Slip' : 'Expense') . ' (' . $currency . ' ' . number_format($amount, 2) . ')!'];
    }
}

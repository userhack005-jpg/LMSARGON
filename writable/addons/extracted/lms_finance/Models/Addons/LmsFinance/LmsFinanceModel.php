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
        'txn_reference',
        'provider',
        'invoice_id',
        'parent_user_id',
        'student_user_id',
        'node_id',
        'billing_cycle',
        'invoice_title',
        'payer_name',
        'payer_email',
        'subtotal_amount',
        'tax_percent',
        'tax_amount',
        'amount',
        'currency',
        'amount_inr_equiv',
        'proof_file_url',
        'verification_note',
        'batch_start_date',
        'batch_end_date',
        'next_due_date',
        'invoice_pdf_path',
        'status',
        'created_at',
        'updated_at',
    ];

    protected string $subsTable     = 'lms_student_subscriptions';
    protected string $invoicesTable = 'lms_finance_invoices';
    protected string $payoutsTable  = 'lms_teacher_payouts';
    protected string $expensesTable = 'lms_general_expenses';
    protected string $lockCacheFile = WRITEPATH . 'addons/lms_finance_locks_cache.json';

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
            if (! $this->db->tableExists('payment_transactions')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `payment_transactions` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `txn_reference` VARCHAR(90) NOT NULL,
                        `provider` VARCHAR(50) NOT NULL DEFAULT 'razorpay',
                        `invoice_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `parent_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `student_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `billing_cycle` VARCHAR(30) NOT NULL DEFAULT 'Monthly',
                        `invoice_title` VARCHAR(190) NOT NULL DEFAULT 'Course Fee',
                        `payer_name` VARCHAR(120) NOT NULL DEFAULT '',
                        `payer_email` VARCHAR(190) NOT NULL DEFAULT '',
                        `subtotal_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        `tax_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                        `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
                        `amount_inr_equiv` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        `proof_file_url` VARCHAR(255) NOT NULL DEFAULT '',
                        `verification_note` VARCHAR(255) NOT NULL DEFAULT '',
                        `batch_start_date` DATE NULL,
                        `batch_end_date` DATE NULL,
                        `next_due_date` DATE NULL,
                        `invoice_pdf_path` VARCHAR(255) NOT NULL DEFAULT '',
                        `status` VARCHAR(30) NOT NULL DEFAULT 'paid',
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        KEY `idx_student_status` (`student_user_id`, `status`),
                        KEY `idx_parent` (`parent_user_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } else {
                $pCols = $this->db->getFieldNames('payment_transactions');
                $alterCols = [
                    'invoice_id'        => "ADD COLUMN `invoice_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'parent_user_id'    => "ADD COLUMN `parent_user_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'student_user_id'   => "ADD COLUMN `student_user_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'node_id'           => "ADD COLUMN `node_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'billing_cycle'     => "ADD COLUMN `billing_cycle` VARCHAR(30) NOT NULL DEFAULT 'Monthly'",
                    'invoice_title'     => "ADD COLUMN `invoice_title` VARCHAR(190) NOT NULL DEFAULT 'Course Fee'",
                    'subtotal_amount'   => "ADD COLUMN `subtotal_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00",
                    'tax_percent'       => "ADD COLUMN `tax_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00",
                    'tax_amount'        => "ADD COLUMN `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00",
                    'amount_inr_equiv'  => "ADD COLUMN `amount_inr_equiv` DECIMAL(10,2) NOT NULL DEFAULT 0.00",
                    'proof_file_url'    => "ADD COLUMN `proof_file_url` VARCHAR(255) NOT NULL DEFAULT ''",
                    'verification_note' => "ADD COLUMN `verification_note` VARCHAR(255) NOT NULL DEFAULT ''",
                    'batch_start_date'  => "ADD COLUMN `batch_start_date` DATE NULL",
                    'batch_end_date'    => "ADD COLUMN `batch_end_date` DATE NULL",
                    'next_due_date'     => "ADD COLUMN `next_due_date` DATE NULL",
                    'invoice_pdf_path'  => "ADD COLUMN `invoice_pdf_path` VARCHAR(255) NOT NULL DEFAULT ''",
                    'updated_at'        => "ADD COLUMN `updated_at` DATETIME NULL",
                ];
                foreach ($alterCols as $colName => $sqlFrag) {
                    if (! in_array($colName, $pCols, true)) {
                        $this->db->query("ALTER TABLE `payment_transactions` " . $sqlFrag);
                    }
                }
            }

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_student_subscriptions` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `student_user_id` INT UNSIGNED NOT NULL,
                    `parent_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `billing_cycle` VARCHAR(30) NOT NULL DEFAULT 'Monthly',
                    `country_code` VARCHAR(10) NOT NULL DEFAULT 'IN',
                    `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
                    `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 1499.00,
                    `batch_start_date` DATE NULL,
                    `batch_end_date` DATE NULL,
                    `due_date` DATE NOT NULL,
                    `grace_period_days` TINYINT UNSIGNED NOT NULL DEFAULT 5,
                    `is_access_locked` TINYINT(1) NOT NULL DEFAULT 0,
                    `last_reminder_sent_at` DATETIME NULL,
                    `last_paid_txn_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `status` VARCHAR(25) NOT NULL DEFAULT 'active',
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    UNIQUE KEY `uniq_stu_node_sub` (`student_user_id`, `node_id`),
                    KEY `idx_due_lock` (`due_date`, `is_access_locked`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $sCols = $this->db->getFieldNames('lms_student_subscriptions');
            if (! in_array('batch_start_date', $sCols, true)) {
                $this->db->query("ALTER TABLE `lms_student_subscriptions` ADD COLUMN `batch_start_date` DATE NULL AFTER `fee_amount`");
            }
            if (! in_array('batch_end_date', $sCols, true)) {
                $this->db->query("ALTER TABLE `lms_student_subscriptions` ADD COLUMN `batch_end_date` DATE NULL AFTER `batch_start_date`");
            }

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_finance_invoices` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `invoice_no` VARCHAR(60) NOT NULL,
                    `invoice_type` VARCHAR(30) NOT NULL DEFAULT 'course_cycle',
                    `subscription_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `parent_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `student_user_id` INT UNSIGNED NOT NULL,
                    `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `title` VARCHAR(190) NOT NULL,
                    `remarks` VARCHAR(255) NOT NULL DEFAULT '',
                    `country_code` VARCHAR(10) NOT NULL DEFAULT 'IN',
                    `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
                    `subtotal_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `tax_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `billing_cycle` VARCHAR(30) NOT NULL DEFAULT 'Monthly',
                    `cycle_month_label` VARCHAR(40) NOT NULL DEFAULT '',
                    `due_date` DATE NOT NULL,
                    `status` VARCHAR(30) NOT NULL DEFAULT 'unpaid',
                    `paid_txn_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_inv_stu_status` (`student_user_id`, `status`),
                    KEY `idx_inv_parent` (`parent_user_id`, `status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_teacher_payouts` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `teacher_user_id` INT UNSIGNED NOT NULL,
                    `pay_period_label` VARCHAR(60) NOT NULL DEFAULT 'Oct 2026',
                    `scheduled_classes_cnt` INT UNSIGNED NOT NULL DEFAULT 0,
                    `present_classes_cnt` INT UNSIGNED NOT NULL DEFAULT 0,
                    `missed_classes_cnt` INT UNSIGNED NOT NULL DEFAULT 0,
                    `substitute_taken_cnt` INT UNSIGNED NOT NULL DEFAULT 0,
                    `unavailability_req_cnt` INT UNSIGNED NOT NULL DEFAULT 0,
                    `fixed_base_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `manual_bonus` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `manual_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `net_payout_inr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `payment_reference` VARCHAR(120) NOT NULL DEFAULT '',
                    `admin_remarks` VARCHAR(255) NOT NULL DEFAULT '',
                    `status` VARCHAR(25) NOT NULL DEFAULT 'paid',
                    `paid_at` DATETIME NULL,
                    `created_at` DATETIME NULL,
                    KEY `idx_teacher_period` (`teacher_user_id`, `pay_period_label`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_general_expenses` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `expense_category` VARCHAR(60) NOT NULL DEFAULT 'Software & Cloud',
                    `title` VARCHAR(160) NOT NULL,
                    `amount_inr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `expense_date` DATE NOT NULL,
                    `vendor_name` VARCHAR(120) NOT NULL DEFAULT '',
                    `notes` VARCHAR(255) NOT NULL DEFAULT '',
                    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NULL,
                    KEY `idx_exp_date` (`expense_date`, `expense_category`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $lockFile = WRITEPATH . 'addons/schema_addon_lms_finance_v81.lock';
            if (is_file($lockFile) && is_file($this->lockCacheFile)) {
                return;
            }

            if ($this->db->tableExists('addons')) {
                $this->db->table('addons')
                    ->where('slug', 'lms_finance')
                    ->update(['name' => 'Fee Billing, Razorpay & Payroll']);
            }

            $this->syncRbacPermissionsAndAutoEnableRoles();
            $this->mirrorAssets(true);
            $this->syncSubscriptionsAndAutoInvoices();
            $this->evaluateGracePeriodLocks();

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, date('c'));
        } catch (Throwable $e) {
            log_message('error', 'LmsFinanceModel schema error: ' . $e->getMessage());
        }
    }

    public function syncRbacPermissionsAndAutoEnableRoles(): void
    {
        try {
            $now = date('Y-m-d H:i:s');
            if ($this->db->tableExists('cms_rbac_features')) {
                $perms = [
                    ['lms_finance.view',         'Access Family Fee Portal & Receipts [Parent + Student View-Only]'],
                    ['lms_finance.teacher_view', 'View My Monthly Salary Slips (View-Only) [Teacher]'],
                    ['lms_finance.collect',      'Manage Invoices, Approve Offline Proofs, Mark Paid & Custom Billing [Super Admin + Admin]'],
                    ['lms_finance.payroll',      'Disburse & Manage Teacher Salaries [Super Admin + Admin]'],
                    ['lms_finance.expenses',     'Manage Expenses, Razorpay Keys & Receipt Template Designer [Super Admin + Admin]'],
                    ['lms_finance.delete',       'Delete Invoices & Expense Entries [Super Admin + Admin]'],
                ];
                foreach ($perms as $p) {
                    $this->db->query("
                        INSERT INTO `cms_rbac_features` (`perm_key`, `perm_label`, `module_group`, `source_type`, `created_at`)
                        VALUES (?, ?, 'Add-on: Fee Billing, Razorpay & Payroll', 'addon', ?)
                        ON DUPLICATE KEY UPDATE `perm_label` = VALUES(`perm_label`), `module_group` = VALUES(`module_group`)
                    ", [$p[0], $p[1], $now]);
                }
            }

            if ($this->db->tableExists('cms_roles')) {
                $roleAutoGrants = [
                    'parent'  => ['lms_finance.view'],
                    'student' => ['lms_finance.view'],
                    'teacher' => ['lms_finance.teacher_view'],
                    'admin'   => ['lms_finance.view', 'lms_finance.collect', 'lms_finance.payroll', 'lms_finance.expenses', 'lms_finance.delete'],
                ];

                $roles = $this->db->table('cms_roles')->get()->getResultArray();
                foreach ($roles as $r) {
                    $rSlug = strtolower(trim((string) ($r['role_slug'] ?? '')));
                    if (! isset($roleAutoGrants[$rSlug])) {
                        continue;
                    }
                    $currPerms = json_decode((string) ($r['permissions_json'] ?? '[]'), true);
                    if (! is_array($currPerms)) {
                        $currPerms = [];
                    }
                    $changed = false;
                    foreach ($roleAutoGrants[$rSlug] as $grantKey) {
                        if (! in_array($grantKey, $currPerms, true) && ! in_array('*', $currPerms, true)) {
                            $currPerms[] = $grantKey;
                            $changed     = true;
                        }
                    }
                    if ($changed) {
                        $this->db->table('cms_roles')->where('id', $r['id'])->update([
                            'permissions_json' => json_encode(array_values(array_unique($currPerms))),
                            'updated_at'       => $now,
                        ]);
                    }
                }
            }

            $rbacModelFile = WRITEPATH . 'addons/extracted/rbac/Models/Addons/Rbac/RbacModel.php';
            if (is_file($rbacModelFile)) {
                require_once $rbacModelFile;
                if (class_exists('\App\Models\Addons\Rbac\RbacModel')) {
                    (new \App\Models\Addons\Rbac\RbacModel())->rebuildStaticCache();
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
                if (is_file($sFile)) {
                    if ($forceTouch || ! is_file($dFile) || md5_file($sFile) !== md5_file($dFile)) {
                        @copy($sFile, $dFile);
                        @touch($dFile, time());
                    }
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

    public function getFinanceSetting(string $key, string $default = ''): string
    {
        $scopedKey = 'lms_finance__' . $key;
        $s = $this->getGlobalSiteSettings();
        if (isset($s[$scopedKey]) && $s[$scopedKey] !== '') {
            return (string) $s[$scopedKey];
        }
        if (isset($s[$key]) && $s[$key] !== '') {
            return (string) $s[$key];
        }
        return $default;
    }

    public function saveFinanceSetting(string $key, string $val): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }
        $scopedKey = (strpos($key, 'lms_finance__') === 0) ? $key : ('lms_finance__' . $key);
        $this->db->query("
            INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`)
            VALUES (?, 'lms_finance', ?)
            ON DUPLICATE KEY UPDATE `setting_group` = 'lms_finance', `setting_value` = VALUES(`setting_value`)
        ", [$scopedKey, $val]);
        self::$globalSettingsCache = null;
    }

    public function getGatewaysConfig(): array
    {
        $raw = $this->getFinanceSetting('gateways_json', '');
        $saved = $raw !== '' ? (json_decode($raw, true) ?: []) : [];

        $defaults = [
            'razorpay' => [
                'slug'            => 'razorpay',
                'name'            => 'Razorpay Checkout (Cards, UPI, Netbanking & International)',
                'is_enabled'      => 1,
                'mode'            => 'sandbox',
                'test_key_id'     => 'rzp_test_1DP5mmOlF5G5ag',
                'test_key_secret' => '',
                'live_key_id'     => '',
                'live_key_secret' => '',
            ],
            'stripe' => [
                'slug'            => 'stripe',
                'name'            => 'Stripe Global Checkout',
                'is_enabled'      => 0,
                'mode'            => 'sandbox',
                'test_key_id'     => '',
                'test_key_secret' => '',
                'live_key_id'     => '',
                'live_key_secret' => '',
            ],
        ];

        foreach ($defaults as $k => $def) {
            if (isset($saved[$k]) && is_array($saved[$k])) {
                $defaults[$k] = array_merge($def, $saved[$k]);
            }
        }
        return $defaults;
    }

    public function getActiveRazorpayConfig(): array
    {
        $gateways = $this->getGatewaysConfig();
        $rzp      = $gateways['razorpay'];
        $mode     = ($rzp['mode'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox';
        $keyId    = ($mode === 'live') ? trim((string) $rzp['live_key_id']) : trim((string) $rzp['test_key_id']);
        $secret   = ($mode === 'live') ? trim((string) $rzp['live_key_secret']) : trim((string) $rzp['test_key_secret']);

        return [
            'is_enabled' => (int) ($rzp['is_enabled'] ?? 1) === 1,
            'mode'       => $mode,
            'key_id'     => $keyId,
            'key_secret' => $secret,
        ];
    }

    public function getReceiptTemplateConfig(): array
    {
        $siteSettings = $this->getGlobalSiteSettings();
        $raw          = $this->getFinanceSetting('receipt_template_json', '');
        $saved        = $raw !== '' ? (json_decode($raw, true) ?: []) : [];

        return array_merge([
            'academy_name'    => $siteSettings['site_title'] ?? 'Online Abacus & Mental Math Academy',
            'logo_url'        => $siteSettings['site_logo'] ?? '',
            'accent_color'    => '#10b981',
            'secondary_color' => '#0f172a',
            'tax_label'       => 'GST / Tax Reg No.',
            'tax_number'      => 'GSTIN-VERIFIED-ACADEMY',
            'support_email'   => 'billing@academy.portal',
            'support_phone'   => '+91 98765 43210',
            'address_line'    => 'Global Online Learning Center',
            'signature_name'  => 'Authorized Signatory',
            'footer_note'     => 'Thank you for your payment! This is a computer-generated official fee receipt.',
        ], $saved);
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
            'IN' => ['code' => 'IN', 'name' => 'India', 'flag' => '🇮🇳', 'currency_code' => 'INR', 'currency_sym' => '₹', 'is_enabled' => 1],
            'AE' => ['code' => 'AE', 'name' => 'UAE',   'flag' => '🇦🇪', 'currency_code' => 'AED', 'currency_sym' => 'AED ', 'is_enabled' => 1],
            'US' => ['code' => 'US', 'name' => 'USA',   'flag' => '🇺🇸', 'currency_code' => 'USD', 'currency_sym' => '$', 'is_enabled' => 1],
            'GB' => ['code' => 'GB', 'name' => 'UK',    'flag' => '🇬🇧', 'currency_code' => 'GBP', 'currency_sym' => '£', 'is_enabled' => 1],
        ];
    }

    public function getRoleFilteredUsers(): array
    {
        if (! $this->db->tableExists('users')) {
            return ['teachers' => [], 'students' => [], 'parents' => [], 'children_by_parent' => [], 'all' => []];
        }

        $countries = $this->getEnabledCountriesOnly();
        $metas     = [];
        if ($this->db->tableExists('cms_user_meta')) {
            foreach ($this->db->table('cms_user_meta')->get()->getResultArray() as $m) {
                $metas[(int) $m['user_id']] = $m;
            }
        }

        $teacherSlugs = ['teacher'];
        $studentSlugs = ['student', 'website_user'];
        $parentSlugs  = ['parent'];

        $authCache = WRITEPATH . 'addons/core_lms_auth_cache.json';
        if (is_file($authCache)) {
            $c = json_decode((string) @file_get_contents($authCache), true);
            if (! empty($c['role_map']['teacher'])) $teacherSlugs = $c['role_map']['teacher'];
            if (! empty($c['role_map']['student'])) $studentSlugs = $c['role_map']['student'];
            if (! empty($c['role_map']['parent']))  $parentSlugs  = $c['role_map']['parent'];
        }

        $rows             = $this->db->table('users')->orderBy('id', 'ASC')->get()->getResultArray();
        $teachers         = [];
        $students         = [];
        $parents          = [];
        $childrenByParent = [];
        $all              = [];

        foreach ($rows as $r) {
            $id       = (int) $r['id'];
            $roleId   = (int) ($r['role_id'] ?? 4);
            $rSlug    = strtolower(trim((string) ($r['role_slug'] ?? 'student')));
            $parentId = (int) ($r['parent_user_id'] ?? 0);

            $uMeta    = $metas[$id] ?? ($parentId > 0 && isset($metas[$parentId]) ? $metas[$parentId] : []);
            $cCode    = strtoupper(trim((string) ($uMeta['country_code'] ?? 'IN'))) ?: 'IN';
            $currCode = strtoupper(trim((string) ($uMeta['currency_code'] ?? ($countries[$cCode]['currency_code'] ?? 'INR'))));
            $currSym  = $countries[$cCode]['currency_sym'] ?? ($currCode . ' ');

            $r['display_name']  = $r['full_name'] ?? ($r['name'] ?? ($r['username'] ?? ($r['email'] ?? 'User')));
            $r['country_code']  = $cCode;
            $r['currency_code'] = $currCode;
            $r['currency_sym']  = $currSym;
            $all[$id]           = $r;

            if (in_array($roleId, [1, 2], true) || in_array($rSlug, ['super_admin', 'admin'], true)) {
                continue;
            }
            if ($roleId === 3 || in_array($rSlug, $teacherSlugs, true)) {
                $teachers[$id] = $r;
            } elseif (in_array($rSlug, $parentSlugs, true)) {
                $parents[$id] = $r;
            } elseif (in_array($rSlug, $studentSlugs, true)) {
                $students[$id] = $r;
                if ($parentId > 0) {
                    $childrenByParent[$parentId][] = $r;
                }
            }
        }

        foreach ($parents as $pid => &$pRow) {
            if ($pRow['country_code'] === 'IN' && ! empty($childrenByParent[$pid])) {
                foreach ($childrenByParent[$pid] as $ch) {
                    if ($ch['country_code'] !== 'IN') {
                        $pRow['country_code']  = $ch['country_code'];
                        $pRow['currency_code'] = $ch['currency_code'];
                        $pRow['currency_sym']  = $ch['currency_sym'];
                        $all[$pid]             = $pRow;
                        break;
                    }
                }
            }
        }
        unset($pRow);

        return [
            'teachers'           => $teachers,
            'students'           => $students,
            'parents'            => $parents,
            'children_by_parent' => $childrenByParent,
            'all'                => $all,
        ];
    }

    public function getLeafNodesWithPricing(): array
    {
        if (! $this->db->tableExists('lms_hierarchy_nodes')) {
            return [];
        }
        $pricingByNode = [];
        if ($this->db->tableExists('lms_hierarchy_pricing')) {
            foreach ($this->db->table('lms_hierarchy_pricing')->where('is_active', 1)->get()->getResultArray() as $pr) {
                $pricingByNode[(int) $pr['node_id']][strtoupper((string) $pr['country_code'])] = $pr;
            }
        }

        $cohortsByLeaf = [];
        if ($this->db->tableExists('lms_classroom_batches')) {
            foreach ($this->db->table('lms_classroom_batches')->where('status', 'active')->orderBy('id', 'DESC')->get()->getResultArray() as $cb) {
                $lid = (int) $cb['leaf_node_id'];
                if (! isset($cohortsByLeaf[$lid])) {
                    $cohortsByLeaf[$lid] = $cb;
                }
            }
        }

        $rows = $this->db->table('lms_hierarchy_nodes')
            ->where('is_leaf_node', 1)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $nid = (int) $r['id'];
            $r['pricing_by_country'] = $pricingByNode[$nid] ?? [];
            $r['active_cohort']      = $cohortsByLeaf[$nid] ?? null;
            $out[$nid]               = $r;
        }
        return $out;
    }

    public function resolveCourseFeeForCountry(int $nodeId, string $countryCode = 'IN', string $billingCycle = 'monthly'): array
    {
        $countryCode  = strtoupper(trim($countryCode)) ?: 'IN';
        $billingCycle = strtolower(trim($billingCycle)) ?: 'monthly';
        $countries    = $this->getEnabledCountriesOnly();
        $leaves       = $this->getLeafNodesWithPricing();
        $node         = $leaves[$nodeId] ?? null;

        $courseTitle  = $node['title'] ?? 'Enrolled Course';
        $cohort       = $node['active_cohort'] ?? null;
        $batchStart   = $cohort['start_date'] ?? date('Y-m-d');
        $batchEnd     = $cohort['end_date'] ?? date('Y-m-d', strtotime('+90 days'));

        $pMap  = $node['pricing_by_country'] ?? [];
        $pRow  = $pMap[$countryCode] ?? ($pMap['IN'] ?? reset($pMap) ?: []);
        $cInfo = $countries[$countryCode] ?? ($countries['IN'] ?? ['currency_code' => 'INR', 'currency_sym' => '₹']);

        $curr = ! empty($pRow['currency_code']) ? $pRow['currency_code'] : ($cInfo['currency_code'] ?? 'INR');
        $sym  = $countries[$countryCode]['currency_sym'] ?? ($curr . ' ');

        $amt = (float) ($pRow['monthly_fee'] ?? 1499.00);
        if ($billingCycle === 'quarterly' && (float) ($pRow['quarterly_fee'] ?? 0) > 0) {
            $amt = (float) $pRow['quarterly_fee'];
        } elseif (in_array($billingCycle, ['full', 'yearly', 'one-time'], true) && (float) ($pRow['full_course_fee'] ?? 0) > 0) {
            $amt = (float) $pRow['full_course_fee'];
        }

        if ($amt <= 0) {
            $amt = 1499.00;
        }

        $inrEquiv = $amt;
        if ($curr !== 'INR' && isset($pMap['IN']['monthly_fee']) && (float) $pMap['IN']['monthly_fee'] > 0) {
            $inrEquiv = (float) $pMap['IN']['monthly_fee'];
        }

        return [
            'node_id'          => $nodeId,
            'course_title'     => $courseTitle,
            'cohort_title'     => $cohort['cohort_title'] ?? $courseTitle,
            'batch_start_date' => $batchStart,
            'batch_end_date'   => $batchEnd,
            'billing_cycle'    => ucfirst($billingCycle),
            'country_code'     => $countryCode,
            'currency'         => $curr,
            'symbol'           => $sym,
            'amount'           => round($amt, 2),
            'amount_inr_equiv' => round($inrEquiv, 2),
        ];
    }

    public function syncSubscriptionsAndAutoInvoices(): void
    {
        try {
            $roleUsers = $this->getRoleFilteredUsers();
            $students  = $roleUsers['students'];
            $leaves    = $this->getLeafNodesWithPricing();
            $now       = gmdate('Y-m-d H:i:s');

            if ($this->db->tableExists('users')) {
                $this->db->query("DELETE s FROM `lms_student_subscriptions` s LEFT JOIN `users` u ON u.id = s.student_user_id WHERE u.id IS NULL");
                $this->db->query("DELETE i FROM `lms_finance_invoices` i LEFT JOIN `users` u ON u.id = i.student_user_id WHERE u.id IS NULL");
            }

            if ($this->db->tableExists('lms_hierarchy_enrollments')) {
                $enrs = $this->db->table('lms_hierarchy_enrollments')->where('status', 'active')->get()->getResultArray();
                foreach ($enrs as $enr) {
                    $sid = (int) $enr['student_user_id'];
                    if (! isset($students[$sid])) {
                        continue;
                    }
                    $lid      = (int) $enr['leaf_node_id'];
                    $parentId = (int) ($enr['parent_user_id'] ?: ($students[$sid]['parent_user_id'] ?? 0));
                    $cCode    = strtoupper((string) ($enr['country_code'] ?: ($students[$sid]['country_code'] ?? 'IN')));
                    $cycle    = (string) ($enr['billing_cycle'] ?: 'Monthly');
                    $quote    = $this->resolveCourseFeeForCountry($lid, $cCode, $cycle);
                    $feeAmt   = (float) ($enr['monthly_fee'] > 0 ? $enr['monthly_fee'] : $quote['amount']);

                    $startYmd = $quote['batch_start_date'] ?: substr((string) ($enr['enrolled_at'] ?: $now), 0, 10);
                    $dueDate  = ! empty($enr['next_due_date']) ? $enr['next_due_date'] : date('Y-m-d', strtotime($startYmd . ' +1 month'));

                    $exSub = $this->db->table($this->subsTable)
                        ->where('student_user_id', $sid)
                        ->where('node_id', $lid)
                        ->get()
                        ->getRowArray();

                    if (! $exSub) {
                        $this->db->table($this->subsTable)->insert([
                            'student_user_id'   => $sid,
                            'parent_user_id'    => $parentId,
                            'node_id'           => $lid,
                            'billing_cycle'     => ucfirst($cycle),
                            'country_code'      => $cCode,
                            'currency'          => $quote['currency'],
                            'fee_amount'        => $feeAmt,
                            'batch_start_date'  => $quote['batch_start_date'],
                            'batch_end_date'    => $quote['batch_end_date'],
                            'due_date'          => $dueDate,
                            'grace_period_days' => (int) $this->getFinanceSetting('grace_period_days', '5'),
                            'is_access_locked'  => 0,
                            'status'            => 'active',
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ]);
                    } else {
                        $this->db->table($this->subsTable)->where('id', $exSub['id'])->update([
                            'parent_user_id'   => $parentId,
                            'country_code'     => $cCode,
                            'currency'         => $quote['currency'],
                            'fee_amount'       => $feeAmt,
                            'batch_start_date' => $quote['batch_start_date'],
                            'batch_end_date'   => $quote['batch_end_date'],
                        ]);
                    }
                }
            }

            $autoDaysBefore = max(1, (int) $this->getFinanceSetting('auto_invoice_days', '5'));
            $thresholdDate  = date('Y-m-d', strtotime('+' . $autoDaysBefore . ' days'));

            $activeSubs = $this->db->table($this->subsTable)->where('status', 'active')->get()->getResultArray();
            foreach ($activeSubs as $sub) {
                $subId  = (int) $sub['id'];
                $stuId  = (int) $sub['student_user_id'];
                $parId  = (int) $sub['parent_user_id'];
                $nid    = (int) $sub['node_id'];
                $dueYmd = (string) $sub['due_date'];

                if ($dueYmd <= $thresholdDate) {
                    $cycleLabel = date('M Y', strtotime($dueYmd));
                    $exInv = $this->db->table($this->invoicesTable)
                        ->where('subscription_id', $subId)
                        ->where('due_date', $dueYmd)
                        ->get()
                        ->getRowArray();

                    if (! $exInv) {
                        $cTitle = $leaves[$nid]['title'] ?? 'Course Fee';
                        $invNo  = 'INV-' . date('Ym') . '-' . $stuId . '-' . rand(100, 999);
                        $amt    = (float) $sub['fee_amount'];

                        $this->db->table($this->invoicesTable)->insert([
                            'invoice_no'        => $invNo,
                            'invoice_type'      => 'course_cycle',
                            'subscription_id'   => $subId,
                            'parent_user_id'    => $parId,
                            'student_user_id'   => $stuId,
                            'node_id'           => $nid,
                            'title'             => $cTitle . ' — ' . ($sub['billing_cycle'] ?: 'Monthly') . ' Fee (' . $cycleLabel . ')',
                            'remarks'           => 'Auto-generated 5 days before cycle due date (' . $dueYmd . ')',
                            'country_code'      => $sub['country_code'] ?: 'IN',
                            'currency'          => $sub['currency'] ?: 'INR',
                            'subtotal_amount'   => $amt,
                            'tax_percent'       => 0.00,
                            'tax_amount'        => 0.00,
                            'total_amount'      => $amt,
                            'billing_cycle'     => $sub['billing_cycle'] ?: 'Monthly',
                            'cycle_month_label' => $cycleLabel,
                            'due_date'          => $dueYmd,
                            'status'            => 'unpaid',
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ]);
                    }
                }
            }
        } catch (Throwable $e) {
        }
    }

    public function evaluateGracePeriodLocks(): array
    {
        $autoLockOn   = $this->getFinanceSetting('auto_lock_overdue', '1') === '1';
        $defaultGrace = max(0, (int) $this->getFinanceSetting('grace_period_days', '5'));
        $todayYmd     = gmdate('Y-m-d');

        $subs        = $this->db->table($this->subsTable)->where('status', 'active')->get()->getResultArray();
        $lockedMap   = [];
        $lockedCount = 0;

        foreach ($subs as $s) {
            $subId     = (int) $s['id'];
            $stuId     = (int) $s['student_user_id'];
            $grace     = isset($s['grace_period_days']) ? (int) $s['grace_period_days'] : $defaultGrace;
            $cutoffYmd = date('Y-m-d', strtotime($s['due_date'] . ' +' . $grace . ' days'));

            $shouldLock = ($autoLockOn && $todayYmd > $cutoffYmd) ? 1 : 0;
            if ((int) $s['is_access_locked'] !== $shouldLock) {
                $this->db->table($this->subsTable)->where('id', $subId)->update([
                    'is_access_locked' => $shouldLock,
                    'updated_at'       => gmdate('Y-m-d H:i:s'),
                ]);
            }

            if ($shouldLock === 1) {
                $lockedMap[$stuId] = [
                    'student_user_id' => $stuId,
                    'due_date'        => $s['due_date'],
                    'cutoff_date'     => $cutoffYmd,
                    'amount'          => $s['fee_amount'],
                    'currency'        => $s['currency'],
                ];
                $lockedCount++;
            }
        }

        $cachePayload = [
            'updated_at'      => time(),
            'locked_students' => $lockedMap,
        ];
        @is_dir(dirname($this->lockCacheFile)) || @mkdir(dirname($this->lockCacheFile), 0775, true);
        @file_put_contents($this->lockCacheFile, json_encode($cachePayload, JSON_UNESCAPED_UNICODE));

        return ['locked_count' => $lockedCount, 'locked_students' => $lockedMap];
    }

    public function finalizePaidTransaction(array $params): array
    {
        $studentId  = (int) ($params['student_user_id'] ?? 0);
        $invoiceId  = (int) ($params['invoice_id'] ?? 0);
        $subId      = (int) ($params['subscription_id'] ?? 0);
        $nodeId     = (int) ($params['node_id'] ?? 0);
        $provider   = trim((string) ($params['provider'] ?? 'razorpay')) ?: 'razorpay';
        $txnRef     = trim((string) ($params['txn_reference'] ?? '')) ?: ('RCPT-' . strtoupper(bin2hex(random_bytes(3))) . '-' . date('His'));
        $proofUrl   = trim((string) ($params['proof_file_url'] ?? ''));
        $verNote    = trim((string) ($params['verification_note'] ?? 'Verified & Paid'));
        $existingTx = (int) ($params['existing_txn_id'] ?? 0);

        $users      = $this->getRoleFilteredUsers()['all'];
        $stuRow     = $users[$studentId] ?? null;
        if (! $stuRow) {
            return ['ok' => false, 'message' => 'Student account not found.'];
        }

        $parentId   = (int) ($params['parent_user_id'] ?? ($stuRow['parent_user_id'] ?? 0));
        $parentRow  = $users[$parentId] ?? $stuRow;
        $cCode      = $stuRow['country_code'] ?? ($parentRow['country_code'] ?? 'IN');

        $invoiceRow = null;
        if ($invoiceId > 0) {
            $invoiceRow = $this->db->table($this->invoicesTable)->where('id', $invoiceId)->get()->getRowArray();
            if ($invoiceRow) {
                $nodeId = (int) ($invoiceRow['node_id'] ?: $nodeId);
                $subId  = (int) ($invoiceRow['subscription_id'] ?: $subId);
            }
        }

        $subRow = null;
        if ($subId > 0) {
            $subRow = $this->db->table($this->subsTable)->where('id', $subId)->get()->getRowArray();
        } elseif ($studentId > 0 && $nodeId > 0) {
            $subRow = $this->db->table($this->subsTable)->where('student_user_id', $studentId)->where('node_id', $nodeId)->get()->getRowArray();
        }

        $quote      = $this->resolveCourseFeeForCountry($nodeId, $cCode, $subRow['billing_cycle'] ?? 'Monthly');
        $cycle      = $invoiceRow['billing_cycle'] ?? ($subRow['billing_cycle'] ?? $quote['billing_cycle']);
        $invTitle   = $invoiceRow['title'] ?? ($quote['course_title'] . ' — ' . $cycle . ' Fee');
        $currency   = strtoupper((string) ($params['currency'] ?? ($invoiceRow['currency'] ?? ($subRow['currency'] ?? $quote['currency']))));
        $subtotal   = (float) ($params['subtotal_amount'] ?? ($invoiceRow['subtotal_amount'] ?? ($subRow['fee_amount'] ?? $quote['amount'])));
        $taxPct     = (float) ($params['tax_percent'] ?? ($invoiceRow['tax_percent'] ?? 0.00));
        $taxAmt     = (float) ($params['tax_amount'] ?? ($invoiceRow['tax_amount'] ?? round($subtotal * ($taxPct / 100), 2)));
        $totalAmt   = (float) ($params['amount'] ?? ($invoiceRow['total_amount'] ?? round($subtotal + $taxAmt, 2)));
        $inrEquiv   = ($currency === 'INR') ? $totalAmt : $quote['amount_inr_equiv'];

        $batchStart = $subRow['batch_start_date'] ?? $quote['batch_start_date'];
        $batchEnd   = $subRow['batch_end_date'] ?? $quote['batch_end_date'];

        $baseDueYmd = ! empty($subRow['due_date']) && $subRow['due_date'] >= date('Y-m-d')
            ? $subRow['due_date']
            : date('Y-m-d');

        $nextDueYmd = date('Y-m-d', strtotime($baseDueYmd . ' +1 month'));
        if (stripos($cycle, 'quarter') !== false) {
            $nextDueYmd = date('Y-m-d', strtotime($baseDueYmd . ' +3 months'));
        } elseif (stripos($cycle, 'year') !== false || stripos($cycle, 'full') !== false) {
            $nextDueYmd = date('Y-m-d', strtotime($baseDueYmd . ' +1 year'));
        }

        $now = gmdate('Y-m-d H:i:s');
        $txPayload = [
            'txn_reference'     => $txnRef,
            'provider'          => $provider,
            'invoice_id'        => $invoiceId,
            'parent_user_id'    => $parentId,
            'student_user_id'   => $studentId,
            'node_id'           => $nodeId,
            'billing_cycle'     => $cycle,
            'invoice_title'     => $invTitle,
            'payer_name'        => $parentRow['display_name'] ?? 'Parent',
            'payer_email'       => $parentRow['email'] ?? '',
            'subtotal_amount'   => $subtotal,
            'tax_percent'       => $taxPct,
            'tax_amount'        => $taxAmt,
            'amount'            => $totalAmt,
            'currency'          => $currency,
            'amount_inr_equiv'  => $inrEquiv,
            'proof_file_url'    => $proofUrl,
            'verification_note' => $verNote,
            'batch_start_date'  => $batchStart,
            'batch_end_date'    => $batchEnd,
            'next_due_date'     => $nextDueYmd,
            'status'            => 'paid',
            'updated_at'        => $now,
        ];

        if ($existingTx > 0) {
            $this->db->table($this->table)->where('id', $existingTx)->update($txPayload);
            $txnId = $existingTx;
        } else {
            $txPayload['created_at'] = $now;
            $this->db->table($this->table)->insert($txPayload);
            $txnId = (int) $this->db->insertID();
        }

        $this->db->table($this->table)->where('id', $txnId)->update([
            'invoice_pdf_path' => 'finance/receipt-view/' . $txnId,
        ]);

        if ($invoiceId > 0) {
            $this->db->table($this->invoicesTable)->where('id', $invoiceId)->update([
                'status'      => 'paid',
                'paid_txn_id' => $txnId,
                'updated_at'  => $now,
            ]);
        }

        if ($subRow) {
            $this->db->table($this->subsTable)->where('id', $subRow['id'])->update([
                'due_date'         => $nextDueYmd,
                'is_access_locked' => 0,
                'last_paid_txn_id' => $txnId,
                'updated_at'       => $now,
            ]);

            $this->db->table($this->invoicesTable)
                ->where('subscription_id', $subRow['id'])
                ->where('status !=', 'paid')
                ->update(['status' => 'paid', 'paid_txn_id' => $txnId, 'updated_at' => $now]);
        } elseif ($nodeId > 0) {
            $this->db->table($this->subsTable)->insert([
                'student_user_id'   => $studentId,
                'parent_user_id'    => $parentId,
                'node_id'           => $nodeId,
                'billing_cycle'     => $cycle,
                'country_code'      => $cCode,
                'currency'          => $currency,
                'fee_amount'        => $totalAmt,
                'batch_start_date'  => $batchStart,
                'batch_end_date'    => $batchEnd,
                'due_date'          => $nextDueYmd,
                'grace_period_days' => (int) $this->getFinanceSetting('grace_period_days', '5'),
                'is_access_locked'  => 0,
                'last_paid_txn_id' => $txnId,
                'status'            => 'active',
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
        }

        if ($this->db->tableExists('lms_hierarchy_enrollments') && $nodeId > 0) {
            $this->db->table('lms_hierarchy_enrollments')
                ->where('student_user_id', $studentId)
                ->where('leaf_node_id', $nodeId)
                ->update(['next_due_date' => $nextDueYmd, 'updated_at' => $now]);
        }

        $this->evaluateGracePeriodLocks();

        return [
            'ok'           => true,
            'txn_id'       => $txnId,
            'receipt_url'  => base_url('finance/receipt-view/' . $txnId),
            'pdf_url'      => base_url('finance/receipt-pdf/' . $txnId),
            'next_due_ymd' => $nextDueYmd,
            'message'      => 'Payment of ' . $currency . ' ' . number_format($totalAmt, 2) . ' confirmed! Receipt generated & next due date set to ' . $nextDueYmd . '.',
        ];
    }

    public function submitOfflineProofTransaction(array $post, $uploadedProof): array
    {
        $studentId = (int) ($post['student_user_id'] ?? 0);
        $invoiceId = (int) ($post['invoice_id'] ?? 0);
        $subId     = (int) ($post['subscription_id'] ?? 0);
        $nodeId    = (int) ($post['node_id'] ?? 0);
        $txnRef    = trim((string) ($post['txn_reference'] ?? ''));

        if ($studentId <= 0) {
            return ['ok' => false, 'message' => 'Invalid student profile.'];
        }
        if ($txnRef === '') {
            return ['ok' => false, 'message' => 'Payment Reference / UTR Number is compulsory.'];
        }

        $proofUrl = '';
        if ($uploadedProof && $uploadedProof->isValid() && ! $uploadedProof->hasMoved()) {
            $ext = strtolower($uploadedProof->getClientExtension());
            if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'pdf'], true)) {
                return ['ok' => false, 'message' => 'Screenshot must be PNG, JPG, WEBP, or PDF.'];
            }
            $uploadDir = FCPATH . 'uploads/lms_finance/proofs/';
            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $newName = 'proof_stu' . $studentId . '_' . time() . '_' . $uploadedProof->getRandomName();
            $uploadedProof->move($uploadDir, $newName);
            $proofUrl = 'uploads/lms_finance/proofs/' . $newName;
        } else {
            return ['ok' => false, 'message' => 'Please upload your payment screenshot / receipt image.'];
        }

        $users     = $this->getRoleFilteredUsers()['all'];
        $stuRow    = $users[$studentId] ?? null;
        $parentId  = (int) ($stuRow['parent_user_id'] ?? (session()->get('user_id') ?: 0));
        $parentRow = $users[$parentId] ?? $stuRow;
        $cCode     = $stuRow['country_code'] ?? 'IN';

        $invRow = $invoiceId > 0 ? $this->db->table($this->invoicesTable)->where('id', $invoiceId)->get()->getRowArray() : null;
        $subRow = $subId > 0 ? $this->db->table($this->subsTable)->where('id', $subId)->get()->getRowArray() : null;
        if ($invRow && $nodeId <= 0) $nodeId = (int) $invRow['node_id'];
        if ($subRow && $nodeId <= 0) $nodeId = (int) $subRow['node_id'];

        $quote    = $this->resolveCourseFeeForCountry($nodeId, $cCode, $subRow['billing_cycle'] ?? 'Monthly');
        $currency = $invRow['currency'] ?? ($subRow['currency'] ?? $quote['currency']);
        $amount   = (float) ($invRow['total_amount'] ?? ($subRow['fee_amount'] ?? $quote['amount']));
        $title    = $invRow['title'] ?? ($quote['course_title'] . ' — Fee');
        $now      = gmdate('Y-m-d H:i:s');

        $this->db->table($this->table)->insert([
            'txn_reference'     => $txnRef,
            'provider'          => 'offline_screenshot',
            'invoice_id'        => $invoiceId,
            'parent_user_id'    => $parentId,
            'student_user_id'   => $studentId,
            'node_id'           => $nodeId,
            'billing_cycle'     => $subRow['billing_cycle'] ?? $quote['billing_cycle'],
            'invoice_title'     => $title,
            'payer_name'        => $parentRow['display_name'] ?? 'Parent',
            'payer_email'       => $parentRow['email'] ?? '',
            'subtotal_amount'   => $amount,
            'tax_percent'       => (float) ($invRow['tax_percent'] ?? 0),
            'tax_amount'        => (float) ($invRow['tax_amount'] ?? 0),
            'amount'            => $amount,
            'currency'          => $currency,
            'amount_inr_equiv'  => ($currency === 'INR') ? $amount : $quote['amount_inr_equiv'],
            'proof_file_url'    => $proofUrl,
            'verification_note' => 'Awaiting Admin Verification',
            'batch_start_date'  => $quote['batch_start_date'],
            'batch_end_date'    => $quote['batch_end_date'],
            'status'            => 'pending_verification',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        if ($invoiceId > 0) {
            $this->db->table($this->invoicesTable)->where('id', $invoiceId)->update([
                'status'     => 'pending_verification',
                'updated_at' => $now,
            ]);
        }

        return [
            'ok'      => true,
            'message' => 'Payment screenshot & Reference "' . $txnRef . '" submitted! Once verified by Admin, your official receipt will be unlocked.',
        ];
    }

    public function createCustomParentChildInvoice(array $post): array
    {
        $parentId  = (int) ($post['parent_user_id'] ?? 0);
        $studentId = (int) ($post['student_user_id'] ?? 0);
        $title     = trim((string) ($post['title'] ?? ''));
        $remarks   = trim((string) ($post['remarks'] ?? ''));
        $subtotal  = max(0, (float) ($post['subtotal_amount'] ?? 0));
        $taxPct    = max(0, min(100, (float) ($post['tax_percent'] ?? 0)));
        $dueDate   = trim((string) ($post['due_date'] ?? date('Y-m-d', strtotime('+5 days'))));

        if ($studentId <= 0 || $title === '' || $subtotal <= 0) {
            return ['ok' => false, 'message' => 'Select a Parent & Child, enter a Payment Description/Remark, and enter a valid Amount.'];
        }

        $users     = $this->getRoleFilteredUsers()['all'];
        $stuRow    = $users[$studentId] ?? null;
        $parRow    = $users[$parentId] ?? $stuRow;
        if ($parentId <= 0 && $stuRow) {
            $parentId = (int) ($stuRow['parent_user_id'] ?? 0);
            $parRow   = $users[$parentId] ?? $stuRow;
        }

        $cCode    = strtoupper(trim((string) ($post['country_code'] ?? ($parRow['country_code'] ?? ($stuRow['country_code'] ?? 'IN')))));
        $currency = strtoupper(trim((string) ($post['currency'] ?? ($parRow['currency_code'] ?? ($stuRow['currency_code'] ?? 'INR')))));

        $taxAmt   = round($subtotal * ($taxPct / 100), 2);
        $totalAmt = round($subtotal + $taxAmt, 2);
        $invNo    = 'REQ-' . date('Ymd') . '-' . $studentId . '-' . rand(10, 99);
        $now      = gmdate('Y-m-d H:i:s');

        $this->db->table($this->invoicesTable)->insert([
            'invoice_no'        => $invNo,
            'invoice_type'      => 'custom_request',
            'subscription_id'   => 0,
            'parent_user_id'    => $parentId,
            'student_user_id'   => $stuId,
            'node_id'           => 0,
            'title'             => $title,
            'remarks'           => $remarks,
            'country_code'      => $cCode,
            'currency'          => $currency,
            'subtotal_amount'   => $subtotal,
            'tax_percent'       => $taxPct,
            'tax_amount'        => $taxAmt,
            'total_amount'      => $totalAmt,
            'billing_cycle'     => 'One-Time',
            'cycle_month_label' => date('M Y'),
            'due_date'          => $dueDate,
            'status'            => 'unpaid',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        return [
            'ok'      => true,
            'message' => 'Custom Payment Request "' . $title . '" for ' . $currency . ' ' . number_format($totalAmt, 2) . ' sent to Parent Portal!',
        ];
    }

    public function saveManualTeacherPayout(array $post): array
    {
        $teacherId = (int) ($post['teacher_user_id'] ?? 0);
        $period    = trim((string) ($post['pay_period_label'] ?? date('M Y'))) ?: date('M Y');
        $baseSal   = max(0, (float) ($post['fixed_base_salary'] ?? 0));
        $bonus     = max(0, (float) ($post['manual_bonus'] ?? 0));
        $deduct    = max(0, (float) ($post['manual_deduction'] ?? 0));
        $ref       = trim((string) ($post['payment_reference'] ?? ''));
        $remarks   = trim((string) ($post['admin_remarks'] ?? ''));

        if ($teacherId <= 0 || $baseSal <= 0) {
            return ['ok' => false, 'message' => 'Select a Teacher and enter a valid Salary Amount.'];
        }

        $netInr = max(0, round($baseSal + $bonus - $deduct, 2));
        $now    = gmdate('Y-m-d H:i:s');

        $this->db->table($this->payoutsTable)->insert([
            'teacher_user_id'        => $teacherId,
            'pay_period_label'       => $period,
            'scheduled_classes_cnt'  => 0,
            'present_classes_cnt'    => 0,
            'missed_classes_cnt'     => 0,
            'substitute_taken_cnt'   => 0,
            'unavailability_req_cnt' => 0,
            'fixed_base_salary'      => $baseSal,
            'manual_bonus'           => $bonus,
            'manual_deduction'       => $deduct,
            'net_payout_inr'         => $netInr,
            'payment_reference'      => $ref ?: ('SAL-' . date('Ymd-His')),
            'admin_remarks'          => $remarks,
            'status'                 => 'paid',
            'paid_at'                => $now,
            'created_at'             => $now,
        ]);

        return [
            'ok'      => true,
            'message' => 'Teacher Salary of ₹' . number_format($netInr, 2) . ' recorded for ' . $period . ' (Now visible on Teacher\'s Salary Slip portal)!',
        ];
    }

    public function saveGeneralExpense(array $post, int $adminId): array
    {
        $cat    = trim((string) ($post['expense_category'] ?? 'Operations')) ?: 'Operations';
        $title  = trim((string) ($post['title'] ?? ''));
        $amount = max(0, (float) ($post['amount_inr'] ?? 0));
        $date   = trim((string) ($post['expense_date'] ?? date('Y-m-d')));
        $vendor = trim((string) ($post['vendor_name'] ?? ''));
        $notes  = trim((string) ($post['notes'] ?? ''));

        if ($title === '' || $amount <= 0) {
            return ['ok' => false, 'message' => 'Enter a valid Expense Title and Amount in INR.'];
        }

        $this->db->table($this->expensesTable)->insert([
            'expense_category' => $cat,
            'title'            => $title,
            'amount_inr'       => round($amount, 2),
            'expense_date'     => $date,
            'vendor_name'      => $vendor,
            'notes'            => $notes,
            'created_by'       => $adminId,
            'created_at'       => gmdate('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'message' => 'Expense "' . $title . '" (₹' . number_format($amount, 2) . ') logged!'];
    }

    public function getProfitAndLossSummary(): array
    {
        $txns = $this->db->table($this->table)->where('status', 'paid')->get()->getResultArray();
        $totalRevenueInr = 0.0;
        foreach ($txns as $t) {
            $equiv = (float) ($t['amount_inr_equiv'] ?? 0);
            $totalRevenueInr += ($equiv > 0) ? $equiv : (float) ($t['amount'] ?? 0);
        }

        $pendingCount = (int) $this->db->table($this->table)->where('status', 'pending_verification')->countAllResults();

        $payouts = $this->db->table($this->payoutsTable)->get()->getResultArray();
        $totalSalariesInr = 0.0;
        foreach ($payouts as $p) {
            $totalSalariesInr += (float) ($p['net_payout_inr'] ?? 0);
        }

        $expenses = $this->db->table($this->expensesTable)->get()->getResultArray();
        $totalExpensesInr = 0.0;
        foreach ($expenses as $e) {
            $totalExpensesInr += (float) ($e['amount_inr'] ?? 0);
        }

        $netProfitInr = $totalRevenueInr - ($totalSalariesInr + $totalExpensesInr);

        return [
            'revenue_inr'   => round($totalRevenueInr, 2),
            'salaries_inr'  => round($totalSalariesInr, 2),
            'expenses_inr'  => round($totalExpensesInr, 2),
            'net_pnl_inr'   => round($netProfitInr, 2),
            'txn_count'     => count($txns),
            'pending_count' => $pendingCount,
        ];
    }

    public function generateBrandedPdfBinary(array $txn, string $studentName, string $courseTitle): string
    {
        $tpl = $this->getReceiptTemplateConfig();

        $hex = ltrim((string) ($tpl['accent_color'] ?? '#10b981'), '#');
        if (strlen($hex) !== 6) $hex = '10b981';
        $rCol = round(hexdec(substr($hex, 0, 2)) / 255, 2);
        $gCol = round(hexdec(substr($hex, 2, 2)) / 255, 2);
        $bCol = round(hexdec(substr($hex, 4, 2)) / 255, 2);

        $escPdf = static function (string $s): string {
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        };

        $bStart  = ! empty($txn['batch_start_date']) ? $txn['batch_start_date'] : 'Active Batch';
        $bEnd    = ! empty($txn['batch_end_date']) ? $txn['batch_end_date'] : 'Ongoing';
        $nextDue = ! empty($txn['next_due_date']) ? $txn['next_due_date'] : date('Y-m-d', strtotime('+30 days'));
        $subAmt  = (float) ($txn['subtotal_amount'] ?? 0) > 0 ? (float) $txn['subtotal_amount'] : (float) ($txn['amount'] ?? 0);
        $taxAmt  = (float) ($txn['tax_amount'] ?? 0);
        $totAmt  = (float) ($txn['amount'] ?? 0);
        $curr    = (string) ($txn['currency'] ?? 'INR');

        $stream = "q\n";
        $stream .= "{$rCol} {$gCol} {$bCol} rg\n";
        $stream .= "0 700 612 92 re f\n";
        $stream .= "Q\n";

        $stream .= "BT\n1 1 1 rg\n/F1 16 Tf\n45 755 Td\n";
        $stream .= "(" . $escPdf(strtoupper((string) $tpl['academy_name'])) . ") Tj\n";
        $stream .= "/F1 10 Tf\n0 -18 Td\n";
        $stream .= "(" . $escPdf($tpl['tax_label'] . ': ' . $tpl['tax_number'] . '  |  ' . $tpl['support_email'] . '  |  ' . $tpl['support_phone']) . ") Tj\n";
        $stream .= "0 -15 Td\n";
        $stream .= "(OFFICIAL FEE PAYMENT RECEIPT  //  RECEIPT NO: " . $escPdf((string) $txn['txn_reference']) . ") Tj\n";
        $stream .= "ET\n";

        $bodyLines = [
            "Payment Date          : " . substr((string) ($txn['created_at'] ?? gmdate('Y-m-d H:i:s')), 0, 16) . " UTC",
            "Payment Status        : " . strtoupper((string) ($txn['status'] ?? 'PAID')) . " (" . strtoupper((string) ($txn['provider'] ?? 'ONLINE')) . ")",
            "----------------------------------------------------------------------",
            "Student Name          : " . $studentName,
            "Parent / Guardian     : " . ($txn['payer_name'] ?: 'Parent'),
            "Enrolled Course       : " . $courseTitle,
            "Fee Description       : " . ($txn['invoice_title'] ?: ($courseTitle . ' Fee')),
            "Billing Plan          : " . ($txn['billing_cycle'] ?? 'Monthly'),
            "Batch Start & End     : " . $bStart . "  to  " . $bEnd,
            "Next Fee Due Date     : " . $nextDue,
            "----------------------------------------------------------------------",
            "Subtotal Amount       : " . $curr . " " . number_format($subAmt, 2),
            "Tax (" . number_format((float) ($txn['tax_percent'] ?? 0), 1) . "%)            : " . $curr . " " . number_format($taxAmt, 2),
            "TOTAL AMOUNT PAID     : " . $curr . " " . number_format($totAmt, 2),
            "----------------------------------------------------------------------",
            "",
            "Authorized Signatory  : " . ($tpl['signature_name'] ?? 'Authorized Signatory'),
            "Note: " . ($tpl['footer_note'] ?? 'Thank you for your payment!'),
        ];

        $stream .= "BT\n0.08 0.10 0.15 rg\n/F1 11 Tf\n45 660 Td\n20 TL\n";
        foreach ($bodyLines as $line) {
            $stream .= "(" . $escPdf($line) . ") Tj T*\n";
        }
        $stream .= "ET\n";

        $objs   = [];
        $objs[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objs[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objs[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $objs[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold >>\nendobj\n";
        $objs[] = "5 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream\nendobj\n";

        $pdf     = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $obj) {
            $offsets[] = strlen($pdf);
            $pdf      .= $obj;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }
}

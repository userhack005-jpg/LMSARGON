<?php

namespace App\Models\Addons\LmsHierarchy;

use CodeIgniter\Model;
use Throwable;

class LmsHierarchyModel extends Model
{
    protected $table            = 'lms_hierarchy_nodes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'parent_id',
        'level_depth',
        'node_type',
        'is_leaf_node',
        'title',
        'slug',
        'short_summary',
        'description_html',
        'thumbnail_source',
        'thumbnail_url',
        'teacher_user_id',
        'batch_schedule',
        'max_seats',
        'allow_demo_booking',
        'demo_note',
        'sort_order',
        'status',
        'created_at',
        'updated_at',
    ];

    protected string $pricingTable     = 'lms_hierarchy_pricing';
    protected string $enrollmentsTable = 'lms_hierarchy_enrollments';

    protected static bool $schemaChecked         = false;
    protected static ?array $globalSettingsCache = null;
    protected static ?array $countriesCache      = null;
    protected static ?array $pricingByNodeCache  = null;
    protected static ?array $hierarchyDataCache  = null;
    protected static ?array $studentsDirCache    = null;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchemaInitialized();
    }

    public static function clearRuntimeCaches(): void
    {
        self::$pricingByNodeCache = null;
        self::$hierarchyDataCache = null;
        self::$studentsDirCache   = null;
    }

    public function ensureSchema(): void
    {
        $this->ensureSchemaInitialized();
    }

    public function ensureSchemaInitialized(): void
    {
        if (self::$schemaChecked) {
            return;
        }
        $lockFile = WRITEPATH . 'addons/schema_addon_lms_hierarchy_v85.lock';
        if (is_file($lockFile)) {
            self::$schemaChecked = true;
            return;
        }

        try {
            if (! $this->db->tableExists('lms_hierarchy_nodes')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `lms_hierarchy_nodes` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `level_depth` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                        `node_type` VARCHAR(35) NOT NULL DEFAULT 'category',
                        `is_leaf_node` TINYINT(1) NOT NULL DEFAULT 0,
                        `title` VARCHAR(160) NOT NULL,
                        `slug` VARCHAR(180) NOT NULL DEFAULT '',
                        `short_summary` VARCHAR(255) NOT NULL DEFAULT '',
                        `description_html` LONGTEXT NULL,
                        `thumbnail_source` VARCHAR(20) NOT NULL DEFAULT 'url',
                        `thumbnail_url` VARCHAR(255) NOT NULL DEFAULT '',
                        `teacher_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `batch_schedule` VARCHAR(160) NOT NULL DEFAULT '',
                        `max_seats` SMALLINT UNSIGNED NOT NULL DEFAULT 25,
                        `allow_demo_booking` TINYINT(1) NOT NULL DEFAULT 1,
                        `demo_note` VARCHAR(180) NOT NULL DEFAULT 'Free Live Demo Available',
                        `sort_order` INT NOT NULL DEFAULT 10,
                        `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        KEY `idx_parent_sort` (`parent_id`, `sort_order`, `status`),
                        KEY `idx_leaf_status` (`is_leaf_node`, `status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } else {
                $nCols   = $this->db->getFieldNames('lms_hierarchy_nodes');
                $nAlters = [
                    'parent_id'          => "ADD COLUMN `parent_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'level_depth'        => "ADD COLUMN `level_depth` TINYINT UNSIGNED NOT NULL DEFAULT 1",
                    'node_type'          => "ADD COLUMN `node_type` VARCHAR(35) NOT NULL DEFAULT 'category'",
                    'is_leaf_node'       => "ADD COLUMN `is_leaf_node` TINYINT(1) NOT NULL DEFAULT 0",
                    'slug'               => "ADD COLUMN `slug` VARCHAR(180) NOT NULL DEFAULT ''",
                    'short_summary'      => "ADD COLUMN `short_summary` VARCHAR(255) NOT NULL DEFAULT ''",
                    'description_html'   => "ADD COLUMN `description_html` LONGTEXT NULL",
                    'thumbnail_source'   => "ADD COLUMN `thumbnail_source` VARCHAR(20) NOT NULL DEFAULT 'url'",
                    'thumbnail_url'      => "ADD COLUMN `thumbnail_url` VARCHAR(255) NOT NULL DEFAULT ''",
                    'teacher_user_id'    => "ADD COLUMN `teacher_user_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'batch_schedule'     => "ADD COLUMN `batch_schedule` VARCHAR(160) NOT NULL DEFAULT ''",
                    'max_seats'          => "ADD COLUMN `max_seats` SMALLINT UNSIGNED NOT NULL DEFAULT 25",
                    'allow_demo_booking' => "ADD COLUMN `allow_demo_booking` TINYINT(1) NOT NULL DEFAULT 1",
                    'demo_note'          => "ADD COLUMN `demo_note` VARCHAR(180) NOT NULL DEFAULT 'Free Live Demo Available'",
                    'sort_order'         => "ADD COLUMN `sort_order` INT NOT NULL DEFAULT 10",
                    'status'             => "ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active'",
                    'created_at'         => "ADD COLUMN `created_at` DATETIME NULL",
                    'updated_at'         => "ADD COLUMN `updated_at` DATETIME NULL",
                ];
                foreach ($nAlters as $col => $frag) {
                    if (! in_array($col, $nCols, true)) {
                        $this->db->query("ALTER TABLE `lms_hierarchy_nodes` " . $frag);
                    }
                }
            }

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_hierarchy_pricing` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `node_id` INT UNSIGNED NOT NULL,
                    `country_code` VARCHAR(10) NOT NULL DEFAULT 'IN',
                    `currency_code` VARCHAR(10) NOT NULL DEFAULT 'INR',
                    `monthly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `quarterly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `full_course_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `registration_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `updated_at` DATETIME NULL,
                    UNIQUE KEY `uniq_node_country` (`node_id`, `country_code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_hierarchy_enrollments` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `student_user_id` INT UNSIGNED NOT NULL,
                    `parent_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `leaf_node_id` INT UNSIGNED NOT NULL,
                    `country_code` VARCHAR(10) NOT NULL DEFAULT 'IN',
                    `currency_code` VARCHAR(10) NOT NULL DEFAULT 'INR',
                    `monthly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `billing_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly',
                    `next_due_date` DATE NULL,
                    `attendance_pct` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
                    `test_score_avg` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    `certificates_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                    `history_json` LONGTEXT NULL,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                    `enrolled_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_student_status` (`student_user_id`, `status`),
                    KEY `idx_parent_status` (`parent_user_id`, `status`),
                    KEY `idx_leaf_node` (`leaf_node_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->cleanupOrphanedEnrollments();
            $this->syncPageBuilderComponent();
            $this->syncRbacPermissionsAndAutoEnableRoles();
            $this->mirrorAssets(true);

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, json_encode(['migrated_at' => date('Y-m-d H:i:s'), 'version' => '8.5.0']));
            self::$schemaChecked = true;
        } catch (Throwable $e) {
            log_message('error', 'LmsHierarchyModel schema error: ' . $e->getMessage());
        }
    }

    public function cleanupOrphanedEnrollments(): void
    {
        try {
            if (! $this->db->tableExists('users') || ! $this->db->tableExists($this->enrollmentsTable)) {
                return;
            }
            $this->db->query("
                DELETE e FROM `lms_hierarchy_enrollments` e
                LEFT JOIN `users` u ON u.id = e.student_user_id
                WHERE u.id IS NULL
            ");
            $this->db->query("
                DELETE e FROM `lms_hierarchy_enrollments` e
                LEFT JOIN `users` p ON p.id = e.parent_user_id
                WHERE e.parent_user_id > 0 AND p.id IS NULL
            ");
            if ($this->db->tableExists('lms_student_subscriptions')) {
                $this->db->query("
                    DELETE s FROM `lms_student_subscriptions` s
                    LEFT JOIN `users` u ON u.id = s.student_user_id
                    WHERE u.id IS NULL
                ");
            }
        } catch (Throwable $e) {
        }
    }

    public function syncPageBuilderComponent(): void
    {
        try {
            if (! $this->db->tableExists('cms_components')) {
                return;
            }
            $defaultData = [
                'badge'             => 'ACADEMIC PROGRAMS',
                'heading'           => 'Explore Our Courses & Learning Levels',
                'subheading'        => 'Select any program below to view curriculum details, level progression, and enrollment options.',
                'display_mode'      => 'drilldown_tree',
                'card_layout'       => 'split',
                'selected_node_ids' => '',
                'show_demo_badge'   => '1',
            ];
            $schemaJson = json_encode($defaultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $this->db->query("
                INSERT INTO `cms_components`
                    (`component_key`, `component_name`, `component_icon`, `category`, `addon_slug`, `default_schema_json`, `is_enabled`, `sort_order`)
                VALUES
                    ('course_hierarchy_explorer', 'Courses & Programs Explorer', '📚', 'lms', 'lms_hierarchy', ?, 1, 16)
                ON DUPLICATE KEY UPDATE
                    `component_name`      = VALUES(`component_name`),
                    `component_icon`      = VALUES(`component_icon`),
                    `addon_slug`          = 'lms_hierarchy',
                    `default_schema_json` = VALUES(`default_schema_json`),
                    `is_enabled`          = 1
            ", [$schemaJson]);
        } catch (Throwable $e) {
        }
    }

    public function syncRbacPermissionsAndAutoEnableRoles(): void
    {
        try {
            $now = date('Y-m-d H:i:s');
            if ($this->db->tableExists('cms_rbac_features')) {
                $perms = [
                    ['lms_hierarchy.view',    'View Courses & Enrolled Programs [Student + Parent + Teacher + Admin]'],
                    ['lms_hierarchy.create',  'Create & Edit Courses & Curriculum Levels [Super Admin + Admin]'],
                    ['lms_hierarchy.pricing', 'Configure Country-Wise Course Fees [Super Admin + Admin]'],
                    ['lms_hierarchy.edit',    'Enroll Students & Promote Course Levels [Super Admin + Admin]'],
                    ['lms_hierarchy.delete',  'Delete Courses & Levels [Super Admin + Admin]'],
                ];
                foreach ($perms as $p) {
                    $this->db->query("
                        INSERT INTO `cms_rbac_features` (`perm_key`, `perm_label`, `module_group`, `source_type`, `created_at`)
                        VALUES (?, ?, 'Add-on: Courses & Academic Programs', 'addon', ?)
                        ON DUPLICATE KEY UPDATE `perm_label` = VALUES(`perm_label`), `module_group` = VALUES(`module_group`)
                    ", [$p[0], $p[1], $now]);
                }
            }
        } catch (Throwable $e) {
        }
    }

    public function mirrorAssets(bool $forceTouch = false): void
    {
        $srcDir = WRITEPATH . 'addons/extracted/lms_hierarchy/assets';
        $dstDir = FCPATH . 'assets/addons/lms_hierarchy';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['lms_hierarchy.css', 'lms_hierarchy.js'] as $f) {
                $sFile = $srcDir . '/' . $f;
                $dFile = $dstDir . '/' . $f;
                if (is_file($sFile)) {
                    if ($forceTouch || ! is_file($dFile) || filesize($sFile) !== filesize($dFile)) {
                        @copy($sFile, $dFile);
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
        self::$globalSettingsCache = ['site_title' => 'Portal', 'active_theme_mode' => 'dark', 'system_environment_mode' => 'production'];
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

    public function getCompanionAddonsStatus(): array
    {
        $active = function_exists('cms_get_active_addon_slugs') ? cms_get_active_addon_slugs() : [];
        return [
            'rbac'            => isset($active['rbac']) || true,
            'core_lms_auth'   => isset($active['core_lms_auth']) || true,
            'lms_finance'     => isset($active['lms_finance']) || is_dir(WRITEPATH . 'addons/extracted/lms_finance'),
            'lms_onboarding'  => isset($active['lms_onboarding']) || is_dir(WRITEPATH . 'addons/extracted/lms_onboarding'),
            'lms_assessments' => isset($active['lms_assessments']) || is_dir(WRITEPATH . 'addons/extracted/lms_assessments'),
            'lms_timetable'   => isset($active['lms_timetable']) || is_dir(WRITEPATH . 'addons/extracted/lms_timetable'),
        ];
    }

    public function getServicingCountriesCatalog(): array
    {
        if (self::$countriesCache !== null) {
            return self::$countriesCache;
        }

        $authCacheFile = WRITEPATH . 'addons/core_lms_auth_cache.json';
        if (is_file($authCacheFile)) {
            $cached = json_decode((string) @file_get_contents($authCacheFile), true);
            if (! empty($cached['enabled_countries']) && is_array($cached['enabled_countries'])) {
                self::$countriesCache = $cached['enabled_countries'];
                return self::$countriesCache;
            }
        }

        $settings = $this->getGlobalSiteSettings();
        $rawJson  = $settings['lms_countries__catalog_json'] ?? ($settings['catalog_json'] ?? '');
        if ($rawJson !== '') {
            $parsed = json_decode((string) $rawJson, true);
            if (is_array($parsed) && ! empty($parsed)) {
                $enabled = [];
                foreach ($parsed as $cCode => $cInfo) {
                    if (! empty($cInfo['is_enabled'])) {
                        $enabled[strtoupper($cCode)] = $cInfo;
                    }
                }
                if (! empty($enabled)) {
                    self::$countriesCache = $enabled;
                    return self::$countriesCache;
                }
            }
        }

        self::$countriesCache = [
            'IN' => ['code' => 'IN', 'name' => 'India',          'flag' => '🇮🇳', 'currency_code' => 'INR', 'currency_sym' => '₹'],
            'AE' => ['code' => 'AE', 'name' => 'UAE',            'flag' => '🇦🇪', 'currency_code' => 'AED', 'currency_sym' => 'AED '],
            'US' => ['code' => 'US', 'name' => 'United States',  'flag' => '🇺🇸', 'currency_code' => 'USD', 'currency_sym' => '$'],
            'GB' => ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => '🇬🇧', 'currency_code' => 'GBP', 'currency_sym' => '£'],
            'NG' => ['code' => 'NG', 'name' => 'Nigeria',        'flag' => '🇳🇬', 'currency_code' => 'NGN', 'currency_sym' => '₦'],
        ];
        return self::$countriesCache;
    }

    public function getEnabledCountriesOnly(): array
    {
        return $this->getServicingCountriesCatalog();
    }

    public function detectVisitorCountryCode(?string $tzHint = null, ?string $overrideCountry = null): string
    {
        $countries = $this->getServicingCountriesCatalog();

        if ($overrideCountry !== null && $overrideCountry !== '') {
            $ov = strtoupper(trim($overrideCountry));
            if (isset($countries[$ov])) {
                return $ov;
            }
        }

        $sessCountry = strtoupper(trim((string) (session()->get('user_country') ?: '')));
        if ($sessCountry !== '' && isset($countries[$sessCountry])) {
            return $sessCountry;
        }

        if (session()->get('isLoggedIn') && $this->db->tableExists('cms_user_meta')) {
            $targetUid = (int) (session()->get('active_student_id') ?: session()->get('user_id') ?: 0);
            if ($targetUid > 0) {
                $meta = $this->db->table('cms_user_meta')->select('country_code')->where('user_id', $targetUid)->get()->getRowArray();
                if (! empty($meta['country_code'])) {
                    $mCode = strtoupper(trim((string) $meta['country_code']));
                    if (isset($countries[$mCode])) {
                        return $mCode;
                    }
                }
            }
        }

        $tz = trim((string) ($tzHint ?: (session()->get('user_timezone') ?: '')));
        if ($tz !== '') {
            $tzToCountry = [
                'Asia/Kolkata'        => 'IN',
                'Asia/Calcutta'       => 'IN',
                'Asia/Dubai'          => 'AE',
                'Asia/Muscat'         => 'AE',
                'Europe/London'       => 'GB',
                'Africa/Lagos'        => 'NG',
                'America/New_York'    => 'US',
                'America/Chicago'     => 'US',
                'America/Los_Angeles' => 'US',
            ];
            if (isset($tzToCountry[$tz]) && isset($countries[$tzToCountry[$tz]])) {
                return $tzToCountry[$tz];
            }
        }

        $firstKey = array_key_first($countries);
        return $firstKey ? (string) $firstKey : 'IN';
    }

    public function getStudentsDirectory(): array
    {
        if (self::$studentsDirCache !== null) {
            return self::$studentsDirCache;
        }
        $students = [];
        if ($this->db->tableExists('users')) {
            $rows = $this->db->table('users')
                ->select('id, role_id, full_name, email, role_slug, parent_user_id')
                ->where('status', 'active')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($rows as $u) {
                $id    = (int) $u['id'];
                $rId   = (int) ($u['role_id'] ?? 4);
                $rSlug = strtolower(trim((string) ($u['role_slug'] ?? 'student')));
                if (! in_array($rId, [1, 2, 3], true) && ! in_array($rSlug, ['super_admin', 'admin', 'teacher', 'parent'], true)) {
                    $students[$id] = [
                        'id'             => $id,
                        'full_name'      => $u['full_name'] ?: ($u['email'] ?: 'Student'),
                        'email'          => $u['email'] ?? '',
                        'parent_user_id' => (int) ($u['parent_user_id'] ?? 0),
                    ];
                }
            }
        }
        self::$studentsDirCache = $students;
        return self::$studentsDirCache;
    }

    public function getAllPricingByNode(): array
    {
        if (self::$pricingByNodeCache !== null) {
            return self::$pricingByNodeCache;
        }
        $countries = $this->getServicingCountriesCatalog();
        $rows      = $this->db->table($this->pricingTable)->where('is_active', 1)->get()->getResultArray();
        $map       = [];
        foreach ($rows as $r) {
            $nid   = (int) $r['node_id'];
            $cCode = strtoupper(trim((string) $r['country_code']));
            $sym   = $countries[$cCode]['currency_sym'] ?? ($r['currency_code'] . ' ');
            $r['currency_sym']        = $sym;
            $r['country_name']        = $countries[$cCode]['name'] ?? $cCode;
            $r['country_flag']        = $countries[$cCode]['flag'] ?? '🌍';
            $r['formatted_monthly']   = $sym . number_format((float) ($r['monthly_fee'] ?? 0), 2);
            $r['formatted_quarterly'] = $sym . number_format((float) ($r['quarterly_fee'] ?? 0), 2);
            $r['formatted_full']      = $sym . number_format((float) ($r['full_course_fee'] ?? 0), 2);
            $map[$nid][$cCode]        = $r;
        }
        self::$pricingByNodeCache = $map;
        return self::$pricingByNodeCache;
    }

    public function getFullHierarchyData(): array
    {
        if (self::$hierarchyDataCache !== null) {
            return self::$hierarchyDataCache;
        }

        $rows = $this->db->table($this->table)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $pricing   = $this->getAllPricingByNode();
        $nodesById = [];

        foreach ($rows as $r) {
            $id    = (int) $r['id'];
            $thumb = trim((string) ($r['thumbnail_url'] ?? ($r['banner_url'] ?? '')));
            if ($thumb !== '' && strpos($thumb, 'http') !== 0 && strpos($thumb, 'data:') !== 0) {
                $thumb = base_url(ltrim($thumb, '/'));
            }
            $r['short_summary']      = (string) ($r['short_summary'] ?? ($r['summary'] ?? ''));
            $r['description_html']   = (string) ($r['description_html'] ?? ($r['description'] ?? ''));
            $r['resolved_thumbnail'] = $thumb;
            $r['pricing_by_country'] = $pricing[$id] ?? [];
            $r['children_ids']       = [];
            $nodesById[$id]          = $r;
        }

        foreach ($nodesById as $id => &$node) {
            $pid = (int) ($node['parent_id'] ?? 0);
            if ($pid > 0 && isset($nodesById[$pid])) {
                $nodesById[$pid]['children_ids'][] = $id;
            }
        }
        unset($node);

        $branchParentsMap = [0 => '— Top-Level Program (Main Category) —'];
        $leafBatchesMap   = [];

        foreach ($nodesById as $id => &$node) {
            $pathParts = [$node['title']];
            $currPid   = (int) ($node['parent_id'] ?? 0);
            $guard     = 0;
            while ($currPid > 0 && isset($nodesById[$currPid]) && $guard < 12) {
                array_unshift($pathParts, $nodesById[$currPid]['title']);
                $currPid = (int) ($nodesById[$currPid]['parent_id'] ?? 0);
                $guard++;
            }
            $node['breadcrumb_path'] = implode(' › ', $pathParts);
            $node['level_depth']     = count($pathParts);

            if ((int) ($node['is_leaf_node'] ?? 0) === 0 || ! empty($node['children_ids'])) {
                $branchParentsMap[$id] = $node['breadcrumb_path'];
            }
            if ((int) ($node['is_leaf_node'] ?? 0) === 1) {
                $leafBatchesMap[$id] = $node['breadcrumb_path'];
            }
        }
        unset($node);

        self::$hierarchyDataCache = [
            'nodes_by_id'        => $nodesById,
            'branch_parents_map' => $branchParentsMap,
            'leaf_batches_map'   => $leafBatchesMap,
        ];
        return self::$hierarchyDataCache;
    }

    public function getFullHierarchyTreeFlat(): array
    {
        return array_values($this->getFullHierarchyData()['nodes_by_id']);
    }

    public function getActiveEnrollmentsCount(): int
    {
        try {
            return (int) $this->db->table($this->enrollmentsTable)->countAllResults();
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function getAppliedDemosForUser(int $uid, string $roleSlug, array $childIds = []): array
    {
        if ($uid <= 0 || ! $this->db->tableExists('lms_demo_leads')) {
            return [];
        }
        $treeData = $this->getFullHierarchyData()['nodes_by_id'];
        $teachers = [];
        if ($this->db->tableExists('users')) {
            foreach ($this->db->table('users')->select('id, full_name, email')->where('role_id', 3)->orWhere('role_slug', 'teacher')->get()->getResultArray() as $u) {
                $teachers[(int) $u['id']] = $u['full_name'] ?: ($u['email'] ?: 'Instructor');
            }
        }

        $batches = [];
        if ($this->db->tableExists('lms_demo_batches')) {
            foreach ($this->db->table('lms_demo_batches')->get()->getResultArray() as $b) {
                $batches[(int) $b['id']] = $b;
            }
        }

        $uRow   = $this->db->table('users')->select('email')->where('id', $uid)->get()->getRowArray();
        $uEmail = strtolower(trim((string) ($uRow['email'] ?? '')));

        $builder = $this->db->table('lms_demo_leads')->orderBy('id', 'DESC');
        $builder->groupStart()
            ->orWhere('converted_parent_id', $uid)
            ->orWhere('converted_student_id', $uid);
        if (! empty($childIds)) {
            $builder->orWhereIn('converted_student_id', array_map('intval', $childIds));
        }
        if ($uEmail !== '') {
            $builder->orWhere('parent_email', $uEmail);
        }
        $builder->groupEnd();

        $rows = $builder->get()->getResultArray();
        foreach ($rows as &$r) {
            $cid   = (int) ($r['course_node_id'] ?? 0);
            $bid   = (int) ($r['demo_batch_id'] ?? 0);
            $bInfo = $batches[$bid] ?? null;
            $tid   = (int) ($bInfo['teacher_user_id'] ?? ($r['assigned_teacher_id'] ?? 0));

            $r['course_title'] = $treeData[$cid]['breadcrumb_path'] ?? ($treeData[$cid]['title'] ?? 'Course Demo');
            $r['teacher_name'] = ($tid > 0 && isset($teachers[$tid])) ? $teachers[$tid] : '';
            if ($bInfo && ! empty($bInfo['meeting_link'])) {
                $r['meeting_link'] = $bInfo['meeting_link'];
            }
        }
        unset($r);
        return $rows;
    }

    public function saveOrUpdateNode(array $post, $uploadedThumb = null): array
    {
        $id          = (int) ($post['id'] ?? 0);
        $parentId    = max(0, (int) ($post['parent_id'] ?? 0));
        $isLeaf      = ! empty($post['is_leaf_node']) ? 1 : 0;
        $title       = trim((string) ($post['title'] ?? ''));
        $summary     = trim((string) ($post['short_summary'] ?? ''));
        $descHtml    = trim((string) ($post['description_html'] ?? ''));
        $thumbUrl    = trim((string) ($post['thumbnail_url'] ?? ''));
        $thumbSource = 'url';
        $allowDemo   = ! empty($post['allow_demo_booking']) ? 1 : 0;
        $demoNote    = trim((string) ($post['demo_note'] ?? 'Free Live Demo Available'));
        $sortOrder   = (int) ($post['sort_order'] ?? 10);
        $status      = in_array($post['status'] ?? '', ['active', 'inactive'], true) ? $post['status'] : 'active';

        if ($title === '') {
            return ['ok' => false, 'message' => 'Course / Level Title is required.'];
        }
        if ($id > 0 && $parentId === $id) {
            return ['ok' => false, 'message' => 'A course level cannot be its own parent.'];
        }

        if ($parentId > 0) {
            $parentRow = $this->find($parentId);
            if ($parentRow && (int) ($parentRow['is_leaf_node'] ?? 0) === 1) {
                $this->db->table($this->table)->where('id', $parentId)->update([
                    'is_leaf_node' => 0,
                    'node_type'    => 'branch',
                ]);
            }
        }

        if ($uploadedThumb && $uploadedThumb->isValid() && ! $uploadedThumb->hasMoved()) {
            $ext = strtolower($uploadedThumb->getClientExtension());
            if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'], true)) {
                return ['ok' => false, 'message' => 'Allowed image formats: PNG, JPG, WEBP, SVG, GIF.'];
            }
            $uploadDir = FCPATH . 'uploads/lms_hierarchy/';
            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $newName = 'course_thumb_' . time() . '_' . $uploadedThumb->getRandomName();
            $uploadedThumb->move($uploadDir, $newName);
            $thumbUrl    = 'uploads/lms_hierarchy/' . $newName;
            $thumbSource = 'upload';
        } elseif ($id > 0 && $thumbUrl === '') {
            $existing = $this->find($id);
            if ($existing) {
                $thumbUrl    = (string) ($existing['thumbnail_url'] ?? '');
                $thumbSource = (string) ($existing['thumbnail_source'] ?? 'url');
            }
        }

        $depth = 1;
        if ($parentId > 0) {
            $pRow = $this->find($parentId);
            if ($pRow) {
                $depth = (int) ($pRow['level_depth'] ?? 1) + 1;
            }
        }

        $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-')) ?: ('course-' . time());
        $now  = gmdate('Y-m-d H:i:s');

        $rawRow = [
            'parent_id'          => $parentId,
            'level_depth'        => $depth,
            'node_type'          => $isLeaf ? 'leaf_batch' : ($parentId === 0 ? 'category' : 'branch'),
            'is_leaf_node'       => $isLeaf,
            'title'              => $title,
            'slug'               => $slug,
            'short_summary'      => $summary,
            'description_html'   => $descHtml,
            'thumbnail_source'   => $thumbSource,
            'thumbnail_url'      => $thumbUrl,
            'allow_demo_booking' => $allowDemo,
            'demo_note'          => $demoNote,
            'sort_order'         => $sortOrder,
            'status'             => $status,
            'updated_at'         => $now,
        ];

        $actualCols = array_flip($this->db->getFieldNames($this->table));
        $row        = array_intersect_key($rawRow, $actualCols);

        if ($id > 0) {
            $this->db->table($this->table)->where('id', $id)->update($row);
            self::clearRuntimeCaches();
            return [
                'ok'      => true,
                'node_id' => $id,
                'message' => 'Course "' . $title . '" updated successfully!',
            ];
        }

        if (isset($actualCols['created_at'])) {
            $row['created_at'] = $now;
        }
        $this->db->table($this->table)->insert($row);
        $newId = (int) $this->db->insertID();
        self::clearRuntimeCaches();

        return [
            'ok'      => true,
            'node_id' => $newId,
            'message' => ($isLeaf
                ? 'Course "' . $title . '" created! Configure country-wise fees in "Course Fee Plans".'
                : 'Program Category "' . $title . '" created!'),
        ];
    }

    public function deleteNodeAndChildren(int $id): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid course ID.'];
        }
        $childrenCount = (int) $this->db->table($this->table)->where('parent_id', $id)->countAllResults();
        if ($childrenCount > 0) {
            return ['ok' => false, 'message' => 'Cannot delete: This program has ' . $childrenCount . ' sub-levels inside it. Delete its sub-levels first.'];
        }
        $this->db->table($this->table)->where('id', $id)->delete();
        $this->db->table($this->pricingTable)->where('node_id', $id)->delete();
        $this->db->table($this->enrollmentsTable)->where('leaf_node_id', $id)->delete();
        self::clearRuntimeCaches();
        return ['ok' => true, 'message' => 'Course deleted successfully.'];
    }

    public function saveLeafPricingMatrix(array $post): array
    {
        $nodeId = (int) ($post['node_id'] ?? 0);
        if ($nodeId <= 0) {
            return ['ok' => false, 'message' => 'Please select a valid Course.'];
        }

        $countries = $this->getServicingCountriesCatalog();
        $now       = gmdate('Y-m-d H:i:s');
        $savedCnt  = 0;

        foreach ($countries as $cCode => $cInfo) {
            $mFee = (float) ($post['monthly_' . $cCode] ?? 0);
            $qFee = (float) ($post['quarterly_' . $cCode] ?? 0);
            $fFee = (float) ($post['full_' . $cCode] ?? 0);
            $rFee = (float) ($post['reg_' . $cCode] ?? 0);
            $act  = isset($post['active_' . $cCode]) ? 1 : 0;
            $curr = trim((string) ($post['curr_' . $cCode] ?? ($cInfo['currency_code'] ?? 'INR')));

            $this->db->query("
                INSERT INTO `lms_hierarchy_pricing`
                    (`node_id`, `country_code`, `currency_code`, `monthly_fee`, `quarterly_fee`, `full_course_fee`, `registration_fee`, `is_active`, `updated_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `currency_code`    = VALUES(`currency_code`),
                    `monthly_fee`      = VALUES(`monthly_fee`),
                    `quarterly_fee`    = VALUES(`quarterly_fee`),
                    `full_course_fee`  = VALUES(`full_course_fee`),
                    `registration_fee` = VALUES(`registration_fee`),
                    `is_active`        = VALUES(`is_active`),
                    `updated_at`       = VALUES(`updated_at`)
            ", [$nodeId, $cCode, $curr, $mFee, $qFee, $fFee, $rFee, $act, $now]);
            $savedCnt++;
        }

        self::clearRuntimeCaches();
        return ['ok' => true, 'message' => 'Course fee plans saved across ' . $savedCnt . ' servicing countries!'];
    }

    public function enrollStudentInLeaf(array $post): array
    {
        $studentId    = (int) ($post['student_user_id'] ?? 0);
        $leafNodeId   = (int) ($post['leaf_node_id'] ?? 0);
        $countryCode  = strtoupper(trim((string) ($post['country_code'] ?? 'IN'))) ?: 'IN';
        $billingCycle = in_array($post['billing_cycle'] ?? '', ['monthly', 'quarterly', 'full'], true) ? $post['billing_cycle'] : 'monthly';

        if ($studentId <= 0 || $leafNodeId <= 0) {
            return ['ok' => false, 'message' => 'Select both a valid Student and a Course.'];
        }

        $students = $this->getStudentsDirectory();
        $stuInfo  = $students[$studentId] ?? null;
        if (! $stuInfo) {
            return ['ok' => false, 'message' => 'Selected student account does not exist.'];
        }

        $parentId   = (int) ($stuInfo['parent_user_id'] ?? ($post['parent_user_id'] ?? 0));
        $pricesMap  = $this->getAllPricingByNode();
        $leafPrices = is_array($pricesMap[$leafNodeId] ?? null) ? $pricesMap[$leafNodeId] : [];
        $priceRow   = $leafPrices[$countryCode] ?? (! empty($leafPrices) ? reset($leafPrices) : []);

        $currCode   = $priceRow['currency_code'] ?? 'INR';
        $monthlyFee = (float) ($priceRow['monthly_fee'] ?? 1000.00);
        $nextDue    = date('Y-m-d', strtotime('+30 days'));
        $now        = gmdate('Y-m-d H:i:s');

        $existing = $this->db->table($this->enrollmentsTable)
            ->where('student_user_id', $studentId)
            ->where('leaf_node_id', $leafNodeId)
            ->get()
            ->getRowArray();

        if ($existing) {
            $this->db->table($this->enrollmentsTable)->where('id', $existing['id'])->update([
                'parent_user_id' => $parentId,
                'country_code'   => $countryCode,
                'currency_code'  => $currCode,
                'monthly_fee'    => $monthlyFee,
                'billing_cycle'  => $billingCycle,
                'status'         => 'active',
                'updated_at'     => $now,
            ]);
        } else {
            $this->db->table($this->enrollmentsTable)->insert([
                'student_user_id'    => $studentId,
                'parent_user_id'     => $parentId,
                'leaf_node_id'       => $leafNodeId,
                'country_code'       => $countryCode,
                'currency_code'      => $currCode,
                'monthly_fee'        => $monthlyFee,
                'billing_cycle'      => $billingCycle,
                'next_due_date'      => $nextDue,
                'attendance_pct'     => 100.00,
                'test_score_avg'     => 0.00,
                'certificates_count' => 0,
                'history_json'       => json_encode([]),
                'status'             => 'active',
                'enrolled_at'        => $now,
                'updated_at'         => $now,
            ]);
        }

        $this->syncEnrollmentToFinanceSubscription($studentId, $parentId, $leafNodeId, $countryCode, $currCode, $monthlyFee, $nextDue);

        return [
            'ok'      => true,
            'message' => $stuInfo['full_name'] . ' enrolled successfully (' . $currCode . ' ' . number_format($monthlyFee, 2) . '/mo)!',
        ];
    }

    private function syncEnrollmentToFinanceSubscription(int $studentId, int $parentId, int $leafNodeId, string $countryCode, string $currCode, float $monthlyFee, string $nextDue): void
    {
        try {
            if (! $this->db->tableExists('lms_student_subscriptions')) {
                return;
            }
            $sCols   = $this->db->getFieldNames('lms_student_subscriptions');
            $nodeCol = in_array('node_id', $sCols, true) ? 'node_id' : 'leaf_node_id';
            $feeCol  = in_array('fee_amount', $sCols, true) ? 'fee_amount' : 'monthly_fee';
            $currCol = in_array('currency', $sCols, true) ? 'currency' : 'currency_code';

            $ex = $this->db->table('lms_student_subscriptions')
                ->where('student_user_id', $studentId)
                ->where($nodeCol, $leafNodeId)
                ->get()
                ->getRowArray();

            $now     = gmdate('Y-m-d H:i:s');
            $payload = [
                'student_user_id' => $studentId,
                'parent_user_id'  => $parentId,
                $nodeCol          => $leafNodeId,
                'country_code'    => $countryCode,
                $currCol          => $currCode,
                $feeCol           => $monthlyFee,
                'due_date'        => $nextDue,
                'status'          => 'active',
                'updated_at'      => $now,
            ];

            $actualCols = array_flip($sCols);
            $payload    = array_intersect_key($payload, $actualCols);

            if ($ex) {
                $this->db->table('lms_student_subscriptions')->where('id', $ex['id'])->update($payload);
            } else {
                if (isset($actualCols['created_at'])) {
                    $payload['created_at'] = $now;
                }
                $this->db->table('lms_student_subscriptions')->insert($payload);
            }
        } catch (Throwable $e) {
        }
    }

    public function upgradeStudentBatch(int $enrollmentId, int $targetLeafNodeId, string $upgradeNote = ''): array
    {
        $enr = $this->db->table($this->enrollmentsTable)->where('id', $enrollmentId)->get()->getRowArray();
        if (! $enr) {
            return ['ok' => false, 'message' => 'Enrollment record not found.'];
        }
        $oldLeafId = (int) $enr['leaf_node_id'];
        if ($oldLeafId === $targetLeafNodeId) {
            return ['ok' => false, 'message' => 'Student is already in that course level.'];
        }

        $treeData = $this->getFullHierarchyData();
        $oldTitle = $treeData['nodes_by_id'][$oldLeafId]['title'] ?? 'Previous Level';
        $newTitle = $treeData['nodes_by_id'][$targetLeafNodeId]['title'] ?? 'Next Level';

        $history = json_decode((string) ($enr['history_json'] ?? '[]'), true);
        if (! is_array($history)) {
            $history = [];
        }
        $now = gmdate('Y-m-d H:i:s');
        $history[] = [
            'from_leaf_id'    => $oldLeafId,
            'from_leaf_title' => $oldTitle,
            'to_leaf_id'      => $targetLeafNodeId,
            'to_leaf_title'   => $newTitle,
            'attendance_pct'  => (float) $enr['attendance_pct'],
            'test_score_avg'  => (float) $enr['test_score_avg'],
            'note'            => $upgradeNote ?: 'Promoted to next course level',
            'upgraded_at'     => $now,
        ];

        $cCode     = (string) ($enr['country_code'] ?: 'IN');
        $pricesMap = $this->getAllPricingByNode();
        $newPrice  = $pricesMap[$targetLeafNodeId][$cCode] ?? null;
        $newFee    = $newPrice ? (float) $newPrice['monthly_fee'] : (float) $enr['monthly_fee'];

        $this->db->table($this->enrollmentsTable)->where('id', $enrollmentId)->update([
            'leaf_node_id' => $targetLeafNodeId,
            'monthly_fee'  => $newFee,
            'history_json' => json_encode($history, JSON_UNESCAPED_UNICODE),
            'updated_at'   => $now,
        ]);

        if ($this->db->tableExists('lms_student_subscriptions')) {
            $this->db->table('lms_student_subscriptions')
                ->where('student_user_id', (int) $enr['student_user_id'])
                ->where('node_id', $oldLeafId)
                ->update(['node_id' => $targetLeafNodeId, 'fee_amount' => $newFee, 'updated_at' => $now]);
        }

        return [
            'ok'      => true,
            'message' => 'Student promoted from "' . $oldTitle . '" to "' . $newTitle . '"!',
        ];
    }

    public function getEnrichedEnrollments(array $studentIdsFilter = [], int $parentIdFilter = 0, array $leafNodeIdsFilter = []): array
    {
        $treeData  = $this->getFullHierarchyData();
        $nodes     = $treeData['nodes_by_id'];
        $students  = $this->getStudentsDirectory();
        $countries = $this->getServicingCountriesCatalog();

        $cohortByLeaf = [];
        if ($this->db->tableExists('lms_classroom_batches')) {
            foreach ($this->db->table('lms_classroom_batches')->where('status', 'active')->get()->getResultArray() as $cb) {
                $cohortByLeaf[(int) $cb['leaf_node_id']] = $cb;
            }
        }

        $builder = $this->db->table($this->enrollmentsTable)->orderBy('id', 'DESC');
        if ($parentIdFilter > 0) {
            $builder->groupStart()->where('parent_user_id', $parentIdFilter);
            if (! empty($studentIdsFilter)) {
                $builder->orWhereIn('student_user_id', array_map('intval', $studentIdsFilter));
            }
            $builder->groupEnd();
        } elseif (! empty($studentIdsFilter)) {
            $builder->whereIn('student_user_id', array_map('intval', $studentIdsFilter));
        }

        if (! empty($leafNodeIdsFilter)) {
            $builder->whereIn('leaf_node_id', array_map('intval', $leafNodeIdsFilter));
        }

        $rows = $builder->get()->getResultArray();
        $out  = [];

        foreach ($rows as $r) {
            $sid = (int) $r['student_user_id'];
            if (! isset($students[$sid])) {
                continue;
            }

            $leafId   = (int) $r['leaf_node_id'];
            $nodeInfo = $nodes[$leafId] ?? null;
            if (! $nodeInfo) {
                continue;
            }

            $cCode  = strtoupper((string) ($r['country_code'] ?: 'IN'));
            $sym    = $countries[$cCode]['currency_sym'] ?? ($r['currency_code'] . ' ');
            $cohort = $cohortByLeaf[$leafId] ?? null;

            $r['student_name']    = $students[$sid]['full_name'];
            $r['student_email']   = $students[$sid]['email'];
            $r['batch_title']     = $nodeInfo['title'];
            $r['breadcrumb_path'] = $nodeInfo['breadcrumb_path'] ?? $r['batch_title'];
            $r['cohort_title']    = $cohort['cohort_title'] ?? $r['batch_title'];
            $r['formatted_fee']   = $sym . number_format((float) $r['monthly_fee'], 2) . ' / ' . ($r['billing_cycle'] ?: 'mo');
            $r['history_array']   = json_decode((string) ($r['history_json'] ?? '[]'), true) ?: [];
            $out[]                = $r;
        }

        return $out;
    }

    public function getTeacherAssignedCoursesAndCohorts(int $teacherId): array
    {
        $treeData      = $this->getFullHierarchyData();
        $nodes         = $treeData['nodes_by_id'];
        $assignedLeafs = [];
        $cohortsList   = [];

        if ($this->db->tableExists('lms_classroom_batches')) {
            $rows = $this->db->table('lms_classroom_batches')
                ->where('teacher_user_id', $teacherId)
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($rows as $c) {
                $lid = (int) ($c['leaf_node_id'] ?? 0);
                if ($lid > 0 && isset($nodes[$lid])) {
                    $assignedLeafs[$lid] = $nodes[$lid];
                }
                $c['course_title']    = $nodes[$lid]['title'] ?? 'Assigned Course';
                $c['breadcrumb_path'] = $nodes[$lid]['breadcrumb_path'] ?? $c['course_title'];
                $cohortsList[]        = $c;
            }
        }

        foreach ($nodes as $nid => $nRow) {
            if ((int) ($nRow['teacher_user_id'] ?? 0) === $teacherId) {
                $assignedLeafs[$nid] = $nRow;
            }
        }

        return [
            'courses' => $assignedLeafs,
            'cohorts' => $cohortsList,
        ];
    }
}

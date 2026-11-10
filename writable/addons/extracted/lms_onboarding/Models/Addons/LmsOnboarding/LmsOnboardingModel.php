<?php

namespace App\Models\Addons\LmsOnboarding;

use CodeIgniter\Model;
use DateTime;
use DateTimeZone;
use Throwable;

class LmsOnboardingModel extends Model
{
    protected $table            = 'lms_demo_leads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'parent_name',
        'parent_email',
        'parent_phone',
        'child_name',
        'child_age',
        'course_node_id',
        'country_code',
        'currency_code',
        'timezone',
        'preferred_date',
        'preferred_time_slot',
        'demo_batch_id',
        'assigned_teacher_id',
        'meeting_link',
        'scheduled_utc',
        'attendance_status',
        'teacher_eval_note',
        'recommended_node_id',
        'admin_notes',
        'status',
        'converted_parent_id',
        'converted_student_id',
        'created_at',
        'updated_at',
    ];

    protected string $batchesTable = 'lms_demo_batches';

    protected static bool $schemaChecked        = false;
    protected static ?array $settingsCache      = null;
    protected static ?array $countriesCache     = null;
    protected static ?array $allCoursesMapCache = null;
    protected static ?array $teachersMapCache   = null;
    protected static ?array $cohortsMapCache    = null;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public static function clearRuntimeCaches(): void
    {
        self::$allCoursesMapCache = null;
        self::$teachersMapCache   = null;
        self::$cohortsMapCache    = null;
    }

    public function ensureSchemaInitialized(): void
    {
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }
        $lockFile = WRITEPATH . 'addons/schema_addon_lms_onboarding_v121.lock';
        if (is_file($lockFile)) {
            self::$schemaChecked = true;
            return;
        }

        try {
            if (! $this->db->tableExists('lms_demo_leads')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `lms_demo_leads` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `parent_name` VARCHAR(140) NOT NULL DEFAULT '',
                        `parent_email` VARCHAR(190) NOT NULL DEFAULT '',
                        `parent_phone` VARCHAR(50) NOT NULL DEFAULT '',
                        `child_name` VARCHAR(140) NOT NULL DEFAULT '',
                        `child_age` TINYINT UNSIGNED NOT NULL DEFAULT 8,
                        `course_node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `country_code` VARCHAR(10) NOT NULL DEFAULT 'IN',
                        `currency_code` VARCHAR(10) NOT NULL DEFAULT 'INR',
                        `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Kolkata',
                        `preferred_date` DATE NULL,
                        `preferred_time_slot` VARCHAR(60) NOT NULL DEFAULT '05:00 PM - 06:00 PM',
                        `demo_batch_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `assigned_teacher_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `meeting_link` VARCHAR(255) NOT NULL DEFAULT '',
                        `scheduled_utc` DATETIME NULL,
                        `attendance_status` VARCHAR(20) NOT NULL DEFAULT 'Pending',
                        `teacher_eval_note` VARCHAR(255) NOT NULL DEFAULT '',
                        `recommended_node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `admin_notes` VARCHAR(255) NOT NULL DEFAULT '',
                        `status` VARCHAR(30) NOT NULL DEFAULT 'new_lead',
                        `converted_parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `converted_student_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        KEY `idx_lead_status` (`status`, `course_node_id`),
                        KEY `idx_lead_batch` (`demo_batch_id`),
                        KEY `idx_lead_teacher` (`assigned_teacher_id`, `status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } else {
                $lCols   = $this->db->getFieldNames('lms_demo_leads');
                $lAlters = [
                    'parent_name'          => "ADD COLUMN `parent_name` VARCHAR(140) NOT NULL DEFAULT ''",
                    'parent_email'         => "ADD COLUMN `parent_email` VARCHAR(190) NOT NULL DEFAULT ''",
                    'parent_phone'         => "ADD COLUMN `parent_phone` VARCHAR(50) NOT NULL DEFAULT ''",
                    'child_name'           => "ADD COLUMN `child_name` VARCHAR(140) NOT NULL DEFAULT ''",
                    'child_age'            => "ADD COLUMN `child_age` TINYINT UNSIGNED NOT NULL DEFAULT 8",
                    'course_node_id'       => "ADD COLUMN `course_node_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'country_code'         => "ADD COLUMN `country_code` VARCHAR(10) NOT NULL DEFAULT 'IN'",
                    'currency_code'        => "ADD COLUMN `currency_code` VARCHAR(10) NOT NULL DEFAULT 'INR'",
                    'timezone'             => "ADD COLUMN `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Kolkata'",
                    'preferred_date'       => "ADD COLUMN `preferred_date` DATE NULL",
                    'preferred_time_slot'  => "ADD COLUMN `preferred_time_slot` VARCHAR(60) NOT NULL DEFAULT '05:00 PM - 06:00 PM'",
                    'demo_batch_id'        => "ADD COLUMN `demo_batch_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'assigned_teacher_id'  => "ADD COLUMN `assigned_teacher_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'meeting_link'         => "ADD COLUMN `meeting_link` VARCHAR(255) NOT NULL DEFAULT ''",
                    'scheduled_utc'        => "ADD COLUMN `scheduled_utc` DATETIME NULL",
                    'attendance_status'    => "ADD COLUMN `attendance_status` VARCHAR(20) NOT NULL DEFAULT 'Pending'",
                    'teacher_eval_note'    => "ADD COLUMN `teacher_eval_note` VARCHAR(255) NOT NULL DEFAULT ''",
                    'recommended_node_id'  => "ADD COLUMN `recommended_node_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'admin_notes'          => "ADD COLUMN `admin_notes` VARCHAR(255) NOT NULL DEFAULT ''",
                    'status'               => "ADD COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'new_lead'",
                    'converted_parent_id'  => "ADD COLUMN `converted_parent_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'converted_student_id' => "ADD COLUMN `converted_student_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'created_at'           => "ADD COLUMN `created_at` DATETIME NULL",
                    'updated_at'           => "ADD COLUMN `updated_at` DATETIME NULL",
                ];
                foreach ($lAlters as $col => $frag) {
                    if (! in_array($col, $lCols, true)) {
                        try {
                            $this->db->query("ALTER TABLE `lms_demo_leads` " . $frag);
                        } catch (Throwable $e) {
                        }
                    }
                }
            }

            if (! $this->db->tableExists('lms_demo_batches')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `lms_demo_batches` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `batch_title` VARCHAR(160) NOT NULL DEFAULT 'Live Demo Slot',
                        `course_node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `teacher_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `admin_ist_input` DATETIME NULL,
                        `demo_date` DATE NULL,
                        `start_time_ist` VARCHAR(10) NOT NULL DEFAULT '17:00',
                        `scheduled_utc` DATETIME NULL,
                        `duration_min` SMALLINT UNSIGNED NOT NULL DEFAULT 30,
                        `max_seats` SMALLINT UNSIGNED NOT NULL DEFAULT 10,
                        `meeting_link` VARCHAR(255) NOT NULL DEFAULT '',
                        `status` VARCHAR(25) NOT NULL DEFAULT 'scheduled',
                        `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
                        `started_at` DATETIME NULL,
                        `ended_at` DATETIME NULL,
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        KEY `idx_demo_teacher` (`teacher_user_id`, `status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } else {
                $bCols   = $this->db->getFieldNames('lms_demo_batches');
                $bAlters = [
                    'batch_title'     => "ADD COLUMN `batch_title` VARCHAR(160) NOT NULL DEFAULT 'Live Demo Slot'",
                    'course_node_id'  => "ADD COLUMN `course_node_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'teacher_user_id' => "ADD COLUMN `teacher_user_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'admin_ist_input' => "ADD COLUMN `admin_ist_input` DATETIME NULL",
                    'demo_date'       => "ADD COLUMN `demo_date` DATE NULL",
                    'start_time_ist'  => "ADD COLUMN `start_time_ist` VARCHAR(10) NOT NULL DEFAULT '17:00'",
                    'scheduled_utc'   => "ADD COLUMN `scheduled_utc` DATETIME NULL",
                    'duration_min'    => "ADD COLUMN `duration_min` SMALLINT UNSIGNED NOT NULL DEFAULT 30",
                    'max_seats'       => "ADD COLUMN `max_seats` SMALLINT UNSIGNED NOT NULL DEFAULT 10",
                    'meeting_link'    => "ADD COLUMN `meeting_link` VARCHAR(255) NOT NULL DEFAULT ''",
                    'status'          => "ADD COLUMN `status` VARCHAR(25) NOT NULL DEFAULT 'scheduled'",
                    'started_at'      => "ADD COLUMN `started_at` DATETIME NULL",
                    'ended_at'        => "ADD COLUMN `ended_at` DATETIME NULL",
                    'created_at'      => "ADD COLUMN `created_at` DATETIME NULL",
                    'updated_at'      => "ADD COLUMN `updated_at` DATETIME NULL",
                ];
                foreach ($bAlters as $col => $frag) {
                    if (! in_array($col, $bCols, true)) {
                        try {
                            $this->db->query("ALTER TABLE `lms_demo_batches` " . $frag);
                        } catch (Throwable $e) {
                        }
                    }
                }
            }

            if ($this->db->tableExists('users') && $this->db->tableExists('roles')) {
                $parentRoleRow = $this->db->table('roles')->where('role_slug', 'parent')->get()->getRowArray();
                if ($parentRoleRow && (int) $parentRoleRow['id'] === 5) {
                    $this->db->query("UPDATE `users` SET `role_id` = 5 WHERE `role_slug` = 'parent' AND `role_id` = 4");
                }
            }

            $this->mirrorAssets(true);

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, json_encode(['migrated_at' => date('Y-m-d H:i:s'), 'version' => '12.1.0']));
            self::$schemaChecked = true;
        } catch (Throwable $e) {
            log_message('error', 'LmsOnboardingModel schema error: ' . $e->getMessage());
        }
    }

    public function mirrorAssets(bool $forceTouch = false): void
    {
        $srcDir = WRITEPATH . 'addons/extracted/lms_onboarding/assets';
        $dstDir = FCPATH . 'assets/addons/lms_onboarding';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['lms_onboarding.css', 'lms_onboarding.js'] as $f) {
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
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }
        self::$settingsCache = ['site_title' => 'Portal', 'active_theme_mode' => 'dark'];
        try {
            if ($this->db->tableExists('settings')) {
                foreach ($this->db->table('settings')->get()->getResultArray() as $r) {
                    self::$settingsCache[$r['setting_key']] = $r['setting_value'];
                }
            }
        } catch (Throwable $e) {
        }
        return self::$settingsCache;
    }

    public function getServicingCountries(): array
    {
        if (self::$countriesCache !== null) {
            return self::$countriesCache;
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
            'IN' => ['code' => 'IN', 'name' => 'India',          'flag' => '🇮🇳', 'currency_code' => 'INR', 'default_tz' => 'Asia/Kolkata'],
            'AE' => ['code' => 'AE', 'name' => 'UAE',            'flag' => '🇦🇪', 'currency_code' => 'AED', 'default_tz' => 'Asia/Dubai'],
            'US' => ['code' => 'US', 'name' => 'United States',  'flag' => '🇺🇸', 'currency_code' => 'USD', 'default_tz' => 'America/New_York'],
            'GB' => ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => '🇬🇧', 'currency_code' => 'GBP', 'default_tz' => 'Europe/London'],
        ];
        return self::$countriesCache;
    }

    public function getEnrollableCoursesMap(bool $onlyDemoEnabled = false): array
    {
        if (self::$allCoursesMapCache === null) {
            $map = [];
            if ($this->db->tableExists('lms_hierarchy_nodes')) {
                $all  = [];
                $rows = $this->db->table('lms_hierarchy_nodes')
                    ->select('id, parent_id, title, is_leaf_node, allow_demo_booking, demo_note, sort_order, status')
                    ->where('status', 'active')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();
                foreach ($rows as $n) {
                    $all[(int) $n['id']] = $n;
                }
                foreach ($all as $nid => $nRow) {
                    $parts = [$nRow['title']];
                    $pid   = (int) ($nRow['parent_id'] ?? 0);
                    $g     = 0;
                    while ($pid > 0 && isset($all[$pid]) && $g < 10) {
                        array_unshift($parts, $all[$pid]['title']);
                        $pid = (int) ($all[$pid]['parent_id'] ?? 0);
                        $g++;
                    }
                    $map[$nid] = [
                        'id'                 => $nid,
                        'title'              => $nRow['title'],
                        'breadcrumb_path'    => implode(' › ', $parts),
                        'is_leaf_node'       => (int) ($nRow['is_leaf_node'] ?? 1),
                        'allow_demo_booking' => (int) ($nRow['allow_demo_booking'] ?? 1),
                        'demo_note'          => (string) ($nRow['demo_note'] ?? 'Book Free Demo'),
                    ];
                }
            }
            self::$allCoursesMapCache = $map;
        }

        if (! $onlyDemoEnabled) {
            return self::$allCoursesMapCache;
        }

        $filtered = [];
        foreach (self::$allCoursesMapCache as $nid => $cRow) {
            if ((int) ($cRow['allow_demo_booking'] ?? 1) === 1) {
                $filtered[$nid] = $cRow;
            }
        }
        return $filtered;
    }

    public function getActiveCohortsMap(): array
    {
        if (self::$cohortsMapCache !== null) {
            return self::$cohortsMapCache;
        }
        $cohorts = [];
        if ($this->db->tableExists('lms_classroom_batches')) {
            foreach ($this->db->table('lms_classroom_batches')->where('status', 'active')->orderBy('id', 'DESC')->get()->getResultArray() as $c) {
                $cohorts[(int) $c['id']] = $c;
            }
        }
        self::$cohortsMapCache = $cohorts;
        return self::$cohortsMapCache;
    }

    public function getTeachersMap(): array
    {
        if (self::$teachersMapCache !== null) {
            return self::$teachersMapCache;
        }
        $teachers = [];
        if ($this->db->tableExists('users')) {
            $rows = $this->db->table('users')
                ->select('id, role_id, full_name, email, role_slug, status')
                ->where('status', 'active')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($rows as $u) {
                $id    = (int) $u['id'];
                $rId   = (int) ($u['role_id'] ?? 4);
                $rSlug = strtolower(trim((string) ($u['role_slug'] ?? 'student')));
                if ($rId === 3 || $rSlug === 'teacher') {
                    $teachers[$id] = [
                        'id'           => $id,
                        'display_name' => $u['full_name'] ?: ($u['email'] ?: 'Teacher'),
                        'email'        => $u['email'] ?? '',
                    ];
                }
            }
        }
        self::$teachersMapCache = $teachers;
        return self::$teachersMapCache;
    }

    public function getLoggedInUserRecord(): ?array
    {
        $sessUser = is_array(session()->get('user')) ? session()->get('user') : [];
        $uid      = (int) (session()->get('user_id') ?: (session()->get('id') ?: ($sessUser['id'] ?? ($sessUser['user_id'] ?? 0))));
        if ($uid <= 0 && ! session()->get('isLoggedIn')) {
            return null;
        }
        if ($uid > 0 && $this->db->tableExists('users')) {
            $uRow = $this->db->table('users')
                ->select('id, role_id, full_name, email, status, role_slug, parent_user_id')
                ->where('id', $uid)
                ->get()
                ->getRowArray();
            if ($uRow) {
                $uRow['display_name'] = $uRow['full_name'] ?: ($uRow['email'] ?: 'Parent');
                return $uRow;
            }
        }
        if ($uid > 0) {
            return [
                'id'           => $uid,
                'full_name'    => (string) (session()->get('full_name') ?: 'Parent'),
                'display_name' => (string) (session()->get('full_name') ?: 'Parent'),
                'email'        => (string) (session()->get('email') ?: ''),
            ];
        }
        return null;
    }

    public function getParentLinkedChildren(int $parentId): array
    {
        if ($parentId <= 0 || ! $this->db->tableExists('users')) {
            return [];
        }

        $kidRows = $this->db->table('users')
            ->select('id, full_name, email')
            ->where('parent_user_id', $parentId)
            ->where('status', 'active')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($kidRows)) {
            return [];
        }

        $kids   = [];
        $kidIds = [];
        foreach ($kidRows as $k) {
            $kidId        = (int) $k['id'];
            $kidIds[]     = $kidId;
            $kids[$kidId] = [
                'id'                 => $kidId,
                'full_name'          => $k['full_name'] ?: 'Child',
                'age'                => 8,
                'applied_course_ids' => [],
            ];
        }

        if ($this->db->tableExists('cms_user_meta')) {
            foreach ($this->db->table('cms_user_meta')->select('user_id, child_age')->whereIn('user_id', $kidIds)->get()->getResultArray() as $m) {
                $uid = (int) $m['user_id'];
                if (isset($kids[$uid])) {
                    $kids[$uid]['age'] = (int) ($m['child_age'] ?? 8);
                }
            }
        }

        if ($this->db->tableExists('lms_demo_leads')) {
            $leads = $this->db->table('lms_demo_leads')
                ->select('converted_student_id, course_node_id')
                ->whereIn('converted_student_id', $kidIds)
                ->get()
                ->getResultArray();
            foreach ($leads as $ld) {
                $sid = (int) ($ld['converted_student_id'] ?? 0);
                $cid = (int) ($ld['course_node_id'] ?? 0);
                if (isset($kids[$sid]) && $cid > 0) {
                    $kids[$sid]['applied_course_ids'][$cid] = true;
                }
            }
        }

        return $kids;
    }

    public function formatTimeAmPm(string $time24): string
    {
        $time24 = trim($time24);
        if ($time24 === '') {
            return '05:00 PM';
        }
        if (stripos($time24, 'AM') !== false || stripos($time24, 'PM') !== false) {
            return $time24;
        }
        $ts = strtotime('2026-01-01 ' . $time24);
        return $ts ? date('h:i A', $ts) : $time24;
    }

    public function getQuickCounts(int $teacherIdFilter = 0): array
    {
        $leadsCnt   = 0;
        $batchesCnt = 0;
        try {
            $qb1 = $this->db->table($this->table);
            if ($teacherIdFilter > 0) {
                $qb1->where('assigned_teacher_id', $teacherIdFilter)->where('demo_batch_id >', 0);
            }
            $leadsCnt = (int) $qb1->countAllResults();

            $qb2 = $this->db->table($this->batchesTable);
            if ($teacherIdFilter > 0) {
                $qb2->where('teacher_user_id', $teacherIdFilter);
            }
            $batchesCnt = (int) $qb2->countAllResults();
        } catch (Throwable $e) {
        }
        return ['leads_count' => $leadsCnt, 'batches_count' => $batchesCnt];
    }

    public function getAppliedDemosForUser(int $uid, string $email = '', array $childIds = []): array
    {
        if ($uid <= 0 || ! $this->db->tableExists('lms_demo_leads')) {
            return [];
        }
        $courses  = $this->getEnrollableCoursesMap(false);
        $teachers = $this->getTeachersMap();
        $batches  = [];
        if ($this->db->tableExists($this->batchesTable)) {
            foreach ($this->db->table($this->batchesTable)->get()->getResultArray() as $b) {
                $batches[(int) $b['id']] = $b;
            }
        }

        $qb = $this->db->table('lms_demo_leads')->orderBy('id', 'DESC');
        $qb->groupStart()
            ->orWhere('converted_parent_id', $uid)
            ->orWhere('converted_student_id', $uid);
        if (! empty($childIds)) {
            $qb->orWhereIn('converted_student_id', array_map('intval', $childIds));
        }
        if ($email !== '') {
            $qb->orWhere('parent_email', strtolower(trim($email)));
        }
        $qb->groupEnd();

        $rows = $qb->get()->getResultArray();
        foreach ($rows as &$r) {
            $cid   = (int) ($r['course_node_id'] ?? 0);
            $bid   = (int) ($r['demo_batch_id'] ?? 0);
            $bInfo = $batches[$bid] ?? null;
            $tid   = (int) ($bInfo['teacher_user_id'] ?? ($r['assigned_teacher_id'] ?? 0));

            $r['course_title']     = $courses[$cid]['breadcrumb_path'] ?? ($courses[$cid]['title'] ?? 'Course Demo');
            $r['teacher_name']     = ($tid > 0 && isset($teachers[$tid])) ? $teachers[$tid]['display_name'] : '';
            $r['slot_title']       = $bInfo ? ($bInfo['batch_title'] ?? 'Demo Slot') : '';
            $r['slot_date']        = $bInfo && ! empty($bInfo['demo_date']) ? $bInfo['demo_date'] : ($r['preferred_date'] ?? '');
            $r['slot_time_ampm']   = $bInfo && ! empty($bInfo['start_time_ist']) ? $this->formatTimeAmPm((string) $bInfo['start_time_ist']) : ($r['preferred_time_slot'] ?? '');
            $r['slot_live_status'] = $bInfo ? strtolower((string) ($bInfo['status'] ?? 'scheduled')) : 'unassigned';
            $r['live_join_link']   = $bInfo && ! empty($bInfo['meeting_link']) ? $bInfo['meeting_link'] : ($r['meeting_link'] ?? '');
        }
        unset($r);
        return $rows;
    }

    public function hasExistingDemoForChildAndCourse(int $studentId, int $parentId, string $parentEmail, string $childName, int $courseNodeId): bool
    {
        if ($courseNodeId <= 0 || ! $this->db->tableExists($this->table)) {
            return false;
        }
        if ($studentId > 0) {
            $ex = $this->db->table($this->table)
                ->select('id')
                ->where('converted_student_id', $studentId)
                ->where('course_node_id', $courseNodeId)
                ->get()
                ->getRowArray();
            if ($ex) {
                return true;
            }
        }
        if ($childName !== '' && ($parentId > 0 || $parentEmail !== '')) {
            $qb = $this->db->table($this->table)
                ->select('id')
                ->where('course_node_id', $courseNodeId)
                ->where('LOWER(child_name)', strtolower(trim($childName)));
            $qb->groupStart();
            if ($parentId > 0) {
                $qb->orWhere('converted_parent_id', $parentId);
            }
            if ($parentEmail !== '') {
                $qb->orWhere('LOWER(parent_email)', strtolower(trim($parentEmail)));
            }
            $qb->groupEnd();
            if ($qb->get()->getRowArray()) {
                return true;
            }
        }
        return false;
    }

    public function createNewDemoLead(array $post): array
    {
        $db          = $this->db;
        $uCols       = array_flip($db->getFieldNames('users'));
        $now         = gmdate('Y-m-d H:i:s');
        $courseId    = (int) ($post['course_node_id'] ?? 0);
        $countryCode = strtoupper(trim((string) ($post['country_code'] ?? (session()->get('user_country') ?: 'IN')))) ?: 'IN';
        $prefSlot    = trim((string) ($post['preferred_time_slot'] ?? '05:00 PM - 06:00 PM')) ?: '05:00 PM - 06:00 PM';
        $tz          = trim((string) ($post['timezone'] ?? 'Asia/Kolkata')) ?: 'Asia/Kolkata';

        $allCourses = $this->getEnrollableCoursesMap(false);
        if ($courseId > 0 && isset($allCourses[$courseId]) && (int) ($allCourses[$courseId]['allow_demo_booking'] ?? 1) === 0) {
            return ['ok' => false, 'message' => 'Demo booking is not enabled for this course.'];
        }

        $loggedUser = $this->getLoggedInUserRecord();

        if ($loggedUser) {
            $parentId    = (int) $loggedUser['id'];
            $parentName  = (string) ($loggedUser['display_name'] ?? 'Parent');
            $parentEmail = strtolower(trim((string) ($loggedUser['email'] ?? '')));
            $parentPhone = trim((string) ($post['parent_phone'] ?? ''));
        } else {
            $parentName     = trim((string) ($post['parent_name'] ?? ''));
            $parentEmail    = strtolower(trim((string) ($post['parent_email'] ?? '')));
            $parentPhone    = trim((string) ($post['parent_phone'] ?? ''));
            $parentPassword = (string) ($post['parent_password'] ?? 'Parent@123');

            if ($parentName === '' || $parentEmail === '') {
                return ['ok' => false, 'message' => 'Please enter your Parent Name and Email Address.'];
            }

            if (! preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $parentEmail)) {
                return ['ok' => false, 'message' => 'Please enter a valid email address.'];
            }
            $existingParent = $db->table('users')->where('email', $parentEmail)->get()->getRowArray();
            if ($existingParent) {
                return [
                    'ok'           => false,
                    'require_login'=> true,
                    'login_url'    => base_url('login'),
                    'message'      => 'This email ID is already used. Please log in to add a new child or book a new course.',
                ];
            } else {
                $hashedPass = password_hash($parentPassword !== '' ? $parentPassword : 'Parent@123', PASSWORD_DEFAULT);
                $pRow = array_intersect_key([
                    'role_id'       => 5,
                    'role_slug'     => 'parent',
                    'full_name'     => $parentName,
                    'email'         => $parentEmail,
                    'password_hash' => $hashedPass,
                    'status'        => 'active',
                    'created_at'    => $now,
                ], $uCols);
                $db->table('users')->insert($pRow);
                $parentId = (int) $db->insertID();
            }

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
                'name'                => $parentName,
                'email'               => $parentEmail,
                'user_country'        => $countryCode,
            ]);
        }

        $existingKids = $this->getParentLinkedChildren($parentId);
        $childrenRows = $post['children'] ?? [];
        if (! is_array($childrenRows) || empty($childrenRows)) {
            return ['ok' => false, 'message' => 'Please select or add at least one Child for the demo.'];
        }

        $countries      = $this->getServicingCountries();
        $currCode       = $countries[$countryCode]['currency_code'] ?? 'INR';
        $actualCols     = array_flip($db->getFieldNames($this->table));
        $bookedNames    = [];
        $duplicateNames = [];

        foreach ($childrenRows as $idx => $ch) {
            $selectedStuId = (int) ($ch['existing_student_id'] ?? 0);
            $kidCourseId   = (int) ($ch['course_id'] ?? $courseId);
            if ($kidCourseId <= 0 && ! empty($allCourses)) {
                $kidCourseId = (int) array_key_first($allCourses);
            }

            $kidName    = '';
            $kidAge     = max(3, min(22, (int) ($ch['age'] ?? 8)));
            $finalStuId = 0;

            if ($selectedStuId > 0 && isset($existingKids[$selectedStuId])) {
                $finalStuId = $selectedStuId;
                $kidName    = $existingKids[$selectedStuId]['full_name'];
                $kidAge     = $existingKids[$selectedStuId]['age'];
            } else {
                $kidName = trim((string) ($ch['name'] ?? ''));
                if ($kidName === '') {
                    continue;
                }
                foreach ($existingKids as $ekId => $ekInfo) {
                    if (strtolower(trim($ekInfo['full_name'])) === strtolower($kidName)) {
                        $finalStuId = (int) $ekId;
                        $kidAge     = $ekInfo['age'];
                        break;
                    }
                }
            }

            if ($this->hasExistingDemoForChildAndCourse($finalStuId, $parentId, $parentEmail, $kidName, $kidCourseId)) {
                $duplicateNames[] = $kidName;
                continue;
            }

            if ($finalStuId <= 0) {
                $slugKid    = preg_replace('/[^a-z0-9]+/', '', strtolower($kidName)) ?: 'learner';
                $childEmail = $slugKid . '.' . $parentId . '.' . rand(100, 999) . '@student.portal';
                $kidPass    = password_hash('Kid@123', PASSWORD_DEFAULT);

                $sRow = array_intersect_key([
                    'role_id'        => 4,
                    'role_slug'      => 'student',
                    'parent_user_id' => $parentId,
                    'full_name'      => $kidName,
                    'email'          => $childEmail,
                    'password_hash'  => $kidPass,
                    'status'         => 'active',
                    'created_at'     => $now,
                ], $uCols);
                $db->table('users')->insert($sRow);
                $finalStuId = (int) $db->insertID();

                if ($db->tableExists('cms_user_meta')) {
                    $cTitle = $allCourses[$kidCourseId]['title'] ?? 'Abacus Level 1';
                    $db->table('cms_user_meta')->insert([
                        'user_id'       => $finalStuId,
                        'leaf_node_id'  => $kidCourseId,
                        'child_age'     => $kidAge,
                        'grade_level'   => $cTitle,
                        'country_code'  => $countryCode,
                        'currency_code' => $currCode,
                        'timezone'      => $tz,
                        'avatar_color'  => '#10b981',
                        'notes'         => '',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
                $existingKids[$finalStuId] = ['id' => $finalStuId, 'full_name' => $kidName, 'age' => $kidAge, 'applied_course_ids' => [$kidCourseId => true]];
            }

            $leadRow = [
                'parent_name'          => $parentName,
                'parent_email'         => $parentEmail,
                'parent_phone'         => $parentPhone,
                'child_name'           => $kidName,
                'child_age'            => $kidAge,
                'course_node_id'       => $kidCourseId,
                'country_code'         => $countryCode,
                'currency_code'        => $currCode,
                'timezone'             => $tz,
                'preferred_date'       => null,
                'preferred_time_slot'  => $prefSlot,
                'demo_batch_id'        => 0,
                'assigned_teacher_id'  => 0,
                'meeting_link'         => '',
                'attendance_status'    => 'Pending',
                'status'               => 'new_lead',
                'converted_parent_id'  => $parentId,
                'converted_student_id' => $finalStuId,
                'created_at'           => $now,
                'updated_at'           => $now,
            ];
            $db->table($this->table)->insert(array_intersect_key($leadRow, $actualCols));
            $bookedNames[] = $kidName;
        }

        if (empty($bookedNames) && ! empty($duplicateNames)) {
            return [
                'ok'      => false,
                'message' => '⚠️ Demo request already exists for ' . implode(', ', $duplicateNames) . ' in this course! You can add another child or request a demo for a different course level.',
            ];
        }

        if (empty($bookedNames)) {
            return ['ok' => false, 'message' => 'Please select an existing child or enter a new child name.'];
        }

        $dupNote = ! empty($duplicateNames) ? (' (Skipped duplicate for ' . implode(', ', $duplicateNames) . ')') : '';
        return [
            'ok'      => true,
            'message' => '🎉 Demo Class request submitted for ' . implode(', ', $bookedNames) . ' (' . $prefSlot . ')' . $dupNote . '! Our admin team will assign your demo slot shortly.',
        ];
    }

    public function assignLeadToDemoSlot(array $post): array
    {
        $leadId      = (int) ($post['lead_id'] ?? 0);
        $demoBatchId = (int) ($post['demo_batch_id'] ?? 0);

        $lead = $this->find($leadId);
        if (! $lead) {
            return ['ok' => false, 'message' => 'Demo request not found.'];
        }
        if ($demoBatchId <= 0) {
            return ['ok' => false, 'message' => 'Please select a Demo Class Slot from the dropdown.'];
        }

        $batch = $this->db->table($this->batchesTable)->where('id', $demoBatchId)->get()->getRowArray();
        if (! $batch) {
            return ['ok' => false, 'message' => 'Selected Demo Class Slot does not exist.'];
        }

        $teacherId    = (int) ($batch['teacher_user_id'] ?? 0);
        $demoDate     = (string) ($batch['demo_date'] ?? date('Y-m-d'));
        $meetingLink  = (string) ($batch['meeting_link'] ?? '');
        $scheduledUtc = $batch['scheduled_utc'] ?: gmdate('Y-m-d H:i:s', strtotime($demoDate . ' ' . ($batch['start_time_ist'] ?? '17:00') . ':00'));
        $slotStatus   = strtolower((string) ($batch['status'] ?? 'scheduled'));

        $now = gmdate('Y-m-d H:i:s');
        $this->update($leadId, [
            'demo_batch_id'       => $demoBatchId,
            'assigned_teacher_id' => $teacherId,
            'preferred_date'      => $demoDate,
            'meeting_link'        => $meetingLink,
            'scheduled_utc'       => $scheduledUtc,
            'attendance_status'   => 'Pending',
            'status'              => ($slotStatus === 'live') ? 'demo_live' : 'demo_scheduled',
            'updated_at'          => $now,
        ]);

        return [
            'ok'      => true,
            'message' => 'Assigned ' . $lead['child_name'] . ' to Demo Slot "' . ($batch['batch_title'] ?? '') . '" (' . $demoDate . ' @ ' . $this->formatTimeAmPm((string) ($batch['start_time_ist'] ?? '17:00')) . ')!',
        ];
    }

    public function startTeacherDemoBatch(int $teacherId, array $post, bool $isAdmin = false): array
    {
        $batchId     = (int) ($post['demo_batch_id'] ?? 0);
        $meetingLink = trim((string) ($post['meeting_link'] ?? ''));

        if ($batchId <= 0 || $meetingLink === '') {
            return ['ok' => false, 'message' => 'Please enter a valid Live Class Meeting Link (Google Meet / Zoom URL) to start the Demo Class.'];
        }

        $batch = $this->db->table($this->batchesTable)->where('id', $batchId)->get()->getRowArray();
        if (! $batch) {
            return ['ok' => false, 'message' => 'Demo Class Slot not found.'];
        }
        if (! $isAdmin && (int) ($batch['teacher_user_id'] ?? 0) !== $teacherId) {
            return ['ok' => false, 'message' => 'Access denied: You can only start Demo Classes assigned to you.'];
        }

        $now = gmdate('Y-m-d H:i:s');
        $this->db->table($this->batchesTable)->where('id', $batchId)->update([
            'meeting_link' => $meetingLink,
            'status'       => 'live',
            'started_at'   => $now,
            'updated_at'   => $now,
        ]);

        $this->db->table($this->table)->where('demo_batch_id', $batchId)->update([
            'meeting_link' => $meetingLink,
            'status'       => 'demo_live',
            'updated_at'   => $now,
        ]);

        $attMap = $post['attendance'] ?? [];
        if (is_array($attMap)) {
            foreach ($attMap as $leadIdRaw => $attVal) {
                $lid = (int) $leadIdRaw;
                $st  = in_array($attVal, ['Present', 'Absent'], true) ? $attVal : 'Pending';
                if ($lid > 0) {
                    $this->db->table($this->table)
                        ->where('id', $lid)
                        ->where('demo_batch_id', $batchId)
                        ->update(['attendance_status' => $st, 'updated_at' => $now]);
                }
            }
        }

        $this->writeDemoLiveSignal($batchId, (string) ($batch['batch_title'] ?? 'Live Demo Class'), 'live');
        return [
            'ok'      => true,
            'message' => '🔴 Demo Class "' . ($batch['batch_title'] ?? '') . '" is now LIVE! All assigned students can now join.',
        ];
    }

    public function updateMidClassDemoAttendance(int $teacherId, array $post, bool $isAdmin = false): array
    {
        $batchId = (int) ($post['demo_batch_id'] ?? 0);
        $attMap  = $post['attendance'] ?? [];
        $now     = gmdate('Y-m-d H:i:s');

        if ($batchId <= 0 || ! is_array($attMap)) {
            return ['ok' => false, 'message' => 'Invalid Demo Slot or attendance data.'];
        }

        $batch = $this->db->table($this->batchesTable)->where('id', $batchId)->get()->getRowArray();
        if (! $batch || (! $isAdmin && (int) ($batch['teacher_user_id'] ?? 0) !== $teacherId)) {
            return ['ok' => false, 'message' => 'Access denied.'];
        }

        foreach ($attMap as $leadIdRaw => $attVal) {
            $lid = (int) $leadIdRaw;
            $st  = in_array($attVal, ['Present', 'Absent'], true) ? $attVal : 'Pending';
            if ($lid > 0) {
                $this->db->table($this->table)
                    ->where('id', $lid)
                    ->where('demo_batch_id', $batchId)
                    ->update(['attendance_status' => $st, 'updated_at' => $now]);
            }
        }

        return ['ok' => true, 'message' => '✓ Live Demo Attendance updated!'];
    }

    public function finalizeAndEndDemoBatch(int $teacherId, array $post, bool $isAdmin = false): array
    {
        $batchId = (int) ($post['demo_batch_id'] ?? 0);
        $attMap  = $post['attendance'] ?? [];
        $remMap  = $post['remarks'] ?? [];
        $now     = gmdate('Y-m-d H:i:s');

        if ($batchId <= 0) {
            return ['ok' => false, 'message' => 'Invalid Demo Class Slot.'];
        }

        $batch = $this->db->table($this->batchesTable)->where('id', $batchId)->get()->getRowArray();
        if (! $batch || (! $isAdmin && (int) ($batch['teacher_user_id'] ?? 0) !== $teacherId)) {
            return ['ok' => false, 'message' => 'Access denied.'];
        }

        $leads        = $this->db->table($this->table)->where('demo_batch_id', $batchId)->get()->getResultArray();
        $presentCount = 0;
        $absentCount  = 0;

        foreach ($leads as $ld) {
            $lid       = (int) $ld['id'];
            $finalAtt  = $attMap[$lid] ?? ($ld['attendance_status'] !== 'Pending' ? $ld['attendance_status'] : 'Present');
            $isPresent = ($finalAtt === 'Present');
            $remark    = isset($remMap[$lid]) ? trim((string) $remMap[$lid]) : (string) ($ld['teacher_eval_note'] ?? '');

            if ($isPresent) {
                $presentCount++;
            } else {
                $absentCount++;
            }

            $this->db->table($this->table)->where('id', $lid)->update([
                'attendance_status' => $isPresent ? 'Present' : 'Absent',
                'status'            => $isPresent ? 'demo_attended' : 'reschedule_needed',
                'teacher_eval_note' => $remark !== '' ? $remark : ($isPresent ? 'Attended Live Demo Class' : 'Absent in Demo — Reschedule Needed'),
                'updated_at'        => $now,
            ]);
        }

        $this->db->table($this->batchesTable)->where('id', $batchId)->update([
            'status'     => 'completed',
            'ended_at'   => $now,
            'updated_at' => $now,
        ]);

        return [
            'ok'      => true,
            'message' => '✅ Demo Class Completed! (' . $presentCount . ' Demo Attended, ' . $absentCount . ' Absent/Reschedule) — Remarks sent to Admin.',
        ];
    }

    public function convertDemoLeadToEnrolledStudent(int $leadId, int $overrideCourseId = 0, int $targetCohortId = 0): array
    {
        $lead = $this->find($leadId);
        if (! $lead) {
            return ['ok' => false, 'message' => 'Demo Lead not found.'];
        }

        $cohorts  = $this->getActiveCohortsMap();
        $courseId = $overrideCourseId > 0
            ? $overrideCourseId
            : ((int) ($lead['recommended_node_id'] ?: $lead['course_node_id']));

        if ($targetCohortId > 0 && isset($cohorts[$targetCohortId])) {
            $courseId = (int) ($cohorts[$targetCohortId]['leaf_node_id'] ?: $courseId);
        }

        $courses     = $this->getEnrollableCoursesMap(false);
        $courseTitle = $courses[$courseId]['title'] ?? 'Enrolled Course';
        $parentId    = (int) ($lead['converted_parent_id'] ?? 0);
        $studentId   = (int) ($lead['converted_student_id'] ?? 0);
        $now         = gmdate('Y-m-d H:i:s');
        $uCols       = array_flip($this->db->getFieldNames('users'));

        if ($parentId <= 0) {
            $pEmail = strtolower(trim((string) ($lead['parent_email'] ?: ('parent.' . $leadId . '@portal.local'))));
            $exP    = $this->db->table('users')->where('email', $pEmail)->get()->getRowArray();
            if ($exP) {
                $parentId = (int) $exP['id'];
            } else {
                $this->db->table('users')->insert(array_intersect_key([
                    'role_id'       => 5,
                    'role_slug'     => 'parent',
                    'full_name'     => $lead['parent_name'] ?: 'Parent',
                    'email'         => $pEmail,
                    'password_hash' => password_hash('Parent@123', PASSWORD_DEFAULT),
                    'status'        => 'active',
                    'created_at'    => $now,
                ], $uCols));
                $parentId = (int) $this->db->insertID();
            }
        }

        if ($studentId <= 0) {
            $slugKid = preg_replace('/[^a-z0-9]+/', '', strtolower((string) $lead['child_name'])) ?: 'learner';
            $this->db->table('users')->insert(array_intersect_key([
                'role_id'        => 4,
                'role_slug'      => 'student',
                'parent_user_id' => $parentId,
                'full_name'      => $lead['child_name'] ?: 'Student',
                'email'          => $slugKid . '.' . $parentId . '.' . rand(100, 999) . '@student.portal',
                'password_hash'  => password_hash('Kid@123', PASSWORD_DEFAULT),
                'status'        => 'active',
                'created_at'     => $now,
            ], $uCols));
            $studentId = (int) $this->db->insertID();
        }

        $batchMsg = '';
        if ($targetCohortId <= 0 && ! empty($cohorts)) {
            foreach ($cohorts as $cid => $cRow) {
                if ((int) $cRow['leaf_node_id'] === $courseId) {
                    $targetCohortId = (int) $cid;
                    break;
                }
            }
        }
        if ($targetCohortId > 0 && isset($cohorts[$targetCohortId]) && $this->db->tableExists('lms_classroom_batch_students')) {
            $this->db->query("
                INSERT INTO `lms_classroom_batch_students` (`cohort_id`, `leaf_node_id`, `student_user_id`, `status`, `assigned_at`)
                VALUES (?, ?, ?, 'active', ?)
                ON DUPLICATE KEY UPDATE `status` = 'active', `leaf_node_id` = VALUES(`leaf_node_id`)
            ", [$targetCohortId, $courseId, $studentId, $now]);
            $batchMsg = ' & added to Batch "' . $cohorts[$targetCohortId]['cohort_title'] . '"';
        }

        $countryCode = strtoupper(trim((string) ($lead['country_code'] ?: 'IN')));
        $currCode    = strtoupper(trim((string) ($lead['currency_code'] ?: 'INR')));
        $monthlyFee  = 1000.00;
        if ($this->db->tableExists('lms_hierarchy_pricing') && $courseId > 0) {
            $pRow = $this->db->table('lms_hierarchy_pricing')
                ->where('node_id', $courseId)
                ->where('country_code', $countryCode)
                ->get()
                ->getRowArray();
            if (! $pRow) {
                $pRow = $this->db->table('lms_hierarchy_pricing')->where('node_id', $courseId)->get()->getRowArray();
            }
            if ($pRow && (float) $pRow['monthly_fee'] > 0) {
                $monthlyFee = (float) $pRow['monthly_fee'];
                $currCode   = $pRow['currency_code'] ?: $currCode;
            }
        }

        $batchStart = ($targetCohortId > 0 && ! empty($cohorts[$targetCohortId]['start_date']))
            ? $cohorts[$targetCohortId]['start_date']
            : date('Y-m-01');
        $batchEnd   = ($targetCohortId > 0 && ! empty($cohorts[$targetCohortId]['end_date']))
            ? $cohorts[$targetCohortId]['end_date']
            : date('Y-m-t', strtotime('+3 months'));
        $nextDueYmd = date('Y-m-01', strtotime('+1 month'));

        if ($this->db->tableExists('cms_user_meta') && $studentId > 0) {
            $exMeta = $this->db->table('cms_user_meta')->where('user_id', $studentId)->get()->getRowArray();
            if ($exMeta) {
                $this->db->table('cms_user_meta')->where('user_id', $studentId)->update([
                    'leaf_node_id' => $courseId,
                    'grade_level'  => $courseTitle,
                    'updated_at'   => $now,
                ]);
            } else {
                $this->db->table('cms_user_meta')->insert([
                    'user_id'       => $studentId,
                    'leaf_node_id'  => $courseId,
                    'child_age'     => (int) ($lead['child_age'] ?: 8),
                    'grade_level'   => $courseTitle,
                    'country_code'  => $countryCode,
                    'currency_code' => $currCode,
                    'timezone'      => (string) ($lead['timezone'] ?: 'Asia/Kolkata'),
                    'avatar_color'  => '#10b981',
                    'notes'         => '',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }

        if ($this->db->tableExists('lms_hierarchy_enrollments') && $courseId > 0) {
            $exEnr = $this->db->table('lms_hierarchy_enrollments')
                ->where('student_user_id', $studentId)
                ->where('leaf_node_id', $courseId)
                ->get()
                ->getRowArray();
            if ($exEnr) {
                $this->db->table('lms_hierarchy_enrollments')->where('id', $exEnr['id'])->update([
                    'parent_user_id' => $parentId,
                    'monthly_fee'    => $monthlyFee,
                    'currency_code'  => $currCode,
                    'status'         => 'active',
                    'updated_at'     => $now,
                ]);
            } else {
                $this->db->table('lms_hierarchy_enrollments')->insert([
                    'student_user_id'    => $studentId,
                    'parent_user_id'     => $parentId,
                    'leaf_node_id'       => $courseId,
                    'country_code'       => $countryCode,
                    'currency_code'      => $currCode,
                    'monthly_fee'        => $monthlyFee,
                    'billing_cycle'      => 'monthly',
                    'next_due_date'      => $nextDueYmd,
                    'attendance_pct'     => 100.00,
                    'test_score_avg'     => 0.00,
                    'certificates_count' => 0,
                    'history_json'       => json_encode([]),
                    'status'             => 'active',
                    'enrolled_at'        => $now,
                    'updated_at'         => $now,
                ]);
            }
        }

        if ($this->db->tableExists('lms_student_subscriptions') && $courseId > 0) {
            $sCols = array_flip($this->db->getFieldNames('lms_student_subscriptions'));
            $exSub = $this->db->table('lms_student_subscriptions')
                ->where('student_user_id', $studentId)
                ->where('node_id', $courseId)
                ->get()
                ->getRowArray();

            $subData = array_intersect_key([
                'student_user_id'   => $studentId,
                'parent_user_id'    => $parentId,
                'node_id'           => $courseId,
                'billing_cycle'     => 'Monthly',
                'country_code'      => $countryCode,
                'currency'          => $currCode,
                'fee_amount'        => $monthlyFee,
                'batch_start_date'  => $batchStart,
                'batch_end_date'    => $batchEnd,
                'due_date'          => $batchStart,
                'grace_period_days' => 5,
                'status'            => 'active',
                'updated_at'        => $now,
            ], $sCols);

            if ($exSub) {
                $this->db->table('lms_student_subscriptions')->where('id', $exSub['id'])->update($subData);
            } else {
                if (isset($sCols['created_at'])) {
                    $subData['created_at'] = $now;
                }
                if (isset($sCols['paid_cycles_count'])) {
                    $subData['paid_cycles_count'] = 0;
                }
                if (isset($sCols['is_access_locked'])) {
                    $subData['is_access_locked'] = 0;
                }
                $this->db->table('lms_student_subscriptions')->insert($subData);
            }
        }

        $this->update($leadId, [
            'course_node_id'       => $courseId,
            'status'               => 'converted',
            'converted_parent_id'  => $parentId,
            'converted_student_id' => $studentId,
            'updated_at'           => $now,
        ]);

        return [
            'ok'      => true,
            'message' => '🚀 Converted "' . $lead['child_name'] . '" into "' . $courseTitle . '"' . $batchMsg . ' & synced Fee Subscription!',
        ];
    }

    public function saveOrUpdateDemoBatch(array $post): array
    {
        $title       = trim((string) ($post['batch_title'] ?? ''));
        $courseId    = (int) ($post['course_node_id'] ?? 0);
        $teacherId   = (int) ($post['teacher_user_id'] ?? 0);
        $demoDate    = trim((string) ($post['demo_date'] ?? date('Y-m-d', strtotime('+1 day'))));
        $timeIst     = trim((string) ($post['start_time_ist'] ?? '17:00')) ?: '17:00';
        $durationMin = max(15, min(120, (int) ($post['duration_min'] ?? 30)));
        $adminId     = (int) (session()->get('user_id') ?: (session()->get('id') ?: 0));

        if ($title === '' || $teacherId <= 0) {
            return ['ok' => false, 'message' => 'Enter a Demo Slot Title and select a Teacher.'];
        }

        $istDatetimeStr = $demoDate . ' ' . (strlen($timeIst) === 5 ? ($timeIst . ':00') : $timeIst);
        $scheduledUtc   = gmdate('Y-m-d H:i:s');
        try {
            $dtIst = new DateTime($istDatetimeStr, new DateTimeZone('Asia/Kolkata'));
            $dtIst->setTimezone(new DateTimeZone('UTC'));
            $scheduledUtc = $dtIst->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
        }

        $now        = gmdate('Y-m-d H:i:s');
        $actualCols = array_flip($this->db->getFieldNames($this->batchesTable));
        $row = array_intersect_key([
            'batch_title'     => $title,
            'course_node_id'  => $courseId,
            'teacher_user_id' => $teacherId,
            'admin_ist_input' => $istDatetimeStr,
            'demo_date'       => $demoDate,
            'start_time_ist'  => $timeIst,
            'scheduled_utc'   => $scheduledUtc,
            'duration_min'    => $durationMin,
            'max_seats'       => 10,
            'meeting_link'    => '',
            'status'          => 'scheduled',
            'created_by'      => $adminId,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $actualCols);

        $this->db->table($this->batchesTable)->insert($row);
        return ['ok' => true, 'message' => 'Demo Class Slot "' . $title . '" (' . $demoDate . ' @ ' . $this->formatTimeAmPm($timeIst) . ') created!'];
    }

    public function getEnrichedLeads(int $teacherIdFilter = 0): array
    {
        $courses  = $this->getEnrollableCoursesMap(false);
        $teachers = $this->getTeachersMap();
        $batches  = [];
        foreach ($this->db->table($this->batchesTable)->get()->getResultArray() as $b) {
            $batches[(int) $b['id']] = $b;
        }

        $builder = $this->db->table($this->table)->orderBy('id', 'DESC');
        if ($teacherIdFilter > 0) {
            $builder->where('assigned_teacher_id', $teacherIdFilter)->where('demo_batch_id >', 0);
        }

        $rows = $builder->limit(300)->get()->getResultArray();
        foreach ($rows as &$r) {
            $cid   = (int) ($r['course_node_id'] ?? 0);
            $bid   = (int) ($r['demo_batch_id'] ?? 0);
            $bInfo = $batches[$bid] ?? null;
            $tid   = (int) ($bInfo['teacher_user_id'] ?? ($r['assigned_teacher_id'] ?? 0));

            $r['course_title']     = $courses[$cid]['breadcrumb_path'] ?? 'Course Demo';
            $r['teacher_name']     = $tid > 0 && isset($teachers[$tid]) ? $teachers[$tid]['display_name'] : 'Unassigned';
            $r['demo_batch_title'] = $bInfo ? ($bInfo['batch_title'] ?? 'Demo Slot') : 'Not Assigned to Slot';
            $r['slot_date']        = $bInfo && ! empty($bInfo['demo_date']) ? $bInfo['demo_date'] : ($r['preferred_date'] ?? '');
            $r['slot_time_ampm']   = $bInfo && ! empty($bInfo['start_time_ist']) ? $this->formatTimeAmPm((string) $bInfo['start_time_ist']) : ($r['preferred_time_slot'] ?? '');
            $r['slot_status']      = $bInfo ? strtolower((string) ($bInfo['status'] ?? 'scheduled')) : 'unassigned';
        }
        unset($r);
        return $rows;
    }

    public function getEnrichedDemoBatches(int $teacherIdFilter = 0, bool $onlyWithAssignedStudents = false): array
    {
        $courses  = $this->getEnrollableCoursesMap(false);
        $teachers = $this->getTeachersMap();

        $builder = $this->db->table($this->batchesTable)->orderBy('demo_date', 'DESC')->orderBy('id', 'DESC');
        if ($teacherIdFilter > 0) {
            $builder->where('teacher_user_id', $teacherIdFilter);
        }

        $rows = $builder->get()->getResultArray();

        $leadsByBatch = [];
        foreach ($this->db->table($this->table)->where('demo_batch_id >', 0)->orderBy('id', 'ASC')->get()->getResultArray() as $ld) {
            $bid = (int) ($ld['demo_batch_id'] ?? 0);
            $cid = (int) ($ld['course_node_id'] ?? 0);
            $ld['course_title']   = $courses[$cid]['breadcrumb_path'] ?? ($courses[$cid]['title'] ?? 'Course Demo');
            $leadsByBatch[$bid][] = $ld;
        }

        $out = [];
        foreach ($rows as $r) {
            $bid           = (int) $r['id'];
            $cid           = (int) ($r['course_node_id'] ?? 0);
            $tid           = (int) ($r['teacher_user_id'] ?? 0);
            $assignedLeads = $leadsByBatch[$bid] ?? [];

            if ($onlyWithAssignedStudents && empty($assignedLeads)) {
                continue;
            }

            $r['batch_title']     = $r['batch_title'] ?? 'Demo Slot';
            $r['demo_date']       = ! empty($r['demo_date']) ? $r['demo_date'] : substr((string) ($r['scheduled_utc'] ?? date('Y-m-d')), 0, 10);
            $r['start_time_ist']  = $r['start_time_ist'] ?? '17:00';
            $r['start_time_ampm'] = $this->formatTimeAmPm((string) $r['start_time_ist']);
            $r['meeting_link']    = $r['meeting_link'] ?? '';
            $r['course_title']    = $courses[$cid]['breadcrumb_path'] ?? ($courses[$cid]['title'] ?? 'All Demo Courses');
            $r['teacher_name']    = $tid > 0 && isset($teachers[$tid]) ? $teachers[$tid]['display_name'] : 'Unassigned';
            $r['assigned_leads']  = $assignedLeads;
            $r['students_count']  = count($assignedLeads);
            $out[]                = $r;
        }

        return $out;
    }

    public function writeDemoLiveSignal(int $batchId, string $batchTitle, string $status): void
    {
        try {
            $sigFile = FCPATH . 'assets/addons/lms_timetable/live_signal.json';
            $existing = is_file($sigFile) ? (json_decode((string) @file_get_contents($sigFile), true) ?: []) : [];
            $sessions = $existing['sessions'] ?? [];
            $stuIds = [];
            foreach ($this->db->table($this->table)->select('converted_student_id')->where('demo_batch_id', $batchId)->get()->getResultArray() as $r) {
                if ((int) ($r['converted_student_id'] ?? 0) > 0) {
                    $stuIds[] = (int) $r['converted_student_id'];
                }
            }
            $key = 'demo_' . $batchId;
            $sessions[$key] = [
                'session_id'   => 900000 + $batchId,
                'cohort_id'    => $batchId,
                'cohort_title' => '🎯 Free Demo: ' . $batchTitle,
                'teacher_name' => 'Demo Instructor',
                'meeting_link' => base_url('demo/book'),
                'pdf_url'      => '',
                'status'       => $status,
                'student_ids'  => $stuIds,
                'updated_ts'   => time(),
            ];
            @is_dir(dirname($sigFile)) || @mkdir(dirname($sigFile), 0775, true);
            @file_put_contents($sigFile, json_encode(['version_ts' => time(), 'sessions' => $sessions], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (Throwable $e) {
        }
    }
}

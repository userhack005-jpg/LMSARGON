<?php

namespace App\Models\Addons\LmsTimetable;

use CodeIgniter\Model;
use DateTime;
use DateTimeZone;
use Throwable;

class LmsTimetableModel extends Model
{
    protected $table            = 'lms_class_sessions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'cohort_id', 'node_id', 'session_title', 'schedule_mode',
        'scheduled_start_utc', 'scheduled_end_utc', 'assigned_teacher_id',
        'is_substituted', 'original_teacher_id', 'actual_start_utc',
        'actual_end_utc', 'duration_minutes', 'teacher_attendance_status',
        'assignment_note', 'material_pdf_url', 'meeting_link', 'status',
        'created_at', 'updated_at',
    ];

    protected string $cohortsTable   = 'lms_classroom_batches';
    protected string $cohortStuTable = 'lms_classroom_batch_students';
    protected string $stuAttTable    = 'lms_session_student_attendance';
    protected string $subReqTable    = 'lms_teacher_sub_requests';
    protected string $subMsgTable    = 'lms_teacher_sub_messages';
    protected static ?array $globalCache = null;

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
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_classroom_batches` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `leaf_node_id` INT UNSIGNED NOT NULL,
                    `cohort_code` VARCHAR(40) NOT NULL DEFAULT '',
                    `cohort_title` VARCHAR(160) NOT NULL,
                    `teacher_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `start_date` DATE NOT NULL,
                    `end_date` DATE NOT NULL,
                    `schedule_mode` VARCHAR(30) NOT NULL DEFAULT 'weekdays',
                    `weekdays_json` VARCHAR(120) NOT NULL DEFAULT '[\"1\",\"3\",\"5\"]',
                    `start_time_ist` VARCHAR(10) NOT NULL DEFAULT '17:00',
                    `class_duration_min` SMALLINT UNSIGNED NOT NULL DEFAULT 60,
                    `max_seats` SMALLINT UNSIGNED NOT NULL DEFAULT 25,
                    `material_pdf_url` VARCHAR(255) NOT NULL DEFAULT '',
                    `meeting_link` VARCHAR(255) NOT NULL DEFAULT 'https://meet.google.com/new',
                    `status` VARCHAR(25) NOT NULL DEFAULT 'active',
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_leaf_status` (`leaf_node_id`, `status`),
                    KEY `idx_cohort_teacher` (`teacher_user_id`, `status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $cCols = $this->db->getFieldNames('lms_classroom_batches');
            if (! in_array('material_pdf_url', $cCols, true)) {
                $this->db->query("ALTER TABLE `lms_classroom_batches` ADD COLUMN `material_pdf_url` VARCHAR(255) NOT NULL DEFAULT ''");
            }

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_classroom_batch_students` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `cohort_id` INT UNSIGNED NOT NULL,
                    `leaf_node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `student_user_id` INT UNSIGNED NOT NULL,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                    `assigned_at` DATETIME NULL,
                    UNIQUE KEY `uniq_cohort_student` (`cohort_id`, `student_user_id`),
                    KEY `idx_stu_cohort` (`student_user_id`, `status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_class_sessions` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `cohort_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `node_id` INT UNSIGNED NOT NULL,
                    `session_title` VARCHAR(160) NOT NULL DEFAULT 'Live Batch Session',
                    `schedule_mode` VARCHAR(30) NOT NULL DEFAULT 'weekdays',
                    `scheduled_start_utc` DATETIME NOT NULL,
                    `scheduled_end_utc` DATETIME NOT NULL,
                    `assigned_teacher_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `is_substituted` TINYINT(1) NOT NULL DEFAULT 0,
                    `original_teacher_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `actual_start_utc` DATETIME NULL,
                    `actual_end_utc` DATETIME NULL,
                    `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
                    `teacher_attendance_status` VARCHAR(40) NOT NULL DEFAULT 'Pending',
                    `assignment_note` TEXT NULL,
                    `material_pdf_url` VARCHAR(255) NOT NULL DEFAULT '',
                    `meeting_link` VARCHAR(255) NOT NULL DEFAULT 'https://meet.google.com/new',
                    `status` VARCHAR(25) NOT NULL DEFAULT 'scheduled',
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_cohort_start` (`cohort_id`, `scheduled_start_utc`),
                    KEY `idx_node_start` (`node_id`, `scheduled_start_utc`),
                    KEY `idx_teacher_start` (`assigned_teacher_id`, `scheduled_start_utc`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $sCols = $this->db->getFieldNames('lms_class_sessions');
            if (! in_array('cohort_id', $sCols, true)) {
                $this->db->query("ALTER TABLE `lms_class_sessions` ADD COLUMN `cohort_id` INT UNSIGNED NOT NULL DEFAULT 0");
            }
            if (! in_array('material_pdf_url', $sCols, true)) {
                $this->db->query("ALTER TABLE `lms_class_sessions` ADD COLUMN `material_pdf_url` VARCHAR(255) NOT NULL DEFAULT ''");
            }

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_session_student_attendance` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `session_id` INT UNSIGNED NOT NULL,
                    `student_user_id` INT UNSIGNED NOT NULL,
                    `is_guest_makeup` TINYINT(1) NOT NULL DEFAULT 0,
                    `guest_reason` VARCHAR(190) NOT NULL DEFAULT '',
                    `attendance_status` VARCHAR(25) NOT NULL DEFAULT 'Present',
                    `marked_by_teacher_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    UNIQUE KEY `uniq_session_student` (`session_id`, `student_user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_teacher_sub_requests` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `teacher_user_id` INT UNSIGNED NOT NULL,
                    `cohort_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `session_ids_json` TEXT NOT NULL,
                    `reason_note` TEXT NOT NULL,
                    `substitute_teacher_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_teacher_status` (`teacher_user_id`, `status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $srCols = $this->db->getFieldNames('lms_teacher_sub_requests');
            if (! in_array('cohort_id', $srCols, true)) {
                $this->db->query("ALTER TABLE `lms_teacher_sub_requests` ADD COLUMN `cohort_id` INT UNSIGNED NOT NULL DEFAULT 0");
            }

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_teacher_sub_messages` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `request_id` INT UNSIGNED NOT NULL,
                    `sender_user_id` INT UNSIGNED NOT NULL,
                    `sender_role` VARCHAR(40) NOT NULL DEFAULT 'teacher',
                    `message_text` TEXT NOT NULL,
                    `created_at` DATETIME NULL,
                    KEY `idx_req_id` (`request_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $lockFile = WRITEPATH . 'addons/schema_addon_lms_timetable_v90.lock';
            if (is_file($lockFile)) {
                return;
            }

            if ($this->db->tableExists('addons')) {
                $this->db->table('addons')->where('slug', 'lms_timetable')->update(['name' => 'Live Batches, Timetable & Attendance']);
            }

            $this->cleanupOrphanedAndDuplicateSessions();
            $this->syncRbacPermissionsAndAutoEnableRoles();
            $this->mirrorAssets(true);
            $this->writeStaticLiveSignalFile();

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, date('c'));
        } catch (Throwable $e) {
            log_message('error', 'LmsTimetableModel schema error: ' . $e->getMessage());
        }
    }

    /**
     * Writes 0ms Static Signal (`public/assets/addons/lms_timetable/live_signal.json`)
     * Includes `student_ids` so ONLY enrolled students/parents see the live class popup!
     */
    public function writeStaticLiveSignalFile(): void
    {
        try {
            $users   = $this->getRoleUsers()['all'];
            $cohorts = [];
            foreach ($this->db->table($this->cohortsTable)->get()->getResultArray() as $c) {
                $cohorts[(int) $c['id']] = $c;
            }

            $cohortStuMap = [];
            foreach ($this->db->table($this->cohortStuTable)->where('status', 'active')->get()->getResultArray() as $cs) {
                $cohortStuMap[(int) $cs['cohort_id']][] = (int) $cs['student_user_id'];
            }

            $liveRows = $this->db->table($this->table)
                ->whereIn('status', ['live', 'completed'])
                ->orderBy('updated_at', 'DESC')
                ->limit(40)
                ->get()
                ->getResultArray();

            $liveMap = [];
            foreach ($liveRows as $r) {
                $sid  = (int) $r['id'];
                $cid  = (int) ($r['cohort_id'] ?? 0);
                $nid  = (int) ($r['node_id'] ?? 0);
                $tid  = (int) ($r['assigned_teacher_id'] ?? 0);
                $rawP = trim((string) ($r['material_pdf_url'] ?: ($cid > 0 && isset($cohorts[$cid]) ? ($cohorts[$cid]['material_pdf_url'] ?? '') : '')));
                $resP = ($rawP !== '' && strpos($rawP, 'http') !== 0) ? base_url(ltrim($rawP, '/')) : $rawP;

                $liveMap[$sid] = [
                    'session_id'   => $sid,
                    'cohort_id'    => $cid,
                    'node_id'      => $nid,
                    'cohort_title' => $cid > 0 && isset($cohorts[$cid]) ? $cohorts[$cid]['cohort_title'] : $r['session_title'],
                    'teacher_name' => $users[$tid]['display_name'] ?? 'Instructor',
                    'meeting_link' => $r['meeting_link'] ?: 'https://meet.google.com/new',
                    'pdf_url'      => $resP,
                    'status'       => $r['status'],
                    'student_ids'  => $cohortStuMap[$cid] ?? [],
                    'updated_ts'   => strtotime((string) ($r['updated_at'] ?: gmdate('Y-m-d H:i:s')) . ' UTC'),
                ];
            }

            $dstDir = FCPATH . 'assets/addons/lms_timetable';
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            @file_put_contents($dstDir . '/live_signal.json', json_encode([
                'version_ts' => time(),
                'sessions'   => $liveMap,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (Throwable $e) {
        }
    }

    public function cleanupOrphanedAndDuplicateSessions(): array
    {
        $removedOrphans    = 0;
        $removedDuplicates = 0;
        try {
            if ($this->db->tableExists('users')) {
                $this->db->query("DELETE cs FROM `lms_classroom_batch_students` cs LEFT JOIN `users` u ON u.id = cs.student_user_id WHERE u.id IS NULL");
                $this->db->query("DELETE sa FROM `lms_session_student_attendance` sa LEFT JOIN `users` u ON u.id = sa.student_user_id WHERE u.id IS NULL");
            }

            $validCohortIds = [];
            foreach ($this->db->table($this->cohortsTable)->select('id')->get()->getResultArray() as $c) {
                $validCohortIds[(int) $c['id']] = true;
            }

            $allSessions = $this->db->table($this->table)->orderBy('id', 'ASC')->get()->getResultArray();
            $seenKeys    = [];
            $deleteIds   = [];

            foreach ($allSessions as $s) {
                $sid   = (int) $s['id'];
                $cid   = (int) ($s['cohort_id'] ?? 0);
                $nid   = (int) ($s['node_id'] ?? 0);
                $tid   = (int) ($s['assigned_teacher_id'] ?? 0);
                $stUtc = (string) ($s['scheduled_start_utc'] ?? '');
                $mode  = (string) ($s['schedule_mode'] ?? '');

                if ($cid > 0 && ! isset($validCohortIds[$cid])) {
                    $deleteIds[] = $sid;
                    $removedOrphans++;
                    continue;
                }
                if ($cid === 0 && empty($validCohortIds) && $mode !== 'makeup_session') {
                    $deleteIds[] = $sid;
                    $removedOrphans++;
                    continue;
                }

                $uniqKey = $cid . '_' . $nid . '_' . $tid . '_' . $stUtc;
                if (isset($seenKeys[$uniqKey])) {
                    $deleteIds[] = $sid;
                    $removedDuplicates++;
                    continue;
                }
                $seenKeys[$uniqKey] = $sid;
            }

            if (! empty($deleteIds)) {
                $this->db->table($this->table)->whereIn('id', $deleteIds)->delete();
                $this->db->table($this->stuAttTable)->whereIn('session_id', $deleteIds)->delete();
            }
        } catch (Throwable $e) {
        }
        return ['removed_orphans' => $removedOrphans, 'removed_duplicates' => $removedDuplicates];
    }

    public function deleteCohortWithCascade(int $cohortId): array
    {
        if ($cohortId <= 0) {
            return ['ok' => false, 'message' => 'Invalid Batch ID.'];
        }
        $cohortSessions = $this->db->table($this->table)->select('id')->where('cohort_id', $cohortId)->get()->getResultArray();
        $sessionIds     = array_map(static fn($r) => (int) $r['id'], $cohortSessions);

        if (! empty($sessionIds)) {
            $this->db->table($this->stuAttTable)->whereIn('session_id', $sessionIds)->delete();
            $this->db->table($this->table)->whereIn('id', $sessionIds)->delete();
        }
        $this->db->table($this->cohortStuTable)->where('cohort_id', $cohortId)->delete();
        $this->db->table($this->subReqTable)->where('cohort_id', $cohortId)->delete();
        $this->db->table($this->cohortsTable)->where('id', $cohortId)->delete();

        $this->cleanupOrphanedAndDuplicateSessions();
        $this->writeStaticLiveSignalFile();

        return ['ok' => true, 'message' => 'Live Classroom Batch and its ' . count($sessionIds) . ' scheduled classes deleted!'];
    }

    public function syncRbacPermissionsAndAutoEnableRoles(): void
    {
        try {
            $now = date('Y-m-d H:i:s');
            if ($this->db->tableExists('cms_rbac_features')) {
                $perms = [
                    ['lms_timetable.view',          'View My Live Class Schedule [Student + Parent + Teacher + Admin]'],
                    ['lms_timetable.create',        'Create Live Classroom Batches & Makeup Classes [Super Admin + Admin]'],
                    ['lms_timetable.edit',          'Reschedule Classes, Approve Substitutes & Edit Rules [Super Admin + Admin]'],
                    ['lms_timetable.teacher_panel', 'Instructor Console: 2-Step Start/End Class, Roll Call & Leave Request [Teacher]'],
                    ['lms_timetable.delete',        'Delete Live Batches & Class Sessions [Super Admin + Admin]'],
                ];
                foreach ($perms as $p) {
                    $this->db->query("
                        INSERT INTO `cms_rbac_features` (`perm_key`, `perm_label`, `module_group`, `source_type`, `created_at`)
                        VALUES (?, ?, 'Add-on: Live Batches, Timetable & Attendance', 'addon', ?)
                        ON DUPLICATE KEY UPDATE `perm_label` = VALUES(`perm_label`), `module_group` = VALUES(`module_group`)
                    ", [$p[0], $p[1], $now]);
                }
            }
        } catch (Throwable $e) {
        }
    }

    public function mirrorAssets(bool $forceTouch = false): void
    {
        $srcDir = WRITEPATH . 'addons/extracted/lms_timetable/assets';
        $dstDir = FCPATH . 'assets/addons/lms_timetable';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['lms_timetable.css', 'lms_timetable.js'] as $f) {
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
        if (self::$globalCache !== null) {
            return self::$globalCache;
        }
        self::$globalCache = ['site_title' => 'Portal', 'active_theme_mode' => 'dark', 'system_environment_mode' => 'testing'];
        try {
            if ($this->db->tableExists('settings')) {
                foreach ($this->db->table('settings')->get()->getResultArray() as $r) {
                    self::$globalCache[$r['setting_key']] = $r['setting_value'];
                }
            }
        } catch (Throwable $e) {
        }
        return self::$globalCache;
    }

    public function isProductionMode(): bool
    {
        if (function_exists('cms_is_sandbox_mode')) {
            return ! cms_is_sandbox_mode();
        }
        $s = $this->getGlobalSiteSettings();
        return strtolower(trim((string) ($s['system_environment_mode'] ?? 'testing'))) === 'production';
    }

    public function getMinDurationMinutes(): int
    {
        if ($this->db->tableExists('settings')) {
            $row = $this->db->table('settings')
                ->whereIn('setting_key', ['min_class_duration_minutes', 'lms_timetable__min_class_duration_minutes'])
                ->get()
                ->getRowArray();
            if ($row && (int) $row['setting_value'] > 0) {
                return (int) $row['setting_value'];
            }
        }
        return 30;
    }

    public function saveMinDurationSetting(int $minutes): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }
        $minutes = max(1, min(300, $minutes));
        $this->db->query("
            INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`)
            VALUES ('lms_timetable__min_class_duration_minutes', 'lms_timetable', ?)
            ON DUPLICATE KEY UPDATE `setting_group` = 'lms_timetable', `setting_value` = VALUES(`setting_value`)
        ", [(string) $minutes]);
    }

    public function getLeafNodesWithPaths(): array
    {
        if (! $this->db->tableExists('lms_hierarchy_nodes')) {
            return [];
        }
        $allNodes = [];
        foreach ($this->db->table('lms_hierarchy_nodes')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray() as $n) {
            $allNodes[(int) $n['id']] = $n;
        }
        $leaves = [];
        foreach ($allNodes as $nid => $nRow) {
            if ((int) ($nRow['is_leaf_node'] ?? 0) !== 1) {
                continue;
            }
            $pathParts = [$nRow['title']];
            $currPid   = (int) ($nRow['parent_id'] ?? 0);
            $guard     = 0;
            while ($currPid > 0 && isset($allNodes[$currPid]) && $guard < 12) {
                array_unshift($pathParts, $allNodes[$currPid]['title']);
                $currPid = (int) ($allNodes[$currPid]['parent_id'] ?? 0);
                $guard++;
            }
            $nRow['breadcrumb_path'] = implode(' › ', $pathParts);
            $leaves[$nid]            = $nRow;
        }
        return $leaves;
    }

    public function getRoleUsers(): array
    {
        if (! $this->db->tableExists('users')) {
            return ['teachers' => [], 'students' => [], 'parents' => [], 'all' => []];
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

        $subscribedLeafsByStu = [];
        if ($this->db->tableExists('lms_hierarchy_enrollments')) {
            foreach ($this->db->table('lms_hierarchy_enrollments')->where('status', 'active')->get()->getResultArray() as $enr) {
                $sid = (int) $enr['student_user_id'];
                $lid = (int) $enr['leaf_node_id'];
                $subscribedLeafsByStu[$sid][$lid] = $lid;
            }
        }
        if ($this->db->tableExists('lms_student_subscriptions')) {
            foreach ($this->db->table('lms_student_subscriptions')->where('status', 'active')->get()->getResultArray() as $sub) {
                $sid = (int) $sub['student_user_id'];
                $lid = (int) ($sub['node_id'] ?? 0);
                if ($lid > 0) {
                    $subscribedLeafsByStu[$sid][$lid] = $lid;
                }
            }
        }

        $rows     = $this->db->table('users')->orderBy('id', 'ASC')->get()->getResultArray();
        $teachers = [];
        $students = [];
        $parents  = [];
        $all      = [];

        foreach ($rows as $r) {
            $id     = (int) $r['id'];
            $roleId = (int) ($r['role_id'] ?? 4);
            $rSlug  = strtolower(trim((string) ($r['role_slug'] ?? 'student')));
            $r['display_name']     = $r['full_name'] ?? ($r['name'] ?? ($r['username'] ?? ($r['email'] ?? 'User')));
            $r['subscribed_leafs'] = array_values($subscribedLeafsByStu[$id] ?? []);
            $all[$id]              = $r;

            if (in_array($roleId, [1, 2], true) || in_array($rSlug, ['super_admin', 'admin'], true)) {
                continue;
            }
            if ($roleId === 3 || in_array($rSlug, $teacherSlugs, true)) {
                $teachers[$id] = $r;
            } elseif (in_array($rSlug, $parentSlugs, true)) {
                $parents[$id] = $r;
            } elseif (in_array($rSlug, $studentSlugs, true)) {
                $students[$id] = $r;
            }
        }
        return ['teachers' => $teachers, 'students' => $students, 'parents' => $parents, 'all' => $all];
    }

    /**
     * ZERO DOUBLE WORK: Finds Subscribed Students who are NOT YET assigned to a Live Classroom Batch (`lms_classroom_batch_students`)
     */
    public function getUnassignedSubscribedStudents(): array
    {
        $roleUsers = $this->getRoleUsers();
        $students  = $roleUsers['students'];
        $leaves    = $this->getLeafNodesWithPaths();

        $assignedPairs = [];
        foreach ($this->db->table($this->cohortStuTable)->where('status', 'active')->get()->getResultArray() as $cs) {
            $assignedPairs[(int) $cs['student_user_id'] . '_' . (int) $cs['leaf_node_id']] = true;
        }

        $waitingList = [];
        foreach ($students as $sid => $sRow) {
            foreach (($sRow['subscribed_leafs'] ?? []) as $lid) {
                $leafId = (int) $lid;
                if (! isset($assignedPairs[$sid . '_' . $leafId])) {
                    $waitingList[] = [
                        'student_id'   => (int) $sid,
                        'student_name' => $sRow['display_name'],
                        'leaf_node_id' => $leafId,
                        'course_title' => $leaves[$leafId]['title'] ?? 'Subscribed Course',
                    ];
                }
            }
        }
        return $waitingList;
    }

    public function getEnrichedCohorts(int $teacherIdFilter = 0, array $studentIdsFilter = []): array
    {
        $leaves   = $this->getLeafNodesWithPaths();
        $users    = $this->getRoleUsers()['all'];
        $stuLinks = $this->db->table($this->cohortStuTable)->where('status', 'active')->get()->getResultArray();

        $studentsByCohort = [];
        foreach ($stuLinks as $sl) {
            $cid = (int) $sl['cohort_id'];
            $sid = (int) $sl['student_user_id'];
            if (! isset($users[$sid])) {
                continue;
            }
            $studentsByCohort[$cid][$sid] = [
                'student_id'   => $sid,
                'student_name' => $users[$sid]['display_name'],
                'email'        => $users[$sid]['email'] ?? '',
            ];
        }

        $builder = $this->db->table($this->cohortsTable)->orderBy('id', 'DESC');
        if ($teacherIdFilter > 0) {
            $builder->where('teacher_user_id', $teacherIdFilter);
        }

        $rows = $builder->get()->getResultArray();
        $out  = [];

        foreach ($rows as $r) {
            $cid    = (int) $r['id'];
            $leafId = (int) $r['leaf_node_id'];
            $tid    = (int) $r['teacher_user_id'];

            $matchedChildNames = [];
            if (! empty($studentIdsFilter)) {
                foreach ($studentIdsFilter as $fStuId) {
                    $fId = (int) $fStuId;
                    if (isset($studentsByCohort[$cid][$fId])) {
                        $matchedChildNames[$fId] = $studentsByCohort[$cid][$fId]['student_name'];
                    }
                }
                if (empty($matchedChildNames)) {
                    continue;
                }
            }

            $pdfUrl = trim((string) ($r['material_pdf_url'] ?? ''));
            if ($pdfUrl !== '' && strpos($pdfUrl, 'http') !== 0) {
                $pdfUrl = base_url(ltrim($pdfUrl, '/'));
            }

            $r['resolved_pdf_url']    = $pdfUrl;
            $r['leaf_title']          = $leaves[$leafId]['title'] ?? 'Course';
            $r['leaf_breadcrumb']     = $leaves[$leafId]['breadcrumb_path'] ?? $r['leaf_title'];
            $r['teacher_name']        = $tid > 0 ? ($users[$tid]['display_name'] ?? 'Instructor') : 'Unassigned';
            $r['enrolled_students']   = array_values($studentsByCohort[$cid] ?? []);
            $r['enrolled_count']      = count($r['enrolled_students']);
            $r['matched_child_names'] = $matchedChildNames;
            $r['weekdays_array']      = json_decode((string) ($r['weekdays_json'] ?? '[]'), true) ?: [];
            $out[$cid]                = $r;
        }
        return $out;
    }

    private function handleUploadedPdf($uploadedFile, string $fallbackUrl = ''): string
    {
        if ($uploadedFile && $uploadedFile->isValid() && ! $uploadedFile->hasMoved()) {
            $ext = strtolower($uploadedFile->getClientExtension());
            if (in_array($ext, ['pdf', 'zip', 'png', 'jpg', 'jpeg', 'doc', 'docx'], true)) {
                $uploadDir = FCPATH . 'uploads/lms_timetable/';
                if (! is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $newName = 'class_mat_' . time() . '_' . $uploadedFile->getRandomName();
                $uploadedFile->move($uploadDir, $newName);
                return 'uploads/lms_timetable/' . $newName;
            }
        }
        return trim($fallbackUrl);
    }

    public function saveOrUpdateCohortBatch(array $post, $uploadedPdf = null): array
    {
        $id          = (int) ($post['id'] ?? 0);
        $leafNodeId  = (int) ($post['leaf_node_id'] ?? 0);
        $cohortTitle = trim((string) ($post['cohort_title'] ?? ''));
        $cohortCode  = strtoupper(trim((string) ($post['cohort_code'] ?? ''))) ?: ('BATCH-' . date('ym') . '-' . rand(10, 99));
        $teacherId   = (int) ($post['teacher_user_id'] ?? 0);
        $startDate   = trim((string) ($post['start_date'] ?? date('Y-m-d')));
        $endDate     = trim((string) ($post['end_date'] ?? date('Y-m-d', strtotime('+30 days'))));
        $mode        = in_array($post['schedule_mode'] ?? '', ['daily', 'weekdays'], true) ? $post['schedule_mode'] : 'weekdays';
        $weekdays    = $post['weekdays'] ?? ['1', '3', '5'];
        if (! is_array($weekdays)) {
            $weekdays = [$weekdays];
        }
        $startTime   = trim((string) ($post['start_time_ist'] ?? '17:00')) ?: '17:00';
        $durationMin = max(15, min(240, (int) ($post['class_duration_min'] ?? 60)));
        $maxSeats    = max(1, min(500, (int) ($post['max_seats'] ?? 25)));
        $meetingLink = trim((string) ($post['meeting_link'] ?? 'https://meet.google.com/new'));
        $pdfUrl      = $this->handleUploadedPdf($uploadedPdf, (string) ($post['material_pdf_url'] ?? ''));

        if ($leafNodeId <= 0 || $cohortTitle === '' || $teacherId <= 0) {
            return ['ok' => false, 'message' => 'Select a Course, enter a Batch Title, and assign a Teacher.'];
        }

        $now = gmdate('Y-m-d H:i:s');
        $row = [
            'leaf_node_id'       => $leafNodeId,
            'cohort_code'        => $cohortCode,
            'cohort_title'       => $cohortTitle,
            'teacher_user_id'    => $teacherId,
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'schedule_mode'      => $mode,
            'weekdays_json'      => json_encode(array_values($weekdays)),
            'start_time_ist'     => $startTime,
            'class_duration_min' => $durationMin,
            'max_seats'          => $maxSeats,
            'material_pdf_url'   => $pdfUrl,
            'meeting_link'       => $meetingLink,
            'status'             => 'active',
            'updated_at'         => $now,
        ];

        if ($id > 0) {
            $this->db->table($this->cohortsTable)->where('id', $id)->update($row);
            $cohortId = $id;
            $this->db->table($this->table)
                ->where('cohort_id', $cohortId)
                ->where('status', 'scheduled')
                ->update([
                    'assigned_teacher_id' => $teacherId,
                    'session_title'       => $cohortTitle,
                    'meeting_link'        => $meetingLink,
                    'updated_at'          => $now,
                ]);
        } else {
            $row['created_at'] = $now;
            $this->db->table($this->cohortsTable)->insert($row);
            $cohortId = (int) $this->db->insertID();
        }

        if ($this->db->tableExists('lms_hierarchy_nodes')) {
            $this->db->table('lms_hierarchy_nodes')->where('id', $leafNodeId)->update([
                'teacher_user_id' => $teacherId,
                'batch_schedule'  => $cohortTitle . ' (' . $startTime . ' IST)',
            ]);
        }

        $genMsg = '';
        if (! empty($post['auto_generate_sessions'])) {
            $genRes = $this->generateSessionsFromBuilder([
                'cohort_id'           => $cohortId,
                'node_id'             => $leafNodeId,
                'assigned_teacher_id' => $teacherId,
                'session_title'       => $cohortTitle,
                'schedule_mode'       => $mode,
                'start_date'          => $startDate,
                'end_date'            => $endDate,
                'weekdays'            => $weekdays,
                'start_time_ist'      => $startTime,
                'class_duration_min'  => $durationMin,
                'material_pdf_url'    => $pdfUrl,
                'meeting_link'        => $meetingLink,
            ]);
            if ($genRes['ok']) {
                $genMsg = ' & ' . $genRes['message'];
            }
        }

        $this->writeStaticLiveSignalFile();
        return ['ok' => true, 'cohort_id' => $cohortId, 'message' => 'Batch "' . $cohortTitle . '" saved' . $genMsg . '!'];
    }

    public function assignStudentsToCohortBatch(int $cohortId, array $studentIds): array
    {
        $cohort = $this->db->table($this->cohortsTable)->where('id', $cohortId)->get()->getRowArray();
        if (! $cohort) {
            return ['ok' => false, 'message' => 'Batch not found.'];
        }
        $leafId = (int) $cohort['leaf_node_id'];
        $now    = gmdate('Y-m-d H:i:s');
        $added  = 0;

        foreach ($studentIds as $sid) {
            $stuId = (int) $sid;
            if ($stuId <= 0) {
                continue;
            }
            $this->db->query("
                INSERT INTO `lms_classroom_batch_students` (`cohort_id`, `leaf_node_id`, `student_user_id`, `status`, `assigned_at`)
                VALUES (?, ?, ?, 'active', ?)
                ON DUPLICATE KEY UPDATE `status` = 'active', `leaf_node_id` = VALUES(`leaf_node_id`)
            ", [$cohortId, $leafId, $stuId, $now]);

            if ($this->db->tableExists('lms_hierarchy_enrollments')) {
                $exEnr = $this->db->table('lms_hierarchy_enrollments')
                    ->where('student_user_id', $stuId)
                    ->where('leaf_node_id', $leafId)
                    ->get()
                    ->getRowArray();
                if (! $exEnr) {
                    $this->db->table('lms_hierarchy_enrollments')->insert([
                        'student_user_id'    => $stuId,
                        'leaf_node_id'       => $leafId,
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
            $added++;
        }

        $this->writeStaticLiveSignalFile();
        return ['ok' => true, 'message' => 'Assigned ' . $added . ' student(s) into "' . $cohort['cohort_title'] . '"!'];
    }

    /**
     * 1-Click Single Class Reschedule (Date, Time, Teacher, or Meeting Link)
     */
    public function rescheduleSingleSession(array $post): array
    {
        $sessionId   = (int) ($post['session_id'] ?? 0);
        $newDateYmd  = trim((string) ($post['reschedule_date'] ?? ''));
        $newTimeIst  = trim((string) ($post['reschedule_time_ist'] ?? '17:00'));
        $durationMin = max(15, min(240, (int) ($post['duration_min'] ?? 60)));
        $teacherId   = (int) ($post['assigned_teacher_id'] ?? 0);
        $meetingLink = trim((string) ($post['meeting_link'] ?? ''));

        $sess = $this->find($sessionId);
        if (! $sess || $newDateYmd === '') {
            return ['ok' => false, 'message' => 'Select a valid Session and New Date.'];
        }

        try {
            $dtStartIst = new DateTime($newDateYmd . ' ' . $newTimeIst . ':00', new DateTimeZone('Asia/Kolkata'));
            $dtStartUtc = clone $dtStartIst;
            $dtStartUtc->setTimezone(new DateTimeZone('UTC'));
            $dtEndUtc   = clone $dtStartUtc;
            $dtEndUtc->modify('+' . $durationMin . ' minutes');

            $upd = [
                'scheduled_start_utc' => $dtStartUtc->format('Y-m-d H:i:s'),
                'scheduled_end_utc'   => $dtEndUtc->format('Y-m-d H:i:s'),
                'updated_at'          => gmdate('Y-m-d H:i:s'),
            ];
            if ($teacherId > 0) {
                $upd['assigned_teacher_id'] = $teacherId;
            }
            if ($meetingLink !== '') {
                $upd['meeting_link'] = $meetingLink;
            }

            $this->update($sessionId, $upd);
            return ['ok' => true, 'message' => 'Class rescheduled to ' . $newDateYmd . ' (' . $newTimeIst . ' IST)!'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Invalid date/time format.'];
        }
    }

    public function generateSessionsFromBuilder(array $post, $uploadedPdf = null): array
    {
        $cohortId    = (int) ($post['cohort_id'] ?? 0);
        $nodeId      = (int) ($post['node_id'] ?? 0);
        $teacherId   = (int) ($post['assigned_teacher_id'] ?? 0);
        $title       = trim((string) ($post['session_title'] ?? 'Live Batch Session')) ?: 'Live Batch Session';
        $mode        = in_array($post['schedule_mode'] ?? '', ['daily', 'weekdays', 'custom_dates'], true) ? $post['schedule_mode'] : 'weekdays';
        $startTime   = trim((string) ($post['start_time_ist'] ?? '17:00'));
        $durationMin = max(15, min(240, (int) ($post['class_duration_min'] ?? 60)));
        $meetingLink = trim((string) ($post['meeting_link'] ?? 'https://meet.google.com/new'));
        $pdfUrl      = $this->handleUploadedPdf($uploadedPdf, (string) ($post['material_pdf_url'] ?? ''));

        if ($cohortId > 0) {
            $cohort = $this->db->table($this->cohortsTable)->where('id', $cohortId)->get()->getRowArray();
            if ($cohort) {
                if ($nodeId <= 0) $nodeId = (int) $cohort['leaf_node_id'];
                if ($teacherId <= 0) $teacherId = (int) $cohort['teacher_user_id'];
                if ($title === 'Live Batch Session') $title = $cohort['cohort_title'];
                if ($pdfUrl === '' && ! empty($cohort['material_pdf_url'])) $pdfUrl = $cohort['material_pdf_url'];
            }
        }

        if ($nodeId <= 0 || $teacherId <= 0) {
            return ['ok' => false, 'message' => 'Select a Batch and an Assigned Teacher.'];
        }

        $targetDates = [];
        if ($mode === 'custom_dates') {
            foreach (explode(',', (string) ($post['custom_dates_csv'] ?? '')) as $d) {
                $cleanD = trim($d);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $cleanD)) {
                    $targetDates[] = $cleanD;
                }
            }
        } else {
            $fromDate = trim((string) ($post['start_date'] ?? date('Y-m-d')));
            $toDate   = trim((string) ($post['end_date'] ?? date('Y-m-d', strtotime('+14 days'))));
            $weekdays = $post['weekdays'] ?? ['1', '3', '5'];
            if (! is_array($weekdays)) {
                $weekdays = [$weekdays];
            }
            try {
                $cur    = new DateTime($fromDate);
                $end    = new DateTime($toDate);
                $safety = 0;
                while ($cur <= $end && $safety < 120) {
                    $dayNum = $cur->format('N');
                    if ($mode === 'daily' || in_array((string) $dayNum, $weekdays, true)) {
                        $targetDates[] = $cur->format('Y-m-d');
                    }
                    $cur->modify('+1 day');
                    $safety++;
                }
            } catch (Throwable $e) {
            }
        }

        if (empty($targetDates)) {
            return ['ok' => false, 'message' => 'No valid class dates matched your selection.'];
        }

        $now     = gmdate('Y-m-d H:i:s');
        $created = 0;
        $skipped = 0;

        foreach (array_unique($targetDates) as $ymd) {
            try {
                $dtStartIst = new DateTime($ymd . ' ' . $startTime . ':00', new DateTimeZone('Asia/Kolkata'));
                $dtStartUtc = clone $dtStartIst;
                $dtStartUtc->setTimezone(new DateTimeZone('UTC'));
                $dtEndUtc   = clone $dtStartUtc;
                $dtEndUtc->modify('+' . $durationMin . ' minutes');

                $startUtcStr = $dtStartUtc->format('Y-m-d H:i:s');
                $dup = $this->db->table($this->table)
                    ->where('cohort_id', $cohortId)
                    ->where('node_id', $nodeId)
                    ->where('scheduled_start_utc', $startUtcStr)
                    ->get()
                    ->getRowArray();

                if ($dup) {
                    $skipped++;
                    continue;
                }

                $this->db->table($this->table)->insert([
                    'cohort_id'                 => $cohortId,
                    'node_id'                   => $nodeId,
                    'session_title'             => $title,
                    'schedule_mode'             => $mode,
                    'scheduled_start_utc'       => $startUtcStr,
                    'scheduled_end_utc'         => $dtEndUtc->format('Y-m-d H:i:s'),
                    'assigned_teacher_id'       => $teacherId,
                    'is_substituted'            => 0,
                    'original_teacher_id'       => $teacherId,
                    'teacher_attendance_status' => 'Pending',
                    'material_pdf_url'          => $pdfUrl,
                    'meeting_link'              => $meetingLink,
                    'status'                    => 'scheduled',
                    'created_at'                => $now,
                    'updated_at'                => $now,
                ]);
                $created++;
            } catch (Throwable $e) {
            }
        }

        return ['ok' => true, 'message' => 'Generated ' . $created . ' class session(s)' . ($skipped > 0 ? ' (' . $skipped . ' existing skipped)' : '')];
    }

    public function saveMakeupClassOrGuestSlots(array $post): array
    {
        $makeupMode = trim((string) ($post['makeup_mode'] ?? 'existing_session'));
        $studentIds = $post['student_ids'] ?? [];
        if (! is_array($studentIds)) {
            $studentIds = ! empty($post['student_user_id']) ? [$post['student_user_id']] : [];
        }
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        $reason     = trim((string) ($post['guest_reason'] ?? 'Missed regular class — Makeup Slot')) ?: 'Makeup Slot';

        if (empty($studentIds)) {
            return ['ok' => false, 'message' => 'Please tick at least 1 Student for the Makeup Class.'];
        }

        $now       = gmdate('Y-m-d H:i:s');
        $sessionId = (int) ($post['session_id'] ?? 0);

        if ($makeupMode === 'new_makeup_session') {
            $cohortId    = (int) ($post['makeup_cohort_id'] ?? 0);
            $nodeId      = (int) ($post['makeup_node_id'] ?? 2);
            $teacherId   = (int) ($post['makeup_teacher_id'] ?? 0);
            $dateYmd     = trim((string) ($post['makeup_date'] ?? date('Y-m-d')));
            $timeIst     = trim((string) ($post['makeup_time_ist'] ?? '18:00'));
            $durationMin = max(15, min(240, (int) ($post['makeup_duration_min'] ?? 45)));
            $title       = trim((string) ($post['makeup_title'] ?? 'Special Makeup Class')) ?: 'Special Makeup Class';
            $meetingLink = trim((string) ($post['makeup_meeting_link'] ?? 'https://meet.google.com/new'));

            if ($cohortId > 0) {
                $cohortRow = $this->db->table($this->cohortsTable)->where('id', $cohortId)->get()->getRowArray();
                if ($cohortRow) {
                    $nodeId = (int) $cohortRow['leaf_node_id'];
                    if ($teacherId <= 0) $teacherId = (int) $cohortRow['teacher_user_id'];
                }
            }
            if ($teacherId <= 0) {
                return ['ok' => false, 'message' => 'Select a Teacher for the Makeup Class.'];
            }

            try {
                $dtStartIst = new DateTime($dateYmd . ' ' . $timeIst . ':00', new DateTimeZone('Asia/Kolkata'));
                $dtStartUtc = clone $dtStartIst;
                $dtStartUtc->setTimezone(new DateTimeZone('UTC'));
                $dtEndUtc   = clone $dtStartUtc;
                $dtEndUtc->modify('+' . $durationMin . ' minutes');

                $this->db->table($this->table)->insert([
                    'cohort_id'                 => $cohortId,
                    'node_id'                   => $nodeId,
                    'session_title'             => '🎟 ' . $title,
                    'schedule_mode'             => 'makeup_session',
                    'scheduled_start_utc'       => $dtStartUtc->format('Y-m-d H:i:s'),
                    'scheduled_end_utc'         => $dtEndUtc->format('Y-m-d H:i:s'),
                    'assigned_teacher_id'       => $teacherId,
                    'is_substituted'            => 0,
                    'original_teacher_id'       => $teacherId,
                    'teacher_attendance_status' => 'Pending',
                    'meeting_link'              => $meetingLink,
                    'status'                    => 'scheduled',
                    'created_at'                => $now,
                    'updated_at'                => $now,
                ]);
                $sessionId = (int) $this->db->insertID();
            } catch (Throwable $e) {
                return ['ok' => false, 'message' => 'Invalid Makeup Class Date/Time.'];
            }
        }

        if ($sessionId <= 0) {
            return ['ok' => false, 'message' => 'Select a target Class Session.'];
        }

        foreach ($studentIds as $stuId) {
            $existing = $this->db->table($this->stuAttTable)
                ->where('session_id', $sessionId)
                ->where('student_user_id', $stuId)
                ->get()
                ->getRowArray();

            if ($existing) {
                $this->db->table($this->stuAttTable)->where('id', $existing['id'])->update([
                    'is_guest_makeup' => 1,
                    'guest_reason'    => $reason,
                    'updated_at'      => $now,
                ]);
            } else {
                $this->db->table($this->stuAttTable)->insert([
                    'session_id'        => $sessionId,
                    'student_user_id'   => $stuId,
                    'is_guest_makeup'   => 1,
                    'guest_reason'      => $reason,
                    'attendance_status' => 'Present',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
        }

        return ['ok' => true, 'message' => 'Assigned ' . count($studentIds) . ' student(s) to Makeup Class!'];
    }

    public function executeStartClassWizard(int $sessionId, int $teacherId, array $post, $uploadedPdf = null): array
    {
        $row = $this->find($sessionId);
        if (! $row) {
            return ['ok' => false, 'message' => 'Class session not found.'];
        }

        $nowUtc      = gmdate('Y-m-d H:i:s');
        $meetingLink = trim((string) ($post['meeting_link'] ?? $row['meeting_link'])) ?: $row['meeting_link'];
        $pdfUrl      = $this->handleUploadedPdf($uploadedPdf, (string) ($post['material_pdf_url'] ?? ($row['material_pdf_url'] ?? '')));
        $hwNote      = trim((string) ($post['assignment_note'] ?? ($row['assignment_note'] ?? '')));

        $this->update($sessionId, [
            'actual_start_utc'          => $row['actual_start_utc'] ?: $nowUtc,
            'teacher_attendance_status' => 'In Progress',
            'meeting_link'              => $meetingLink,
            'material_pdf_url'          => $pdfUrl,
            'assignment_note'           => $hwNote,
            'status'                    => 'live',
            'updated_at'                => $nowUtc,
        ]);

        $attMap = $post['attendance_status'] ?? [];
        if (is_array($attMap) && ! empty($attMap)) {
            foreach ($attMap as $stuIdRaw => $stVal) {
                $stuId  = (int) $stuIdRaw;
                $status = in_array($stVal, ['Present', 'Absent', 'Late'], true) ? $stVal : 'Present';
                if ($stuId <= 0) {
                    continue;
                }

                $ex = $this->db->table($this->stuAttTable)
                    ->where('session_id', $sessionId)
                    ->where('student_user_id', $stuId)
                    ->get()
                    ->getRowArray();

                if ($ex) {
                    $this->db->table($this->stuAttTable)->where('id', $ex['id'])->update([
                        'attendance_status'    => $status,
                        'marked_by_teacher_id' => $teacherId,
                        'updated_at'           => $nowUtc,
                    ]);
                } else {
                    $this->db->table($this->stuAttTable)->insert([
                        'session_id'           => $sessionId,
                        'student_user_id'      => $stuId,
                        'is_guest_makeup'      => 0,
                        'guest_reason'         => '',
                        'attendance_status'    => $status,
                        'marked_by_teacher_id' => $teacherId,
                        'created_at'           => $nowUtc,
                        'updated_at'           => $nowUtc,
                    ]);
                }
                $this->recalculateStudentAttendancePct($stuId);
            }
        }

        $this->writeStaticLiveSignalFile();

        return [
            'ok'           => true,
            'session_id'   => $sessionId,
            'status'       => 'live',
            'started_utc'  => $row['actual_start_utc'] ?: $nowUtc,
            'meeting_link' => $meetingLink,
            'message'      => 'Attendance saved & Live Class is now IN PROGRESS!',
        ];
    }

    private function recalculateStudentAttendancePct(int $studentId): void
    {
        try {
            if (! $this->db->tableExists('lms_hierarchy_enrollments')) {
                return;
            }
            $total = (int) $this->db->table($this->stuAttTable)->where('student_user_id', $studentId)->countAllResults();
            if ($total <= 0) {
                return;
            }
            $present = (int) $this->db->table($this->stuAttTable)
                ->where('student_user_id', $studentId)
                ->whereIn('attendance_status', ['Present', 'Late'])
                ->countAllResults();

            $pct = round(($present / $total) * 100, 2);
            $this->db->table('lms_hierarchy_enrollments')->where('student_user_id', $studentId)->update(['attendance_pct' => $pct]);
        } catch (Throwable $e) {
        }
    }

    public function logTeacherEndClass(int $sessionId): array
    {
        $row = $this->find($sessionId);
        if (! $row || empty($row['actual_start_utc'])) {
            return ['ok' => false, 'message' => 'Class must be started before ending.'];
        }

        $nowUtc      = gmdate('Y-m-d H:i:s');
        $startTs     = strtotime((string) $row['actual_start_utc'] . ' UTC');
        $endTs       = strtotime($nowUtc . ' UTC');
        $diffMins    = max(1, (int) round(($endTs - $startTs) / 60));
        $minRequired = $this->getMinDurationMinutes();
        $attStatus   = ($diffMins >= $minRequired)
            ? 'Present'
            : ('Incomplete (' . $diffMins . 'm < ' . $minRequired . 'm)');

        $this->update($sessionId, [
            'actual_end_utc'            => $nowUtc,
            'duration_minutes'          => $diffMins,
            'teacher_attendance_status' => $attStatus,
            'status'                    => 'completed',
            'updated_at'                => $nowUtc,
        ]);

        $this->writeStaticLiveSignalFile();

        return [
            'ok'               => true,
            'session_id'       => $sessionId,
            'status'           => 'completed',
            'duration_minutes' => $diffMins,
            'teacher_status'   => $attStatus,
            'message'          => 'Class Ended! Duration: ' . $diffMins . 'm ➔ Teacher Status: ' . $attStatus,
        ];
    }

    public function createSubstituteRequest(int $teacherId, array $post): array
    {
        $sessionIds = $post['session_ids'] ?? [];
        $reason     = trim((string) ($post['reason_note'] ?? ''));

        if (! is_array($sessionIds) || empty($sessionIds) || $reason === '') {
            return ['ok' => false, 'message' => 'Select at least one upcoming session and enter your note.'];
        }

        $cleanIds  = array_values(array_map('intval', $sessionIds));
        $firstSess = $this->find($cleanIds[0]);
        $nodeId    = (int) ($firstSess['node_id'] ?? 0);
        $cohortId  = (int) ($firstSess['cohort_id'] ?? 0);
        $now       = gmdate('Y-m-d H:i:s');

        $this->db->table($this->subReqTable)->insert([
            'teacher_user_id'       => $teacherId,
            'cohort_id'             => $cohortId,
            'node_id'               => $nodeId,
            'session_ids_json'      => json_encode($cleanIds),
            'reason_note'           => $reason,
            'substitute_teacher_id' => 0,
            'status'                => 'pending',
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);
        $reqId = (int) $this->db->insertID();

        $this->db->table($this->subMsgTable)->insert([
            'request_id'     => $reqId,
            'sender_user_id' => $teacherId,
            'sender_role'    => 'teacher',
            'message_text'   => $reason,
            'created_at'     => $now,
        ]);

        return ['ok' => true, 'request_id' => $reqId, 'message' => 'Leave / Substitute Request submitted!'];
    }

    public function addReplyToSubstituteRequest(int $requestId, int $senderId, string $senderRole, string $message): array
    {
        $message = trim($message);
        if ($requestId <= 0 || $message === '') {
            return ['ok' => false, 'message' => 'Reply message cannot be empty.'];
        }
        $now = gmdate('Y-m-d H:i:s');
        $this->db->table($this->subMsgTable)->insert([
            'request_id'     => $requestId,
            'sender_user_id' => $senderId,
            'sender_role'    => $senderRole,
            'message_text'   => $message,
            'created_at'     => $now,
        ]);
        $this->db->table($this->subReqTable)->where('id', $requestId)->update(['updated_at' => $now]);
        return ['ok' => true, 'request_id' => $requestId, 'message' => 'Reply sent.'];
    }

    public function assignSubstituteToSessions(int $requestId, int $subTeacherId, array $explicitSessionIds = []): array
    {
        if ($subTeacherId <= 0) {
            return ['ok' => false, 'message' => 'Select a Substitute Teacher.'];
        }
        $now        = gmdate('Y-m-d H:i:s');
        $sessionIds = $explicitSessionIds;
        $users      = $this->getRoleUsers()['all'];
        $subName    = $users[$subTeacherId]['display_name'] ?? 'Substitute Teacher';

        if ($requestId > 0) {
            $req = $this->db->table($this->subReqTable)->where('id', $requestId)->get()->getRowArray();
            if ($req) {
                $decoded = json_decode((string) $req['session_ids_json'], true);
                if (is_array($decoded)) {
                    $sessionIds = array_merge($sessionIds, $decoded);
                }
                $this->db->table($this->subReqTable)->where('id', $requestId)->update([
                    'substitute_teacher_id' => $subTeacherId,
                    'status'                => 'approved_substituted',
                    'updated_at'            => $now,
                ]);
                $this->db->table($this->subMsgTable)->insert([
                    'request_id'     => $requestId,
                    'sender_user_id' => (int) (session()->get('user_id') ?: 1),
                    'sender_role'    => 'admin',
                    'message_text'   => '✓ Substitute Approved: Assigned ' . $subName . ' to cover the selected session(s).',
                    'created_at'     => $now,
                ]);
            }
        }

        $sessionIds = array_values(array_unique(array_filter(array_map('intval', $sessionIds))));
        foreach ($sessionIds as $sid) {
            $sess = $this->find($sid);
            if (! $sess) {
                continue;
            }
            $origTeacher = (int) ($sess['original_teacher_id'] ?: $sess['assigned_teacher_id']);
            $this->update($sid, [
                'original_teacher_id' => $origTeacher,
                'assigned_teacher_id' => $subTeacherId,
                'is_substituted'      => 1,
                'updated_at'          => $now,
            ]);
        }
        return ['ok' => true, 'message' => 'Assigned ' . $subName . ' to ' . count($sessionIds) . ' session(s)!'];
    }

    public function formatUtcForUser(string $utcDt, string $tz = 'Asia/Kolkata'): array
    {
        try {
            $dt = new DateTime($utcDt, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone($tz ?: 'Asia/Kolkata'));
            return [
                'display' => $dt->format('D, d M Y — h:i A'),
                'ymd'     => $dt->format('Y-m-d'),
                'hm'      => $dt->format('H:i'),
            ];
        } catch (Throwable $e) {
            return ['display' => $utcDt, 'ymd' => substr($utcDt, 0, 10), 'hm' => '17:00'];
        }
    }

    public function getEnrichedSessions(string $displayTz = 'Asia/Kolkata', int $teacherIdFilter = 0, array $studentIdsFilter = []): array
    {
        $roleUsers = $this->getRoleUsers();
        $users     = $roleUsers['all'];
        $leaves    = $this->getLeafNodesWithPaths();
        $cohorts   = [];
        foreach ($this->db->table($this->cohortsTable)->get()->getResultArray() as $c) {
            $cohorts[(int) $c['id']] = $c;
        }

        $cohortRoster = [];
        foreach ($this->db->table($this->cohortStuTable)->where('status', 'active')->get()->getResultArray() as $cs) {
            $cid = (int) $cs['cohort_id'];
            $sid = (int) $cs['student_user_id'];
            if (isset($users[$sid])) {
                $cohortRoster[$cid][$sid] = [
                    'student_id'   => $sid,
                    'student_name' => $users[$sid]['display_name'],
                    'is_guest'     => 0,
                    'status'       => 'Present',
                ];
            }
        }

        $leafRoster = [];
        if ($this->db->tableExists('lms_hierarchy_enrollments')) {
            foreach ($this->db->table('lms_hierarchy_enrollments')->where('status', 'active')->get()->getResultArray() as $enr) {
                $lid = (int) $enr['leaf_node_id'];
                $sid = (int) $enr['student_user_id'];
                if (isset($users[$sid])) {
                    $leafRoster[$lid][$sid] = [
                        'student_id'   => $sid,
                        'student_name' => $users[$sid]['display_name'],
                        'is_guest'     => 0,
                        'status'       => 'Present',
                    ];
                }
            }
        }

        $attRows     = $this->db->table($this->stuAttTable)->get()->getResultArray();
        $bySess      = [];
        $guestBySess = [];
        foreach ($attRows as $ar) {
            $sid = (int) $ar['session_id'];
            $uid = (int) $ar['student_user_id'];
            $ar['student_name']    = $users[$uid]['display_name'] ?? 'Student';
            $bySess[$sid][$uid]    = $ar;
            $guestBySess[$sid][$uid] = $ar['student_name'];
        }

        $builder = $this->db->table($this->table)->orderBy('scheduled_start_utc', 'ASC')->orderBy('id', 'ASC');
        if ($teacherIdFilter > 0) {
            $builder->groupStart()
                ->where('assigned_teacher_id', $teacherIdFilter)
                ->orWhere('original_teacher_id', $teacherIdFilter)
                ->groupEnd();
        }

        $rows         = $builder->get()->getResultArray();
        $cleanStuIds  = array_values(array_filter(array_map('intval', $studentIdsFilter), static fn($v) => $v > 0));
        $out          = [];
        $seenSlotKeys = [];

        $todayLocalYmd = (new DateTime('now', new DateTimeZone($displayTz ?: 'Asia/Kolkata')))->format('Y-m-d');

        foreach ($rows as $r) {
            $sid  = (int) $r['id'];
            $cid  = (int) ($r['cohort_id'] ?? 0);
            $nid  = (int) $r['node_id'];
            $tid  = (int) $r['assigned_teacher_id'];
            $oid  = (int) $r['original_teacher_id'];
            $mode = (string) ($r['schedule_mode'] ?? '');

            if ($cid > 0 && ! isset($cohorts[$cid])) {
                continue;
            }
            if ($cid === 0 && empty($cohorts) && $mode !== 'makeup_session') {
                continue;
            }

            $slotKey = $cid . '_' . $nid . '_' . $tid . '_' . $r['scheduled_start_utc'];
            if (isset($seenSlotKeys[$slotKey])) {
                continue;
            }
            $seenSlotKeys[$slotKey] = true;

            $matchedChildren = [];
            $childAttStatus  = [];
            if (! empty($cleanStuIds)) {
                foreach ($cleanStuIds as $fStuId) {
                    $cName = $users[$fStuId]['display_name'] ?? 'Student';
                    if ($cid > 0 && isset($cohortRoster[$cid][$fStuId])) {
                        $matchedChildren[$fStuId] = $cName;
                    } elseif ($cid === 0 && isset($leafRoster[$nid][$fStuId])) {
                        $matchedChildren[$fStuId] = $cName;
                    } elseif (isset($guestBySess[$sid][$fStuId])) {
                        $matchedChildren[$fStuId] = $cName . ' (Makeup)';
                    }
                    if (isset($bySess[$sid][$fStuId])) {
                        $childAttStatus[$fStuId] = $bySess[$sid][$fStuId]['attendance_status'];
                    }
                }
                if (empty($matchedChildren)) {
                    continue;
                }
            }

            $rollCallMap = ($cid > 0 && ! empty($cohortRoster[$cid]))
                ? $cohortRoster[$cid]
                : ($leafRoster[$nid] ?? []);

            if (! empty($bySess[$sid])) {
                foreach ($bySess[$sid] as $uid => $attItem) {
                    $rollCallMap[$uid] = [
                        'student_id'   => $uid,
                        'student_name' => $attItem['student_name'],
                        'is_guest'     => (int) ($attItem['is_guest_makeup'] ?? 0),
                        'status'       => in_array($attItem['attendance_status'], ['Present', 'Absent', 'Late'], true) ? $attItem['attendance_status'] : 'Present',
                    ];
                }
            }

            $rawPdf = trim((string) ($r['material_pdf_url'] ?: ($cid > 0 && isset($cohorts[$cid]) ? ($cohorts[$cid]['material_pdf_url'] ?? '') : '')));
            $resPdf = ($rawPdf !== '' && strpos($rawPdf, 'http') !== 0) ? base_url(ltrim($rawPdf, '/')) : $rawPdf;
            $locObj = $this->formatUtcForUser((string) $r['scheduled_start_utc'], $displayTz);

            $r['raw_material_pdf']      = $rawPdf;
            $r['resolved_material_pdf'] = $resPdf;
            $r['leaf_title']            = $leaves[$nid]['breadcrumb_path'] ?? 'Course';
            $r['cohort_title']          = $cid > 0 && isset($cohorts[$cid]) ? $cohorts[$cid]['cohort_title'] : $r['leaf_title'];
            $r['cohort_code']           = $cid > 0 && isset($cohorts[$cid]) ? $cohorts[$cid]['cohort_code'] : ($mode === 'makeup_session' ? 'MAKEUP' : 'BATCH');
            $r['assigned_teacher_name'] = $users[$tid]['display_name'] ?? 'Instructor';
            $r['original_teacher_name'] = $users[$oid]['display_name'] ?? 'Instructor';
            $r['local_start_display']   = $locObj['display'];
            $r['local_ymd']             = $locObj['ymd'];
            $r['local_hm']              = $locObj['hm'];
            $r['is_today']              = ($locObj['ymd'] === $todayLocalYmd);
            $r['matched_children']      = $matchedChildren;
            $r['child_att_status']      = $childAttStatus;
            $r['students_list']         = array_values($bySess[$sid] ?? []);
            $r['roster_for_rollcall']   = array_values($rollCallMap);
            $out[]                      = $r;
        }
        return $out;
    }

    public function getSubstituteRequestsWithThreads(int $teacherIdFilter = 0): array
    {
        $builder = $this->db->table($this->subReqTable)->orderBy('id', 'DESC');
        if ($teacherIdFilter > 0) {
            $builder->where('teacher_user_id', $teacherIdFilter);
        }
        $reqs  = $builder->get()->getResultArray();
        $msgs  = $this->db->table($this->subMsgTable)->orderBy('id', 'ASC')->get()->getResultArray();
        $users = $this->getRoleUsers()['all'];

        $msgByReq = [];
        $maxMsgId = 0;
        foreach ($msgs as $m) {
            $mid = (int) $m['id'];
            if ($mid > $maxMsgId) $maxMsgId = $mid;
            $uid = (int) $m['sender_user_id'];
            $m['sender_name'] = $users[$uid]['display_name'] ?? 'User';
            $msgByReq[(int) $m['request_id']][] = $m;
        }

        foreach ($reqs as &$rq) {
            $tid = (int) $rq['teacher_user_id'];
            $sid = (int) $rq['substitute_teacher_id'];
            $rq['teacher_name']    = $users[$tid]['display_name'] ?? 'Teacher';
            $rq['substitute_name'] = $sid > 0 ? ($users[$sid]['display_name'] ?? 'Teacher') : 'Unassigned';
            $rq['session_ids']     = json_decode((string) $rq['session_ids_json'], true) ?: [];
            $rq['thread']          = $msgByReq[(int) $rq['id']] ?? [];
            $rq['latest_msg_id']   = $maxMsgId;
        }
        return $reqs;
    }
}

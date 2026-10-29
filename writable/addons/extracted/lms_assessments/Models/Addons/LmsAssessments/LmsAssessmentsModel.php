<?php

namespace App\Models\Addons\LmsAssessments;

use CodeIgniter\Model;
use Throwable;

class LmsAssessmentsModel extends Model
{
    protected $table            = 'lms_exams';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'node_id',
        'exam_type',
        'title',
        'duration_minutes',
        'pass_percentage',
        'award_certificate',
        'selection_mode',
        'question_count',
        'selected_qids_json',
        'unlock_mode',
        'unlock_after_session_id',
        'retention_mode',
        'is_enabled',
        'questions_json',
        'status',
        'created_at',
        'updated_at',
    ];

    protected string $qbankTable      = 'lms_question_bank';
    protected string $worksheetsTable = 'lms_worksheets';
    protected string $attemptsTable   = 'lms_assessment_attempts';
    protected string $certsTable      = 'lms_certificates';

    protected static ?array $globalSettingsCache = null;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchemaInitialized();
    }

    public function ensureSchema(): void
    {
        $this->ensureSchemaInitialized();
    }

    public function ensureSchemaInitialized(): void
    {
        try {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_question_bank` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `question_type` VARCHAR(20) NOT NULL DEFAULT 'mcq',
                    `question_text` TEXT NOT NULL,
                    `option_a` VARCHAR(255) NOT NULL DEFAULT '',
                    `option_b` VARCHAR(255) NOT NULL DEFAULT '',
                    `option_c` VARCHAR(255) NOT NULL DEFAULT '',
                    `option_d` VARCHAR(255) NOT NULL DEFAULT '',
                    `correct_answer` VARCHAR(255) NOT NULL,
                    `difficulty` VARCHAR(20) NOT NULL DEFAULT 'medium',
                    `topic_tag` VARCHAR(80) NOT NULL DEFAULT '',
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_qbank_node` (`node_id`, `is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_exams` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `exam_type` VARCHAR(30) NOT NULL DEFAULT 'practice_test',
                    `title` VARCHAR(160) NOT NULL,
                    `duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 10,
                    `pass_percentage` DECIMAL(5,2) NOT NULL DEFAULT 75.00,
                    `award_certificate` TINYINT(1) NOT NULL DEFAULT 1,
                    `selection_mode` VARCHAR(30) NOT NULL DEFAULT 'random_from_bank',
                    `question_count` SMALLINT UNSIGNED NOT NULL DEFAULT 5,
                    `selected_qids_json` LONGTEXT NULL,
                    `unlock_mode` VARCHAR(30) NOT NULL DEFAULT 'always_open',
                    `unlock_after_session_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `retention_mode` VARCHAR(35) NOT NULL DEFAULT 'permanent_after_upgrade',
                    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
                    `questions_json` LONGTEXT NULL,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_node_type` (`node_id`, `exam_type`, `status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $eCols = $this->db->getFieldNames('lms_exams');
            $eAlters = [
                'selection_mode'          => "ADD COLUMN `selection_mode` VARCHAR(30) NOT NULL DEFAULT 'random_from_bank'",
                'question_count'          => "ADD COLUMN `question_count` SMALLINT UNSIGNED NOT NULL DEFAULT 5",
                'selected_qids_json'      => "ADD COLUMN `selected_qids_json` LONGTEXT NULL",
                'unlock_mode'             => "ADD COLUMN `unlock_mode` VARCHAR(30) NOT NULL DEFAULT 'always_open'",
                'unlock_after_session_id' => "ADD COLUMN `unlock_after_session_id` INT UNSIGNED NOT NULL DEFAULT 0",
                'retention_mode'          => "ADD COLUMN `retention_mode` VARCHAR(35) NOT NULL DEFAULT 'permanent_after_upgrade'",
                'is_enabled'              => "ADD COLUMN `is_enabled` TINYINT(1) NOT NULL DEFAULT 1",
            ];
            foreach ($eAlters as $col => $sqlFrag) {
                if (! in_array($col, $eCols, true)) {
                    $this->db->query("ALTER TABLE `lms_exams` " . $sqlFrag);
                }
            }

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_worksheets` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `title` VARCHAR(160) NOT NULL,
                    `worksheet_type` VARCHAR(40) NOT NULL DEFAULT 'PDF Practice Sheet',
                    `file_source` VARCHAR(20) NOT NULL DEFAULT 'upload',
                    `file_url` VARCHAR(255) NOT NULL DEFAULT '',
                    `retention_mode` VARCHAR(35) NOT NULL DEFAULT 'permanent_after_upgrade',
                    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
                    `instructions` VARCHAR(255) NOT NULL DEFAULT '',
                    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_ws_node` (`node_id`, `is_enabled`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_assessment_attempts` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `exam_id` INT UNSIGNED NOT NULL,
                    `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `student_user_id` INT UNSIGNED NOT NULL,
                    `attempt_mode` VARCHAR(30) NOT NULL DEFAULT 'practice_test',
                    `status` VARCHAR(20) NOT NULL DEFAULT 'completed',
                    `started_at` DATETIME NULL,
                    `expires_at` DATETIME NULL,
                    `assigned_questions_json` LONGTEXT NULL,
                    `draft_answers_json` LONGTEXT NULL,
                    `total_questions` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                    `correct_answers` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                    `score_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    `time_taken_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
                    `avg_sec_per_question` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
                    `passed` TINYINT(1) NOT NULL DEFAULT 0,
                    `certificate_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `answers_json` LONGTEXT NULL,
                    `created_at` DATETIME NULL,
                    KEY `idx_stu_exam_status` (`student_user_id`, `exam_id`, `status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $aCols = $this->db->getFieldNames('lms_assessment_attempts');
            $aAlters = [
                'attempt_mode'            => "ADD COLUMN `attempt_mode` VARCHAR(30) NOT NULL DEFAULT 'practice_test'",
                'status'                  => "ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'completed'",
                'started_at'              => "ADD COLUMN `started_at` DATETIME NULL",
                'expires_at'              => "ADD COLUMN `expires_at` DATETIME NULL",
                'assigned_questions_json' => "ADD COLUMN `assigned_questions_json` LONGTEXT NULL",
                'draft_answers_json'      => "ADD COLUMN `draft_answers_json` LONGTEXT NULL",
            ];
            foreach ($aAlters as $col => $sqlFrag) {
                if (! in_array($col, $aCols, true)) {
                    $this->db->query("ALTER TABLE `lms_assessment_attempts` " . $sqlFrag);
                }
            }

            // Heal any historical attempts where `status` was NULL/empty or stuck `in_progress` past expiry
            $this->db->query("UPDATE `lms_assessment_attempts` SET `status` = 'completed' WHERE (`status` IS NULL OR `status` = '') AND `total_questions` > 0");

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_certificates` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `certificate_code` VARCHAR(60) NOT NULL,
                    `student_user_id` INT UNSIGNED NOT NULL,
                    `student_name` VARCHAR(140) NOT NULL,
                    `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `course_title` VARCHAR(160) NOT NULL,
                    `exam_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `exam_title` VARCHAR(160) NOT NULL,
                    `score_pct` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
                    `issued_at` DATETIME NULL,
                    UNIQUE KEY `uniq_cert_code` (`certificate_code`),
                    KEY `idx_cert_student` (`student_user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $lockFile = WRITEPATH . 'addons/schema_addon_lms_assessments_v80.lock';
            if (is_file($lockFile)) {
                return;
            }

            if ($this->db->tableExists('addons')) {
                $this->db->table('addons')
                    ->where('slug', 'lms_assessments')
                    ->update(['name' => 'Exams, Worksheets & Certificates']);
            }

            $this->syncPageBuilderComponent();
            $this->syncRbacPermissionsAndAutoEnableRoles();
            $this->mirrorAssets(true);

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
        } catch (Throwable $e) {
            log_message('error', 'LmsAssessmentsModel schema error: ' . $e->getMessage());
        }
    }

    public function syncPageBuilderComponent(): void
    {
        try {
            if (! $this->db->tableExists('cms_components')) {
                return;
            }
            $defaultData = [
                'badge'      => 'VERIFY ACADEMIC CREDENTIALS',
                'heading'    => 'Official Certificate Verification Portal',
                'subheading' => 'Enter a student certificate serial code (e.g. CERT-2026-XXXX) to verify completion and accuracy.',
            ];
            $schemaJson = json_encode($defaultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $this->db->query("
                INSERT INTO `cms_components`
                    (`component_key`, `component_name`, `component_icon`, `category`, `addon_slug`, `default_schema_json`, `is_enabled`, `sort_order`)
                VALUES
                    ('certificate_verifier', 'Student Certificate Verifier Box', '🏆', 'interactive', 'lms_assessments', ?, 1, 18)
                ON DUPLICATE KEY UPDATE
                    `component_name`      = VALUES(`component_name`),
                    `component_icon`      = VALUES(`component_icon`),
                    `addon_slug`          = 'lms_assessments',
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
                    ['lms_assessments.take_exam', 'Access Practice Drills, Exams, PDF Worksheets & Certificates [Student + Parent]'],
                    ['lms_assessments.view',      'View All Student Exam Scores & Certificates [Teacher + Admin]'],
                    ['lms_assessments.create',    'Manage Question Bank, Create Exams & Upload Worksheets [Teacher + Admin]'],
                    ['lms_assessments.edit',      'Edit Question Bank, Exams, Worksheets & Settings [Super Admin + Admin]'],
                    ['lms_assessments.delete',    'Delete Questions, Exams & Worksheets [Super Admin + Admin]'],
                ];
                foreach ($perms as $p) {
                    $this->db->query("
                        INSERT INTO `cms_rbac_features` (`perm_key`, `perm_label`, `module_group`, `source_type`, `created_at`)
                        VALUES (?, ?, 'Add-on: Exams, Worksheets & Certificates', 'addon', ?)
                        ON DUPLICATE KEY UPDATE `perm_label` = VALUES(`perm_label`), `module_group` = VALUES(`module_group`)
                    ", [$p[0], $p[1], $now]);
                }
            }

            if ($this->db->tableExists('cms_roles')) {
                $roleAutoGrants = [
                    'student' => ['lms_assessments.take_exam'],
                    'parent'  => ['lms_assessments.take_exam'],
                    'teacher' => ['lms_assessments.view', 'lms_assessments.create'],
                    'admin'   => ['lms_assessments.view', 'lms_assessments.create', 'lms_assessments.edit', 'lms_assessments.take_exam'],
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
        $srcDir = WRITEPATH . 'addons/extracted/lms_assessments/assets';
        $dstDir = FCPATH . 'assets/addons/lms_assessments';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['lms_assessments.css', 'lms_assessments.js'] as $f) {
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
        self::$globalSettingsCache = [
            'site_title'                         => 'Portal',
            'active_theme_mode'                  => 'dark',
            'system_environment_mode'            => 'testing',
            'lms_assessments__default_retention' => 'permanent_after_upgrade',
        ];
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

    public function isProductionMode(): bool
    {
        if (function_exists('cms_is_sandbox_mode')) {
            return ! cms_is_sandbox_mode();
        }
        $s = $this->getGlobalSiteSettings();
        return strtolower(trim((string) ($s['system_environment_mode'] ?? 'testing'))) === 'production';
    }

    public function getLeafNodesMap(int $teacherIdFilter = 0): array
    {
        $map = [];
        if ($teacherIdFilter === 0) {
            $map[0] = '🌐 All Courses (Global Resource)';
        }

        // If teacherIdFilter > 0, check `lms_classroom_batches` for courses assigned to this teacher
        $teacherLeafIds = [];
        if ($teacherIdFilter > 0 && $this->db->tableExists('lms_classroom_batches')) {
            foreach ($this->db->table('lms_classroom_batches')->where('teacher_user_id', $teacherIdFilter)->get()->getResultArray() as $cb) {
                $teacherLeafIds[(int) $cb['leaf_node_id']] = true;
            }
        }

        if ($this->db->tableExists('lms_hierarchy_nodes')) {
            $allNodes = [];
            foreach ($this->db->table('lms_hierarchy_nodes')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray() as $n) {
                $allNodes[(int) $n['id']] = $n;
            }

            foreach ($allNodes as $nid => $nRow) {
                if ((int) ($nRow['is_leaf_node'] ?? 0) !== 1) {
                    continue;
                }
                if ($teacherIdFilter > 0 && (int) ($nRow['teacher_user_id'] ?? 0) !== $teacherIdFilter && ! isset($teacherLeafIds[$nid])) {
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

                $map[$nid] = implode(' › ', $pathParts);
            }
        }
        if (empty($map) && ! $this->isProductionMode()) {
            $map[2] = 'Abacus Mastery › Abacus Level 1';
        }
        return $map;
    }

    public function getClassSessionsMap(int $teacherIdFilter = 0): array
    {
        $sessions = [];
        if ($this->db->tableExists('lms_class_sessions')) {
            $builder = $this->db->table('lms_class_sessions')->orderBy('scheduled_start_utc', 'ASC');
            if ($teacherIdFilter > 0) {
                $builder->where('assigned_teacher_id', $teacherIdFilter);
            }
            foreach ($builder->get()->getResultArray() as $s) {
                $sid = (int) $s['id'];
                $sessions[$sid] = [
                    'id'          => $sid,
                    'node_id'     => (int) $s['node_id'],
                    'title'       => ($s['session_title'] ?? 'Class') . ' (' . substr((string) $s['scheduled_start_utc'], 0, 16) . ' UTC)',
                    'status'      => strtolower((string) ($s['status'] ?? 'scheduled')),
                    'is_complete' => strtolower((string) ($s['status'] ?? '')) === 'completed' || ! empty($s['actual_end_utc']),
                ];
            }
        }
        return $sessions;
    }

    public function getStudentsMap(): array
    {
        $map = [];
        if ($this->db->tableExists('users')) {
            foreach ($this->db->table('users')->orderBy('id', 'ASC')->get()->getResultArray() as $u) {
                $id    = (int) $u['id'];
                $rId   = (int) ($u['role_id'] ?? 4);
                $rSlug = strtolower((string) ($u['role_slug'] ?? 'student'));
                if (in_array($rId, [1, 2, 3], true) || in_array($rSlug, ['super_admin', 'admin', 'teacher', 'parent'], true)) {
                    continue;
                }
                $map[$id] = $u['full_name'] ?: ($u['email'] ?: 'Student');
            }
        }
        return $map;
    }

    public function getQuestionBankList(int $nodeIdFilter = -1, int $teacherIdFilter = 0): array
    {
        $nodes   = $this->getLeafNodesMap($teacherIdFilter);
        $builder = $this->db->table($this->qbankTable)->orderBy('id', 'DESC');

        if ($teacherIdFilter > 0) {
            $nodeKeys = array_keys($nodes);
            if (empty($nodeKeys)) {
                return [];
            }
            $builder->whereIn('node_id', $nodeKeys);
        }
        if ($nodeIdFilter >= 0) {
            $builder->where('node_id', $nodeIdFilter);
        }

        $rows = $builder->get()->getResultArray();
        foreach ($rows as &$r) {
            $nid = (int) $r['node_id'];
            $r['node_title'] = $nodes[$nid] ?? 'Course Level';
            $r['options']    = array_values(array_filter([
                trim((string) ($r['option_a'] ?? '')),
                trim((string) ($r['option_b'] ?? '')),
                trim((string) ($r['option_c'] ?? '')),
                trim((string) ($r['option_d'] ?? '')),
            ], static fn($v) => $v !== ''));
        }
        return $rows;
    }

    public function getQuestionBankCountsByNode(): array
    {
        $counts = [0 => 0];
        $rows   = $this->db->table($this->qbankTable)
            ->select('node_id, COUNT(*) AS cnt')
            ->where('is_active', 1)
            ->groupBy('node_id')
            ->get()
            ->getResultArray();

        foreach ($rows as $r) {
            $counts[(int) $r['node_id']] = (int) $r['cnt'];
        }
        return $counts;
    }

    public function saveOrUpdateQuestionBankItem(array $post, int $userId): array
    {
        $id      = (int) ($post['id'] ?? 0);
        $nodeId  = (int) ($post['node_id'] ?? 0);
        $qType   = in_array($post['question_type'] ?? '', ['mcq', 'input'], true) ? $post['question_type'] : 'mcq';
        $qText   = trim((string) ($post['question_text'] ?? ''));
        $optA    = trim((string) ($post['option_a'] ?? ''));
        $optB    = trim((string) ($post['option_b'] ?? ''));
        $optC    = trim((string) ($post['option_c'] ?? ''));
        $optD    = trim((string) ($post['option_d'] ?? ''));
        $correct = trim((string) ($post['correct_answer'] ?? ''));
        $diff    = in_array($post['difficulty'] ?? '', ['easy', 'medium', 'hard'], true) ? $post['difficulty'] : 'medium';
        $topic   = trim((string) ($post['topic_tag'] ?? ''));

        if ($qText === '' || $correct === '') {
            return ['ok' => false, 'message' => 'Both Question Text and Correct Answer are required.'];
        }

        if ($qType === 'mcq') {
            $upperAns = strtoupper($correct);
            if ($upperAns === 'A' && $optA !== '') $correct = $optA;
            elseif ($upperAns === 'B' && $optB !== '') $correct = $optB;
            elseif ($upperAns === 'C' && $optC !== '') $correct = $optC;
            elseif ($upperAns === 'D' && $optD !== '') $correct = $optD;
        }

        $now = gmdate('Y-m-d H:i:s');
        $row = [
            'node_id'        => $nodeId,
            'question_type'  => $qType,
            'question_text'  => $qText,
            'option_a'       => $qType === 'mcq' ? $optA : '',
            'option_b'       => $qType === 'mcq' ? $optB : '',
            'option_c'       => $qType === 'mcq' ? $optC : '',
            'option_d'       => $qType === 'mcq' ? $optD : '',
            'correct_answer' => $correct,
            'difficulty'     => $diff,
            'topic_tag'      => $topic,
            'updated_at'     => $now,
        ];

        if ($id > 0) {
            $this->db->table($this->qbankTable)->where('id', $id)->update($row);
            return ['ok' => true, 'node_id' => $nodeId, 'message' => 'Question updated in Question Bank!'];
        }

        $row['is_active']  = 1;
        $row['created_by'] = $userId;
        $row['created_at'] = $now;
        $this->db->table($this->qbankTable)->insert($row);

        return ['ok' => true, 'node_id' => $nodeId, 'message' => 'Question added to Question Bank!'];
    }

    public function getStudentEnrollmentContext(array $studentIds): array
    {
        $activeNodeIds     = [];
        $historicalNodeIds = [];

        $cleanIds = array_values(array_filter(array_map('intval', $studentIds), static fn($v) => $v > 0));
        if (! empty($cleanIds) && $this->db->tableExists('lms_hierarchy_enrollments')) {
            $rows = $this->db->table('lms_hierarchy_enrollments')
                ->whereIn('student_user_id', $cleanIds)
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $currLeaf = (int) ($r['leaf_node_id'] ?? 0);
                if ($currLeaf > 0 && ($r['status'] ?? 'active') === 'active') {
                    $activeNodeIds[] = $currLeaf;
                }
                $histArr = json_decode((string) ($r['history_json'] ?? '[]'), true);
                if (is_array($histArr)) {
                    foreach ($histArr as $hItem) {
                        $oldLeaf = (int) ($hItem['from_leaf_id'] ?? 0);
                        if ($oldLeaf > 0 && ! in_array($oldLeaf, $activeNodeIds, true)) {
                            $historicalNodeIds[] = $oldLeaf;
                        }
                    }
                }
            }
        }

        if (! empty($cleanIds) && $this->db->tableExists('cms_user_meta')) {
            foreach ($this->db->table('cms_user_meta')->whereIn('user_id', $cleanIds)->get()->getResultArray() as $m) {
                $lid = (int) ($m['leaf_node_id'] ?? 0);
                if ($lid > 0) {
                    $activeNodeIds[] = $lid;
                }
            }
        }

        if (empty($activeNodeIds) && ! $this->isProductionMode()) {
            $activeNodeIds[] = 2;
        }

        return [
            'active_nodes'     => array_values(array_unique($activeNodeIds)),
            'historical_nodes' => array_values(array_unique($historicalNodeIds)),
        ];
    }

    public function getWorksheetsForStudents(array $studentIds): array
    {
        $ctx = $this->getStudentEnrollmentContext($studentIds);
        if (empty($ctx['active_nodes']) && empty($ctx['historical_nodes'])) {
            return [];
        }

        $nodes = $this->getLeafNodesMap(0);
        $allWs = $this->db->table($this->worksheetsTable)
            ->where('is_enabled', 1)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $visible = [];
        foreach ($allWs as $ws) {
            $nid       = (int) $ws['node_id'];
            $retention = (string) ($ws['retention_mode'] ?? 'permanent_after_upgrade');

            $isCurrentBatch = ($nid === 0) || in_array($nid, $ctx['active_nodes'], true);
            $isUpgradedPast = in_array($nid, $ctx['historical_nodes'], true) && ($retention === 'permanent_after_upgrade');

            if ($isCurrentBatch || $isUpgradedPast) {
                $ws['node_title']      = $nodes[$nid] ?? 'Course Worksheet';
                $ws['access_tag']      = $isCurrentBatch ? 'Current Course' : 'Completed Level Archive';
                $ws['resolved_dl_url'] = (strpos((string) $ws['file_url'], 'http') === 0 || strpos((string) $ws['file_url'], '#') === 0)
                    ? $ws['file_url']
                    : base_url(ltrim((string) $ws['file_url'], '/'));
                $visible[] = $ws;
            }
        }
        return $visible;
    }

    /**
     * Fetches Available Exams for Student AND checks if there is already an active `in_progress` attempt
     * (Never starts a new attempt automatically!)
     */
    public function getExamsForStudent(int $studentId): array
    {
        $ctx = $this->getStudentEnrollmentContext([$studentId]);
        if (empty($ctx['active_nodes'])) {
            return [];
        }

        $nodes    = $this->getLeafNodesMap(0);
        $sessions = $this->getClassSessionsMap(0);

        // Check any existing `in_progress` attempts for this student
        $inProgressByExam = [];
        $nowTs            = time();
        foreach ($this->db->table($this->attemptsTable)->where('student_user_id', $studentId)->where('status', 'in_progress')->get()->getResultArray() as $ip) {
            $expTs = ! empty($ip['expires_at']) ? strtotime($ip['expires_at'] . ' UTC') : 0;
            if ($expTs > $nowTs) {
                $inProgressByExam[(int) $ip['exam_id']] = $ip;
            }
        }

        $allExams = $this->db->table($this->table)
            ->where('is_enabled', 1)
            ->where('status', 'active')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $visible = [];
        foreach ($allExams as $ex) {
            $eid = (int) $ex['id'];
            $nid = (int) $ex['node_id'];

            $isCurrentActiveBatch = ($nid === 0) || in_array($nid, $ctx['active_nodes'], true);
            if (! $isCurrentActiveBatch) {
                continue;
            }

            $unlockMode = (string) ($ex['unlock_mode'] ?? 'always_open');
            $reqSessId  = (int) ($ex['unlock_after_session_id'] ?? 0);
            $isUnlocked = true;
            $lockReason = '';

            if ($unlockMode === 'after_class_session' && $reqSessId > 0) {
                $sessInfo = $sessions[$reqSessId] ?? null;
                if (! $sessInfo || ! $sessInfo['is_complete']) {
                    $isUnlocked = false;
                    $lockReason = 'Unlocks after ' . ($sessInfo['title'] ?? 'your scheduled class') . ' is completed.';
                }
            }

            $ex['node_title']         = $nodes[$nid] ?? 'Course Level';
            $ex['is_unlocked']        = $isUnlocked;
            $ex['lock_reason']        = $lockReason;
            $ex['has_active_attempt'] = isset($inProgressByExam[$eid]);
            $visible[]                = $ex;
        }
        return $visible;
    }

    public function drawQuestionsForExam(array $exam): array
    {
        $nodeId  = (int) ($exam['node_id'] ?? 0);
        $selMode = (string) ($exam['selection_mode'] ?? 'random_from_bank');
        $qCount  = max(1, (int) ($exam['question_count'] ?? 5));
        $selQids = json_decode((string) ($exam['selected_qids_json'] ?? '[]'), true);
        if (! is_array($selQids)) {
            $selQids = [];
        }

        $builder = $this->db->table($this->qbankTable)->where('is_active', 1);

        if ($selMode === 'manual_selected' && ! empty($selQids)) {
            $builder->whereIn('id', array_map('intval', $selQids));
            $rows = $builder->get()->getResultArray();
        } else {
            if ($nodeId > 0) {
                $builder->whereIn('node_id', [$nodeId, 0]);
            }
            $rows = $builder->get()->getResultArray();
            if (! empty($rows)) {
                shuffle($rows);
                $rows = array_slice($rows, 0, $qCount);
            }
        }

        $drawn = [];
        foreach ($rows as $r) {
            $opts = array_values(array_filter([
                trim((string) ($r['option_a'] ?? '')),
                trim((string) ($r['option_b'] ?? '')),
                trim((string) ($r['option_c'] ?? '')),
                trim((string) ($r['option_d'] ?? '')),
            ], static fn($v) => $v !== ''));

            if (count($opts) > 1) {
                shuffle($opts);
            }

            $drawn[] = [
                'qid'     => (int) $r['id'],
                'q'       => $r['question_text'],
                'type'    => $r['question_type'] ?? 'mcq',
                'options' => $opts,
                'ans'     => $r['correct_answer'],
                'topic'   => $r['topic_tag'] ?? '',
            ];
        }

        if (empty($drawn)) {
            $legacy = json_decode((string) ($exam['questions_json'] ?? '[]'), true);
            if (is_array($legacy) && ! empty($legacy)) {
                if ($selMode === 'random_from_bank') {
                    shuffle($legacy);
                    $legacy = array_slice($legacy, 0, $qCount);
                }
                $drawn = $legacy;
            }
        }

        return $drawn;
    }

    /**
     * Checks if an active non-expired `in_progress` attempt already exists for `($studentId, $examId)`.
     * Only creates a new attempt if `$forceCreateNew === true` (when user explicitly clicked "Start Exam Now")!
     */
    public function getOrStartActiveAttempt(int $studentId, array $exam, bool $forceCreateNew = false): ?array
    {
        $examId          = (int) ($exam['id'] ?? 0);
        $nodeId          = (int) ($exam['node_id'] ?? 0);
        $durationMinutes = max(1, (int) ($exam['duration_minutes'] ?? 5));
        $durationSeconds = $durationMinutes * 60;
        $nowTs           = time();

        $existing = $this->db->table($this->attemptsTable)
            ->where('student_user_id', $studentId)
            ->where('exam_id', $examId)
            ->where('status', 'in_progress')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if ($existing) {
            $expiresTs = ! empty($existing['expires_at']) ? strtotime($existing['expires_at'] . ' UTC') : ($nowTs + $durationSeconds);
            $remSec    = $expiresTs - $nowTs;

            if ($remSec > 0) {
                $lockedQuestions = json_decode((string) ($existing['assigned_questions_json'] ?? '[]'), true) ?: [];
                $draftAnswers    = json_decode((string) ($existing['draft_answers_json'] ?? '{}'), true) ?: [];
                return [
                    'attempt_id'        => (int) $existing['id'],
                    'remaining_seconds' => $remSec,
                    'total_seconds'     => $durationSeconds,
                    'started_at'        => $existing['started_at'],
                    'expires_at'        => $existing['expires_at'],
                    'questions'         => $lockedQuestions,
                    'draft_answers'     => $draftAnswers,
                    'is_resumed'        => true,
                ];
            }

            // Expired while away -> auto-grade and close it
            $draftAnswers = json_decode((string) ($existing['draft_answers_json'] ?? '{}'), true) ?: [];
            $this->gradeAndRecordAttempt($studentId, [
                'attempt_id'         => (int) $existing['id'],
                'exam_id'            => $examId,
                'time_taken_seconds' => $durationSeconds,
                'answers'            => $draftAnswers,
            ]);
        }

        // Do NOT auto-start a new attempt unless the student explicitly clicked "▶ Start Exam Now"!
        if (! $forceCreateNew) {
            return null;
        }

        $drawnQuestions = $this->drawQuestionsForExam($exam);
        $startedUtc     = gmdate('Y-m-d H:i:s', $nowTs);
        $expiresUtc     = gmdate('Y-m-d H:i:s', $nowTs + $durationSeconds);

        $this->db->table($this->attemptsTable)->insert([
            'exam_id'                 => $examId,
            'node_id'                 => $nodeId,
            'student_user_id'         => $studentId,
            'attempt_mode'            => (string) ($exam['exam_type'] ?? 'practice_test'),
            'status'                  => 'in_progress',
            'started_at'              => $startedUtc,
            'expires_at'              => $expiresUtc,
            'assigned_questions_json' => json_encode($drawnQuestions, JSON_UNESCAPED_UNICODE),
            'draft_answers_json'      => json_encode([]),
            'total_questions'         => count($drawnQuestions),
            'correct_answers'         => 0,
            'score_pct'               => 0.00,
            'time_taken_seconds'      => 0,
            'avg_sec_per_question'    => 0.00,
            'passed'                  => 0,
            'certificate_id'          => 0,
            'answers_json'            => json_encode([]),
            'created_at'              => $startedUtc,
        ]);

        $newAttemptId = (int) $this->db->insertID();

        return [
            'attempt_id'        => $newAttemptId,
            'remaining_seconds' => $durationSeconds,
            'total_seconds'     => $durationSeconds,
            'started_at'        => $startedUtc,
            'expires_at'        => $expiresUtc,
            'questions'         => $drawnQuestions,
            'draft_answers'     => [],
            'is_resumed'        => false,
        ];
    }

    public function saveAttemptDraftAnswers(int $studentId, int $attemptId, array $answers): bool
    {
        if ($attemptId <= 0) {
            return false;
        }
        $this->db->table($this->attemptsTable)
            ->where('id', $attemptId)
            ->where('student_user_id', $studentId)
            ->where('status', 'in_progress')
            ->update([
                'draft_answers_json' => json_encode($answers, JSON_UNESCAPED_UNICODE),
            ]);
        return true;
    }

    public function getAllExamsWithQuestions(int $teacherIdFilter = 0): array
    {
        $nodes      = $this->getLeafNodesMap($teacherIdFilter);
        $sessions   = $this->getClassSessionsMap(0);
        $bankCounts = $this->getQuestionBankCountsByNode();

        $builder = $this->db->table($this->table)->orderBy('id', 'DESC');
        if ($teacherIdFilter > 0) {
            $nodeKeys = array_keys($nodes);
            if (empty($nodeKeys)) {
                return [];
            }
            $builder->whereIn('node_id', $nodeKeys);
        }
        $rows = $builder->get()->getResultArray();

        foreach ($rows as &$r) {
            $nid                    = (int) $r['node_id'];
            $r['node_title']        = $nodes[$nid] ?? 'Course Level';
            $r['selected_qids_arr'] = json_decode((string) ($r['selected_qids_json'] ?? '[]'), true) ?: [];
            $r['bank_available']    = ($bankCounts[$nid] ?? 0) + ($nid > 0 ? ($bankCounts[0] ?? 0) : 0);
            $sid                    = (int) ($r['unlock_after_session_id'] ?? 0);
            $r['session_title']     = $sid > 0 ? ($sessions[$sid]['title'] ?? 'Linked Class') : 'Always Open';
        }
        return $rows;
    }

    public function getAllWorksheets(int $teacherIdFilter = 0): array
    {
        $nodes   = $this->getLeafNodesMap($teacherIdFilter);
        $builder = $this->db->table($this->worksheetsTable)->orderBy('id', 'DESC');
        if ($teacherIdFilter > 0) {
            $nodeKeys = array_keys($nodes);
            if (empty($nodeKeys)) {
                return [];
            }
            $builder->whereIn('node_id', $nodeKeys);
        }
        $rows = $builder->get()->getResultArray();
        foreach ($rows as &$r) {
            $r['node_title']      = $nodes[(int) $r['node_id']] ?? 'Course Level';
            $r['resolved_dl_url'] = (strpos((string) $r['file_url'], 'http') === 0 || strpos((string) $r['file_url'], '#') === 0)
                ? $r['file_url']
                : base_url(ltrim((string) $r['file_url'], '/'));
        }
        return $rows;
    }

    public function saveExamData(array $post): array
    {
        $id            = (int) ($post['id'] ?? 0);
        $nodeId        = (int) ($post['node_id'] ?? 0);
        $type          = in_array($post['exam_type'] ?? '', ['practice_test', 'weekly_exam', 'monthly_exam', 'final_exam'], true)
            ? $post['exam_type']
            : 'practice_test';
        $title         = trim((string) ($post['title'] ?? ''));
        $duration      = max(1, min(180, (int) ($post['duration_minutes'] ?? 10)));
        $passPct       = max(1, min(100, (float) ($post['pass_percentage'] ?? 75)));
        $awardCert     = ! empty($post['award_certificate']) ? 1 : 0;
        $selectionMode = in_array($post['selection_mode'] ?? '', ['random_from_bank', 'manual_selected'], true)
            ? $post['selection_mode']
            : 'random_from_bank';
        $questionCount = max(1, min(200, (int) ($post['question_count'] ?? 10)));
        $selectedQids  = $post['selected_qids'] ?? [];
        if (! is_array($selectedQids)) {
            $selectedQids = [];
        }
        $selectedQids  = array_values(array_unique(array_filter(array_map('intval', $selectedQids))));
        if ($selectionMode === 'manual_selected' && ! empty($selectedQids)) {
            $questionCount = count($selectedQids);
        }

        $unlockMode    = in_array($post['unlock_mode'] ?? '', ['always_open', 'after_class_session'], true)
            ? $post['unlock_mode']
            : 'always_open';
        $unlockSessId  = ($unlockMode === 'after_class_session') ? (int) ($post['unlock_after_session_id'] ?? 0) : 0;
        $retentionMode = in_array($post['retention_mode'] ?? '', ['permanent_after_upgrade', 'expire_on_batch_change'], true)
            ? $post['retention_mode']
            : 'expire_on_batch_change';
        $isEnabled     = isset($post['is_enabled']) ? ((int) $post['is_enabled'] ? 1 : 0) : 1;

        if ($title === '') {
            return ['ok' => false, 'message' => 'Assessment Title is required.'];
        }
        if ($selectionMode === 'manual_selected' && empty($selectedQids)) {
            return ['ok' => false, 'message' => 'Please tick at least 1 question from the Question Bank for Manual Selection mode, or switch to Randomize mode.'];
        }

        $now = gmdate('Y-m-d H:i:s');
        $row = [
            'node_id'                 => $nodeId,
            'exam_type'               => $type,
            'title'                   => $title,
            'duration_minutes'        => $duration,
            'pass_percentage'         => $passPct,
            'award_certificate'       => $awardCert,
            'selection_mode'          => $selectionMode,
            'question_count'          => $questionCount,
            'selected_qids_json'      => json_encode($selectedQids),
            'unlock_mode'             => $unlockMode,
            'unlock_after_session_id' => $unlockSessId,
            'retention_mode'          => $retentionMode,
            'is_enabled'              => $isEnabled,
            'status'                  => 'active',
            'updated_at'              => $now,
        ];

        if ($id > 0) {
            $this->db->table($this->table)->where('id', $id)->update($row);
            return ['ok' => true, 'message' => 'Assessment "' . $title . '" updated!'];
        }

        $row['questions_json'] = json_encode([]);
        $row['created_at']     = $now;
        $this->db->table($this->table)->insert($row);
        return ['ok' => true, 'message' => 'New Assessment "' . $title . '" published!'];
    }

    public function saveOrUpdateWorksheet(array $post, $uploadedFile, int $userId): array
    {
        $id            = (int) ($post['id'] ?? 0);
        $nodeId        = (int) ($post['node_id'] ?? 0);
        $title         = trim((string) ($post['title'] ?? ''));
        $type          = trim((string) ($post['worksheet_type'] ?? 'PDF Practice Sheet')) ?: 'PDF Practice Sheet';
        $retentionMode = in_array($post['retention_mode'] ?? '', ['permanent_after_upgrade', 'expire_on_batch_change'], true)
            ? $post['retention_mode']
            : 'permanent_after_upgrade';
        $isEnabled     = isset($post['is_enabled']) ? ((int) $post['is_enabled'] ? 1 : 0) : 1;
        $instr         = trim((string) ($post['instructions'] ?? ''));
        $fileUrl       = trim((string) ($post['file_url'] ?? ''));
        $fileSource    = 'url';

        if ($title === '') {
            return ['ok' => false, 'message' => 'Worksheet Title is required.'];
        }

        if ($uploadedFile && $uploadedFile->isValid() && ! $uploadedFile->hasMoved()) {
            $ext = strtolower($uploadedFile->getClientExtension());
            if (! in_array($ext, ['pdf', 'zip', 'png', 'jpg', 'jpeg', 'webp', 'doc', 'docx'], true)) {
                return ['ok' => false, 'message' => 'Allowed file types: PDF, ZIP, PNG, JPG, DOC/DOCX.'];
            }
            $uploadDir = FCPATH . 'uploads/lms_resources/';
            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $newName = 'resource_node' . $nodeId . '_' . time() . '_' . $uploadedFile->getRandomName();
            $uploadedFile->move($uploadDir, $newName);
            $fileUrl    = 'uploads/lms_resources/' . $newName;
            $fileSource = 'upload';
        } elseif ($id > 0 && $fileUrl === '') {
            $existing = $this->db->table($this->worksheetsTable)->where('id', $id)->get()->getRowArray();
            if ($existing) {
                $fileUrl    = $existing['file_url'];
                $fileSource = $existing['file_source'] ?? 'upload';
            }
        }

        if ($fileUrl === '') {
            return ['ok' => false, 'message' => 'Please upload a PDF file OR enter a File URL.'];
        }

        $now = gmdate('Y-m-d H:i:s');
        $row = [
            'node_id'        => $nodeId,
            'title'          => $title,
            'worksheet_type' => $type,
            'file_source'    => $fileSource,
            'file_url'       => $fileUrl,
            'retention_mode' => $retentionMode,
            'is_enabled'     => $isEnabled,
            'instructions'   => $instr,
            'updated_at'     => $now,
        ];

        if ($id > 0) {
            $this->db->table($this->worksheetsTable)->where('id', $id)->update($row);
            return ['ok' => true, 'message' => 'Worksheet "' . $title . '" updated!'];
        }

        $row['created_by'] = $userId;
        $row['created_at'] = $now;
        $this->db->table($this->worksheetsTable)->insert($row);
        return ['ok' => true, 'message' => 'Worksheet "' . $title . '" uploaded!'];
    }

    public function gradeAndRecordAttempt(int $studentId, array $post): array
    {
        $attemptId = (int) ($post['attempt_id'] ?? 0);
        $examId    = (int) ($post['exam_id'] ?? 0);
        $submitted = $post['answers'] ?? [];
        if (! is_array($submitted)) {
            $submitted = [];
        }

        $exam = $this->find($examId);
        if (! $exam) {
            return ['ok' => false, 'message' => 'Assessment not found.'];
        }

        $attemptRow = null;
        if ($attemptId > 0) {
            $attemptRow = $this->db->table($this->attemptsTable)
                ->where('id', $attemptId)
                ->where('student_user_id', $studentId)
                ->get()
                ->getRowArray();
        }
        if (! $attemptRow) {
            $attemptRow = $this->db->table($this->attemptsTable)
                ->where('exam_id', $examId)
                ->where('student_user_id', $studentId)
                ->where('status', 'in_progress')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();
        }

        $questions = [];
        if ($attemptRow && ! empty($attemptRow['assigned_questions_json'])) {
            $questions = json_decode((string) $attemptRow['assigned_questions_json'], true) ?: [];
        }
        if (empty($questions)) {
            $questions = $this->drawQuestionsForExam($exam);
        }

        $totalQ = count($questions);
        if ($totalQ === 0) {
            return ['ok' => false, 'message' => 'No questions found in the Question Bank for this course.'];
        }

        $correct = 0;
        foreach ($questions as $idx => $qItem) {
            $expectedAns = strtolower(trim((string) ($qItem['ans'] ?? '')));
            $userAns     = strtolower(trim((string) ($submitted[$idx] ?? '')));
            if ($expectedAns !== '' && $userAns === $expectedAns) {
                $correct++;
            }
        }

        $maxDurationSec = max(60, (int) ($exam['duration_minutes'] ?? 5) * 60);
        if ($attemptRow && ! empty($attemptRow['started_at'])) {
            $serverElapsed = max(1, time() - strtotime($attemptRow['started_at'] . ' UTC'));
            $timeTaken     = min($maxDurationSec, $serverElapsed);
        } else {
            $timeTaken = max(1, min($maxDurationSec, (int) ($post['time_taken_seconds'] ?? 30)));
        }

        $scorePct = round(($correct / $totalQ) * 100, 2);
        $avgSec   = round($timeTaken / $totalQ, 2);
        $passed   = ($scorePct >= (float) ($exam['pass_percentage'] ?? 75)) ? 1 : 0;
        $nodeId   = (int) ($exam['node_id'] ?? 0);
        $examType = (string) ($exam['exam_type'] ?? 'practice_test');
        $now      = gmdate('Y-m-d H:i:s');

        $certId   = 0;
        $certCode = '';

        if ($passed === 1 && (int) ($exam['award_certificate'] ?? 0) === 1) {
            $students    = $this->getStudentsMap();
            $nodes       = $this->getLeafNodesMap(0);
            $studentName = $students[$studentId] ?? 'Student';
            $courseTitle = $nodes[$nodeId] ?? $exam['title'];
            $certCode    = 'CERT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $this->db->table($this->certsTable)->insert([
                'certificate_code' => $certCode,
                'student_user_id'  => $studentId,
                'student_name'     => $studentName,
                'node_id'          => $nodeId,
                'course_title'     => $courseTitle,
                'exam_id'          => $examId,
                'exam_title'       => $exam['title'],
                'score_pct'        => $scorePct,
                'issued_at'        => $now,
            ]);
            $certId = (int) $this->db->insertID();
        }

        $payload = [
            'status'               => 'completed',
            'total_questions'      => $totalQ,
            'correct_answers'      => $correct,
            'score_pct'            => $scorePct,
            'time_taken_seconds'   => $timeTaken,
            'avg_sec_per_question' => $avgSec,
            'passed'               => $passed,
            'certificate_id'       => $certId,
            'answers_json'         => json_encode($submitted, JSON_UNESCAPED_UNICODE),
            'created_at'           => $now,
        ];

        if ($attemptRow) {
            $this->db->table($this->attemptsTable)->where('id', $attemptRow['id'])->update($payload);
        } else {
            $payload['exam_id']                 = $examId;
            $payload['node_id']                 = $nodeId;
            $payload['student_user_id']         = $studentId;
            $payload['attempt_mode']            = $examType;
            $payload['started_at']              = $now;
            $payload['expires_at']              = $now;
            $payload['assigned_questions_json'] = json_encode($questions, JSON_UNESCAPED_UNICODE);
            $this->db->table($this->attemptsTable)->insert($payload);
        }

        if ($this->db->tableExists('lms_hierarchy_enrollments')) {
            $enr = $this->db->table('lms_hierarchy_enrollments')
                ->where('student_user_id', $studentId)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            if ($enr) {
                $prevScore = (float) ($enr['test_score_avg'] ?? 0);
                $newAvg    = $prevScore > 0 ? round(($prevScore + $scorePct) / 2, 2) : $scorePct;
                $newCerts  = (int) ($enr['certificates_count'] ?? 0) + ($certId > 0 ? 1 : 0);

                $this->db->table('lms_hierarchy_enrollments')
                    ->where('id', $enr['id'])
                    ->update([
                        'test_score_avg'     => $newAvg,
                        'certificates_count' => $newCerts,
                        'updated_at'         => $now,
                    ]);
            }
        }

        return [
            'ok'             => true,
            'passed'         => (bool) $passed,
            'score_pct'      => $scorePct,
            'correct'        => $correct,
            'total'          => $totalQ,
            'time_taken'     => $timeTaken,
            'avg_sec'        => $avgSec,
            'certificate_id' => $certId,
            'cert_code'      => $certCode,
            'pdf_url'        => $certId > 0 ? base_url('assessments/certificate-pdf/' . $certId) : '',
            'next_set_url'   => base_url('assessments/portal?take_exam_id=' . $examId . '&start=1'),
            'history_url'    => base_url('assessments/portal/history'),
            'created_at'     => $now,
            'message'        => 'Scored ' . $correct . '/' . $totalQ . ' (' . $scorePct . '%) in ' . $timeTaken . 's (' . $avgSec . 's/Q) — '
                . ($passed ? ('🎉 PASSED!' . ($certCode ? ' Certificate ' . $certCode . ' Unlocked!' : '')) : 'Practice completed! Click "Start Next Practice Set" below for a fresh set of questions.'),
        ];
    }

    /**
     * Fetches Completed Attempts reliably (including legacy rows where status was empty/null)
     * and cleans out any deleted students!
     */
    public function getAttemptsWithDetails(array $studentIdsFilter = [], int $teacherIdFilter = 0): array
    {
        $students = $this->getStudentsMap();
        $builder  = $this->db->table($this->attemptsTable)
            ->where('total_questions >', 0)
            ->groupStart()
                ->where('status', 'completed')
                ->orWhere('status', '')
                ->orWhere('status IS NULL', null, false)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->limit(100);

        if (! empty($studentIdsFilter)) {
            $builder->whereIn('student_user_id', array_map('intval', $studentIdsFilter));
        }
        if ($teacherIdFilter > 0) {
            $tNodes = array_keys($this->getLeafNodesMap($teacherIdFilter));
            if (empty($tNodes)) {
                return [];
            }
            $builder->whereIn('node_id', $tNodes);
        }

        $rows  = $builder->get()->getResultArray();
        $exams = [];
        foreach ($this->findAll() as $e) {
            $exams[(int) $e['id']] = $e['title'];
        }

        $out = [];
        foreach ($rows as $r) {
            $sid = (int) $r['student_user_id'];
            if (! isset($students[$sid]) && $this->isProductionMode()) {
                continue;
            }
            $eid = (int) $r['exam_id'];
            $r['student_name'] = $students[$sid] ?? 'Learner';
            $r['exam_title']   = $exams[$eid] ?? 'Practice Set';
            $out[]             = $r;
        }
        return $out;
    }

    public function getCertificatesList(array $studentIdsFilter = [], int $teacherIdFilter = 0): array
    {
        $builder = $this->db->table($this->certsTable);
        if (! empty($studentIdsFilter)) {
            $builder->whereIn('student_user_id', array_map('intval', $studentIdsFilter));
        }
        if ($teacherIdFilter > 0) {
            $tNodes = array_keys($this->getLeafNodesMap($teacherIdFilter));
            if (empty($tNodes)) {
                return [];
            }
            $builder->whereIn('node_id', $tNodes);
        }
        return $builder->orderBy('id', 'DESC')->get()->getResultArray();
    }

    public function generateCertificatePdfBinary(array $cert): string
    {
        $lines = [
            "======================================================================",
            "                 OFFICIAL CERTIFICATE OF ACHIEVEMENT                  ",
            "======================================================================",
            "",
            "Certificate Serial Code : " . ($cert['certificate_code'] ?? 'CERT-VERIFIED'),
            "Date of Issue           : " . substr((string) ($cert['issued_at'] ?? gmdate('Y-m-d')), 0, 10),
            "",
            "This is to certify that",
            "   >>>  " . strtoupper((string) ($cert['student_name'] ?? 'STUDENT')) . "  <<<",
            "",
            "has successfully completed the assessment & mastery benchmark for:",
            "   Program / Course : " . ($cert['course_title'] ?? 'Academic Program'),
            "   Examination      : " . ($cert['exam_title'] ?? 'Championship Exam'),
            "   Accuracy Score   : " . number_format((float) ($cert['score_pct'] ?? 100), 2) . "%",
            "",
            "======================================================================",
            "        Verified & Issued by Global Academic Assessment Engine        ",
            "======================================================================",
        ];

        $streamText = "BT\n/F1 13 Tf\n70 500 Td\n20 TL\n";
        foreach ($lines as $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $streamText .= "(" . $escaped . ") Tj T*\n";
        }
        $streamText .= "ET\n";

        $objs   = [];
        $objs[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objs[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objs[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 792 612] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $objs[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold >>\nendobj\n";
        $objs[] = "5 0 obj\n<< /Length " . strlen($streamText) . " >>\nstream\n" . $streamText . "endstream\nendobj\n";

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

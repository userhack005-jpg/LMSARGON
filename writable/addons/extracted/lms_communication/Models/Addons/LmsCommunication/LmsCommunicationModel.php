<?php

namespace App\Models\Addons\LmsCommunication;

use CodeIgniter\Model;
use Throwable;

class LmsCommunicationModel extends Model
{
    protected $table            = 'lms_chat_messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'node_id',
        'cohort_id',
        'student_user_id',
        'teacher_user_id',
        'sender_user_id',
        'sender_role',
        'message_text',
        'message_body',
        'is_flagged',
        'is_hidden',
        'is_blocked',
        'triggered_filter_id',
        'triggered_filter_name',
        'flag_reason',
        'moderation_status',
        'moderated_by',
        'created_at',
        'updated_at',
    ];

    protected string $filtersTable  = 'lms_dlp_filters';
    protected string $feedbackTable = 'lms_teacher_feedback';
    protected string $alertsTable   = 'lms_notification_logs';

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
            // 1. Create or Heal `lms_chat_messages` (100% compatible with both v4/v6 and v8!)
            if (! $this->db->tableExists('lms_chat_messages')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `lms_chat_messages` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `cohort_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `student_user_id` INT UNSIGNED NOT NULL,
                        `teacher_user_id` INT UNSIGNED NOT NULL,
                        `sender_user_id` INT UNSIGNED NOT NULL,
                        `sender_role` VARCHAR(30) NOT NULL DEFAULT 'student',
                        `message_text` TEXT NULL,
                        `message_body` TEXT NULL,
                        `is_flagged` TINYINT(1) NOT NULL DEFAULT 0,
                        `is_hidden` TINYINT(1) NOT NULL DEFAULT 0,
                        `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
                        `triggered_filter_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `triggered_filter_name` VARCHAR(190) NOT NULL DEFAULT '',
                        `flag_reason` VARCHAR(190) NOT NULL DEFAULT '',
                        `moderation_status` VARCHAR(30) NOT NULL DEFAULT 'clean',
                        `moderated_by` INT UNSIGNED NOT NULL DEFAULT 0,
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        KEY `idx_pair_node` (`student_user_id`, `teacher_user_id`, `node_id`),
                        KEY `idx_mod_flag` (`is_flagged`, `is_blocked`, `moderation_status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } else {
                // Safely add any missing columns without `AFTER` dependency!
                $mCols = $this->db->getFieldNames('lms_chat_messages');
                $mAlters = [
                    'node_id'               => "ADD COLUMN `node_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'cohort_id'             => "ADD COLUMN `cohort_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'message_text'          => "ADD COLUMN `message_text` TEXT NULL",
                    'message_body'          => "ADD COLUMN `message_body` TEXT NULL",
                    'is_flagged'            => "ADD COLUMN `is_flagged` TINYINT(1) NOT NULL DEFAULT 0",
                    'is_hidden'             => "ADD COLUMN `is_hidden` TINYINT(1) NOT NULL DEFAULT 0",
                    'is_blocked'            => "ADD COLUMN `is_blocked` TINYINT(1) NOT NULL DEFAULT 0",
                    'triggered_filter_id'   => "ADD COLUMN `triggered_filter_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'triggered_filter_name' => "ADD COLUMN `triggered_filter_name` VARCHAR(190) NOT NULL DEFAULT ''",
                    'flag_reason'           => "ADD COLUMN `flag_reason` VARCHAR(190) NOT NULL DEFAULT ''",
                    'moderation_status'     => "ADD COLUMN `moderation_status` VARCHAR(30) NOT NULL DEFAULT 'clean'",
                    'moderated_by'          => "ADD COLUMN `moderated_by` INT UNSIGNED NOT NULL DEFAULT 0",
                    'updated_at'            => "ADD COLUMN `updated_at` DATETIME NULL",
                ];
                foreach ($mAlters as $col => $sqlFrag) {
                    if (! in_array($col, $mCols, true)) {
                        try {
                            $this->db->query("ALTER TABLE `lms_chat_messages` " . $sqlFrag);
                        } catch (Throwable $e) {
                        }
                    }
                }

                // Sync legacy v4/v6 rows into v8 columns if needed
                try {
                    $this->db->query("UPDATE `lms_chat_messages` SET `message_body` = `message_text` WHERE (`message_body` IS NULL OR `message_body` = '') AND `message_text` IS NOT NULL");
                    $this->db->query("UPDATE `lms_chat_messages` SET `is_blocked` = `is_hidden` WHERE `is_hidden` = 1 AND `is_blocked` = 0");
                    $this->db->query("UPDATE `lms_chat_messages` SET `flag_reason` = `triggered_filter_name` WHERE (`flag_reason` = '' OR `flag_reason` IS NULL) AND `triggered_filter_name` != ''");
                    $this->db->query("UPDATE `lms_chat_messages` SET `moderation_status` = 'shadow_blocked' WHERE `is_blocked` = 1 AND `moderation_status` = 'clean'");
                } catch (Throwable $e) {
                }
            }

            // 2. Immutable 1-to-1 Student-to-Teacher Feedback Table (`lms_teacher_feedback`)
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_teacher_feedback` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `student_user_id` INT UNSIGNED NOT NULL,
                    `parent_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `teacher_user_id` INT UNSIGNED NOT NULL,
                    `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `cohort_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `batch_name_snapshot` VARCHAR(160) NOT NULL DEFAULT '',
                    `sentiment_type` VARCHAR(25) NOT NULL DEFAULT 'positive',
                    `rating_stars` TINYINT UNSIGNED NOT NULL DEFAULT 5,
                    `category_tag` VARCHAR(60) NOT NULL DEFAULT 'Teaching Clarity',
                    `feedback_text` TEXT NOT NULL,
                    `is_locked` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NULL,
                    KEY `idx_fb_teacher` (`teacher_user_id`, `sentiment_type`),
                    KEY `idx_fb_student` (`student_user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 3. DLP Safety Filter Rules Table (`lms_dlp_filters`)
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_dlp_filters` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `rule_name` VARCHAR(120) NOT NULL,
                    `pattern_type` VARCHAR(25) NOT NULL DEFAULT 'regex',
                    `pattern_value` VARCHAR(255) NOT NULL,
                    `action_mode` VARCHAR(30) NOT NULL DEFAULT 'shadow_block',
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 4. Email & WhatsApp Alert Logs Table (`lms_notification_logs`)
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `lms_notification_logs` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `event_type` VARCHAR(120) NOT NULL,
                    `channel` VARCHAR(40) NOT NULL DEFAULT 'email_and_whatsapp',
                    `recipient_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `recipient_timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Kolkata',
                    `formatted_message` TEXT NOT NULL,
                    `status` VARCHAR(30) NOT NULL DEFAULT 'sent',
                    `created_at` DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $lockFile = WRITEPATH . 'addons/schema_addon_lms_communication_v81.lock';
            if (is_file($lockFile)) {
                return;
            }

            if ((int) $this->db->table($this->filtersTable)->countAllResults() === 0) {
                $now = gmdate('Y-m-d H:i:s');
                $this->db->table($this->filtersTable)->insertBatch([
                    [
                        'rule_name'     => 'Phone & WhatsApp Number Guard',
                        'pattern_type'  => 'regex',
                        'pattern_value' => '(?:\\+?\\d[\\d\\s\\-()]{7,}\\d)',
                        'action_mode'   => 'shadow_block',
                        'is_active'     => 1,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ],
                    [
                        'rule_name'     => 'Email Address Guard',
                        'pattern_type'  => 'regex',
                        'pattern_value' => '[a-zA-Z0-9._%+\\-]+@[a-zA-Z0-9.\\-]+\\.[a-zA-Z]{2,}',
                        'action_mode'   => 'shadow_block',
                        'is_active'     => 1,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ],
                    [
                        'rule_name'     => 'External Meeting / Website Link Guard',
                        'pattern_type'  => 'regex',
                        'pattern_value' => '(https?:\\/\\/|www\\.|zoom\\.us|meet\\.google|t\\.me|wa\\.me)',
                        'action_mode'   => 'shadow_block',
                        'is_active'     => 1,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ],
                ]);
            }

            if ($this->db->tableExists('addons')) {
                $this->db->table('addons')
                    ->where('slug', 'lms_communication')
                    ->update(['name' => 'Classroom Chat, Teacher Feedback & Safety']);
            }

            $this->syncRbacPermissionsAndAutoEnableRoles();
            $this->mirrorAssets(true);

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, date('c'));
        } catch (Throwable $e) {
            log_message('error', 'LmsCommunicationModel schema error: ' . $e->getMessage());
        }
    }

    public function syncRbacPermissionsAndAutoEnableRoles(): void
    {
        try {
            $now = date('Y-m-d H:i:s');
            if ($this->db->tableExists('cms_rbac_features')) {
                $perms = [
                    ['lms_communication.chat',     'Access 1-to-1 Batch Classroom Chat [Student + Teacher + Parent Read-Only]'],
                    ['lms_communication.feedback', 'Submit & View 1-to-1 Teacher Feedback Cards [Student + Parent + Teacher + Admin]'],
                    ['lms_communication.moderate', 'Review Shadow-Blocked & Flagged Chat Queue [Super Admin + Admin]'],
                    ['lms_communication.filters',  'Manage DLP Keyword & Regex Safety Filters [Super Admin + Admin]'],
                    ['lms_communication.alerts',   'View Email & WhatsApp Alert Logs [Super Admin + Admin]'],
                ];
                foreach ($perms as $p) {
                    $this->db->query("
                        INSERT INTO `cms_rbac_features` (`perm_key`, `perm_label`, `module_group`, `source_type`, `created_at`)
                        VALUES (?, ?, 'Add-on: Classroom Chat, Teacher Feedback & Safety', 'addon', ?)
                        ON DUPLICATE KEY UPDATE `perm_label` = VALUES(`perm_label`), `module_group` = VALUES(`module_group`)
                    ", [$p[0], $p[1], $now]);
                }
            }

            if ($this->db->tableExists('cms_roles')) {
                $roleAutoGrants = [
                    'student' => ['lms_communication.chat', 'lms_communication.feedback'],
                    'parent'  => ['lms_communication.chat', 'lms_communication.feedback'],
                    'teacher' => ['lms_communication.chat', 'lms_communication.feedback'],
                    'admin'   => ['lms_communication.chat', 'lms_communication.feedback', 'lms_communication.moderate', 'lms_communication.filters', 'lms_communication.alerts'],
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
        $srcDir = WRITEPATH . 'addons/extracted/lms_communication/assets';
        $dstDir = FCPATH . 'assets/addons/lms_communication';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['lms_communication.css', 'lms_communication.js'] as $f) {
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

    public function isProductionMode(): bool
    {
        if (function_exists('cms_is_sandbox_mode')) {
            return ! cms_is_sandbox_mode();
        }
        $s = $this->getGlobalSiteSettings();
        return strtolower(trim((string) ($s['system_environment_mode'] ?? 'testing'))) === 'production';
    }

    public function getUsersByIdMap(): array
    {
        $map = [];
        if (! $this->db->tableExists('users')) {
            return $map;
        }
        foreach ($this->db->table('users')->orderBy('id', 'ASC')->get()->getResultArray() as $u) {
            $id = (int) $u['id'];
            $u['display_name'] = $u['full_name'] ?? ($u['name'] ?? ($u['username'] ?? ($u['email'] ?? 'User')));
            $map[$id]          = $u;
        }
        return $map;
    }

    public function getValidStudentTeacherPairs(string $effectiveRole, int $loggedInUserId, int $activeChildId = 0): array
    {
        $users    = $this->getUsersByIdMap();
        $nodesMap = [];
        if ($this->db->tableExists('lms_hierarchy_nodes')) {
            foreach ($this->db->table('lms_hierarchy_nodes')->get()->getResultArray() as $n) {
                $nodesMap[(int) $n['id']] = $n;
            }
        }

        $cohortsById   = [];
        $cohortsByLeaf = [];
        if ($this->db->tableExists('lms_classroom_batches')) {
            foreach ($this->db->table('lms_classroom_batches')->orderBy('id', 'DESC')->get()->getResultArray() as $cb) {
                $cid = (int) $cb['id'];
                $lid = (int) $cb['leaf_node_id'];
                $cohortsById[$cid]     = $cb;
                $cohortsByLeaf[$lid][] = $cb;
            }
        }

        $rawPairs = [];

        if ($this->db->tableExists('lms_classroom_batch_students')) {
            foreach ($this->db->table('lms_classroom_batch_students')->where('status', 'active')->get()->getResultArray() as $cs) {
                $cid    = (int) $cs['cohort_id'];
                $stuId  = (int) $cs['student_user_id'];
                $cohort = $cohortsById[$cid] ?? null;
                if (! $cohort || ! isset($users[$stuId])) {
                    continue;
                }
                $tid    = (int) ($cohort['teacher_user_id'] ?? 0);
                $leafId = (int) ($cohort['leaf_node_id'] ?? 0);
                if ($tid <= 0 || ! isset($users[$tid])) {
                    continue;
                }

                $pairKey = $stuId . '_' . $tid . '_' . $leafId . '_' . $cid;
                $rawPairs[$pairKey] = [
                    'pair_key'      => $pairKey,
                    'student_id'    => $stuId,
                    'student_name'  => $users[$stuId]['display_name'],
                    'parent_id'     => (int) ($users[$stuId]['parent_user_id'] ?? 0),
                    'teacher_id'    => $tid,
                    'teacher_name'  => $users[$tid]['display_name'],
                    'node_id'       => $leafId,
                    'cohort_id'     => $cid,
                    'batch_title'   => $cohort['cohort_title'] ?: ($nodesMap[$leafId]['title'] ?? 'Live Batch'),
                    'batch_code'    => $cohort['cohort_code'] ?: ('BATCH-' . $cid),
                    'is_past_batch' => false,
                ];
            }
        }

        if ($this->db->tableExists('lms_hierarchy_enrollments')) {
            foreach ($this->db->table('lms_hierarchy_enrollments')->get()->getResultArray() as $enr) {
                $stuId = (int) $enr['student_user_id'];
                if (! isset($users[$stuId])) {
                    continue;
                }
                $leafId = (int) $enr['leaf_node_id'];
                $node   = $nodesMap[$leafId] ?? null;

                $tid        = (int) ($node['teacher_user_id'] ?? 0);
                $cid        = 0;
                $batchTitle = $node['title'] ?? 'Course Batch';
                $batchCode  = 'COURSE-' . $leafId;

                if (! empty($cohortsByLeaf[$leafId])) {
                    $firstCoh   = $cohortsByLeaf[$leafId][0];
                    $cid        = (int) $firstCoh['id'];
                    $tid        = (int) ($firstCoh['teacher_user_id'] ?: $tid);
                    $batchTitle = $firstCoh['cohort_title'] ?: $batchTitle;
                    $batchCode  = $firstCoh['cohort_code'] ?: $batchCode;
                }

                if ($tid > 0 && isset($users[$tid])) {
                    $pairKey = $stuId . '_' . $tid . '_' . $leafId . '_' . $cid;
                    if (! isset($rawPairs[$pairKey])) {
                        $rawPairs[$pairKey] = [
                            'pair_key'      => $pairKey,
                            'student_id'    => $stuId,
                            'student_name'  => $users[$stuId]['display_name'],
                            'parent_id'     => (int) ($enr['parent_user_id'] ?: ($users[$stuId]['parent_user_id'] ?? 0)),
                            'teacher_id'    => $tid,
                            'teacher_name'  => $users[$tid]['display_name'],
                            'node_id'       => $leafId,
                            'cohort_id'     => $cid,
                            'batch_title'   => $batchTitle,
                            'batch_code'    => $batchCode,
                            'is_past_batch' => ($enr['status'] ?? 'active') !== 'active',
                        ];
                    }
                }

                $histArr = json_decode((string) ($enr['history_json'] ?? '[]'), true);
                if (is_array($histArr)) {
                    foreach ($histArr as $hItem) {
                        $oldLeaf = (int) ($hItem['from_leaf_id'] ?? 0);
                        if ($oldLeaf <= 0 || ! isset($nodesMap[$oldLeaf])) {
                            continue;
                        }
                        $oldNode = $nodesMap[$oldLeaf];
                        $oldTid  = (int) ($oldNode['teacher_user_id'] ?? 0);
                        $oldCid  = 0;
                        $oldTit  = $hItem['from_leaf_title'] ?? ($oldNode['title'] ?? 'Previous Batch');
                        if (! empty($cohortsByLeaf[$oldLeaf])) {
                            $oldCoh = $cohortsByLeaf[$oldLeaf][0];
                            $oldCid = (int) $oldCoh['id'];
                            $oldTid = (int) ($oldCoh['teacher_user_id'] ?: $oldTid);
                            $oldTit = $oldCoh['cohort_title'] ?: $oldTit;
                        }
                        if ($oldTid > 0 && isset($users[$oldTid])) {
                            $pKey = $stuId . '_' . $oldTid . '_' . $oldLeaf . '_' . $oldCid;
                            if (! isset($rawPairs[$pKey])) {
                                $rawPairs[$pKey] = [
                                    'pair_key'      => $pKey,
                                    'student_id'    => $stuId,
                                    'student_name'  => $users[$stuId]['display_name'],
                                    'parent_id'     => (int) ($enr['parent_user_id'] ?: ($users[$stuId]['parent_user_id'] ?? 0)),
                                    'teacher_id'    => $oldTid,
                                    'teacher_name'  => $users[$oldTid]['display_name'],
                                    'node_id'       => $oldLeaf,
                                    'cohort_id'     => $oldCid,
                                    'batch_title'   => $oldTit . ' (Completed Level)',
                                    'batch_code'    => 'PAST-' . $oldLeaf,
                                    'is_past_batch' => true,
                                ];
                            }
                        }
                    }
                }
            }
        }

        $filtered = [];
        foreach ($rawPairs as $pk => $p) {
            if ($effectiveRole === 'teacher') {
                if ((int) $p['teacher_id'] === $loggedInUserId) {
                    $filtered[$pk] = $p;
                }
            } elseif ($effectiveRole === 'parent' && $activeChildId <= 0) {
                if ((int) $p['parent_id'] === $loggedInUserId) {
                    $filtered[$pk] = $p;
                }
            } elseif ($effectiveRole === 'student' || $activeChildId > 0) {
                $targetStu = $activeChildId > 0 ? $activeChildId : $loggedInUserId;
                if ((int) $p['student_id'] === $targetStu) {
                    $filtered[$pk] = $p;
                }
            } else {
                $filtered[$pk] = $p;
            }
        }

        return array_values($filtered);
    }

    public function inspectMessageWithDlp(string $messageText): array
    {
        $rules = $this->db->table($this->filtersTable)->where('is_active', 1)->get()->getResultArray();
        foreach ($rules as $r) {
            $type    = strtolower(trim((string) ($r['pattern_type'] ?? 'keyword')));
            $pattern = trim((string) ($r['pattern_value'] ?? ''));
            $mode    = strtolower(trim((string) ($r['action_mode'] ?? 'shadow_block')));
            if ($pattern === '') {
                continue;
            }

            $matched = false;
            if ($type === 'regex') {
                $delim = '/';
                $clean = trim($pattern, '/');
                if (@preg_match($delim . $clean . $delim . 'i', $messageText)) {
                    $matched = true;
                }
            } else {
                if (stripos($messageText, $pattern) !== false) {
                    $matched = true;
                }
            }

            if ($matched) {
                return [
                    'is_flagged'        => 1,
                    'is_blocked'        => ($mode === 'shadow_block' || $mode === 'block') ? 1 : 0,
                    'flag_reason'       => 'Triggered Rule: ' . ($r['rule_name'] ?? 'Safety Filter'),
                    'moderation_status' => ($mode === 'shadow_block' || $mode === 'block') ? 'shadow_blocked' : 'flagged',
                ];
            }
        }

        return [
            'is_flagged'        => 0,
            'is_blocked'        => 0,
            'flag_reason'       => '',
            'moderation_status' => 'clean',
        ];
    }

    public function sendOneToOneMessage(int $senderId, string $senderRole, array $post): array
    {
        $studentId = (int) ($post['student_user_id'] ?? 0);
        $teacherId = (int) ($post['teacher_user_id'] ?? 0);
        $nodeId    = (int) ($post['node_id'] ?? 0);
        $cohortId  = (int) ($post['cohort_id'] ?? 0);
        $msgBody   = trim((string) ($post['message_body'] ?? ($post['message_text'] ?? '')));

        if ($studentId <= 0 || $teacherId <= 0 || $msgBody === '') {
            return ['ok' => false, 'message' => 'Select a valid conversation and type a message.'];
        }

        $dlp = $this->inspectMessageWithDlp($msgBody);
        $now = gmdate('Y-m-d H:i:s');

        $rawInsert = [
            'node_id'               => $nodeId,
            'cohort_id'             => $cohortId,
            'student_user_id'       => $studentId,
            'teacher_user_id'       => $teacherId,
            'sender_user_id'        => $senderId,
            'sender_role'           => $senderRole,
            'message_text'          => $msgBody,
            'message_body'          => $msgBody,
            'is_flagged'            => $dlp['is_flagged'],
            'is_hidden'             => $dlp['is_blocked'],
            'is_blocked'            => $dlp['is_blocked'],
            'triggered_filter_name' => $dlp['flag_reason'],
            'flag_reason'           => $dlp['flag_reason'],
            'moderation_status'     => $dlp['moderation_status'],
            'created_at'            => $now,
            'updated_at'            => $now,
        ];

        $actualCols = array_flip($this->db->getFieldNames($this->table));
        $insertData = array_intersect_key($rawInsert, $actualCols);

        $this->db->table($this->table)->insert($insertData);
        $msgId = (int) $this->db->insertID();

        return [
            'ok'         => true,
            'message_id' => $msgId,
            'created_at' => substr($now, 11, 5),
        ];
    }

    public function getConversationMessages(int $studentId, int $teacherId, int $nodeId, int $viewerUserId, bool $isAdminViewer = false): array
    {
        if ($studentId <= 0 || $teacherId <= 0) {
            return [];
        }

        $users   = $this->getUsersByIdMap();
        $mCols   = $this->db->getFieldNames($this->table);
        $builder = $this->db->table($this->table)
            ->where('student_user_id', $studentId)
            ->where('teacher_user_id', $teacherId);

        if ($nodeId > 0 && in_array('node_id', $mCols, true)) {
            $builder->whereIn('node_id', [$nodeId, 0]);
        }

        $rows = $builder->orderBy('id', 'ASC')->limit(120)->get()->getResultArray();
        $out  = [];

        foreach ($rows as $r) {
            $isBlocked = ((int) ($r['is_blocked'] ?? ($r['is_hidden'] ?? 0)) === 1);
            $modStatus = (string) ($r['moderation_status'] ?? 'clean');
            $senderUid = (int) ($r['sender_user_id'] ?? 0);

            if ($isBlocked && $modStatus !== 'approved' && ! $isAdminViewer && $senderUid !== $viewerUserId) {
                continue;
            }

            $r['message_body'] = $r['message_body'] ?: ($r['message_text'] ?? '');
            $r['sender_name']  = $users[$senderUid]['display_name'] ?? ucfirst((string) $r['sender_role']);
            $r['time_short']   = substr((string) ($r['created_at'] ?? ''), 0, 16);
            $out[]             = $r;
        }

        return $out;
    }

    public function submitImmutableTeacherFeedback(int $studentId, int $parentId, array $post): array
    {
        $teacherId = (int) ($post['teacher_user_id'] ?? 0);
        $nodeId    = (int) ($post['node_id'] ?? 0);
        $cohortId  = (int) ($post['cohort_id'] ?? 0);
        $batchName = trim((string) ($post['batch_name'] ?? 'Enrolled Batch'));
        $sentiment = in_array($post['sentiment_type'] ?? '', ['positive', 'constructive'], true)
            ? $post['sentiment_type']
            : 'positive';
        $stars     = max(1, min(5, (int) ($post['rating_stars'] ?? 5)));
        $category  = trim((string) ($post['category_tag'] ?? 'Teaching Clarity')) ?: 'Teaching Clarity';
        $text      = trim((string) ($post['feedback_text'] ?? ''));

        if ($studentId <= 0 || $teacherId <= 0 || $text === '') {
            return ['ok' => false, 'message' => 'Please select your Batch Teacher and write your feedback note.'];
        }

        $now = gmdate('Y-m-d H:i:s');
        $this->db->table($this->feedbackTable)->insert([
            'student_user_id'     => $studentId,
            'parent_user_id'      => $parentId,
            'teacher_user_id'     => $teacherId,
            'node_id'             => $nodeId,
            'cohort_id'           => $cohortId,
            'batch_name_snapshot' => $batchName,
            'sentiment_type'      => $sentiment,
            'rating_stars'        => $stars,
            'category_tag'        => $category,
            'feedback_text'       => $text,
            'is_locked'           => 1,
            'created_at'          => $now,
        ]);

        return [
            'ok'      => true,
            'message' => 'Your feedback has been submitted and permanently locked!',
        ];
    }

    public function getFeedbackCardsForRole(string $effectiveRole, int $loggedInUserId, array $childIds = []): array
    {
        $users   = $this->getUsersByIdMap();
        $builder = $this->db->table($this->feedbackTable)->orderBy('id', 'DESC');

        if ($effectiveRole === 'teacher') {
            $builder->where('teacher_user_id', $loggedInUserId);
        } elseif ($effectiveRole === 'parent') {
            $builder->groupStart()->where('parent_user_id', $loggedInUserId);
            if (! empty($childIds)) {
                $builder->orWhereIn('student_user_id', array_map('intval', $childIds));
            }
            $builder->groupEnd();
        } elseif ($effectiveRole === 'student') {
            $targetIds = ! empty($childIds) ? array_map('intval', $childIds) : [$loggedInUserId];
            $builder->whereIn('student_user_id', $targetIds);
        }

        $rows = $builder->limit(100)->get()->getResultArray();
        $out  = [];

        foreach ($rows as $r) {
            $sid = (int) $r['student_user_id'];
            $tid = (int) $r['teacher_user_id'];
            $r['student_name'] = $users[$sid]['display_name'] ?? 'Student';
            $r['teacher_name'] = $users[$tid]['display_name'] ?? 'Teacher';
            $out[]             = $r;
        }

        return $out;
    }

    public function getFlaggedAndBlockedMessages(): array
    {
        $users   = $this->getUsersByIdMap();
        $mCols   = $this->db->getFieldNames($this->table);
        $builder = $this->db->table($this->table);

        $builder->groupStart()
            ->where('is_flagged', 1);
        if (in_array('is_blocked', $mCols, true)) {
            $builder->orWhere('is_blocked', 1);
        }
        if (in_array('is_hidden', $mCols, true)) {
            $builder->orWhere('is_hidden', 1);
        }
        $builder->groupEnd();

        $rows = $builder->orderBy('id', 'DESC')->limit(100)->get()->getResultArray();

        foreach ($rows as &$r) {
            $sid = (int) $r['student_user_id'];
            $tid = (int) $r['teacher_user_id'];
            $uid = (int) $r['sender_user_id'];
            $r['message_body'] = $r['message_body'] ?: ($r['message_text'] ?? '');
            $r['flag_reason']  = $r['flag_reason'] ?: ($r['triggered_filter_name'] ?? 'Safety Rule');
            $r['student_name'] = $users[$sid]['display_name'] ?? 'Student';
            $r['teacher_name'] = $users[$tid]['display_name'] ?? 'Teacher';
            $r['sender_name']  = $users[$uid]['display_name'] ?? 'Sender';
        }
        return $rows;
    }

    public function getAllDlpFilters(): array
    {
        return $this->db->table($this->filtersTable)->orderBy('id', 'ASC')->get()->getResultArray();
    }

    public function getNotificationLogs(): array
    {
        $users = $this->getUsersByIdMap();
        $rows  = $this->db->table($this->alertsTable)->orderBy('id', 'DESC')->limit(80)->get()->getResultArray();
        foreach ($rows as &$r) {
            $uid = (int) ($r['recipient_user_id'] ?? 0);
            $r['recipient_name'] = $users[$uid]['display_name'] ?? 'Recipient';
        }
        return $rows;
    }
}

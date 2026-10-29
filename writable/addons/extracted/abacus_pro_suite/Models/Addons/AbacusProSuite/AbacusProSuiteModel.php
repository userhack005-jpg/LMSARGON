<?php

namespace App\Models\Addons\AbacusProSuite;

use CodeIgniter\Model;
use Throwable;

class AbacusProSuiteModel extends Model
{
    protected $table            = 'lms_abacus_attempts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_user_id', 'node_id', 'cohort_id', 'drill_mode',
        'digits_count', 'rows_count', 'speed_seconds', 'total_questions',
        'correct_answers', 'accuracy_pct', 'time_taken_sec', 'created_at',
    ];

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
            if (! $this->db->tableExists('lms_abacus_attempts')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `lms_abacus_attempts` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `student_user_id` INT UNSIGNED NOT NULL,
                        `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `cohort_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `drill_mode` VARCHAR(40) NOT NULL DEFAULT 'flash_anzan',
                        `digits_count` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                        `rows_count` SMALLINT UNSIGNED NOT NULL DEFAULT 5,
                        `speed_seconds` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                        `total_questions` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                        `correct_answers` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                        `accuracy_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                        `time_taken_sec` INT UNSIGNED NOT NULL DEFAULT 0,
                        `created_at` DATETIME NULL,
                        KEY `idx_stu_created` (`student_user_id`, `created_at`),
                        KEY `idx_cohort_mode` (`cohort_id`, `drill_mode`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }

            $this->cleanupNonStudentAndOrphanedAttempts();
            $this->syncPageBuilderComponents();

            $lockFile = WRITEPATH . 'addons/schema_addon_abacus_pro_suite_v85.lock';
            if (is_file($lockFile)) {
                return;
            }

            if ($this->db->tableExists('addons')) {
                $this->db->table('addons')->where('slug', 'abacus_pro_suite')->update(['name' => '3D Abacus & Flash Anzan Lab']);
            }

            $this->syncRbacPermissionsAndAutoEnableRoles();
            $this->mirrorAssets(true);

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, date('c'));
        } catch (Throwable $e) {
            log_message('error', 'AbacusProSuiteModel schema error: ' . $e->getMessage());
        }
    }

    /**
     * Registers TWO Distinct Frontend Page Builder Components in `cms_components`
     * so Admin can drop 3D Soroban Abacus OR Flash Anzan (or both) on any public page for Non-Registered Visitors!
     */
    public function syncPageBuilderComponents(): void
    {
        try {
            if (! $this->db->tableExists('cms_components')) {
                return;
            }
            $components = [
                [
                    'key'   => 'abacus_soroban_lab',
                    'name'  => '3D Soroban Abacus Interactive Block (5-17 Rods)',
                    'icon'  => '🧮',
                    'order' => 14,
                    'data'  => [
                        'badge'        => 'INTERACTIVE 3D SOROBAN LAB',
                        'heading'      => 'Try Our 3D Japanese Soroban Abacus Live',
                        'subheading'   => 'Slide the beads or click New Target Challenge to test your mental math skills right now — no login required!',
                        'widget_mode'  => 'soroban_only',
                        'default_rods' => '5',
                        'theme'        => 'cyber_emerald',
                    ],
                ],
                [
                    'key'   => 'abacus_flash_anzan',
                    'name'  => 'Flash Anzan & Hindi/English Voice Dictation Block',
                    'icon'  => '⚡',
                    'order' => 15,
                    'data'  => [
                        'badge'        => 'SPEED MENTAL MATH ARENA',
                        'heading'      => 'Flash Anzan & Voice Dictation Challenge',
                        'subheading'   => 'Test your calculation speed from 2.0s down to 0.3s with Hindi & English voice dictation!',
                        'widget_mode'  => 'anzan_only',
                        'default_rods' => '5',
                        'theme'        => 'cyber_emerald',
                    ],
                ],
            ];

            foreach ($components as $c) {
                $json = json_encode($c['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->db->query("
                    INSERT INTO `cms_components`
                        (`component_key`, `component_name`, `component_icon`, `category`, `addon_slug`, `default_schema_json`, `is_enabled`, `sort_order`)
                    VALUES (?, ?, ?, 'interactive', 'abacus_pro_suite', ?, 1, ?)
                    ON DUPLICATE KEY UPDATE
                        `component_name`      = VALUES(`component_name`),
                        `component_icon`      = VALUES(`component_icon`),
                        `addon_slug`          = 'abacus_pro_suite',
                        `default_schema_json` = VALUES(`default_schema_json`),
                        `is_enabled`          = 1
                ", [$c['key'], $c['name'], $c['icon'], $json, $c['order']]);
            }
        } catch (Throwable $e) {
        }
    }

    public function cleanupNonStudentAndOrphanedAttempts(): void
    {
        try {
            if (! $this->db->tableExists('users') || ! $this->db->tableExists($this->table)) {
                return;
            }
            $this->db->query("
                DELETE a FROM `lms_abacus_attempts` a
                LEFT JOIN `users` u ON u.id = a.student_user_id
                WHERE u.id IS NULL
                   OR u.role_id IN (1, 2, 3)
                   OR u.role_slug IN ('super_admin', 'admin', 'teacher', 'parent')
            ");
        } catch (Throwable $e) {
        }
    }

    public function syncRbacPermissionsAndAutoEnableRoles(): void
    {
        try {
            $now = date('Y-m-d H:i:s');
            if ($this->db->tableExists('cms_rbac_features')) {
                $perms = [
                    ['abacus_pro_suite.practice', 'Access 3D Soroban Lab, Flash Anzan & My Speed Badges [Student + Parent + Teacher]'],
                    ['abacus_pro_suite.view',     'View Batch Student Abacus Practice Analytics [Teacher + Admin]'],
                    ['abacus_pro_suite.edit',     'Configure Soroban Themes, Rods & Default Drill Settings [Super Admin + Admin]'],
                    ['abacus_pro_suite.delete',   'Delete Practice Attempt Logs [Super Admin + Admin]'],
                ];
                foreach ($perms as $p) {
                    $this->db->query("
                        INSERT INTO `cms_rbac_features` (`perm_key`, `perm_label`, `module_group`, `source_type`, `created_at`)
                        VALUES (?, ?, 'Add-on: 3D Abacus & Flash Anzan Lab', 'addon', ?)
                        ON DUPLICATE KEY UPDATE `perm_label` = VALUES(`perm_label`), `module_group` = VALUES(`module_group`)
                    ", [$p[0], $p[1], $now]);
                }
            }
        } catch (Throwable $e) {
        }
    }

    public function mirrorAssets(bool $forceTouch = false): void
    {
        $srcDir = WRITEPATH . 'addons/extracted/abacus_pro_suite/assets';
        $dstDir = FCPATH . 'assets/addons/abacus_pro_suite';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['abacus_pro_suite.css', 'abacus_pro_suite.js'] as $f) {
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

    public function getAbacusConfig(): array
    {
        $s   = $this->getGlobalSiteSettings();
        $raw = $s['abacus_pro_suite__config_json'] ?? '';
        $cfg = $raw !== '' ? (json_decode((string) $raw, true) ?: []) : [];

        return array_merge([
            'default_rods'      => 5,
            'default_theme'     => 'cyber_emerald',
            'default_digits'    => 1,
            'default_rows'      => 5,
            'default_speed_sec' => 1.0,
            'default_lang'      => 'hi-IN',
            'enable_sound'      => 1,
            'enable_voice'      => 1,
        ], $cfg);
    }

    public function saveAbacusConfig(array $post): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }
        $cfg = [
            'default_rods'      => in_array((int) ($post['default_rods'] ?? 5), [5, 7, 9, 11, 13, 15, 17], true) ? (int) $post['default_rods'] : 5,
            'default_theme'     => in_array($post['default_theme'] ?? '', ['cyber_emerald', 'classic_wood', 'royal_gold'], true) ? $post['default_theme'] : 'cyber_emerald',
            'default_digits'    => max(1, min(5, (int) ($post['default_digits'] ?? 1))),
            'default_rows'      => max(2, min(25, (int) ($post['default_rows'] ?? 5))),
            'default_speed_sec' => max(0.2, min(5.0, (float) ($post['default_speed_sec'] ?? 1.0))),
            'default_lang'      => in_array($post['default_lang'] ?? '', ['hi-IN', 'en-IN'], true) ? $post['default_lang'] : 'hi-IN',
            'enable_sound'      => ! empty($post['enable_sound']) ? 1 : 0,
            'enable_voice'      => ! empty($post['enable_voice']) ? 1 : 0,
        ];

        $this->db->query("
            INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`)
            VALUES ('abacus_pro_suite__config_json', 'abacus_pro_suite', ?)
            ON DUPLICATE KEY UPDATE `setting_group` = 'abacus_pro_suite', `setting_value` = VALUES(`setting_value`)
        ", [json_encode($cfg, JSON_UNESCAPED_UNICODE)]);
        self::$globalSettingsCache = null;
    }

    public function logStudentAttemptStrict(string $effectiveRole, int $targetStudentId, array $post): array
    {
        $mode     = in_array($post['drill_mode'] ?? '', ['soroban_challenge', 'flash_anzan', 'voice_dictation'], true) ? $post['drill_mode'] : 'flash_anzan';
        $digits   = max(1, min(6, (int) ($post['digits_count'] ?? 1)));
        $rowsCnt  = max(1, min(30, (int) ($post['rows_count'] ?? 5)));
        $speedSec = max(0.1, min(10.0, (float) ($post['speed_seconds'] ?? 1.0)));
        $totalQ   = max(1, (int) ($post['total_questions'] ?? 1));
        $correctQ = max(0, min($totalQ, (int) ($post['correct_answers'] ?? 0)));
        $timeSec  = max(1, (int) ($post['time_taken_sec'] ?? round($rowsCnt * $speedSec)));
        $accPct   = round(($correctQ / $totalQ) * 100, 2);

        $isGenuineStudent = false;
        if ($effectiveRole === 'student' && $targetStudentId > 0 && $this->db->tableExists('users')) {
            $uRow = $this->db->table('users')->where('id', $targetStudentId)->get()->getRowArray();
            if ($uRow) {
                $rId   = (int) ($uRow['role_id'] ?? 4);
                $rSlug = strtolower(trim((string) ($uRow['role_slug'] ?? 'student')));
                if (! in_array($rId, [1, 2, 3], true) && ! in_array($rSlug, ['super_admin', 'admin', 'teacher', 'parent'], true)) {
                    $isGenuineStudent = true;
                }
            }
        }

        if (! $isGenuineStudent) {
            return [
                'ok'           => true,
                'saved_to_db'  => false,
                'accuracy_pct' => $accPct,
                'message'      => 'Score ' . $accPct . '%! (Log in with your Student account to save scores to your badge history).',
            ];
        }

        $cohortId = 0;
        $nodeId   = 0;
        if ($this->db->tableExists('lms_classroom_batch_students')) {
            $cs = $this->db->table('lms_classroom_batch_students')
                ->where('student_user_id', $targetStudentId)
                ->where('status', 'active')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();
            if ($cs) {
                $cohortId = (int) $cs['cohort_id'];
                $nodeId   = (int) $cs['leaf_node_id'];
            }
        }

        $now = gmdate('Y-m-d H:i:s');
        $this->db->table($this->table)->insert([
            'student_user_id' => $targetStudentId,
            'node_id'         => $nodeId,
            'cohort_id'       => $cohortId,
            'drill_mode'      => $mode,
            'digits_count'    => $digits,
            'rows_count'      => $rowsCnt,
            'speed_seconds'   => $speedSec,
            'total_questions' => $totalQ,
            'correct_answers' => $correctQ,
            'accuracy_pct'    => $accPct,
            'time_taken_sec'  => $timeSec,
            'created_at'      => $now,
        ]);

        return [
            'ok'           => true,
            'saved_to_db'  => true,
            'accuracy_pct' => $accPct,
            'message'      => ($accPct >= 100
                ? '🎉 100% Accuracy! Saved to your Student Progress & Badges!'
                : 'Practice saved (' . $accPct . '% Accuracy)!'),
        ];
    }

    public function getStudentProgressAndBadges(array $studentIds): array
    {
        $this->cleanupNonStudentAndOrphanedAttempts();
        $cleanIds = array_values(array_filter(array_map('intval', $studentIds), static fn($v) => $v > 0));
        if (empty($cleanIds)) {
            return ['total_drills' => 0, 'avg_accuracy' => 0.0, 'best_speed' => 0.0, 'perfect_cnt' => 0, 'badges' => [], 'attempts' => []];
        }

        $users = [];
        if ($this->db->tableExists('users')) {
            foreach ($this->db->table('users')->whereIn('id', $cleanIds)->get()->getResultArray() as $u) {
                $users[(int) $u['id']] = $u['full_name'] ?: ($u['name'] ?: 'Student');
            }
        }

        $rows = $this->db->table($this->table)->whereIn('student_user_id', $cleanIds)->orderBy('id', 'DESC')->limit(100)->get()->getResultArray();
        $totalDrills = count($rows);
        $accSum      = 0.0;
        $bestSpeed   = 99.0;
        $perfectCnt  = 0;

        foreach ($rows as &$r) {
            $sid = (int) $r['student_user_id'];
            $acc = (float) $r['accuracy_pct'];
            $spd = (float) $r['speed_seconds'];
            $r['student_name'] = $users[$sid] ?? 'Learner';
            $accSum += $acc;
            if ($acc >= 100.0) $perfectCnt++;
            if ($acc >= 80.0 && $spd > 0 && $spd < $bestSpeed) $bestSpeed = $spd;
        }
        unset($r);
        if ($bestSpeed >= 99.0) $bestSpeed = 0.0;
        $avgAcc = $totalDrills > 0 ? round($accSum / $totalDrills, 1) : 0.0;

        $badges = [
            ['icon' => '🧮', 'title' => 'Soroban Explorer',       'desc' => 'Complete your first Abacus or Anzan drill',     'unlocked' => $totalDrills >= 1],
            ['icon' => '🎯', 'title' => 'Sharp Shooter (100%)',   'desc' => 'Achieve 100% accuracy in 3+ speed drills',     'unlocked' => $perfectCnt >= 3],
            ['icon' => '⚡', 'title' => 'Sub-Second Speedster',   'desc' => 'Solve Flash Anzan at 0.7s speed or faster',    'unlocked' => ($bestSpeed > 0 && $bestSpeed <= 0.7)],
            ['icon' => '🔥', 'title' => 'Grandmaster Calculator', 'desc' => 'Complete 15+ drills with 80%+ avg accuracy',   'unlocked' => ($totalDrills >= 15 && $avgAcc >= 80.0)],
        ];

        return [
            'total_drills' => $totalDrills,
            'avg_accuracy' => $avgAcc,
            'best_speed'   => $bestSpeed,
            'perfect_cnt'  => $perfectCnt,
            'badges'       => $badges,
            'attempts'     => $rows,
        ];
    }

    public function getBatchAnalyticsForRole(int $teacherIdFilter = 0, int $selectedCohortId = 0): array
    {
        $this->cleanupNonStudentAndOrphanedAttempts();
        $cohortsMap = [];
        if ($this->db->tableExists('lms_classroom_batches')) {
            $cBuilder = $this->db->table('lms_classroom_batches')->orderBy('id', 'DESC');
            if ($teacherIdFilter > 0) $cBuilder->where('teacher_user_id', $teacherIdFilter);
            foreach ($cBuilder->get()->getResultArray() as $c) {
                $cohortsMap[(int) $c['id']] = $c;
            }
        }

        $allowedStudentIds = [];
        if ($teacherIdFilter > 0) {
            $cIds = array_keys($cohortsMap);
            if (! empty($cIds) && $this->db->tableExists('lms_classroom_batch_students')) {
                foreach ($this->db->table('lms_classroom_batch_students')->whereIn('cohort_id', $cIds)->get()->getResultArray() as $cs) {
                    $allowedStudentIds[(int) $cs['student_user_id']] = (int) $cs['cohort_id'];
                }
            }
            if (empty($allowedStudentIds)) {
                return ['cohorts' => $cohortsMap, 'leaderboard' => [], 'attempts' => []];
            }
        }

        $users = [];
        if ($this->db->tableExists('users')) {
            foreach ($this->db->table('users')->get()->getResultArray() as $u) {
                $users[(int) $u['id']] = $u['full_name'] ?: ($u['name'] ?: 'Student');
            }
        }

        $aBuilder = $this->db->table($this->table)->orderBy('id', 'DESC')->limit(150);
        if ($teacherIdFilter > 0) $aBuilder->whereIn('student_user_id', array_keys($allowedStudentIds));
        if ($selectedCohortId > 0) $aBuilder->where('cohort_id', $selectedCohortId);

        $attempts    = $aBuilder->get()->getResultArray();
        $leaderboard = [];
        foreach ($attempts as &$at) {
            $sid = (int) $at['student_user_id'];
            $cid = (int) ($at['cohort_id'] ?: ($allowedStudentIds[$sid] ?? 0));
            $at['student_name'] = $users[$sid] ?? 'Student';
            $at['batch_title']  = isset($cohortsMap[$cid]) ? $cohortsMap[$cid]['cohort_title'] : 'Enrolled Batch';

            if (! isset($leaderboard[$sid])) {
                $leaderboard[$sid] = ['student_id' => $sid, 'student_name' => $at['student_name'], 'batch_title' => $at['batch_title'], 'drills_count' => 0, 'acc_sum' => 0.0, 'best_speed' => 99.0];
            }
            $leaderboard[$sid]['drills_count']++;
            $leaderboard[$sid]['acc_sum'] += (float) $at['accuracy_pct'];
            $spd = (float) $at['speed_seconds'];
            if ((float) $at['accuracy_pct'] >= 80.0 && $spd > 0 && $spd < $leaderboard[$sid]['best_speed']) {
                $leaderboard[$sid]['best_speed'] = $spd;
            }
        }
        unset($at);

        foreach ($leaderboard as &$lb) {
            $lb['avg_accuracy'] = $lb['drills_count'] > 0 ? round($lb['acc_sum'] / $lb['drills_count'], 1) : 0.0;
            if ($lb['best_speed'] >= 99.0) $lb['best_speed'] = 0.0;
        }
        unset($lb);

        return ['cohorts' => $cohortsMap, 'leaderboard' => array_values($leaderboard), 'attempts' => $attempts];
    }
}

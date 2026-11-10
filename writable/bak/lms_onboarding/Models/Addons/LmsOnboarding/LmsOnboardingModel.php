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
                        `meeting_link` VARCHAR(255) NOT NULL DEFAULT 'https://meet.google.com/new',
                        `scheduled_utc` DATETIME NULL,
                        `teacher_eval_note` VARCHAR(255) NOT NULL DEFAULT '',
                        `recommended_node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `admin_notes` VARCHAR(255) NOT NULL DEFAULT '',
                        `status` VARCHAR(30) NOT NULL DEFAULT 'new_lead',
                        `converted_parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `converted_student_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        KEY `idx_lead_status` (`status`, `course_node_id`),
                        KEY `idx_lead_teacher` (`assigned_teacher_id`, `status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } else {
                $lCols = $this->db->getFieldNames('lms_demo_leads');
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
                    'meeting_link'         => "ADD COLUMN `meeting_link` VARCHAR(255) NOT NULL DEFAULT 'https://meet.google.com/new'",
                    'scheduled_utc'        => "ADD COLUMN `scheduled_utc` DATETIME NULL",
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
                        `demo_date` DATE NULL,
                        `start_time_ist` VARCHAR(10) NOT NULL DEFAULT '17:00',
                        `scheduled_utc` DATETIME NULL,
                        `duration_min` SMALLINT UNSIGNED NOT NULL DEFAULT 30,
                        `max_seats` SMALLINT UNSIGNED NOT NULL DEFAULT 10,
                        `meeting_link` VARCHAR(255) NOT NULL DEFAULT 'https://meet.google.com/new',
                        `status` VARCHAR(25) NOT NULL DEFAULT 'scheduled',
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        KEY `idx_demo_teacher` (`teacher_user_id`, `status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } else {
                $bCols = $this->db->getFieldNames('lms_demo_batches');
                $bAlters = [
                    'batch_title'     => "ADD COLUMN `batch_title` VARCHAR(160) NOT NULL DEFAULT 'Live Demo Slot'",
                    'course_node_id'  => "ADD COLUMN `course_node_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'teacher_user_id' => "ADD COLUMN `teacher_user_id` INT UNSIGNED NOT NULL DEFAULT 0",
                    'demo_date'       => "ADD COLUMN `demo_date` DATE NULL",
                    'start_time_ist'  => "ADD COLUMN `start_time_ist` VARCHAR(10) NOT NULL DEFAULT '17:00'",
                    'scheduled_utc'   => "ADD COLUMN `scheduled_utc` DATETIME NULL",
                    'duration_min'    => "ADD COLUMN `duration_min` SMALLINT UNSIGNED NOT NULL DEFAULT 30",
                    'max_seats'       => "ADD COLUMN `max_seats` SMALLINT UNSIGNED NOT NULL DEFAULT 10",
                    'meeting_link'    => "ADD COLUMN `meeting_link` VARCHAR(255) NOT NULL DEFAULT 'https://meet.google.com/new'",
                    'status'          => "ADD COLUMN `status` VARCHAR(25) NOT NULL DEFAULT 'scheduled'",
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

            $this->mirrorAssets(false);
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
        $out = ['site_title' => 'Portal', 'active_theme_mode' => 'dark'];
        try {
            if ($this->db->tableExists('settings')) {
                foreach ($this->db->table('settings')->get()->getResultArray() as $r) {
                    $out[$r['setting_key']] = $r['setting_value'];
                }
            }
        } catch (Throwable $e) {
        }
        return $out;
    }

    public function getServicingCountries(): array
    {
        return [
            'IN' => ['code' => 'IN', 'name' => 'India',          'flag' => '🇮🇳', 'currency_code' => 'INR', 'default_tz' => 'Asia/Kolkata'],
            'AE' => ['code' => 'AE', 'name' => 'UAE',            'flag' => '🇦🇪', 'currency_code' => 'AED', 'default_tz' => 'Asia/Dubai'],
            'US' => ['code' => 'US', 'name' => 'United States',  'flag' => '🇺🇸', 'currency_code' => 'USD', 'default_tz' => 'America/New_York'],
            'GB' => ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => '🇬🇧', 'currency_code' => 'GBP', 'default_tz' => 'Europe/London'],
        ];
    }

    public function getEnrollableCoursesMap(): array
    {
        $map = [];
        if (! $this->db->tableExists('lms_hierarchy_nodes')) {
            return $map;
        }
        $all = [];
        foreach ($this->db->table('lms_hierarchy_nodes')->where('status', 'active')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray() as $n) {
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
                'id'              => $nid,
                'title'           => $nRow['title'],
                'breadcrumb_path' => implode(' › ', $parts),
                'is_leaf_node'    => (int) ($nRow['is_leaf_node'] ?? 1),
            ];
        }
        return $map;
    }

    public function getActiveCohortsMap(): array
    {
        $cohorts = [];
        if ($this->db->tableExists('lms_classroom_batches')) {
            foreach ($this->db->table('lms_classroom_batches')->where('status', 'active')->orderBy('id', 'DESC')->get()->getResultArray() as $c) {
                $cohorts[(int) $c['id']] = $c;
            }
        }
        return $cohorts;
    }

    public function getTeachersMap(): array
    {
        $teachers = [];
        if (! $this->db->tableExists('users')) {
            return $teachers;
        }
        foreach ($this->db->table('users')->where('status', 'active')->orderBy('id', 'ASC')->get()->getResultArray() as $u) {
            $id    = (int) $u['id'];
            $rId   = (int) ($u['role_id'] ?? 4);
            $rSlug = strtolower(trim((string) ($u['role_slug'] ?? 'student')));
            if ($rId === 3 || $rSlug === 'teacher') {
                $teachers[$id] = [
                    'id'           => $id,
                    'display_name' => $u['full_name'] ?: ($u['name'] ?: ($u['email'] ?: 'Teacher')),
                    'email'        => $u['email'] ?? '',
                ];
            }
        }
        return $teachers;
    }

    /**
     * Reliably resolves the currently logged-in user from Session + `users` table
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
                $uRow['display_name'] = $uRow['full_name'] ?: ($uRow['name'] ?: ($uRow['username'] ?: 'Parent'));
                return $uRow;
            }
        }
        if ($uid > 0) {
            return [
                'id'           => $uid,
                'full_name'    => (string) (session()->get('full_name') ?: (session()->get('name') ?: 'Parent')),
                'display_name' => (string) (session()->get('full_name') ?: (session()->get('name') ?: 'Parent')),
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
                'full_name' => $k['full_name'] ?: ($k['name'] ?: 'Child'),
                'age'       => $metaAge[$kidId] ?? 8,
            ];
        }
        return $kids;
    }

    public function getAppliedDemosForUser(int $uid, string $email = '', array $childIds = []): array
    {
        if ($uid <= 0 || ! $this->db->tableExists('lms_demo_leads')) {
            return [];
        }
        $courses  = $this->getEnrollableCoursesMap();
        $teachers = $this->getTeachersMap();
        $lCols    = $this->db->getFieldNames('lms_demo_leads');

        $qb = $this->db->table('lms_demo_leads')->orderBy('id', 'DESC');
        $qb->groupStart();
        if (in_array('converted_parent_id', $lCols, true)) {
            $qb->orWhere('converted_parent_id', $uid);
        }
        if (in_array('converted_student_id', $lCols, true)) {
            $qb->orWhere('converted_student_id', $uid);
            if (! empty($childIds)) {
                $qb->orWhereIn('converted_student_id', array_map('intval', $childIds));
            }
        }
        if ($email !== '' && in_array('parent_email', $lCols, true)) {
            $qb->orWhere('parent_email', strtolower(trim($email)));
        }
        $qb->groupEnd();

        $rows = $qb->get()->getResultArray();
        foreach ($rows as &$r) {
            $cid = (int) ($r['course_node_id'] ?? 0);
            $tid = (int) ($r['assigned_teacher_id'] ?? 0);
            $r['course_title'] = $courses[$cid]['breadcrumb_path'] ?? ($courses[$cid]['title'] ?? 'Selected Course');
            $r['teacher_name'] = ($tid > 0 && isset($teachers[$tid])) ? $teachers[$tid]['display_name'] : '';
        }
        unset($r);
        return $rows;
    }

    public function createNewDemoLead(array $post): array
    {
        $this->ensureSchema();
        $db          = $this->db;
        $uCols       = array_flip($db->getFieldNames('users'));
        $now         = gmdate('Y-m-d H:i:s');
        $courseId    = (int) ($post['course_node_id'] ?? 0);
        $countryCode = strtoupper(trim((string) ($post['country_code'] ?? (session()->get('user_country') ?: 'IN')))) ?: 'IN';
        $prefDate    = date('Y-m-d', strtotime('+1 day'));
        $prefSlot    = trim((string) ($post['preferred_time_slot'] ?? '05:00 PM - 06:00 PM')) ?: '05:00 PM - 06:00 PM';
        $tz          = trim((string) ($post['timezone'] ?? 'Asia/Kolkata')) ?: 'Asia/Kolkata';

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

            $existingParent = $db->table('users')->where('email', $parentEmail)->get()->getRowArray();
            if ($existingParent) {
                $parentId = (int) $existingParent['id'];
            } else {
                $hashedPass = password_hash($parentPassword !== '' ? $parentPassword : 'Parent@123', PASSWORD_DEFAULT);
                $pRow = array_intersect_key([
                    'role_id'       => 4,
                    'role_slug'     => 'parent',
                    'full_name'     => $parentName,
                    'name'          => $parentName,
                    'username'      => explode('@', $parentEmail)[0],
                    'email'         => $parentEmail,
                    'password'      => $hashedPass,
                    'password_hash' => $hashedPass,
                    'status'        => 'active',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ], $uCols);
                $db->table('users')->insert($pRow);
                $parentId = (int) $db->insertID();
            }

            $parentPerms = ['dashboard.view', 'core_lms_auth.view', 'lms_hierarchy.view', 'lms_assessments.take_exam', 'lms_communication.chat', 'lms_communication.feedback', 'lms_timetable.view', 'lms_finance.view'];
            session()->set([
                'isLoggedIn'          => true,
                'user_id'             => $parentId,
                'id'                  => $parentId,
                'role_id'             => 4,
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

        $countries   = $this->getServicingCountries();
        $currCode    = $countries[$countryCode]['currency_code'] ?? 'INR';
        $actualCols  = array_flip($db->getFieldNames($this->table));
        $bookedNames = [];

        foreach ($childrenRows as $idx => $ch) {
            $selectedStuId = (int) ($ch['existing_student_id'] ?? 0);
            $kidCourseId   = (int) ($ch['course_id'] ?? $courseId);
            $kidName       = '';
            $kidAge        = max(3, min(22, (int) ($ch['age'] ?? 8)));
            $finalStuId    = 0;

            if ($selectedStuId > 0 && isset($existingKids[$selectedStuId])) {
                $finalStuId = $selectedStuId;
                $kidName    = $existingKids[$selectedStuId]['full_name'];
                $kidAge     = $existingKids[$selectedStuId]['age'];
            } else {
                $kidName = trim((string) ($ch['name'] ?? ''));
                if ($kidName === '') {
                    continue;
                }
                $slugKid    = preg_replace('/[^a-z0-9]+/', '', strtolower($kidName)) ?: 'learner';
                $childEmail = $slugKid . '.' . $parentId . '.' . rand(100, 999) . '@student.portal';
                $kidPass    = password_hash('Kid@123', PASSWORD_DEFAULT);

                $sRow = array_intersect_key([
                    'role_id'        => 4,
                    'role_slug'      => 'student',
                    'parent_user_id' => $parentId,
                    'full_name'      => $kidName,
                    'name'           => $kidName,
                    'username'       => $slugKid . $parentId . $idx,
                    'email'          => $childEmail,
                    'password'       => $kidPass,
                    'password_hash'  => $kidPass,
                    'status'         => 'active',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ], $uCols);
                $db->table('users')->insert($sRow);
                $finalStuId = (int) $db->insertID();

                if ($db->tableExists('cms_user_meta')) {
                    $db->table('cms_user_meta')->insert([
                        'user_id'       => $finalStuId,
                        'leaf_node_id'  => $kidCourseId,
                        'child_age'     => $kidAge,
                        'country_code'  => $countryCode,
                        'currency_code' => $currCode,
                        'timezone'      => $tz,
                        'avatar_color'  => '#10b981',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
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
                'preferred_date'       => $prefDate,
                'preferred_time_slot'  => $prefSlot,
                'status'               => 'new_lead',
                'converted_parent_id'  => $parentId,
                'converted_student_id' => $finalStuId,
                'created_at'           => $now,
                'updated_at'           => $now,
            ];
            $db->table($this->table)->insert(array_intersect_key($leadRow, $actualCols));
            $bookedNames[] = $kidName;
        }

        if (empty($bookedNames)) {
            return ['ok' => false, 'message' => 'Please select an existing child or enter a new child name.'];
        }

        return [
            'ok'      => true,
            'message' => '🎉 Free Demo Class application submitted for ' . implode(', ', $bookedNames) . ' (Preferred Timing: ' . $prefSlot . ')! Our team will schedule your demo and update your status right here.',
        ];
    }

    public function assignLeadToDemoSlot(array $post): array
    {
        $this->ensureSchema();
        $leadId      = (int) ($post['lead_id'] ?? 0);
        $demoBatchId = (int) ($post['demo_batch_id'] ?? 0);
        $teacherId   = (int) ($post['assigned_teacher_id'] ?? 0);
        $demoDate    = trim((string) ($post['demo_date'] ?? date('Y-m-d', strtotime('+1 day'))));
        $meetingLink = trim((string) ($post['meeting_link'] ?? 'https://meet.google.com/new'));

        $lead = $this->find($leadId);
        if (! $lead) {
            return ['ok' => false, 'message' => 'Demo Lead not found.'];
        }

        $scheduledUtc = gmdate('Y-m-d H:i:s', strtotime($demoDate . ' 11:30:00'));

        if ($demoBatchId > 0) {
            $batch = $this->db->table($this->batchesTable)->where('id', $demoBatchId)->get()->getRowArray();
            if ($batch) {
                $teacherId    = (int) ($batch['teacher_user_id'] ?: $teacherId);
                $meetingLink  = $batch['meeting_link'] ?: $meetingLink;
                $scheduledUtc = $batch['scheduled_utc'] ?: $scheduledUtc;
                $demoDate     = $batch['demo_date'] ?: $demoDate;
            }
        }

        if ($teacherId <= 0) {
            return ['ok' => false, 'message' => 'Select a Teacher or Demo Slot.'];
        }

        $now = gmdate('Y-m-d H:i:s');
        $this->update($leadId, [
            'demo_batch_id'       => $demoBatchId,
            'assigned_teacher_id' => $teacherId,
            'preferred_date'      => $demoDate,
            'meeting_link'        => $meetingLink,
            'scheduled_utc'       => $scheduledUtc,
            'status'              => 'demo_scheduled',
            'updated_at'          => $now,
        ]);

        $stuId = (int) ($lead['converted_student_id'] ?? 0);
        if ($stuId > 0 && $this->db->tableExists('lms_class_sessions')) {
            $endUtc = gmdate('Y-m-d H:i:s', strtotime($scheduledUtc . ' +30 minutes'));
            $this->db->table('lms_class_sessions')->insert([
                'cohort_id'                 => 0,
                'node_id'                   => (int) ($lead['course_node_id'] ?: 1),
                'session_title'             => '🎯 Free Live Demo — ' . $lead['child_name'],
                'schedule_mode'             => 'makeup_session',
                'scheduled_start_utc'       => $scheduledUtc,
                'scheduled_end_utc'         => $endUtc,
                'assigned_teacher_id'       => $teacherId,
                'original_teacher_id'       => $teacherId,
                'teacher_attendance_status' => 'Pending',
                'meeting_link'              => $meetingLink,
                'status'                    => 'scheduled',
                'created_at'                => $now,
                'updated_at'                => $now,
            ]);
            $sessId = (int) $this->db->insertID();
            if ($sessId > 0 && $this->db->tableExists('lms_session_student_attendance')) {
                $this->db->table('lms_session_student_attendance')->insert([
                    'session_id'        => $sessId,
                    'student_user_id'   => $stuId,
                    'is_guest_makeup'   => 1,
                    'guest_reason'      => 'Scheduled Demo Class',
                    'attendance_status' => 'Present',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
        }

        return ['ok' => true, 'message' => 'Demo Class scheduled for ' . $lead['child_name'] . ' on ' . $demoDate . '!'];
    }

    public function convertDemoLeadToEnrolledStudent(int $leadId, int $overrideCourseId = 0, int $targetCohortId = 0): array
    {
        $this->ensureSchema();
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

        $courses     = $this->getEnrollableCoursesMap();
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
                    'role_id'       => 4,
                    'role_slug'     => 'parent',
                    'full_name'     => $lead['parent_name'] ?: 'Parent',
                    'name'          => $lead['parent_name'] ?: 'Parent',
                    'email'         => $pEmail,
                    'password_hash' => password_hash('Parent@123', PASSWORD_DEFAULT),
                    'status'        => 'active',
                    'created_at'    => $now,
                    'updated_at'    => $now,
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
                'name'           => $lead['child_name'] ?: 'Student',
                'email'          => $slugKid . '.' . $parentId . '.' . rand(100, 999) . '@student.portal',
                'password_hash'  => password_hash('Kid@123', PASSWORD_DEFAULT),
                'status'         => 'active',
                'created_at'     => $now,
                'updated_at'     => $now,
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
            $pRow = $this->db->table('lms_hierarchy_pricing')->where('node_id', $courseId)->where('country_code', $countryCode)->get()->getRowArray();
            if ($pRow && (float) $pRow['monthly_fee'] > 0) {
                $monthlyFee = (float) $pRow['monthly_fee'];
                $currCode   = $pRow['currency_code'] ?: $currCode;
            }
        }

        $batchStart = ($targetCohortId > 0 && ! empty($cohorts[$targetCohortId]['start_date'])) ? $cohorts[$targetCohortId]['start_date'] : date('Y-m-d');
        $nextDueYmd = date('Y-m-d', strtotime($batchStart . ' +30 days'));

        if ($this->db->tableExists('lms_hierarchy_enrollments') && $courseId > 0) {
            $exEnr = $this->db->table('lms_hierarchy_enrollments')->where('student_user_id', $studentId)->where('leaf_node_id', $courseId)->get()->getRowArray();
            if (! $exEnr) {
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

        $this->update($leadId, [
            'course_node_id'       => $courseId,
            'status'               => 'converted',
            'converted_parent_id'  => $parentId,
            'converted_student_id' => $studentId,
            'updated_at'           => $now,
        ]);

        return [
            'ok'      => true,
            'message' => '🚀 Converted "' . $lead['child_name'] . '" into "' . $courseTitle . '"' . $batchMsg . '!',
        ];
    }

    public function saveOrUpdateDemoBatch(array $post): array
    {
        $this->ensureSchema();
        $title       = trim((string) ($post['batch_title'] ?? ''));
        $courseId    = (int) ($post['course_node_id'] ?? 0);
        $teacherId   = (int) ($post['teacher_user_id'] ?? 0);
        $demoDate    = trim((string) ($post['demo_date'] ?? date('Y-m-d', strtotime('+1 day'))));
        $timeIst     = trim((string) ($post['start_time_ist'] ?? '17:00')) ?: '17:00';
        $durationMin = max(15, min(120, (int) ($post['duration_min'] ?? 30)));
        $meetingLink = trim((string) ($post['meeting_link'] ?? 'https://meet.google.com/new'));

        if ($title === '' || $teacherId <= 0) {
            return ['ok' => false, 'message' => 'Enter a Demo Slot Title and select a Teacher.'];
        }

        $scheduledUtc = gmdate('Y-m-d H:i:s');
        try {
            $dtIst = new DateTime($demoDate . ' ' . $timeIst . ':00', new DateTimeZone('Asia/Kolkata'));
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
            'demo_date'       => $demoDate,
            'start_time_ist'  => $timeIst,
            'scheduled_utc'   => $scheduledUtc,
            'duration_min'    => $durationMin,
            'max_seats'       => 10,
            'meeting_link'    => $meetingLink,
            'status'          => 'scheduled',
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $actualCols);

        $this->db->table($this->batchesTable)->insert($row);
        return ['ok' => true, 'message' => 'Demo Slot "' . $title . '" created!'];
    }

    public function saveTeacherDemoEvaluation(int $teacherId, array $post): array
    {
        $this->ensureSchema();
        $leadId    = (int) ($post['lead_id'] ?? 0);
        $status    = in_array($post['status'] ?? '', ['demo_attended', 'reschedule_needed'], true) ? $post['status'] : 'demo_attended';
        $recNodeId = (int) ($post['recommended_node_id'] ?? 0);
        $evalNote  = trim((string) ($post['teacher_eval_note'] ?? ''));

        $lead = $this->find($leadId);
        if (! $lead) {
            return ['ok' => false, 'message' => 'Demo record not found.'];
        }

        $upd = [
            'status'            => $status,
            'teacher_eval_note' => $evalNote,
            'updated_at'        => gmdate('Y-m-d H:i:s'),
        ];
        if ($recNodeId > 0) {
            $upd['recommended_node_id'] = $recNodeId;
        }
        $this->update($leadId, $upd);
        return ['ok' => true, 'message' => 'Evaluation saved for ' . $lead['child_name'] . '!'];
    }

    public function getEnrichedLeads(int $teacherIdFilter = 0): array
    {
        $this->ensureSchema();
        $courses  = $this->getEnrollableCoursesMap();
        $teachers = $this->getTeachersMap();
        $batches  = [];
        foreach ($this->db->table($this->batchesTable)->get()->getResultArray() as $b) {
            $batches[(int) $b['id']] = $b;
        }

        $builder = $this->db->table($this->table)->orderBy('id', 'DESC');
        if ($teacherIdFilter > 0) {
            $builder->where('assigned_teacher_id', $teacherIdFilter);
        }

        $rows = $builder->limit(150)->get()->getResultArray();
        foreach ($rows as &$r) {
            $cid = (int) ($r['course_node_id'] ?? 0);
            $tid = (int) ($r['assigned_teacher_id'] ?? 0);
            $bid = (int) ($r['demo_batch_id'] ?? 0);
            $r['course_title']     = $courses[$cid]['breadcrumb_path'] ?? 'Course Demo';
            $r['teacher_name']     = $tid > 0 && isset($teachers[$tid]) ? $teachers[$tid]['display_name'] : 'Unassigned';
            $r['demo_batch_title'] = $bid > 0 && isset($batches[$bid]) ? ($batches[$bid]['batch_title'] ?? 'Live Demo') : 'Live Demo';
        }
        unset($r);
        return $rows;
    }

    public function getEnrichedDemoBatches(int $teacherIdFilter = 0): array
    {
        $this->ensureSchema();
        $courses  = $this->getEnrollableCoursesMap();
        $teachers = $this->getTeachersMap();
        $bCols    = $this->db->getFieldNames($this->batchesTable);

        $builder = $this->db->table($this->batchesTable);
        if (in_array('demo_date', $bCols, true)) {
            $builder->orderBy('demo_date', 'DESC');
        }
        $builder->orderBy('id', 'DESC');

        if ($teacherIdFilter > 0 && in_array('teacher_user_id', $bCols, true)) {
            $builder->where('teacher_user_id', $teacherIdFilter);
        }

        $rows = $builder->get()->getResultArray();
        foreach ($rows as &$r) {
            $cid = (int) ($r['course_node_id'] ?? 0);
            $tid = (int) ($r['teacher_user_id'] ?? 0);
            $r['batch_title']    = $r['batch_title'] ?? ($r['title'] ?? 'Demo Slot');
            $r['demo_date']      = ! empty($r['demo_date']) ? $r['demo_date'] : substr((string) ($r['scheduled_utc'] ?? date('Y-m-d')), 0, 10);
            $r['start_time_ist'] = $r['start_time_ist'] ?? '17:00';
            $r['meeting_link']   = $r['meeting_link'] ?? 'https://meet.google.com/new';
            $r['course_title']   = $courses[$cid]['title'] ?? 'All Courses';
            $r['teacher_name']   = $tid > 0 && isset($teachers[$tid]) ? $teachers[$tid]['display_name'] : 'Unassigned';
        }
        unset($r);
        return $rows;
    }
}

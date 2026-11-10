<?php

namespace App\Controllers\Dashboard;

use App\Controllers\BaseController;
use App\Models\Settings\SettingModel;
use DateTime;
use DateTimeZone;
use Throwable;

class DashboardController extends BaseController
{
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->settingModel = new SettingModel();
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $db       = \Config\Database::connect();
        $settings = $this->settingModel->getSystemSettings();

        $baseRole = strtolower(trim((string) (session()->get('role_slug') ?: 'student')));
        if ((int) session()->get('role_id') === 1) {
            $baseRole = 'super_admin';
        }

        $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');
        $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
        $isChildSandbox = ($portalViewMode === 'student' && $activeStuId > 0 && $baseRole !== 'super_admin');

        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : ($isChildSandbox ? 'student' : $baseRole);

        $uid    = (int) (session()->get('user_id') ?: (session()->get('id') ?: 1));
        $userTz = (string) (session()->get('user_timezone') ?: 'Asia/Kolkata');

        // Load RBAC Workspace Configuration (0ms Static Cache)
        $wsWidgets     = ['kpi_overview', 'quick_modules', 'parent_children', 'student_arena', 'teacher_schedule', 'mod_flagged_chat'];
        $compiledNav   = [];
        $rbacCacheFile = WRITEPATH . 'addons/rbac_permissions_cache.json';
        if (is_file($rbacCacheFile)) {
            $rCache = json_decode((string) @file_get_contents($rbacCacheFile), true);
            if (! empty($rCache['roles'][$effectiveRole]['workspace']['dashboard_widgets'])) {
                $wsWidgets = $rCache['roles'][$effectiveRole]['workspace']['dashboard_widgets'];
            }
            if (! empty($rCache['roles'][$effectiveRole]['compiled_sidebar'])) {
                $compiledNav = $rCache['roles'][$effectiveRole]['compiled_sidebar'];
            }
        }

        $data = [
            'pageTitle'      => 'Dashboard',
            'activeMenu'     => 'dashboard',
            'settings'       => $settings,
            'user'           => session()->get(),
            'baseRole'       => $baseRole,
            'effectiveRole'  => $effectiveRole,
            'isChildSandbox' => $isChildSandbox,
            'activeStuId'    => $activeStuId,
            'userTz'         => $userTz,
            'wsWidgets'      => $wsWidgets,
            'compiledNav'    => $compiledNav,
        ];

        if ($effectiveRole === 'student') {
            $targetStuId = $activeStuId > 0 ? $activeStuId : $uid;
            $data['studentHub'] = $this->buildStudentDashboardData($db, $targetStuId, $userTz);
        } elseif ($effectiveRole === 'parent') {
            $data['parentHub'] = $this->buildParentDashboardData($db, $uid);
        } elseif ($effectiveRole === 'teacher') {
            $data['teacherHub'] = $this->buildTeacherDashboardData($db, $uid, $userTz);
        } else {
            $data['adminHub'] = $this->buildAdminDashboardData($db);
        }

        return view('dashboard/index_view', $data);
    }

    private function formatUtcLocal(string $utcStr, string $tz): string
    {
        try {
            $dt = new DateTime($utcStr, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone($tz ?: 'Asia/Kolkata'));
            return $dt->format('D, d M — h:i A');
        } catch (Throwable $e) {
            return $utcStr;
        }
    }

    /**
     * 1. STUDENT / CHILD LEARNING HUB DASHBOARD DATA
     */
    private function buildStudentDashboardData($db, int $studentId, string $userTz): array
    {
        $studentName   = (string) (session()->get('full_name') ?: 'Learner');
        $courseTitle   = 'Foundation Course';
        $attendancePct = 100.0;
        $avgExamScore  = 0.0;
        $certsCount    = 0;
        $examsDoneCnt  = 0;
        $abacusDrills  = 0;
        $nextClass     = null;

        try {
            if ($db->tableExists('users')) {
                $uRow = $db->table('users')->select('full_name')->where('id', $studentId)->get()->getRowArray();
                if ($uRow) {
                    $studentName = $uRow['full_name'] ?: ($uRow['name'] ?: $studentName);
                }
            }

            $nodesMap = [];
            if ($db->tableExists('lms_hierarchy_nodes')) {
                foreach ($db->table('lms_hierarchy_nodes')->select('id, title')->get()->getResultArray() as $n) {
                    $nodesMap[(int) $n['id']] = $n['title'];
                }
            }

            $enrolledLeafId = 0;
            if ($db->tableExists('lms_hierarchy_enrollments')) {
                $enr = $db->table('lms_hierarchy_enrollments')
                    ->where('student_user_id', $studentId)
                    ->where('status', 'active')
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getRowArray();
                if ($enr) {
                    $enrolledLeafId = (int) $enr['leaf_node_id'];
                    $courseTitle    = $nodesMap[$enrolledLeafId] ?? $courseTitle;
                    $attendancePct  = (float) ($enr['attendance_pct'] ?? 100.0);
                    $avgExamScore   = (float) ($enr['test_score_avg'] ?? 0.0);
                    $certsCount     = (int) ($enr['certificates_count'] ?? 0);
                }
            }

            if ($db->tableExists('lms_assessment_attempts')) {
                $examsDoneCnt = (int) $db->table('lms_assessment_attempts')
                    ->where('student_user_id', $studentId)
                    ->where('total_questions >', 0)
                    ->countAllResults();
            }

            if ($db->tableExists('lms_certificates')) {
                $certsCount = max($certsCount, (int) $db->table('lms_certificates')->where('student_user_id', $studentId)->countAllResults());
            }

            if ($db->tableExists('lms_abacus_attempts')) {
                $abacusDrills = (int) $db->table('lms_abacus_attempts')->where('student_user_id', $studentId)->countAllResults();
            }

            // Find next upcoming or live class for this student
            if ($db->tableExists('lms_class_sessions')) {
                $cohortIds = [];
                if ($db->tableExists('lms_classroom_batch_students')) {
                    foreach ($db->table('lms_classroom_batch_students')->select('cohort_id')->where('student_user_id', $studentId)->where('status', 'active')->get()->getResultArray() as $cs) {
                        $cohortIds[] = (int) $cs['cohort_id'];
                    }
                }

                $sBuilder = $db->table('lms_class_sessions')
                    ->whereIn('status', ['live', 'scheduled'])
                    ->orderBy('status', 'ASC')
                    ->orderBy('scheduled_start_utc', 'ASC');

                if (! empty($cohortIds)) {
                    $sBuilder->whereIn('cohort_id', $cohortIds);
                } elseif ($enrolledLeafId > 0) {
                    $sBuilder->where('node_id', $enrolledLeafId);
                } else {
                    $sBuilder->where('id', -1);
                }

                $sessRow = $sBuilder->get(1)->getRowArray();
                if ($sessRow) {
                    $sessRow['local_time'] = $this->formatUtcLocal((string) $sessRow['scheduled_start_utc'], $userTz);
                    $nextClass             = $sessRow;
                }
            }
        } catch (Throwable $e) {
        }

        return [
            'student_name'   => $studentName,
            'course_title'   => $courseTitle,
            'attendance_pct' => $attendancePct,
            'avg_exam_score' => $avgExamScore,
            'exams_done_cnt' => $examsDoneCnt,
            'certs_count'    => $certsCount,
            'abacus_drills'  => $abacusDrills,
            'next_class'     => $nextClass,
        ];
    }

    /**
     * 2. PARENT FAMILY OVERVIEW DASHBOARD DATA
     */
    private function buildParentDashboardData($db, int $parentId): array
    {
        $children      = [];
        $activeCourses = 0;
        $unpaidCount   = 0;
        $nextDueStr    = 'All Clear';
        $totalCerts    = 0;

        try {
            $nodesMap = [];
            if ($db->tableExists('lms_hierarchy_nodes')) {
                foreach ($db->table('lms_hierarchy_nodes')->select('id, title')->get()->getResultArray() as $n) {
                    $nodesMap[(int) $n['id']] = $n['title'];
                }
            }

            $metaByUid = [];
            if ($db->tableExists('cms_user_meta')) {
                foreach ($db->table('cms_user_meta')->get()->getResultArray() as $m) {
                    $metaByUid[(int) $m['user_id']] = $m;
                }
            }

            $enrByStu = [];
            if ($db->tableExists('lms_hierarchy_enrollments')) {
                foreach ($db->table('lms_hierarchy_enrollments')->where('status', 'active')->get()->getResultArray() as $e) {
                    $enrByStu[(int) $e['student_user_id']] = $e;
                }
            }

            if ($db->tableExists('users')) {
                $kidRows = $db->table('users')
                    ->where('parent_user_id', $parentId)
                    ->where('status', 'active')
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();

                $childIds = [];
                foreach ($kidRows as $kr) {
                    $kidId      = (int) $kr['id'];
                    $childIds[] = $kidId;
                    $m          = $metaByUid[$kidId] ?? [];
                    $enr        = $enrByStu[$kidId] ?? null;
                    $leafId     = (int) ($enr['leaf_node_id'] ?? ($m['leaf_node_id'] ?? 0));
                    $cTitle     = ($leafId > 0 && isset($nodesMap[$leafId])) ? $nodesMap[$leafId] : ($m['grade_level'] ?? 'Enrolled Course');

                    if ($enr || $leafId > 0) {
                        $activeCourses++;
                    }
                    $certs = (int) ($enr['certificates_count'] ?? 0);
                    $totalCerts += $certs;

                    $children[] = [
                        'id'             => $kidId,
                        'full_name'      => $kr['full_name'] ?: ($kr['name'] ?: 'Child'),
                        'age'            => (int) ($m['child_age'] ?? 8),
                        'avatar_color'   => $m['avatar_color'] ?? '#10b981',
                        'course_title'   => $cTitle,
                        'attendance_pct' => (float) ($enr['attendance_pct'] ?? 100.0),
                        'test_score_avg' => (float) ($enr['test_score_avg'] ?? 0.0),
                        'certs_count'    => $certs,
                    ];
                }

                if (! empty($childIds)) {
                    if ($db->tableExists('lms_finance_invoices')) {
                        $unpaidCount = (int) $db->table('lms_finance_invoices')
                            ->whereIn('student_user_id', $childIds)
                            ->where('status !=', 'paid')
                            ->countAllResults();
                    }
                    if ($db->tableExists('lms_student_subscriptions')) {
                        $sub = $db->table('lms_student_subscriptions')
                            ->whereIn('student_user_id', $childIds)
                            ->where('status', 'active')
                            ->orderBy('due_date', 'ASC')
                            ->get(1)
                            ->getRowArray();
                        if ($sub && ! empty($sub['due_date'])) {
                            $nextDueStr = $sub['currency'] . ' ' . number_format((float) $sub['fee_amount'], 0) . ' (Due: ' . $sub['due_date'] . ')';
                        }
                    }
                }
            }
        } catch (Throwable $e) {
        }

        return [
            'children'       => $children,
            'children_count' => count($children),
            'active_courses' => $activeCourses,
            'unpaid_count'   => $unpaidCount,
            'next_due_str'   => $nextDueStr,
            'total_certs'    => $totalCerts,
        ];
    }

    /**
     * 3. TEACHER / INSTRUCTOR DASHBOARD DATA
     */
    private function buildTeacherDashboardData($db, int $teacherId, string $userTz): array
    {
        $batchesCount   = 0;
        $studentsCount  = 0;
        $upcomingCount  = 0;
        $feedbackCount  = 0;
        $avgRating      = 5.0;
        $assignedDemos  = 0;
        $upcomingList   = [];

        try {
            $cohortIds = [];
            if ($db->tableExists('lms_classroom_batches')) {
                $cohorts = $db->table('lms_classroom_batches')
                    ->where('teacher_user_id', $teacherId)
                    ->where('status', 'active')
                    ->get()
                    ->getResultArray();
                $batchesCount = count($cohorts);
                foreach ($cohorts as $c) {
                    $cohortIds[] = (int) $c['id'];
                }
            }

            if (! empty($cohortIds) && $db->tableExists('lms_classroom_batch_students')) {
                $studentsCount = (int) $db->table('lms_classroom_batch_students')
                    ->whereIn('cohort_id', $cohortIds)
                    ->where('status', 'active')
                    ->countAllResults();
            }

            if ($db->tableExists('lms_class_sessions')) {
                $sRows = $db->table('lms_class_sessions')
                    ->where('assigned_teacher_id', $teacherId)
                    ->whereIn('status', ['live', 'scheduled'])
                    ->orderBy('scheduled_start_utc', 'ASC')
                    ->limit(6)
                    ->get()
                    ->getResultArray();
                $upcomingCount = count($sRows);
                foreach ($sRows as $sr) {
                    $sr['local_time'] = $this->formatUtcLocal((string) $sr['scheduled_start_utc'], $userTz);
                    $upcomingList[]   = $sr;
                }
            }

            if ($db->tableExists('lms_teacher_feedback')) {
                $fbRows = $db->table('lms_teacher_feedback')
                    ->select('rating_stars')
                    ->where('teacher_user_id', $teacherId)
                    ->get()
                    ->getResultArray();
                $feedbackCount = count($fbRows);
                if ($feedbackCount > 0) {
                    $sum = 0;
                    foreach ($fbRows as $f) {
                        $sum += (int) ($f['rating_stars'] ?? 5);
                    }
                    $avgRating = round($sum / $feedbackCount, 1);
                }
            }

            if ($db->tableExists('lms_demo_leads')) {
                $assignedDemos = (int) $db->table('lms_demo_leads')
                    ->where('assigned_teacher_id', $teacherId)
                    ->whereIn('status', ['new_lead', 'demo_scheduled'])
                    ->countAllResults();
            }
        } catch (Throwable $e) {
        }

        return [
            'batches_count'  => $batchesCount,
            'students_count' => $studentsCount,
            'upcoming_count' => $upcomingCount,
            'feedback_count' => $feedbackCount,
            'avg_rating'     => $avgRating,
            'assigned_demos' => $assignedDemos,
            'upcoming_list'  => $upcomingList,
        ];
    }

    /**
     * 4. SUPER ADMIN & ADMIN EXECUTIVE DASHBOARD DATA
     */
    private function buildAdminDashboardData($db): array
    {
        $stats = [
            'students_count'      => 0,
            'parents_count'       => 0,
            'teachers_count'      => 0,
            'cohorts_count'       => 0,
            'courses_count'       => 0,
            'exams_count'         => 0,
            'revenue_inr'         => 0.0,
            'receipts_count'      => 0,
            'pending_approvals'   => 0,
            'new_demo_leads'      => 0,
            'pending_substitutes' => 0,
            'flagged_chats'       => 0,
        ];

        try {
            if ($db->tableExists('users')) {
                foreach ($db->table('users')->select('role_id, role_slug')->where('status', 'active')->get()->getResultArray() as $u) {
                    $rId   = (int) ($u['role_id'] ?? 4);
                    $rSlug = strtolower(trim((string) ($u['role_slug'] ?? 'student')));
                    if ($rId === 3 || $rSlug === 'teacher') {
                        $stats['teachers_count']++;
                    } elseif ($rSlug === 'parent') {
                        $stats['parents_count']++;
                    } elseif (! in_array($rId, [1, 2], true) && ! in_array($rSlug, ['super_admin', 'admin'], true)) {
                        $stats['students_count']++;
                    }
                }
            }

            if ($db->tableExists('lms_classroom_batches')) {
                $stats['cohorts_count'] = (int) $db->table('lms_classroom_batches')->where('status', 'active')->countAllResults();
            }
            if ($db->tableExists('lms_hierarchy_nodes')) {
                $stats['courses_count'] = (int) $db->table('lms_hierarchy_nodes')->where('is_leaf_node', 1)->where('status', 'active')->countAllResults();
            }
            if ($db->tableExists('lms_exams')) {
                $stats['exams_count'] = (int) $db->table('lms_exams')->where('status', 'active')->countAllResults();
            }
            if ($db->tableExists('payment_transactions')) {
                foreach ($db->table('payment_transactions')->select('amount, amount_inr_equiv, status')->get()->getResultArray() as $tx) {
                    $st = strtolower((string) ($tx['status'] ?? 'paid'));
                    if ($st === 'paid') {
                        $eq = (float) ($tx['amount_inr_equiv'] ?? 0);
                        $stats['revenue_inr'] += ($eq > 0) ? $eq : (float) ($tx['amount'] ?? 0);
                        $stats['receipts_count']++;
                    } elseif ($st === 'pending_verification') {
                        $stats['pending_approvals']++;
                    }
                }
            }
            if ($db->tableExists('lms_demo_leads')) {
                $stats['new_demo_leads'] = (int) $db->table('lms_demo_leads')->whereIn('status', ['new_lead', 'demo_scheduled', 'demo_attended'])->countAllResults();
            }
            if ($db->tableExists('lms_teacher_sub_requests')) {
                $stats['pending_substitutes'] = (int) $db->table('lms_teacher_sub_requests')->where('status', 'pending')->countAllResults();
            }
            if ($db->tableExists('lms_chat_messages')) {
                $mCols = $db->getFieldNames('lms_chat_messages');
                $b     = $db->table('lms_chat_messages')->where('is_flagged', 1);
                if (in_array('moderation_status', $mCols, true)) {
                    $b->where('moderation_status !=', 'approved');
                }
                $stats['flagged_chats'] = (int) $b->countAllResults();
            }
        } catch (Throwable $e) {
        }

        return $stats;
    }
}

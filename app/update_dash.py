#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os

def update_core_dashboard():
    files = {}

    # =========================================================================
    # 1. app/Controllers/Dashboard/DashboardController.php
    # =========================================================================
    files["app/Controllers/Dashboard/DashboardController.php"] = r'''<?php

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
                $uRow = $db->table('users')->select('full_name, name')->where('id', $studentId)->get()->getRowArray();
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
'''

    # =========================================================================
    # 2. app/Views/dashboard/index_view.php
    # =========================================================================
    files["app/Views/dashboard/index_view.php"] = r'''<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $role          = $effectiveRole ?? 'student';
    $userName      = $user['full_name'] ?? ($user['name'] ?? 'User');
    $widgets       = $wsWidgets ?? [];
?>
<style>
.dsh-wrap { width: 100%; max-width: 100%; box-sizing: border-box; }
.dsh-hero {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;
    padding: 1.25rem 1.5rem; border-radius: 14px;
    background: var(--bg-surface, #11131c); border: 1px solid var(--border-subtle, #232738);
    margin-bottom: 1.35rem; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}
.dsh-kpi-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 1.15rem; margin-bottom: 1.5rem;
}
.dsh-kpi-card {
    padding: 1.2rem 1.35rem; border-radius: 14px;
    background: var(--bg-surface, #11131c); border: 1px solid var(--border-subtle, #232738);
    display: flex; flex-direction: column; justify-content: space-between; gap: 0.55rem;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12); transition: transform 0.15s ease, border-color 0.15s ease;
}
.dsh-kpi-card:hover { transform: translateY(-2px); border-color: var(--accent-emerald, #10b981); }
.dsh-kpi-num { font-family: var(--font-mono, monospace); font-size: 1.65rem; font-weight: 900; margin: 0.15rem 0; }
.dsh-cards-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(295px, 1fr));
    gap: 1.2rem; margin-bottom: 1.5rem;
}
.dsh-action-card {
    padding: 1.3rem; border-radius: 14px;
    background: var(--bg-surface, #11131c); border: 1px solid var(--border-subtle, #232738);
    display: flex; flex-direction: column; justify-content: space-between; gap: 1rem;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12); transition: transform 0.15s ease, border-color 0.15s ease;
}
.dsh-action-card:hover { transform: translateY(-2px); border-color: var(--accent-emerald, #10b981); }
.dsh-child-top { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; }
.dsh-avatar {
    width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 1.15rem; color: #04130d; flex-shrink: 0;
}
.dsh-child-stats {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.45rem;
    padding: 0.65rem; border-radius: 10px; background: var(--bg-canvas, #090a0f);
    border: 1px solid var(--border-subtle, #232738); text-align: center;
}
.dsh-child-stats strong { display: block; font-size: 0.95rem; margin-top: 0.15rem; font-family: var(--font-mono, monospace); }
</style>

<div class="dsh-wrap">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- =====================================================================
         ROLE 1: STUDENT / CHILD LEARNING HUB DASHBOARD
         ===================================================================== -->
    <?php if ($role === 'student'): ?>
        <?php $sh = $studentHub ?? []; ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-emerald">🎓 STUDENT LEARNING HUB</span>
                <h2 style="margin:0.3rem 0 0.1rem;">Welcome back, <?= esc($sh['student_name'] ?? $userName) ?>!</h2>
                <p class="muted-cell" style="margin:0; font-size:0.86rem;">Enrolled Program: <strong class="text-emerald"><?= esc($sh['course_title'] ?? 'Foundation Course') ?></strong></p>
            </div>

            <?php if (! empty($isChildSandbox)): ?>
                <form action="<?= base_url('portal/switch-student') ?>" method="post" style="margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_id" value="0">
                    <button type="submit" class="btn-secondary btn-sm">🏠 Return to Parent Account</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- 4 Countable Student KPI Cards -->
        <div class="dsh-kpi-grid">
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">CLASS ATTENDANCE</span>
                <div class="dsh-kpi-num text-emerald"><?= number_format((float) ($sh['attendance_pct'] ?? 100), 1) ?>%</div>
                <span class="muted-cell" style="font-size:0.8rem;"><?= esc($sh['course_title'] ?? 'Active Batch') ?></span>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">NEXT LIVE CLASS</span>
                <?php if (! empty($sh['next_class'])): ?>
                    <div class="identity-name text-cyan" style="margin:0.25rem 0;"><?= esc($sh['next_class']['local_time']) ?></div>
                    <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-sm" style="align-self:flex-start; text-decoration:none;">🎥 Open Class Schedule →</a>
                <?php else: ?>
                    <div class="identity-name" style="margin:0.25rem 0;">View Weekly Schedule</div>
                    <a href="<?= base_url('timetable/portal') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">📅 Open Timetable →</a>
                <?php endif; ?>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">EXAM &amp; PRACTICE SCORE</span>
                <div class="dsh-kpi-num text-cyan"><?= number_format((float) ($sh['avg_exam_score'] ?? 0), 1) ?>%</div>
                <span class="muted-cell" style="font-size:0.8rem;"><?= (int) ($sh['exams_done_cnt'] ?? 0) ?> Practice Sets Completed</span>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">ABACUS DRILLS &amp; BADGES</span>
                <div class="dsh-kpi-num">🏆 <?= (int) ($sh['certs_count'] ?? 0) ?> Certs</div>
                <span class="muted-cell" style="font-size:0.8rem;"><?= (int) ($sh['abacus_drills'] ?? 0) ?> Speed Drills Logged</span>
            </div>
        </div>

        <!-- Student Interactive Learning Launchers -->
        <div class="dsh-cards-grid">
            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">🧮 3D SOROBAN LAB</span>
                    <h3 style="margin:0.55rem 0 0.25rem;">Interactive 3D Abacus</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Practice bead movements and solve target number challenges with audio feedback.</p>
                </div>
                <a href="<?= base_url('abacus/arena') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">🧮 Open 3D Soroban Lab →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">⚡ MENTAL MATH SPEED</span>
                    <h3 style="margin:0.55rem 0 0.25rem;">Flash Anzan &amp; Voice Drill</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Boost mental calculation speed with screen flash numbers and voice dictation.</p>
                </div>
                <a href="<?= base_url('abacus/anzan') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">⚡ Start Flash Anzan →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">🎯 EXAMS &amp; WORKSHEETS</span>
                    <h3 style="margin:0.55rem 0 0.25rem;">Practice Drills &amp; PDF Sheets</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Take timed practice sets, download PDF worksheets, and earn certificates.</p>
                </div>
                <a href="<?= base_url('assessments/portal') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">📄 Open Exams &amp; Worksheets →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">💬 CLASSROOM SUPPORT</span>
                    <h3 style="margin:0.55rem 0 0.25rem;">Ask My Batch Teacher</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Message your batch instructor 1-to-1 or share feedback on your classes.</p>
                </div>
                <a href="<?= base_url('chat/portal') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">💬 Open Teacher Chat →</a>
            </article>
        </div>

    <!-- =====================================================================
         ROLE 2: PARENT FAMILY OVERVIEW DASHBOARD
         ===================================================================== -->
    <?php elseif ($role === 'parent'): ?>
        <?php $ph = $parentHub ?? []; ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-cyan">👨‍👩‍👧‍👦 FAMILY LEARNING PORTAL</span>
                <h2 style="margin:0.3rem 0 0.1rem;">Welcome, <?= esc($userName) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.86rem;">Select any child below to enter their learning hub, or manage fee subscriptions and profile settings.</p>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('portal/family/add-child') ?>" class="btn-primary btn-sm" style="text-decoration:none;">➕ Add Child Profile</a>
                <a href="<?= base_url('portal/family/profile') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">⚙ Profile &amp; Password</a>
            </div>
        </div>

        <!-- 4 Countable Parent KPI Cards -->
        <div class="dsh-kpi-grid">
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">MY ENROLLED CHILDREN</span>
                <div class="dsh-kpi-num text-emerald"><?= (int) ($ph['children_count'] ?? 0) ?> Kids</div>
                <a href="<?= base_url('portal/family/add-child') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">+ Add Another Child →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">ACTIVE COURSES</span>
                <div class="dsh-kpi-num text-cyan"><?= (int) ($ph['active_courses'] ?? 0) ?> Programs</div>
                <a href="<?= base_url('courses/catalog') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">📖 View Enrolled Courses →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">FEE &amp; SUBSCRIPTIONS</span>
                <div class="identity-name text-emerald" style="margin:0.25rem 0;"><?= esc($ph['next_due_str'] ?? 'Active') ?></div>
                <a href="<?= base_url('finance/portal') ?>" class="btn-primary btn-sm" style="align-self:flex-start; text-decoration:none;">💳 Open Fee &amp; Receipts →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">EARNED CERTIFICATES</span>
                <div class="dsh-kpi-num">🏆 <?= (int) ($ph['total_certs'] ?? 0) ?></div>
                <a href="<?= base_url('assessments/portal/history') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">📄 View Score Reports →</a>
            </div>
        </div>

        <!-- Clean Children Profile Cards -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.9rem;">
            <h3 style="margin:0;">My Children's Learning Profiles</h3>
            <a href="<?= base_url('timetable/portal') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">📅 View Kids Class Schedule →</a>
        </div>

        <?php if (empty($ph['children'])): ?>
            <section class="console-card" style="text-align:center; padding:2.5rem;">
                <h3>No Child Profiles Linked Yet</h3>
                <p class="muted-cell">Click below to add your child's profile and start their learning journey.</p>
                <a href="<?= base_url('portal/family/add-child') ?>" class="btn-primary">➕ Add Your First Child Profile</a>
            </section>
        <?php else: ?>
            <div class="dsh-cards-grid">
                <?php foreach ($ph['children'] as $ch): ?>
                    <article class="dsh-action-card">
                        <div>
                            <div class="dsh-child-top">
                                <div style="display:flex; align-items:center; gap:0.75rem;">
                                    <span class="dsh-avatar" style="background: <?= esc($ch['avatar_color']) ?>;">
                                        <?= strtoupper(substr(trim((string) $ch['full_name']), 0, 1)) ?>
                                    </span>
                                    <div>
                                        <h3 style="margin:0; font-size:1.12rem;"><?= esc($ch['full_name']) ?></h3>
                                        <span class="muted-cell" style="font-size:0.8rem;">Age <?= (int) $ch['age'] ?> Years</span>
                                    </div>
                                </div>
                                <span class="role-badge text-emerald">Active</span>
                            </div>

                            <div style="margin:0.85rem 0; padding:0.65rem 0.85rem; border-radius:10px; background:var(--bg-canvas, #090a0f); border:1px solid var(--border-subtle, #232738);">
                                <span class="mono-label">ENROLLED COURSE</span>
                                <div class="identity-name text-emerald" style="margin-top:0.15rem;"><?= esc($ch['course_title']) ?></div>
                            </div>

                            <div class="dsh-child-stats">
                                <div>
                                    <span class="mono-label">ATTENDANCE</span>
                                    <strong class="text-emerald"><?= number_format((float) $ch['attendance_pct'], 1) ?>%</strong>
                                </div>
                                <div>
                                    <span class="mono-label">AVG SCORE</span>
                                    <strong class="text-cyan"><?= number_format((float) $ch['test_score_avg'], 1) ?>%</strong>
                                </div>
                                <div>
                                    <span class="mono-label">CERTS</span>
                                    <strong>🏆 <?= (int) $ch['certs_count'] ?></strong>
                                </div>
                            </div>
                        </div>

                        <form action="<?= base_url('portal/switch-student') ?>" method="post" style="margin:0;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="student_id" value="<?= (int) $ch['id'] ?>">
                            <button type="submit" class="btn-primary btn-full" style="justify-content:center;">
                                🎓 Enter <?= esc($ch['full_name']) ?>'s Learning Hub →
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- =====================================================================
         ROLE 3: TEACHER / INSTRUCTOR DASHBOARD
         ===================================================================== -->
    <?php elseif ($role === 'teacher'): ?>
        <?php $th = $teacherHub ?? []; ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-emerald">👩‍🏫 INSTRUCTOR WORKSPACE</span>
                <h2 style="margin:0.3rem 0 0.1rem;">Welcome, <?= esc($userName) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.86rem;">Manage your live classroom batches, student attendance, question banks, and 1-to-1 student chat.</p>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-sm" style="text-decoration:none;">🎥 Live Class Console →</a>
                <a href="<?= base_url('chat/portal') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">💬 Student Chat</a>
            </div>
        </div>

        <!-- 4 Countable Teacher KPI Cards -->
        <div class="dsh-kpi-grid">
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">MY ASSIGNED BATCHES</span>
                <div class="dsh-kpi-num text-emerald"><?= (int) ($th['batches_count'] ?? 0) ?> Batches</div>
                <a href="<?= base_url('timetable/portal?tab=rosters') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">🏫 View Batch Rosters →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">ENROLLED STUDENTS</span>
                <div class="dsh-kpi-num text-cyan"><?= (int) ($th['students_count'] ?? 0) ?> Students</div>
                <a href="<?= base_url('addons/abacus-pro-suite/analytics') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">📊 Batch Abacus Progress →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">UPCOMING LIVE CLASSES</span>
                <div class="dsh-kpi-num"><?= (int) ($th['upcoming_count'] ?? 0) ?> Classes</div>
                <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-sm" style="align-self:flex-start; text-decoration:none;">▶ Start Class Console →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">STUDENT FEEDBACK</span>
                <div class="dsh-kpi-num text-emerald">★ <?= number_format((float) ($th['avg_rating'] ?? 5.0), 1) ?></div>
                <a href="<?= base_url('chat/feedback') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">⭐ <?= (int) ($th['feedback_count'] ?? 0) ?> Reviews →</a>
            </div>
        </div>

        <div class="dsh-cards-grid">
            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">🎥 LIVE CLASSROOM</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Start / End Class &amp; Roll Call</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Confirm meeting link, share study PDFs, mark student attendance, or request a substitute teacher.</p>
                </div>
                <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">🎥 Open Live Class Console →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">🗃 QUESTION BANK &amp; EXAMS</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Course Questions &amp; Worksheets</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Add math questions to your assigned courses, build practice drills, and upload PDF worksheets.</p>
                </div>
                <a href="<?= base_url('addons/lms-assessments/question-bank') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">📝 Manage Question Bank →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">💬 STUDENT MESSENGER</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">1-to-1 Batch Student Chat</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Filter students by batch and solve their worksheet or abacus doubts in real time.</p>
                </div>
                <a href="<?= base_url('chat/portal') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">💬 Open Classroom Chat →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">🎯 DEMO EVALUATIONS &amp; PAYROLL</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Assigned Demos (<?= (int) ($th['assigned_demos'] ?? 0) ?>) &amp; Salary Slips</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Conduct assigned trial classes or view your monthly credited salary slips.</p>
                </div>
                <div class="action-btn-group">
                    <a href="<?= base_url('addons/lms-onboarding/my-demos') ?>" class="btn-primary btn-sm" style="flex:1; justify-content:center; text-decoration:none;">👩‍🏫 My Demos</a>
                    <a href="<?= base_url('finance/my-payouts') ?>" class="btn-secondary btn-sm" style="flex:1; justify-content:center; text-decoration:none;">💵 Salary Slips</a>
                </div>
            </article>
        </div>

    <!-- =====================================================================
         ROLE 4: SUPER ADMIN & ADMIN EXECUTIVE COMMAND DASHBOARD
         ===================================================================== -->
    <?php else: ?>
        <?php $ah = $adminHub ?? []; ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-emerald">🛡 ACADEMY COMMAND CENTER</span>
                <h2 style="margin:0.3rem 0 0.1rem;">Welcome, <?= esc($userName) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.86rem;">Real-time countable metrics across students, live batches, fee revenue, demo admissions, and safety moderation.</p>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('addons/lms-hierarchy/builder') ?>" class="btn-primary btn-sm" style="text-decoration:none;">➕ Create Course</a>
                <a href="<?= base_url('addons/lms-timetable/cohorts') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">🏫 Create Live Batch</a>
                <a href="<?= base_url('addons/rbac/workspace') ?>" class="btn-action" style="text-decoration:none;">🎨 Customize Menus</a>
            </div>
        </div>

        <!-- 8 Countable Executive KPI Blocks -->
        <div class="dsh-kpi-grid">
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">ENROLLED STUDENTS &amp; PARENTS</span>
                <div class="dsh-kpi-num text-emerald"><?= (int) ($ah['students_count'] ?? 0) ?> Students</div>
                <a href="<?= base_url('addons/core-lms-auth/directory') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">👨‍👩‍👧‍👦 <?= (int) ($ah['parents_count'] ?? 0) ?> Parents Directory →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">TEACHERS &amp; LIVE BATCHES</span>
                <div class="dsh-kpi-num text-cyan"><?= (int) ($ah['cohorts_count'] ?? 0) ?> Batches</div>
                <a href="<?= base_url('addons/lms-timetable/cohorts') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">👩‍🏫 <?= (int) ($ah['teachers_count'] ?? 0) ?> Active Teachers →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">VERIFIED FEE REVENUE</span>
                <div class="dsh-kpi-num text-emerald">₹<?= number_format((float) ($ah['revenue_inr'] ?? 0), 0) ?></div>
                <a href="<?= base_url('addons/lms-finance') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">🧾 <?= (int) ($ah['receipts_count'] ?? 0) ?> Paid Receipts →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-amber">OFFLINE FEE APPROVALS</span>
                <div class="dsh-kpi-num <?= (int) ($ah['pending_approvals'] ?? 0) > 0 ? 'text-amber' : '' ?>"><?= (int) ($ah['pending_approvals'] ?? 0) ?> Pending</div>
                <a href="<?= base_url('addons/lms-finance/invoices') ?>" class="btn-action text-amber" style="align-self:flex-start; text-decoration:none;">✅ Review Screenshots →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">DEMO INQUIRIES PIPELINE</span>
                <div class="dsh-kpi-num text-cyan"><?= (int) ($ah['new_demo_leads'] ?? 0) ?> Leads</div>
                <a href="<?= base_url('addons/lms-onboarding') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">🚀 Convert to Students →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">SUBSTITUTE REQUESTS</span>
                <div class="dsh-kpi-num <?= (int) ($ah['pending_substitutes'] ?? 0) > 0 ? 'text-danger' : '' ?>"><?= (int) ($ah['pending_substitutes'] ?? 0) ?> Pending</div>
                <a href="<?= base_url('addons/lms-timetable/substitutes') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">⇄ Open Substitute Inbox →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">CHAT SAFETY MODERATION</span>
                <div class="dsh-kpi-num <?= (int) ($ah['flagged_chats'] ?? 0) > 0 ? 'text-danger' : 'text-emerald' ?>"><?= (int) ($ah['flagged_chats'] ?? 0) ?> Flagged</div>
                <a href="<?= base_url('addons/lms-communication/moderation') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">🛡 Moderation Queue →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">COURSES &amp; EXAMS</span>
                <div class="dsh-kpi-num"><?= (int) ($ah['courses_count'] ?? 0) ?> Courses</div>
                <a href="<?= base_url('addons/lms-assessments/exams') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">📝 <?= (int) ($ah['exams_count'] ?? 0) ?> Active Exams →</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
'''

    candidate_roots = [
        os.getcwd(),
        r"C:\xampp\htdocs\LMSARGON",
    ]
    updated_paths = []
    for root in candidate_roots:
        if os.path.isdir(os.path.join(root, "app")):
            for rel_path, content in files.items():
                dst = os.path.join(root, rel_path.replace("/", os.sep))
                os.makedirs(os.path.dirname(dst), exist_ok=True)
                with open(dst, "w", encoding="utf-8", newline="\n") as f:
                    f.write(content.replace("\r\n", "\n").strip() + "\n")
                updated_paths.append(dst)

    if updated_paths:
        for p in updated_paths:
            print(f"[UPDATED] {p}")
    else:
        for rel_path, content in files.items():
            dst = os.path.join(os.getcwd(), rel_path.replace("/", os.sep))
            os.makedirs(os.path.dirname(dst), exist_ok=True)
            with open(dst, "w", encoding="utf-8", newline="\n") as f:
                f.write(content.replace("\r\n", "\n").strip() + "\n")
            print(f"[CREATED] {dst}")

if __name__ == "__main__":
    update_core_dashboard()
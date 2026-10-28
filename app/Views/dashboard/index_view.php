<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $effectiveRole    = function_exists('cms_get_effective_role')
        ? cms_get_effective_role()
        : strtolower(trim((string) (session()->get('role_slug') ?: 'student')));

    $roleWorkspace    = function_exists('cms_get_role_workspace') ? cms_get_role_workspace($effectiveRole) : [];
    $wsConfig         = $roleWorkspace['workspace'] ?? [];
    $enabledWidgets   = $wsConfig['dashboard_widgets'] ?? ['kpi_overview', 'quick_modules'];
    $compiledSidebar  = $roleWorkspace['compiled_sidebar'] ?? [];

    $showWidget = static function (string $widgetKey) use ($enabledWidgets): bool {
        return in_array($widgetKey, $enabledWidgets, true);
    };
?>

<!-- =========================================================================
     WIDGET 1: SYSTEM ACCOUNT & ROLE COUNTER KPIS (`kpi_overview`)
     ========================================================================= -->
<?php if ($showWidget('kpi_overview') && cms_has_permission(['dashboard.view', 'users.view'])): ?>
<div class="metrics-grid-5">
    <div class="metric-card">
        <span class="mono-label">TOTAL ACCOUNTS</span>
        <strong class="metric-val"><?= esc((string) ($stats['total'] ?? 0)) ?></strong>
        <span class="metric-sub">Provisioned identities</span>
    </div>
    <div class="metric-card">
        <span class="mono-label">SUPER ADMINS</span>
        <strong class="metric-val text-emerald"><?= esc((string) ($stats['super_admin'] ?? 0)) ?></strong>
        <span class="metric-sub">Root system authority</span>
    </div>
    <div class="metric-card">
        <span class="mono-label">ADMINISTRATORS</span>
        <strong class="metric-val text-cyan"><?= esc((string) ($stats['admin'] ?? 0)) ?></strong>
        <span class="metric-sub">Operations & staff control</span>
    </div>
    <div class="metric-card">
        <span class="mono-label">TEACHERS</span>
        <strong class="metric-val text-amber"><?= esc((string) ($stats['teacher'] ?? 0)) ?></strong>
        <span class="metric-sub">Instructors & evaluators</span>
    </div>
    <div class="metric-card">
        <span class="mono-label">STUDENTS</span>
        <strong class="metric-val text-violet"><?= esc((string) ($stats['student'] ?? 0)) ?></strong>
        <span class="metric-sub">Enrolled learners</span>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================================
     WIDGET 2: STUDENT LEARNING ARENA (`student_arena`)
     ========================================================================= -->
<?php if ($showWidget('student_arena')): ?>
<div class="grid-3-col" style="margin-bottom: 1.5rem;">
    <?php if (cms_has_permission('abacus_pro_suite.practice')): ?>
    <div class="panel-card">
        <span class="mono-label text-emerald">MY LEARNING ARENA</span>
        <h3 class="panel-title">3D Soroban & Flash Anzan</h3>
        <p class="muted-cell" style="margin: 0.45rem 0 1rem;">Practice virtual abacus bead calculations and high-speed mental math drills.</p>
        <a href="<?= base_url('abacus/arena') ?>" class="btn-primary btn-sm">🧮 Launch Abacus Arena →</a>
    </div>
    <?php endif; ?>

    <?php if (cms_has_permission('lms_timetable.view')): ?>
    <div class="panel-card">
        <span class="mono-label text-cyan">MY LIVE CLASSES</span>
        <h3 class="panel-title">Class Schedule & Homework</h3>
        <p class="muted-cell" style="margin: 0.45rem 0 1rem;">Join upcoming live sessions and check homework shared by your teacher.</p>
        <a href="<?= base_url('timetable/portal') ?>" class="btn-secondary btn-sm">📅 Open Class Timetable →</a>
    </div>
    <?php endif; ?>

    <?php if (cms_has_permission('lms_assessments.take_exam')): ?>
    <div class="panel-card">
        <span class="mono-label text-amber">EXAMS & WORKSHEETS</span>
        <h3 class="panel-title">Timed Exams & Certificates</h3>
        <p class="muted-cell" style="margin: 0.45rem 0 1rem;">Download practice PDF sheets, take timed exams, and download certificates.</p>
        <a href="<?= base_url('assessments/portal') ?>" class="btn-secondary btn-sm">🏆 Open Exam Portal →</a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- =========================================================================
     WIDGET 3: PARENT FAMILY OVERVIEW & BILLING (`parent_children`)
     ========================================================================= -->
<?php if ($showWidget('parent_children')): ?>
<div class="grid-2-col" style="margin-bottom: 1.5rem;">
    <?php if (cms_has_permission('core_lms_auth.view')): ?>
    <div class="panel-card">
        <span class="mono-label text-emerald">FAMILY & SIBLING PORTAL</span>
        <h3 class="panel-title">Children Profiles & Progress</h3>
        <p class="muted-cell" style="margin: 0.45rem 0 1rem;">Switch into your child's learning mode or track attendance and batch timings.</p>
        <a href="<?= base_url('portal/family') ?>" class="btn-primary btn-sm">👨‍👩‍👧‍👦 Open Family Portal →</a>
    </div>
    <?php endif; ?>

    <?php if (cms_has_permission('lms_finance.view')): ?>
    <div class="panel-card">
        <span class="mono-label text-cyan">FEE BILLING & RECEIPTS</span>
        <h3 class="panel-title">Course Fees & PDF Invoices</h3>
        <p class="muted-cell" style="margin: 0.45rem 0 1rem;">Pay recurring course fees and download official PDF payment receipts.</p>
        <a href="<?= base_url('finance/portal') ?>" class="btn-secondary btn-sm">💳 Open Fee Portal →</a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- =========================================================================
     WIDGET 4: ASSIGNED MODULE SHORTCUT CARDS (`quick_modules`)
     Automatically displays ONLY modules allowed in `compiled_sidebar`!
     ========================================================================= -->
<?php if ($showWidget('quick_modules') && ! empty($compiledSidebar)): ?>
<section class="panel-card" style="margin-bottom: 1.5rem;">
    <div class="panel-header">
        <div>
            <span class="mono-label">AUTHORIZED WORKSPACE MODULES</span>
            <h3 class="panel-title">Quick Access Modules for <?= esc(strtoupper($effectiveRole)) ?></h3>
        </div>
        <span class="req-badge"><?= count($compiledSidebar) ?> ACTIVE</span>
    </div>

    <div class="grid-3-col">
        <?php foreach ($compiledSidebar as $itemKey => $navItem): ?>
            <?php if ($itemKey === 'core_dashboard') continue; ?>
            <div class="asset-box" style="justify-content: space-between;">
                <div>
                    <span class="mono-label text-emerald"><?= esc($navItem['section'] ?? 'MODULE') ?></span>
                    <h4 style="margin: 0.3rem 0;"><?= esc($navItem['icon'] ?? '⚡') ?> <?= esc($navItem['display_label']) ?></h4>
                </div>
                <div class="action-btn-group" style="justify-content: flex-start; margin-top: 0.5rem;">
                    <a href="<?= base_url(esc($navItem['route'])) ?>" class="btn-primary btn-sm">Open →</a>
                    <?php foreach (array_slice($navItem['sub_views'] ?? [], 0, 2) as $sv): ?>
                        <a href="<?= base_url(esc($sv['route'])) ?>" class="btn-action" style="text-decoration:none;">
                            <?= esc($sv['display_label'] ?? $sv['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?= $this->endSection() ?>
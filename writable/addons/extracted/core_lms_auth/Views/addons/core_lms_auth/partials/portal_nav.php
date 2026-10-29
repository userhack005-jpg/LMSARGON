<?php
    $currSub         = $activeSubTab ?? 'family';
    $activeChild     = $portalCtx['active_student'] ?? null;
    $activeStudentId = (int) ($portalCtx['active_student_id'] ?? 0);
    $portalViewMode  = (string) ($portalCtx['portal_view_mode'] ?? 'parent');
    $isChildMode     = ($portalViewMode === 'student' && $activeStudentId > 0);
    $siblings        = $portalCtx['siblings'] ?? [];
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="cla-portal-header-card">
    <div class="cla-flex-between">
        <div>
            <?php if ($isChildMode && $activeChild): ?>
                <span class="role-badge text-emerald">🎓 Student Learning Mode</span>
                <h2 style="margin:0.3rem 0 0.1rem;"><?= esc($activeChild['full_name'] ?? 'Learner') ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.86rem;"><?= esc($activeChild['meta']['grade_level'] ?? 'Enrolled Course') ?></p>
            <?php else: ?>
                <span class="role-badge text-cyan">👨‍👩‍👧‍👦 Family Account</span>
                <h2 style="margin:0.3rem 0 0.1rem;">Welcome, <?= esc($portalCtx['current_user']['full_name'] ?? ($user['full_name'] ?? 'Parent')) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.86rem;">Manage your children's learning, courses, and account settings.</p>
            <?php endif; ?>
        </div>

        <!-- Clean Profile Mode Switcher Pills (Zero #IDs!) -->
        <?php if (! empty($siblings)): ?>
        <div class="cla-sibling-avatars">
            <?php if ($isChildMode && (string) ($user['role_slug'] ?? '') !== 'student'): ?>
                <form action="<?= base_url('portal/switch-student') ?>" method="post" class="cla-inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_id" value="0">
                    <button type="submit" class="cla-profile-pill">
                        <span class="cla-avatar-dot" style="background:#06b6d4;">🏠</span>
                        <span class="cla-pill-meta">
                            <strong>Back to Parent Account</strong>
                            <small>View All Children</small>
                        </span>
                    </button>
                </form>
            <?php endif; ?>

            <?php if (count($siblings) > 1 || ! $isChildMode): ?>
                <?php foreach ($siblings as $sib): ?>
                    <?php
                        $sId    = (int) $sib['id'];
                        $sName  = $sib['full_name'] ?? ($sib['name'] ?? 'Child');
                        $sMeta  = $sib['meta'] ?? [];
                        $isCurr = ($isChildMode && $sId === $activeStudentId);
                    ?>
                    <form action="<?= base_url('portal/switch-student') ?>" method="post" class="cla-inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="student_id" value="<?= $sId ?>">
                        <button type="submit" class="cla-profile-pill <?= $isCurr ? 'is-selected' : '' ?>">
                            <span class="cla-avatar-dot" style="background: <?= esc($sMeta['avatar_color'] ?? '#10b981') ?>;">
                                <?= strtoupper(substr(trim((string) $sName), 0, 1)) ?>
                            </span>
                            <span class="cla-pill-meta">
                                <strong><?= esc($sName) ?></strong>
                                <small><?= esc($sMeta['grade_level'] ?? 'Course') ?></small>
                            </span>
                        </button>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Dedicated Portal Navigation Tabs (Each Tab Opens a Single Clean Page!) -->
    <div class="settings-tabs" style="margin-top: 1.15rem; padding-top: 0.9rem; border-top: 1px solid var(--border-subtle);">
        <a href="<?= base_url('portal/family') ?>" class="settings-tab <?= $currSub === 'family' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span><?= $isChildMode ? '🎓 My Learning Hub' : '👨‍👩‍👧‍👦 My Children (' . count($siblings) . ')' ?></span>
        </a>
        <?php if (! $isChildMode): ?>
            <a href="<?= base_url('portal/family/add-child') ?>" class="settings-tab <?= $currSub === 'add_child' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>➕ Add Child Profile</span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('portal/family/profile') ?>" class="settings-tab <?= $currSub === 'profile' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>⚙ My Profile &amp; Password</span>
        </a>
    </div>
</div>

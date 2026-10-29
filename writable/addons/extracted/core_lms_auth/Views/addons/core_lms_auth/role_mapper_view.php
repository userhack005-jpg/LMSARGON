<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="cla-admin-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <section class="console-card">
        <div class="card-head cla-flex-between">
            <div>
                <h3>5-Behavior System Role Mapper</h3>
                <p class="field-hint" style="margin:0;">Map any custom RBAC roles to the 5 core LMS behaviors.</p>
            </div>
            <form action="<?= base_url('addons/core-lms-auth/sync-fk-roles') ?>" method="post" class="cla-inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn-secondary btn-sm">↻ Sync Roles &amp; User Accounts</button>
            </form>
        </div>

        <form action="<?= base_url('addons/core-lms-auth/save-role-map') ?>" method="post">
            <?= csrf_field() ?>
            <?php
                $behaviorMeta = [
                    'super_admin' => ['title' => 'Super Admin', 'desc' => 'Root system & security control.', 'badge' => 'ROOT'],
                    'admin'       => ['title' => 'Admin',       'desc' => 'Courses, users, pages & moderation.', 'badge' => 'STAFF'],
                    'teacher'     => ['title' => 'Teacher',     'desc' => 'Live classes, attendance & grading.', 'badge' => 'FACULTY'],
                    'parent'      => ['title' => 'Parent',      'desc' => 'Family account managing children.', 'badge' => 'FAMILY'],
                    'student'     => ['title' => 'Student',     'desc' => 'Learner accessing classes & drills.', 'badge' => 'LEARNER'],
                ];
            ?>
            <div class="cla-behavior-grid">
                <?php foreach ($behaviorMeta as $bKey => $bInfo): ?>
                    <?php $mappedSlugs = $roleMap[$bKey] ?? [$bKey]; ?>
                    <div class="panel-card cla-behavior-card">
                        <div class="cla-behavior-head">
                            <strong><?= esc($bInfo['title']) ?></strong>
                            <span class="role-badge"><?= esc($bInfo['badge']) ?></span>
                        </div>
                        <p class="muted-cell cla-behavior-desc"><?= esc($bInfo['desc']) ?></p>

                        <div class="cla-role-checkbox-list">
                            <?php foreach (($availableRoles ?? []) as $rRow): ?>
                                <?php
                                    $rSlug     = (string) ($rRow['role_slug'] ?? '');
                                    $isChecked = in_array($rSlug, $mappedSlugs, true);
                                ?>
                                <label class="cla-check-item">
                                    <input type="checkbox" name="behavior_<?= esc($bKey) ?>[]" value="<?= esc($rSlug) ?>" <?= $isChecked ? 'checked' : '' ?>>
                                    <span><?= esc($rRow['role_name'] ?? $rSlug) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="action-btn-group cla-footer-bar">
                <button type="submit" class="btn-primary">💾 Save Role Behavior Mapping</button>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

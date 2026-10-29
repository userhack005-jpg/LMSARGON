<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $es      = $editStudent ?? null;
    $esId    = (int) ($es['id'] ?? 0);
    $esParId = (int) ($es['parent_user_id'] ?? 0);
    $esMeta  = $es['meta'] ?? [];
?>
<div class="cla-admin-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <section class="console-card" style="max-width: 780px; margin: 0 auto;">
        <div class="card-head cla-flex-between">
            <div>
                <h3><?= $esId > 0 ? ('Edit Child Link: ' . esc($es['full_name'])) : 'Link Student to Parent & Course' ?></h3>
                <p class="field-hint" style="margin:0;">Assign a student account to a parent profile and enroll them in a course.</p>
            </div>
            <a href="<?= base_url('addons/core-lms-auth/directory') ?>" class="btn-secondary btn-sm">← Back to Directory</a>
        </div>

        <form action="<?= base_url('addons/core-lms-auth/save-child-link') ?>" method="post">
            <?= csrf_field() ?>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Select Student Account *</span></label>
                    <select name="student_user_id" id="cla_student_id" class="field-select" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach (($familyDir['eligible_learners'] ?? []) as $uRow): ?>
                            <?php $uId = (int) $uRow['id']; ?>
                            <option value="<?= $uId ?>" <?= $esId === $uId ? 'selected' : '' ?>>
                                🎓 <?= esc($uRow['full_name'] ?: $uRow['email']) ?> (<?= esc($uRow['email'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Select Parent Account</span></label>
                    <select name="parent_user_id" id="cla_parent_id" class="field-select">
                        <option value="0">-- Independent Student (No Linked Parent) --</option>
                        <?php foreach (($familyDir['eligible_parents'] ?? []) as $pRow): ?>
                            <?php $pId = (int) $pRow['id']; ?>
                            <option value="<?= $pId ?>" <?= $esParId === $pId ? 'selected' : '' ?>>
                                👨‍👩‍👧‍👦 <?= esc($pRow['full_name'] ?: $pRow['email']) ?> (<?= esc($pRow['email'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Enroll in Course *</span></label>
                    <?php if (! empty($leafBatchesMap)): ?>
                        <select name="leaf_node_id" id="cla_leaf_node_id" class="field-select">
                            <option value="0">-- Select Course --</option>
                            <?php foreach ($leafBatchesMap as $lid => $bInfo): ?>
                                <option value="<?= (int) $lid ?>"
                                        data-title="<?= esc($bInfo['title']) ?>"
                                        <?= (int) ($esMeta['leaf_node_id'] ?? 0) === (int) $lid ? 'selected' : '' ?>>
                                    <?= esc($bInfo['breadcrumb_path']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="grade_level" id="cla_grade" value="<?= esc($esMeta['grade_level'] ?? 'Foundation Level 1') ?>">
                    <?php else: ?>
                        <input type="text" name="grade_level" id="cla_grade" class="field-input" value="<?= esc($esMeta['grade_level'] ?? 'Foundation Level 1') ?>">
                    <?php endif; ?>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Child Age (Years)</span></label>
                    <input type="number" name="child_age" id="cla_child_age" class="field-input mono-input" value="<?= (int) ($esMeta['child_age'] ?? 9) ?>" min="3" max="25">
                </div>
            </div>

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Country *</span></label>
                    <select name="country_code" id="cla_country" class="field-select">
                        <?php foreach (($enabledCountries ?? []) as $cCode => $cInfo): ?>
                            <option value="<?= esc($cCode) ?>"
                                    data-tz="<?= esc($cInfo['default_tz'] ?? 'Asia/Kolkata') ?>"
                                    <?= ($esMeta['country_code'] ?? 'IN') === $cCode ? 'selected' : '' ?>>
                                <?= esc($cInfo['flag'] ?? '') ?> <?= esc($cInfo['name'] ?? $cCode) ?> (<?= esc($cInfo['currency_code'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Timezone</span></label>
                    <input type="text" name="timezone" id="cla_timezone" class="field-input mono-input" value="<?= esc($esMeta['timezone'] ?? 'Asia/Kolkata') ?>">
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Avatar Color</span></label>
                    <input type="color" name="avatar_color" id="cla_avatar_color" class="field-input" value="<?= esc($esMeta['avatar_color'] ?? '#10b981') ?>" style="height:40px;padding:4px;">
                </div>
            </div>

            <div class="action-btn-group cla-footer-bar">
                <button type="submit" class="btn-primary">💾 Save Child Link &amp; Course Enrollment</button>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="cla-admin-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <section class="console-card">
        <div class="card-head cla-flex-between">
            <div>
                <h3>Student Profiles &amp; Linked Parents</h3>
                <p class="field-hint" style="margin:0;">View all student profiles, their linked parent accounts, and enrolled courses.</p>
            </div>
            <div class="action-btn-group">
                <input type="search" id="js-cla-dir-search" class="field-input" placeholder="Search student or parent..." style="width:220px; padding:0.4rem 0.7rem;">
                <?php if (! empty($canCreate)): ?>
                    <a href="<?= base_url('addons/core-lms-auth/linker') ?>" class="btn-primary btn-sm">+ Assign Child &amp; Course</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name &amp; Email</th>
                        <th>Linked Parent Account</th>
                        <th>Enrolled Course &amp; Age</th>
                        <th>Country &amp; Timezone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($familyDir['students'])): ?>
                        <tr><td colspan="5" class="muted-cell" style="text-align:center;padding:1.5rem;">No student profiles found yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($familyDir['students'] as $stu): ?>
                            <?php
                                $sId       = (int) $stu['id'];
                                $sName     = $stu['full_name'] ?? ($stu['name'] ?? 'Student');
                                $pId       = (int) ($stu['parent_user_id'] ?? 0);
                                $parentRow = $familyDir['all_users'][$pId] ?? null;
                                $meta      = $stu['meta'] ?? [];
                                $searchTxt = strtolower($sName . ' ' . ($stu['email'] ?? '') . ' ' . ($parentRow['full_name'] ?? '') . ' ' . ($meta['grade_level'] ?? ''));
                            ?>
                            <tr class="js-cla-dir-row" data-search="<?= esc($searchTxt) ?>">
                                <td>
                                    <div class="cla-child-cell">
                                        <span class="cla-avatar-dot" style="background: <?= esc($meta['avatar_color'] ?? '#10b981') ?>;">
                                            <?= strtoupper(substr(trim((string) $sName), 0, 1)) ?>
                                        </span>
                                        <div>
                                            <div class="identity-name"><?= esc($sName) ?></div>
                                            <div class="identity-email"><?= esc($stu['email'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($pId > 0 && $parentRow): ?>
                                        <div class="identity-name">👨‍👩‍👧‍👦 <?= esc($parentRow['full_name'] ?? 'Parent') ?></div>
                                        <div class="identity-email"><?= esc($parentRow['email'] ?? '') ?></div>
                                    <?php else: ?>
                                        <span class="muted-cell">Independent Learner</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="mono-cell text-emerald"><strong><?= esc($meta['grade_level'] ?? 'Course') ?></strong></div>
                                    <div class="muted-cell">Age: <?= (int) ($meta['child_age'] ?? 8) ?> yrs</div>
                                </td>
                                <td>
                                    <div class="identity-name"><?= esc($meta['country_code'] ?? 'IN') ?> (<?= esc($meta['currency_code'] ?? 'INR') ?>)</div>
                                    <div class="mono-cell muted-cell"><?= esc($meta['timezone'] ?? 'Asia/Kolkata') ?></div>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="<?= base_url('addons/core-lms-auth/linker?student_id=' . $sId) ?>" class="btn-action" style="text-decoration:none;">✎ Edit</a>
                                        <?php if (! empty($canDelete) && $pId > 0): ?>
                                            <form action="<?= base_url('addons/core-lms-auth/delete-child-link') ?>" method="post" class="cla-inline-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="student_user_id" value="<?= $sId ?>">
                                                <button type="submit" class="btn-action text-danger">Unlink</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

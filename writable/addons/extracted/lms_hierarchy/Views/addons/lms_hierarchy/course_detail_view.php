<?php
    $layoutToExtend = ! empty($isLoggedIn) ? 'layouts/master_layout' : 'layouts/frontend_layout';
?>
<?= $this->extend($layoutToExtend) ?>

<?= $this->section('content') ?>
<?php
    $cNode         = $courseNode ?? [];
    $isLeaf        = ((int) ($cNode['is_leaf_node'] ?? 0) === 1);
    $hasChildren   = ! empty($childNodes);
    $hasOnboarding = ! empty($companions['lms_onboarding']);
    $bookingUrl    = $hasOnboarding ? base_url('addons/lms-onboarding?course_id=' . (int) ($cNode['id'] ?? 0)) : base_url('login');
?>
<div class="lhr-detail-page-wrap" style="max-width: 1120px; margin: 2rem auto; padding: 0 1.25rem;">
    <!-- Clean Breadcrumb Navigation -->
    <nav class="lhr-clean-breadcrumbs" style="margin-bottom: 1.25rem;">
        <a href="<?= ! empty($isLoggedIn) ? base_url('courses/catalog') : base_url('/') ?>" class="btn-action" style="text-decoration:none;">← Back to Courses</a>
        <?php foreach (($ancestors ?? []) as $anc): ?>
            <span class="lhr-crumb-sep">›</span>
            <a href="<?= base_url('courses/view/' . (int) $anc['id']) ?>" class="btn-action" style="text-decoration:none;"><?= esc($anc['title']) ?></a>
        <?php endforeach; ?>
        <span class="lhr-crumb-sep">›</span>
        <span class="mono-cell text-emerald"><strong><?= esc($cNode['title'] ?? '') ?></strong></span>
    </nav>

    <!-- Course Hero & Full Rich-Text Description Card -->
    <section class="console-card" style="margin-bottom: 1.75rem;">
        <div class="lhr-course-detail-grid">
            <div>
                <span class="mono-label text-emerald"><?= esc($cNode['breadcrumb_path'] ?? '') ?></span>
                <h1 style="margin: 0.35rem 0 0.75rem; font-size: clamp(1.65rem, 3.2vw, 2.35rem);"><?= esc($cNode['title'] ?? '') ?></h1>

                <?php if (! empty($cNode['short_summary'])): ?>
                    <p class="muted-cell" style="font-size: 1.02rem; margin-bottom: 1.15rem;"><?= esc($cNode['short_summary']) ?></p>
                <?php endif; ?>

                <div class="cms-prose lhr-course-prose">
                    <?= $cNode['description_html'] ?? '' ?>
                </div>

                <?php if ($isLeaf): ?>
                    <div class="lhr-detail-cta-bar">
                        <?php if ($priceInfo && (float) ($priceInfo['monthly_fee'] ?? 0) > 0): ?>
                            <div>
                                <span class="mono-label">COURSE FEE PLAN</span>
                                <div class="lhr-price-tag" style="font-size: 1.55rem;">
                                    <?= esc($priceInfo['formatted_monthly']) ?> <small class="mono-cell muted-cell">/ month</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="action-btn-group">
                            <?php if ((int) ($cNode['allow_demo_booking'] ?? 0) === 1): ?>
                                <a href="<?= esc($bookingUrl) ?>" class="btn-secondary">
                                    🎯 <?= esc($cNode['demo_note'] ?: 'Book Free Demo') ?>
                                </a>
                            <?php endif; ?>
                            <a href="<?= esc($bookingUrl) ?>" class="btn-primary">
                                🎓 Enroll in This Course →
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (! empty($cNode['resolved_thumbnail'])): ?>
                <div>
                    <img src="<?= esc($cNode['resolved_thumbnail']) ?>"
                         alt="<?= esc($cNode['title'] ?? '') ?>"
                         style="width:100%; border-radius:14px; object-fit:cover; max-height:360px; border:1px solid var(--border-subtle);">
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sub-Levels Grid (If this Course Category has Levels inside it) -->
    <?php if ($hasChildren): ?>
        <section>
            <h3 style="margin-bottom: 1rem;">Available Levels in <?= esc($cNode['title']) ?></h3>
            <div class="grid-3-col">
                <?php foreach ($childNodes as $ch): ?>
                    <?php $chLeaf = ((int) ($ch['is_leaf_node'] ?? 0) === 1); ?>
                    <a href="<?= base_url('courses/view/' . (int) $ch['id']) ?>" class="lhr-node-card" style="text-decoration:none; color:inherit;">
                        <?php if (! empty($ch['resolved_thumbnail'])): ?>
                            <img src="<?= esc($ch['resolved_thumbnail']) ?>" alt="<?= esc($ch['title']) ?>" class="lhr-card-thumb" loading="lazy">
                        <?php endif; ?>
                        <div class="lhr-card-body">
                            <span class="mono-label text-emerald"><?= $chLeaf ? 'COURSE LEVEL' : 'PROGRAM' ?></span>
                            <h3 style="margin:0.2rem 0;"><?= esc($ch['title']) ?></h3>
                            <p class="muted-cell" style="font-size:0.86rem; margin:0;"><?= esc($ch['short_summary'] ?? '') ?></p>
                        </div>
                        <div class="lhr-card-footer">
                            <span class="mono-cell text-cyan"><?= $chLeaf ? 'View Curriculum & Fee' : (count($ch['children_ids'] ?? []) . ' Sub-Levels') ?></span>
                            <span class="btn-action text-emerald">Explore →</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

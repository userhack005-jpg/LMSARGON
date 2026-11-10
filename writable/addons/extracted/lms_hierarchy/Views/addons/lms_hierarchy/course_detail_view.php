<?php
    $useBackendLayout = ! empty($isLoggedIn) && empty($_GET['public_view']);
    $layoutToExtend   = $useBackendLayout ? 'layouts/master_layout' : 'layouts/frontend_layout';
?>
<?= $this->extend($layoutToExtend) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/addons/lms_hierarchy/lms_hierarchy.css?v=8.5') ?>">
<?php
    $cNode       = is_array($courseNode ?? null) ? $courseNode : [];
    $cid         = (int) ($cNode['id'] ?? 0);
    $isLeaf      = ((int) ($cNode['is_leaf_node'] ?? 0) === 1);
    $hasChildren = ! empty($childNodes);
    $demoUrl     = base_url('demo/book/' . $cid);
    $enrollUrl   = base_url('finance/enroll-checkout/' . $cid);
?>
<div class="lhr-detail-page-wrap">
    <nav class="lhr-clean-breadcrumbs" style="margin-bottom: 1.25rem;">
        <a href="javascript:history.back()" class="lhr-btn lhr-btn-outline lhr-btn-sm">← Back</a>
        <?php foreach (($ancestors ?? []) as $anc): ?>
            <span class="lhr-crumb-sep">›</span>
            <a href="<?= base_url('courses/view/' . (int) $anc['id']) ?>" class="lhr-btn lhr-btn-outline lhr-btn-sm"><?= esc($anc['title']) ?></a>
        <?php endforeach; ?>
        <span class="lhr-crumb-sep">›</span>
        <span class="mono-cell text-emerald"><strong><?= esc($cNode['title'] ?? '') ?></strong></span>
    </nav>

        <?php if (! empty($isLoggedIn) && ! empty($myCourseSubs)): ?>
        <section class="console-card" style="margin-bottom: 1.35rem; border: 1.5px solid #10b981 !important;">
            <div class="lhr-flex-between" style="margin-bottom: 0.75rem;">
                <div>
                    <span class="lhr-badge lhr-badge-emerald">📦 ACTIVE SUBSCRIPTION &amp; PACKAGE DETAILS</span>
                    <h3 style="margin: 0.3rem 0 0;">Your Enrolled Package &amp; Billing Status</h3>
                </div>
                <a href="<?= base_url('finance/portal') ?>" class="lhr-btn lhr-btn-emerald lhr-btn-sm">💳 Open Fee &amp; Receipts Portal →</a>
            </div>
            <div class="lhr-cards-auto-grid">
                <?php foreach ($myCourseSubs as $subRow): ?>
                    <div class="panel-card" style="background: var(--lhr-canvas-bg); padding: 1rem !important;">
                        <div class="lhr-flex-between">
                            <span class="lhr-badge lhr-badge-cyan"><?= esc($subRow['billing_cycle'] ?? 'Monthly') ?> Package</span>
                            <span class="lhr-badge <?= (int) ($subRow['is_access_locked'] ?? 0) === 1 ? 'lhr-badge-amber' : 'lhr-badge-emerald' ?>">
                                <?= (int) ($subRow['is_access_locked'] ?? 0) === 1 ? '🔒 Fee Due' : '✓ Active Access' ?>
                            </span>
                        </div>
                        <div style="font-size: 1.25rem; font-weight: 800; color: #10b981; margin: 0.5rem 0;">
                            <?= esc($subRow['currency'] ?? 'INR') ?> <?= number_format((float) ($subRow['fee_amount'] ?? 0), 2) ?>
                            <small class="mono-cell muted-cell">/ <?= esc($subRow['billing_cycle'] ?? 'cycle') ?></small>
                        </div>
                        <div class="mono-cell muted-cell">
                            📅 Batch Duration: <strong><?= esc($subRow['batch_start_date'] ?: 'Active') ?></strong> to <strong><?= esc($subRow['batch_end_date'] ?: 'Ongoing') ?></strong><br>
                            ⏰ Next Due Date: <strong class="text-cyan"><?= esc($subRow['due_date'] ?? '-') ?></strong> •
                            ✅ Paid Cycles: <strong><?= (int) ($subRow['paid_cycles_count'] ?? 0) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="console-card" style="margin-bottom: 1.75rem;">
        <div class="lhr-course-detail-grid">
            <div>
                <span class="mono-label text-emerald"><?= esc($cNode['breadcrumb_path'] ?? '') ?></span>
                <h1 style="margin: 0.35rem 0 0.75rem; font-size: clamp(1.5rem, 3.2vw, 2.25rem);"><?= esc($cNode['title'] ?? '') ?></h1>

                <?php if (! empty($cNode['short_summary'])): ?>
                    <p class="muted-cell" style="font-size: 1rem; margin-bottom: 1.15rem;"><?= esc($cNode['short_summary']) ?></p>
                <?php endif; ?>

                <div class="cms-prose lhr-course-prose">
                    <?= $cNode['description_html'] ?? '' ?>
                </div>

                <?php if ($isLeaf): ?>
                    <div class="lhr-detail-cta-bar">
                        <?php if (! empty($priceInfo) && (float) ($priceInfo['monthly_fee'] ?? 0) > 0): ?>
                            <div>
                                <span class="mono-label">COURSE FEE PLAN</span>
                                <div class="lhr-price-tag" style="font-size: 1.45rem;">
                                    <?= esc($priceInfo['formatted_monthly']) ?> <small class="mono-cell muted-cell">/ month</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="lhr-embed-cta-stack">
                            <?php if ((int) ($cNode['allow_demo_booking'] ?? 0) === 1): ?>
                                <a href="<?= esc($demoUrl) ?>" class="lhr-btn lhr-btn-cyan lhr-btn-lg">
                                    🎯 <?= esc($cNode['demo_note'] ?: 'Book Free Demo') ?>
                                </a>
                            <?php endif; ?>
                            <a href="<?= esc($enrollUrl) ?>" class="lhr-btn lhr-btn-emerald lhr-btn-lg">
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
                         loading="lazy" decoding="async"
                         style="width:100%; border-radius:14px; object-fit:cover; max-height:360px; border:1px solid var(--lhr-border);">
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($hasChildren): ?>
        <section>
            <h3 style="margin-bottom: 1rem;">Available Levels in <?= esc($cNode['title']) ?></h3>
            <div class="lhr-cards-auto-grid">
                <?php foreach ($childNodes as $ch): ?>
                    <?php $chLeaf = ((int) ($ch['is_leaf_node'] ?? 0) === 1); ?>
                    <a href="<?= base_url('courses/view/' . (int) $ch['id']) ?>" class="lhr-node-card" style="text-decoration:none; color:inherit;">
                        <?php if (! empty($ch['resolved_thumbnail'])): ?>
                            <img src="<?= esc($ch['resolved_thumbnail']) ?>" alt="<?= esc($ch['title']) ?>" class="lhr-card-thumb" loading="lazy" decoding="async">
                        <?php endif; ?>
                        <div class="lhr-card-body">
                            <span class="mono-label text-emerald"><?= $chLeaf ? 'COURSE LEVEL' : 'PROGRAM' ?></span>
                            <h3 style="margin:0.2rem 0;"><?= esc($ch['title']) ?></h3>
                            <p class="muted-cell" style="font-size:0.86rem; margin:0;"><?= esc($ch['short_summary'] ?? '') ?></p>
                        </div>
                        <div class="lhr-card-footer">
                            <span class="mono-cell text-cyan"><?= $chLeaf ? 'View Curriculum & Fee' : (count($ch['children_ids'] ?? []) . ' Sub-Levels') ?></span>
                            <span class="lhr-btn lhr-btn-emerald lhr-btn-sm"><?= $chLeaf ? 'View Details →' : 'Explore Levels →' ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

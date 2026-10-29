<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="las-portal-wrapper">
    <?php if (! empty($canManageAdmin)): ?>
        <?php include __DIR__ . '/partials/admin_nav.php'; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/portal_nav.php'; ?>

    <?php if (empty($studentWorksheets)): ?>
        <section class="console-card" style="text-align:center; padding:2.5rem;">
            <h3>No PDF Worksheets Available Yet</h3>
            <p class="muted-cell">Downloadable practice sheets for your enrolled course will appear here.</p>
        </section>
    <?php else: ?>
        <div class="las-cards-grid">
            <?php foreach ($studentWorksheets as $ws): ?>
                <article class="las-modern-card">
                    <div>
                        <div class="las-flex-between" style="margin-bottom:0.45rem;">
                            <span class="role-badge text-emerald"><?= esc($ws['worksheet_type']) ?></span>
                            <span class="mono-cell text-cyan"><?= esc($ws['access_tag']) ?></span>
                        </div>
                        <h3 style="margin:0.25rem 0 0.35rem;"><?= esc($ws['title']) ?></h3>
                        <div class="mono-cell muted-cell" style="margin-bottom:0.5rem;"><?= esc($ws['node_title']) ?></div>
                        <?php if (! empty($ws['instructions'])): ?>
                            <p class="muted-cell" style="font-size:0.86rem; margin:0;"><?= esc($ws['instructions']) ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= esc($ws['resolved_dl_url']) ?>" target="_blank" download class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">
                        ⬇ Download PDF Worksheet
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="cla-portal-wrapper">
    <?php include __DIR__ . '/partials/portal_nav.php'; ?>

    <section class="console-card" style="max-width: 680px; margin: 0 auto;">
        <div class="card-head cla-flex-between">
            <div>
                <h3>Add a New Child to Your Family Account</h3>
                <p class="field-hint" style="margin:0;">Create a dedicated learning profile for your child and select their course.</p>
            </div>
            <a href="<?= base_url('portal/family') ?>" class="btn-secondary btn-sm">← Back to My Children</a>
        </div>

        <form action="<?= base_url('portal/family/create-child') ?>" method="post">
            <?= csrf_field() ?>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Child's Full Name *</span></label>
                    <input type="text" name="full_name" class="field-input" placeholder="e.g. Aarav Sharma" required>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Child's Age (Years) *</span></label>
                    <input type="number" name="child_age" class="field-input mono-input" value="8" min="3" max="20" required>
                </div>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Select Course / Program</span></label>
                    <select name="leaf_node_id" class="field-select">
                        <option value="0">-- Choose Course --</option>
                        <?php foreach (($leafBatchesMap ?? []) as $lid => $bInfo): ?>
                            <option value="<?= (int) $lid ?>"><?= esc($bInfo['breadcrumb_path']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Profile Badge Color</span></label>
                    <input type="color" name="avatar_color" class="field-input" value="#10b981" style="height:40px;padding:4px;">
                </div>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Child Login Email / Username (Optional)</span></label>
                    <input type="text" name="email" class="field-input" placeholder="Leave blank to auto-generate">
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Child Login Password (Optional)</span></label>
                    <input type="password" name="password" class="field-input" placeholder="Min 6 characters">
                </div>
            </div>

            <div class="action-btn-group cla-footer-bar">
                <button type="submit" class="btn-primary">➕ Create Child Profile</button>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

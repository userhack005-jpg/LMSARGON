<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lcm-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <section class="console-card">
        <div class="card-head">
            <div>
                <h3>Shadow-Blocked &amp; Flagged Chat Messages (<?= count($flaggedMessages ?? []) ?>)</h3>
                <p class="field-hint" style="margin:0;">Messages containing phone numbers, emails, or external links are shadow-blocked automatically so recipients cannot see them unless you approve and release them below.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date &amp; Sender</th>
                        <th>Student ↔ Teacher Pair</th>
                        <th>Intercepted Message Content</th>
                        <th>Safety Trigger Reason</th>
                        <th>Status</th>
                        <th>Moderation Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($flaggedMessages)): ?>
                        <tr><td colspan="6" class="muted-cell" style="text-align:center; padding:2rem;">No shadow-blocked or flagged messages in the queue.</td></tr>
                    <?php else: ?>
                        <?php foreach ($flaggedMessages as $fm): ?>
                            <tr>
                                <td>
                                    <div class="identity-name"><?= esc($fm['sender_name']) ?> (<?= strtoupper(esc($fm['sender_role'])) ?>)</div>
                                    <div class="mono-cell muted-cell"><?= esc(substr((string) $fm['created_at'], 0, 16)) ?></div>
                                </td>
                                <td>
                                    <div>🎓 <?= esc($fm['student_name']) ?></div>
                                    <div class="mono-cell text-cyan">👩‍🏫 <?= esc($fm['teacher_name']) ?></div>
                                </td>
                                <td class="mono-cell text-danger"><strong><?= esc($fm['message_body']) ?></strong></td>
                                <td><span class="role-badge text-amber"><?= esc($fm['flag_reason'] ?: 'DLP Rule') ?></span></td>
                                <td>
                                    <span class="role-badge <?= ($fm['moderation_status'] ?? '') === 'approved' ? 'text-emerald' : 'text-danger' ?>">
                                        <?= strtoupper(esc($fm['moderation_status'] ?? 'SHADOW_BLOCKED')) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <form action="<?= base_url('addons/lms-communication/approve-message') ?>" method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $fm['id'] ?>">
                                            <button type="submit" class="btn-primary btn-sm">✓ Approve &amp; Release</button>
                                        </form>
                                        <form action="<?= base_url('addons/lms-communication/delete-message') ?>" method="post" style="display:inline;" onsubmit="return confirm('Delete this message permanently?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $fm['id'] ?>">
                                            <button type="submit" class="btn-action text-danger">✕ Delete</button>
                                        </form>
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

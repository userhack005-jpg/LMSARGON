<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lcm-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <section class="console-card">
        <div class="card-head lcm-flex-between">
            <div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <h3>Email &amp; WhatsApp Notification Dispatch Log</h3>
                    <span class="role-badge <?= ! empty($isProdMode) ? 'text-emerald' : 'text-amber' ?>">
                        <?= ! empty($isProdMode) ? '🚀 LIVE PRODUCTION MODE' : '🧪 SANDBOX TESTING MODE' ?>
                    </span>
                </div>
                <p class="field-hint" style="margin:0;">Automated class reminders, fee alerts, and communication notifications.</p>
            </div>
            <form action="<?= base_url('addons/lms-communication/trigger-test-alert') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="btn-secondary btn-sm">🔔 Log Sample Notification</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Channel</th>
                        <th>Recipient &amp; Timezone</th>
                        <th>Formatted Message</th>
                        <th>Date (UTC)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alertLogs)): ?>
                        <tr><td colspan="5" class="muted-cell" style="text-align:center; padding:2rem;">No notification logs recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($alertLogs as $al): ?>
                            <tr>
                                <td class="identity-name"><?= esc($al['event_type']) ?></td>
                                <td><span class="role-badge text-cyan"><?= strtoupper(esc($al['channel'])) ?></span></td>
                                <td>
                                    <div class="identity-name"><?= esc($al['recipient_name']) ?></div>
                                    <div class="mono-cell muted-cell"><?= esc($al['recipient_timezone']) ?></div>
                                </td>
                                <td><?= esc($al['formatted_message']) ?></td>
                                <td class="mono-cell muted-cell"><?= esc(substr((string) $al['created_at'], 0, 16)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="ltt-wrapper" id="js-ltt-sub-poll-root"
     data-poll-url="<?= base_url('addons/lms-timetable/live-sub-threads-json') ?>"
     data-reply-url="<?= base_url('addons/lms-timetable/reply-sub-request') ?>"
     data-assign-url="<?= base_url('addons/lms-timetable/assign-substitute') ?>"
     data-is-admin="1">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <section class="console-card ltt-section-gap">
        <div class="card-head ltt-flex-between">
            <div>
                <h3>Teacher Leave &amp; Substitute Allocation Inbox</h3>
                <p class="field-hint" style="margin:0;">Real-time conversation threads and 1-click substitute teacher assignment.</p>
            </div>
            <span class="status-pill"><span class="ltt-led-green"></span><span class="mono-label text-emerald">LIVE SYNC</span></span>
        </div>

        <div class="ltt-req-list" id="js-ltt-sub-threads-container">
            <?php if (empty($subRequests)): ?>
                <p class="muted-cell">No teacher substitute requests submitted yet.</p>
            <?php else: ?>
                <?php foreach ($subRequests as $req): ?>
                    <div class="panel-card ltt-req-card" data-req-id="<?= (int) $req['id'] ?>">
                        <div class="ltt-flex-between">
                            <div>
                                <strong>Request by 👩‍🏫 <?= esc($req['teacher_name']) ?></strong>
                                <span class="status-pill"><span class="mono-label"><?= strtoupper(esc($req['status'])) ?></span></span>
                            </div>
                            <div class="mono-label text-cyan">Assigned Substitute: <strong><?= esc($req['substitute_name']) ?></strong></div>
                        </div>

                        <div class="ltt-thread-box" id="js-sub-thread-msgs-<?= (int) $req['id'] ?>">
                            <?php foreach (($req['thread'] ?? []) as $msg): ?>
                                <div class="ltt-thread-msg <?= ($msg['sender_role'] ?? '') === 'admin' ? 'is-admin-msg' : 'is-teacher-msg' ?>">
                                    <strong><?= esc($msg['sender_name']) ?> (<?= strtoupper(esc($msg['sender_role'])) ?>):</strong>
                                    <span><?= esc($msg['message_text']) ?></span>
                                    <small class="muted-cell"><?= esc(substr((string) $msg['created_at'], 0, 16)) ?> UTC</small>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="ltt-req-actions-grid">
                            <form action="<?= base_url('addons/lms-timetable/reply-sub-request') ?>" method="post" class="ltt-mini-row js-ajax-sub-reply-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                <input type="text" name="message_text" class="field-input" placeholder="Write instant reply to Teacher..." autocomplete="off" required>
                                <button type="submit" class="btn-secondary btn-sm">💬 Reply</button>
                            </form>

                            <?php if (! empty($canEdit)): ?>
                                <form action="<?= base_url('addons/lms-timetable/assign-substitute') ?>" method="post" class="ltt-mini-row js-ajax-sub-assign-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                    <select name="substitute_teacher_id" class="field-select" required>
                                        <option value="">-- Assign Substitute Teacher --</option>
                                        <?php foreach (($teachers ?? []) as $tid => $tRow): ?>
                                            <option value="<?= (int) $tid ?>" <?= (int) $req['substitute_teacher_id'] === (int) $tid ? 'selected' : '' ?>>
                                                👩‍🏫 <?= esc($tRow['display_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-primary btn-sm">✓ Assign Substitute</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

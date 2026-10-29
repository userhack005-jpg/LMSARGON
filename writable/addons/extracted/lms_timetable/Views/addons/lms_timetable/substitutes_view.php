<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $activeReq   = $activeSubReq ?? null;
    $sessionsMap = [];
    foreach (($sessions ?? []) as $s) {
        $sessionsMap[(int) $s['id']] = $s;
    }
?>
<div class="ltt-wrapper" id="js-ltt-sub-poll-root"
     data-poll-url="<?= base_url('addons/lms-timetable/live-sub-threads-json') ?>"
     data-reply-url="<?= base_url('addons/lms-timetable/reply-sub-request') ?>"
     data-assign-url="<?= base_url('addons/lms-timetable/assign-substitute') ?>"
     data-is-admin="1">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <!-- Status Filter Strip for Multi-Teacher Requests -->
    <div class="ltt-compact-bar" style="margin-bottom: 1rem;">
        <div class="action-btn-group" id="js-sub-status-filter">
            <button type="button" class="btn-action is-active js-sub-filter-btn" data-status="all">
                📋 All Teacher Requests (<?= count($subRequests ?? []) ?>)
            </button>
            <button type="button" class="btn-action js-sub-filter-btn" data-status="pending">
                🔴 Pending Allocation
            </button>
            <button type="button" class="btn-action js-sub-filter-btn" data-status="approved_substituted">
                ✓ Substitute Assigned
            </button>
        </div>
        <input type="search" id="js-sub-teacher-search" class="field-input" placeholder="Search teacher or reason..." style="width:230px; padding:0.35rem 0.65rem;">
    </div>

    <?php if (empty($subRequests)): ?>
        <section class="console-card" style="text-align:center; padding:3rem 1.5rem;">
            <h3>No Teacher Leave or Substitute Requests Yet</h3>
            <p class="muted-cell">When any instructor requests a substitute teacher for their batch, their ticket and live chat thread will appear here.</p>
        </section>
    <?php else: ?>
        <!-- 2-Pane Multi-Teacher Split Desk -->
        <div style="display:grid; grid-template-columns: 350px minmax(0, 1fr); gap:1.25rem; align-items:start;">
            <!-- Left Sidebar: All Teacher Request Tickets -->
            <aside class="console-card" style="padding:0.75rem !important; max-height:72vh; overflow-y:auto;">
                <div style="display:flex; flex-direction:column; gap:0.5rem;" id="js-sub-tickets-list">
                    <?php foreach ($subRequests as $req): ?>
                        <?php
                            $rid     = (int) $req['id'];
                            $isSel   = ($activeReq && (int) $activeReq['id'] === $rid);
                            $isPend  = (($req['status'] ?? '') === 'pending');
                            $msgCnt  = count($req['thread'] ?? []);
                            $lastMsg = $msgCnt > 0 ? $req['thread'][$msgCnt - 1]['message_text'] : $req['reason_note'];
                            $srch    = strtolower($req['teacher_name'] . ' ' . $req['substitute_name'] . ' ' . $req['reason_note']);
                        ?>
                        <a href="<?= base_url('addons/lms-timetable/substitutes?req_id=' . $rid) ?>"
                           class="panel-card js-sub-ticket-item"
                           data-status="<?= esc($req['status']) ?>"
                           data-search="<?= esc($srch) ?>"
                           style="text-decoration:none; color:inherit; padding:0.85rem !important; border: <?= $isSel ? '2px solid #10b981' : '1px solid var(--border-subtle)' ?> !important; background: <?= $isSel ? 'rgba(16,185,129,0.1)' : 'var(--bg-canvas)' ?>;">
                            <div class="ltt-flex-between" style="margin-bottom:0.35rem;">
                                <strong>👩‍🏫 <?= esc($req['teacher_name']) ?></strong>
                                <span class="role-badge <?= $isPend ? 'text-amber' : 'text-emerald' ?>">
                                    <?= $isPend ? '⏳ PENDING' : '✓ ASSIGNED' ?>
                                </span>
                            </div>
                            <div class="mono-cell text-cyan" style="font-size:0.76rem; margin-bottom:0.3rem;">
                                <?= count($req['session_ids'] ?? []) ?> Class Session(s) • Sub: <strong><?= esc($req['substitute_name']) ?></strong>
                            </div>
                            <div class="muted-cell" style="font-size:0.8rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                💬 <?= esc($lastMsg) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <!-- Right Stage: Selected Teacher's Request Details, Affected Classes, 1-Click Substitute Assigner & Live Chat -->
            <?php if ($activeReq): ?>
                <section class="console-card" data-req-id="<?= (int) $activeReq['id'] ?>">
                    <div class="card-head ltt-flex-between">
                        <div>
                            <span class="role-badge <?= ($activeReq['status'] ?? '') === 'pending' ? 'text-amber' : 'text-emerald' ?>">
                                <?= ($activeReq['status'] ?? '') === 'pending' ? '⏳ AWAITING SUBSTITUTE TEACHER' : '✓ SUBSTITUTE TEACHER ASSIGNED' ?>
                            </span>
                            <h3 style="margin:0.3rem 0 0;">Request by 👩‍🏫 <?= esc($activeReq['teacher_name']) ?></h3>
                        </div>
                        <div style="text-align:right;">
                            <span class="mono-label">ASSIGNED SUBSTITUTE</span>
                            <div class="identity-name text-cyan"><?= esc($activeReq['substitute_name']) ?></div>
                        </div>
                    </div>

                    <!-- Affected Class Sessions List -->
                    <div class="panel-card" style="margin-bottom:1rem; background:var(--bg-canvas);">
                        <span class="mono-label text-emerald">AFFECTED BATCH SESSIONS REQUESTED FOR COVER:</span>
                        <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.45rem;">
                            <?php foreach (($activeReq['session_ids'] ?? []) as $sid): ?>
                                <?php $sInfo = $sessionsMap[(int) $sid] ?? null; ?>
                                <span class="role-badge">
                                    <?php if ($sInfo): ?>
                                        🏫 <?= esc($sInfo['cohort_title']) ?> — ⏰ <?= esc($sInfo['local_start_display']) ?>
                                    <?php else: ?>
                                        📅 Class Session #<?= (int) $sid ?>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 1-Click Assign Substitute Teacher Bar (Excludes the requesting teacher automatically!) -->
                    <?php if (! empty($canEdit)): ?>
                        <form action="<?= base_url('addons/lms-timetable/assign-substitute') ?>" method="post" class="panel-card js-ajax-sub-assign-form" style="margin-bottom:1rem; border-color:rgba(16,185,129,0.4); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="request_id" value="<?= (int) $activeReq['id'] ?>">
                            <div>
                                <strong class="text-emerald">1-Click Assign Substitute Instructor</strong>
                                <div class="field-hint">Replaces <?= esc($activeReq['teacher_name']) ?> on the above session(s) and notifies them in chat.</div>
                            </div>
                            <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                                <select name="substitute_teacher_id" class="field-select" style="min-width:220px;" required>
                                    <option value="">-- Choose Substitute Teacher --</option>
                                    <?php foreach (($teachers ?? []) as $tid => $tRow): ?>
                                        <?php if ((int) $tid === (int) $activeReq['teacher_user_id']) continue; ?>
                                        <option value="<?= (int) $tid ?>" <?= (int) $activeReq['substitute_teacher_id'] === (int) $tid ? 'selected' : '' ?>>
                                            👩‍🏫 <?= esc($tRow['display_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-primary">✓ Assign Substitute</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <!-- Live Conversation Thread with Selected Teacher -->
                    <span class="mono-label">LIVE CONVERSATION WITH <?= strtoupper(esc($activeReq['teacher_name'])) ?>:</span>
                    <div class="ltt-thread-box" id="js-sub-thread-msgs-<?= (int) $activeReq['id'] ?>" style="max-height:290px;">
                        <?php foreach (($activeReq['thread'] ?? []) as $msg): ?>
                            <div class="ltt-thread-msg <?= ($msg['sender_role'] ?? '') === 'admin' ? 'is-admin-msg' : 'is-teacher-msg' ?>">
                                <strong><?= esc($msg['sender_name']) ?> (<?= strtoupper(esc($msg['sender_role'])) ?>):</strong>
                                <span><?= esc($msg['message_text']) ?></span>
                                <small class="muted-cell"><?= esc(substr((string) $msg['created_at'], 0, 16)) ?> UTC</small>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form action="<?= base_url('addons/lms-timetable/reply-sub-request') ?>" method="post" class="ltt-mini-row js-ajax-sub-reply-form" style="margin-top:0.75rem;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= (int) $activeReq['id'] ?>">
                        <input type="text" name="message_text" class="field-input" placeholder="Reply to <?= esc($activeReq['teacher_name']) ?>..." autocomplete="off" required style="flex:1;">
                        <button type="submit" class="btn-secondary">💬 Send Live Reply</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var filterBar = document.getElementById('js-sub-status-filter');
    var searchInp = document.getElementById('js-sub-teacher-search');
    var currSt    = 'all';

    function filterTickets() {
        var q = searchInp ? searchInp.value.toLowerCase().trim() : '';
        document.querySelectorAll('.js-sub-ticket-item').forEach(function (el) {
            var st  = el.getAttribute('data-status') || '';
            var txt = el.getAttribute('data-search') || '';
            var mSt = (currSt === 'all' || st === currSt);
            var mQ  = (q === '' || txt.indexOf(q) !== -1);
            el.style.display = (mSt && mQ) ? '' : 'none';
        });
    }
    if (filterBar) {
        filterBar.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-sub-filter-btn');
            if (!btn) return;
            filterBar.querySelectorAll('.js-sub-filter-btn').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            currSt = btn.getAttribute('data-status') || 'all';
            filterTickets();
        });
    }
    if (searchInp) searchInp.addEventListener('input', filterTickets);
});
</script>
<?= $this->endSection() ?>

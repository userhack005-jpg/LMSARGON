<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $ap       = $activePair ?? null;
    $readOnly = ! empty($isParentReadOnly);
    $batches  = $batchesFilterMap ?? [];
    $kidsMap  = $parentKidsMap ?? [];
?>
<div class="lcm-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <?php if ($effectiveRole === 'teacher' && count($batches) > 1): ?>
    <div class="lcm-batch-filter-strip" id="js-lcm-batch-filter-bar">
        <span class="mono-label">FILTER STUDENTS BY BATCH:</span>
        <button type="button" class="btn-action is-active js-lcm-batch-pill" data-batch-key="all">
            🏫 All My Batches (<?= count($activePairs ?? []) ?> Students)
        </button>
        <?php foreach ($batches as $bKey => $bInfo): ?>
            <button type="button" class="btn-action js-lcm-batch-pill" data-batch-key="<?= (int) $bKey ?>">
                📚 <?= esc($bInfo['batch_title']) ?> (<?= (int) $bInfo['count'] ?>)
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($readOnly && count($kidsMap) > 1): ?>
    <div class="lcm-batch-filter-strip" id="js-lcm-child-filter-bar">
        <span class="mono-label">FILTER BY CHILD:</span>
        <button type="button" class="btn-action is-active js-lcm-child-pill" data-child-id="all">
            👨‍👩‍👧‍👦 All Children (<?= count($kidsMap) ?>)
        </button>
        <?php foreach ($kidsMap as $kidId => $kidName): ?>
            <button type="button" class="btn-action js-lcm-child-pill" data-child-id="<?= (int) $kidId ?>">
                🎓 <?= esc($kidName) ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($activePairs)): ?>
        <section class="console-card" style="text-align:center; padding:3rem 1.5rem;">
            <h3>No Active Classroom Conversations Available Yet</h3>
            <p class="muted-cell" style="max-width:480px; margin:0.4rem auto 0;">
                1-to-1 classroom chat automatically unlocks between enrolled batch students and their assigned batch teacher.
            </p>
        </section>
    <?php else: ?>
        <div class="lcm-messenger-shell"
             id="js-lcm-messenger-shell"
             data-poll-url="<?= base_url('chat/poll-messages-json') ?>"
             data-send-url="<?= base_url('chat/send-message') ?>"
             data-student-id="<?= (int) ($ap['student_id'] ?? 0) ?>"
             data-teacher-id="<?= (int) ($ap['teacher_id'] ?? 0) ?>"
             data-node-id="<?= (int) ($ap['node_id'] ?? 0) ?>"
             data-cohort-id="<?= (int) ($ap['cohort_id'] ?? 0) ?>"
             data-viewer-role="<?= esc($effectiveRole) ?>">

            <aside class="lcm-contacts-pane">
                <div class="lcm-contacts-search">
                    <input type="search" id="js-lcm-contact-search" class="field-input" placeholder="Search student, teacher or batch...">
                </div>

                <div class="lcm-contacts-list" id="js-lcm-contacts-list">
                    <?php foreach ($activePairs as $p): ?>
                        <?php
                            $isSelected   = ($ap && $ap['pair_key'] === $p['pair_key']);
                            $bKey         = (int) ($p['cohort_id'] ?: $p['node_id']);
                            $primaryTitle = ($effectiveRole === 'teacher')
                                ? $p['student_name']
                                : (($effectiveRole === 'student') ? $p['teacher_name'] : ($p['student_name'] . ' ↔ ' . $p['teacher_name']));
                            $avatarLetter = strtoupper(substr(trim((string) $primaryTitle), 0, 1));
                            $searchStr    = strtolower($p['student_name'] . ' ' . $p['teacher_name'] . ' ' . $p['batch_title']);
                        ?>
                        <a href="<?= base_url('chat/portal?pair=' . urlencode($p['pair_key'])) ?>"
                           class="lcm-contact-item <?= $isSelected ? 'is-active' : '' ?> js-lcm-contact-row"
                           data-batch-key="<?= $bKey ?>"
                           data-child-id="<?= (int) $p['student_id'] ?>"
                           data-search="<?= esc($searchStr) ?>">
                            <span class="lcm-contact-avatar"><?= esc($avatarLetter) ?></span>
                            <div class="lcm-contact-meta">
                                <div class="lcm-contact-name"><?= esc($primaryTitle) ?></div>
                                <div class="lcm-contact-batch">🏫 <?= esc($p['batch_title']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <section class="lcm-chat-stage">
                <?php if ($ap): ?>
                    <header class="lcm-chat-header">
                        <div class="lcm-chat-peer-info">
                            <span class="lcm-contact-avatar is-lg">
                                <?= strtoupper(substr(trim((string) ($effectiveRole === 'teacher' ? $ap['student_name'] : $ap['teacher_name'])), 0, 1)) ?>
                            </span>
                            <div>
                                <h3>
                                    <?php if ($effectiveRole === 'teacher'): ?>
                                        🎓 <?= esc($ap['student_name']) ?>
                                    <?php elseif ($effectiveRole === 'student'): ?>
                                        👩‍🏫 <?= esc($ap['teacher_name']) ?>
                                    <?php else: ?>
                                        🎓 <?= esc($ap['student_name']) ?> &amp; 👩‍🏫 <?= esc($ap['teacher_name']) ?>
                                    <?php endif; ?>
                                </h3>
                                <div class="lcm-chat-subline">
                                    <span class="role-badge text-emerald">🏫 <?= esc($ap['batch_title']) ?></span>
                                    <span class="mono-cell muted-cell">🛡 Guarded 1-to-1 Classroom Channel</span>
                                </div>
                            </div>
                        </div>

                        <div class="action-btn-group">
                            <a href="<?= base_url('chat/feedback') ?>" class="btn-secondary btn-sm">⭐ Teacher Feedback</a>
                        </div>
                    </header>

                    <div class="lcm-messages-box" id="js-lcm-messages-box">
                        <?php if (empty($messages)): ?>
                            <div class="lcm-empty-chat">
                                <p>No messages in this batch conversation yet. Say hello to start!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $m): ?>
                                <?php
                                    $isMine = ((int) $m['sender_user_id'] === (int) ($activeStuId > 0 ? $activeStuId : $uid))
                                        || ($effectiveRole === 'teacher' && $m['sender_role'] === 'teacher')
                                        || ($effectiveRole === 'student' && $m['sender_role'] === 'student');
                                ?>
                                <div class="lcm-msg-row <?= $isMine ? 'is-mine' : 'is-peer' ?>" data-msg-id="<?= (int) $m['id'] ?>">
                                    <div class="lcm-msg-bubble">
                                        <div class="lcm-msg-sender"><?= esc($m['sender_name']) ?></div>
                                        <div class="lcm-msg-text"><?= esc($m['message_body']) ?></div>
                                        <div class="lcm-msg-time"><?= esc($m['time_short']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($readOnly): ?>
                        <div class="lcm-readonly-footer">
                            <span>👁 <strong>Parent Read-Only Transcript View:</strong> You are viewing your child's classroom conversation with their batch teacher.</span>
                        </div>
                    <?php else: ?>
                        <form action="<?= base_url('chat/send-message') ?>" method="post" class="lcm-composer-bar" id="js-lcm-send-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="student_user_id" value="<?= (int) $ap['student_id'] ?>">
                            <input type="hidden" name="teacher_user_id" value="<?= (int) $ap['teacher_id'] ?>">
                            <input type="hidden" name="node_id" value="<?= (int) $ap['node_id'] ?>">
                            <input type="hidden" name="cohort_id" value="<?= (int) $ap['cohort_id'] ?>">
                            <input type="text"
                                   name="message_body"
                                   id="js-lcm-msg-input"
                                   class="field-input"
                                   placeholder="Type your message (Phone numbers, emails & external links are filtered)..."
                                   autocomplete="off"
                                   required>
                            <button type="submit" class="btn-primary" id="js-lcm-send-btn">Send ➤</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

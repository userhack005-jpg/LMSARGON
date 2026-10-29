<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $canSubmitFeedback = in_array($effectiveRole, ['student', 'parent'], true) && ! empty($pairs);
?>
<div class="lcm-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <div class="grid-3-col" style="margin-bottom: 1.5rem;">
        <div class="panel-card">
            <span class="mono-label text-emerald">TOTAL FEEDBACK CARDS</span>
            <h2 class="text-emerald" style="margin:0.3rem 0 0;"><?= (int) ($totalFeedback ?? 0) ?> Reviews</h2>
            <p class="muted-cell" style="margin:0.2rem 0 0; font-size:0.8rem;">1-to-1 Enrolled Batch Verified</p>
        </div>
        <div class="panel-card">
            <span class="mono-label text-cyan">AVERAGE TEACHER RATING</span>
            <h2 class="text-cyan" style="margin:0.3rem 0 0;">★ <?= number_format((float) ($avgStars ?? 5.0), 1) ?> / 5.0</h2>
            <p class="muted-cell" style="margin:0.2rem 0 0; font-size:0.8rem;">Immutable &amp; Non-Editable Records</p>
        </div>
        <div class="panel-card">
            <span class="mono-label">POSITIVE SENTIMENT RATIO</span>
            <h2 class="text-emerald" style="margin:0.3rem 0 0;"><?= number_format((float) ($positivePct ?? 100), 1) ?>% Positive</h2>
            <p class="muted-cell" style="margin:0.2rem 0 0; font-size:0.8rem;">Visible to Student, Parent, Teacher &amp; Admin</p>
        </div>
    </div>

    <div class="<?= $canSubmitFeedback ? 'lcm-feedback-layout' : '' ?>">
        <?php if ($canSubmitFeedback): ?>
        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="role-badge text-emerald">⭐ 1-TO-1 BATCH TEACHER FEEDBACK</span>
                    <h3 style="margin-top:0.35rem;">Share Feedback for Your Batch Teacher</h3>
                    <p class="field-hint" style="margin:0;">Once submitted, feedback is permanently locked and cannot be edited.</p>
                </div>
            </div>

            <form action="<?= base_url('chat/submit-feedback') ?>" method="post" id="js-lcm-feedback-form" onsubmit="return confirm('Submit this feedback? Please note that feedback is permanent and cannot be edited once submitted.');">
                <?= csrf_field() ?>

                <div class="field-group">
                    <label class="field-label"><span>Select Enrolled Batch &amp; Teacher *</span></label>
                    <select id="js-fb-pair-picker" class="field-select" required>
                        <?php foreach ($pairs as $idx => $p): ?>
                            <option value="<?= $idx ?>"
                                    data-stu="<?= (int) $p['student_id'] ?>"
                                    data-tchr="<?= (int) $p['teacher_id'] ?>"
                                    data-node="<?= (int) $p['node_id'] ?>"
                                    data-cohort="<?= (int) $p['cohort_id'] ?>"
                                    data-batch="<?= esc($p['batch_title']) ?>">
                                👩‍🏫 <?= esc($p['teacher_name']) ?> — 🏫 <?= esc($p['batch_title']) ?> (🎓 <?= esc($p['student_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php $firstP = $pairs[0]; ?>
                    <input type="hidden" name="student_user_id" id="fb_stu_id" value="<?= (int) $firstP['student_id'] ?>">
                    <input type="hidden" name="teacher_user_id" id="fb_tchr_id" value="<?= (int) $firstP['teacher_id'] ?>">
                    <input type="hidden" name="node_id" id="fb_node_id" value="<?= (int) $firstP['node_id'] ?>">
                    <input type="hidden" name="cohort_id" id="fb_cohort_id" value="<?= (int) $firstP['cohort_id'] ?>">
                    <input type="hidden" name="batch_name" id="fb_batch_name" value="<?= esc($firstP['batch_title']) ?>">
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Feedback Type *</span></label>
                        <select name="sentiment_type" class="field-select">
                            <option value="positive">🌟 Positive Appreciation</option>
                            <option value="constructive">💡 Constructive / Improvement Needed</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label"><span>Star Rating *</span></label>
                        <select name="rating_stars" class="field-select">
                            <option value="5">★★★★★ (5/5 — Excellent)</option>
                            <option value="4">★★★★☆ (4/5 — Very Good)</option>
                            <option value="3">★★★☆☆ (3/5 — Average)</option>
                            <option value="2">★★☆☆☆ (2/5 — Needs Improvement)</option>
                            <option value="1">★☆☆☆☆ (1/5 — Dissatisfied)</option>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Feedback Area</span></label>
                    <select name="category_tag" class="field-select">
                        <option value="Concept Clarity & Teaching">Concept Clarity &amp; Teaching Style</option>
                        <option value="Doubt Solving & Patience">Doubt Solving &amp; Patience with Child</option>
                        <option value="Speed Math & Abacus Drills">Speed Math &amp; Abacus Practice Drills</option>
                        <option value="Punctuality & Class Engagement">Punctuality &amp; Class Engagement</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Your Detailed Feedback *</span></label>
                    <textarea name="feedback_text" class="field-input" rows="3" placeholder="Share your experience with the teacher for this batch..." required></textarea>
                </div>

                <button type="submit" class="btn-primary btn-full">🔒 Submit Permanent Feedback Card</button>
            </form>
        </section>
        <?php endif; ?>

        <div>
            <?php if (empty($feedbackCards)): ?>
                <section class="console-card" style="text-align:center; padding:3rem 1.5rem;">
                    <h3>No Teacher Feedback Cards Submitted Yet</h3>
                    <p class="muted-cell">Feedback cards submitted by enrolled batch students will appear here.</p>
                </section>
            <?php else: ?>
                <div class="lcm-feedback-cards-grid">
                    <?php foreach ($feedbackCards as $fc): ?>
                        <?php
                            $isPos = (($fc['sentiment_type'] ?? 'positive') === 'positive');
                            $stars = max(1, min(5, (int) ($fc['rating_stars'] ?? 5)));
                        ?>
                        <article class="lcm-feedback-card <?= $isPos ? 'is-positive' : 'is-constructive' ?>">
                            <div class="lcm-fc-top">
                                <div class="lcm-fc-ident">
                                    <span class="lcm-contact-avatar">
                                        <?= strtoupper(substr(trim((string) $fc['student_name']), 0, 1)) ?>
                                    </span>
                                    <div>
                                        <strong>🎓 <?= esc($fc['student_name']) ?></strong>
                                        <div class="mono-cell text-cyan">For: 👩‍🏫 <?= esc($fc['teacher_name']) ?></div>
                                    </div>
                                </div>

                                <span class="role-badge <?= $isPos ? 'text-emerald' : 'text-amber' ?>">
                                    <?= $isPos ? '🌟 Positive' : '💡 Constructive' ?>
                                </span>
                            </div>

                            <div class="lcm-fc-batch-bar">
                                <span class="mono-cell">🏫 Batch: <strong><?= esc($fc['batch_name_snapshot'] ?: 'Enrolled Batch') ?></strong></span>
                                <span class="lcm-stars-pill"><?= str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) ?></span>
                            </div>

                            <p class="lcm-fc-quote">“<?= esc($fc['feedback_text']) ?>”</p>

                            <div class="lcm-fc-footer">
                                <span class="role-badge"><?= esc($fc['category_tag']) ?></span>
                                <span class="mono-cell muted-cell">🔒 Locked • <?= esc(substr((string) $fc['created_at'], 0, 10)) ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

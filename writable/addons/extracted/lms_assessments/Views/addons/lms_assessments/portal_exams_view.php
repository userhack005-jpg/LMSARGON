<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="las-portal-wrapper">
    <?php if (! empty($canManageAdmin)): ?>
        <?php include __DIR__ . '/partials/admin_nav.php'; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/portal_nav.php'; ?>

    <?php if ($selectedExam && $activeAttempt): ?>
        <!-- =================================================================
             ACTIVE EXAM RUNNER (Only opens AFTER Student clicks "Start Exam Now"!)
             ================================================================= -->
        <?php
            $remSec    = (int) ($activeAttempt['remaining_seconds'] ?? 300);
            $totSec    = (int) ($activeAttempt['total_seconds'] ?? 300);
            $attemptId = (int) ($activeAttempt['attempt_id'] ?? 0);
            $lockedQs  = $activeAttempt['questions'] ?? [];
            $draftAns  = $activeAttempt['draft_answers'] ?? [];
            $initMins  = floor($remSec / 60);
            $initSecs  = $remSec % 60;
        ?>
        <section class="console-card" style="max-width: 860px; margin: 0 auto;">
            <div class="card-head las-flex-between">
                <div>
                    <span class="role-badge text-emerald"><?= ! empty($activeAttempt['is_resumed']) ? '⏱ Resumed Session' : '🎯 Live Assessment Active' ?></span>
                    <h3 style="margin:0.3rem 0 0;"><?= esc($selectedExam['title']) ?></h3>
                </div>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div class="las-timer-badge"
                         id="js-las-timer"
                         data-remaining-seconds="<?= $remSec ?>"
                         data-total-seconds="<?= $totSec ?>"
                         data-attempt-id="<?= $attemptId ?>"
                         data-draft-url="<?= base_url('assessments/save-draft-answers') ?>">
                        ⏱ <?= sprintf('%02d:%02d', $initMins, $initSecs) ?>
                    </div>
                    <a href="<?= base_url('assessments/portal') ?>" class="btn-action" style="text-decoration:none;">← All Exams</a>
                </div>
            </div>

            <div id="js-las-instant-result" class="alert-banner is-hidden" style="margin-bottom: 1rem;"></div>

            <?php if (empty($lockedQs)): ?>
                <p class="muted-cell">No questions have been added to this course's Question Bank yet.</p>
            <?php else: ?>
                <form action="<?= base_url('assessments/submit-attempt') ?>" method="post" id="js-las-exam-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
                    <input type="hidden" name="exam_id" value="<?= (int) $selectedExam['id'] ?>">
                    <input type="hidden" name="student_user_id" value="<?= (int) $activeStuId ?>">
                    <input type="hidden" name="time_taken_seconds" id="js-las-time-taken" value="<?= max(1, $totSec - $remSec) ?>">

                    <div class="las-questions-stack">
                        <?php foreach ($lockedQs as $qIdx => $qObj): ?>
                            <?php $savedVal = isset($draftAns[$qIdx]) ? (string) $draftAns[$qIdx] : ''; ?>
                            <div class="panel-card las-q-card">
                                <div class="identity-name las-q-title">
                                    Q<?= $qIdx + 1 ?>. <?= esc($qObj['q'] ?? '') ?>
                                </div>

                                <?php if (! empty($qObj['options']) && is_array($qObj['options'])): ?>
                                    <div class="las-opts-grid">
                                        <?php foreach ($qObj['options'] as $optVal): ?>
                                            <label class="las-opt-pill">
                                                <input type="radio"
                                                       name="answers[<?= $qIdx ?>]"
                                                       value="<?= esc($optVal) ?>"
                                                       class="js-las-ans-input"
                                                       <?= ($savedVal !== '' && $savedVal === (string) $optVal) ? 'checked' : '' ?>
                                                       required>
                                                <span><?= esc($optVal) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <input type="text"
                                           name="answers[<?= $qIdx ?>]"
                                           value="<?= esc($savedVal) ?>"
                                           class="field-input js-las-ans-input"
                                           placeholder="Type your answer..."
                                           required>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="js-las-submit-actions" style="margin-top:1.35rem; display:flex; gap:0.75rem; flex-wrap:wrap;">
                        <button type="submit" class="btn-primary" id="js-las-submit-btn" style="flex:1; justify-content:center; padding:0.8rem;">
                            🚀 Submit Answers &amp; View Instant Score →
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

    <?php else: ?>
        <!-- =================================================================
             EXAM & PRACTICE DRILL LAUNCHER CARDS (Explicit "▶ Start Now" Button!)
             ================================================================= -->
        <?php if (empty($studentExams)): ?>
            <section class="console-card" style="text-align:center; padding:2.5rem;">
                <h3>No Practice Drills or Exams Assigned Yet</h3>
                <p class="muted-cell">Once your instructor publishes practice sets or exams for your course, they will appear here.</p>
            </section>
        <?php else: ?>
            <div class="las-cards-grid">
                <?php foreach ($studentExams as $ex): ?>
                    <?php
                        $eid        = (int) $ex['id'];
                        $isUnlocked = ! empty($ex['is_unlocked']);
                        $hasActive  = ! empty($ex['has_active_attempt']);
                        $isCertExam = ((int) ($ex['award_certificate'] ?? 0) === 1);
                    ?>
                    <article class="las-modern-card">
                        <div>
                            <div class="las-flex-between" style="margin-bottom:0.5rem;">
                                <span class="role-badge <?= $isCertExam ? 'text-emerald' : 'text-cyan' ?>">
                                    <?= $isCertExam ? '🏆 Certification Exam' : '⚡ Speed Practice Drill' ?>
                                </span>
                                <span class="mono-cell">⏱ <?= (int) $ex['duration_minutes'] ?> Mins</span>
                            </div>
                            <h3 style="margin:0.25rem 0 0.35rem;"><?= esc($ex['title']) ?></h3>
                            <div class="mono-cell muted-cell"><?= esc($ex['node_title']) ?></div>

                            <div class="las-card-specs">
                                <div>
                                    <span class="mono-label">QUESTIONS</span>
                                    <strong><?= (int) ($ex['question_count'] ?? 5) ?> Qs</strong>
                                </div>
                                <div>
                                    <span class="mono-label">PASS SCORE</span>
                                    <strong><?= number_format((float) $ex['pass_percentage'], 0) ?>%</strong>
                                </div>
                                <div>
                                    <span class="mono-label">MODE</span>
                                    <strong><?= ($ex['selection_mode'] ?? 'random_from_bank') === 'random_from_bank' ? 'Randomized' : 'Standard' ?></strong>
                                </div>
                            </div>
                        </div>

                        <div>
                            <?php if ($isUnlocked): ?>
                                <form action="<?= base_url('assessments/start-attempt') ?>" method="post" style="margin:0;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="exam_id" value="<?= $eid ?>">
                                    <button type="submit" class="btn-primary btn-full" style="justify-content:center;">
                                        <?= $hasActive ? '⏱ Resume Active Exam →' : '▶ Start Exam / Practice Now →' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="panel-card" style="padding:0.65rem; text-align:center; opacity:0.75;">
                                    <span class="mono-cell text-amber">🔒 <?= esc($ex['lock_reason']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

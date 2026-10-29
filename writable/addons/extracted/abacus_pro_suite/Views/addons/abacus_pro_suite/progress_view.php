<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $prog         = $progressData ?? [];
    $badges       = $prog['badges'] ?? [];
    $attempts     = $prog['attempts'] ?? [];
    $kidsMap      = $parentKidsMap ?? [];
    $hasMultiKids = ! empty($isParentOverview) && count($kidsMap) > 1;
?>
<div class="aps-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <?php if ($hasMultiKids): ?>
    <div class="aps-compact-bar" id="js-aps-child-filter-bar">
        <span class="mono-label">FILTER PROGRESS BY CHILD:</span>
        <div class="action-btn-group">
            <button type="button" class="btn-action is-active js-aps-child-tab" data-child-id="all">
                👨‍👩‍👧‍👦 All Children (<?= count($kidsMap) ?>)
            </button>
            <?php foreach ($kidsMap as $kidId => $kidName): ?>
                <button type="button" class="btn-action js-aps-child-tab" data-child-id="<?= (int) $kidId ?>">
                    🎓 <?= esc($kidName) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top Speed & Mastery KPIs -->
    <div class="grid-4-col" style="margin-bottom: 1.5rem;">
        <div class="panel-card">
            <span class="mono-label text-emerald">COMPLETED DRILLS</span>
            <h2 class="text-emerald" style="margin:0.3rem 0 0;"><?= (int) ($prog['total_drills'] ?? 0) ?> Sets</h2>
        </div>
        <div class="panel-card">
            <span class="mono-label text-cyan">AVERAGE ACCURACY</span>
            <h2 class="text-cyan" style="margin:0.3rem 0 0;"><?= number_format((float) ($prog['avg_accuracy'] ?? 0), 1) ?>%</h2>
        </div>
        <div class="panel-card">
            <span class="mono-label">FASTEST FLASH SPEED</span>
            <h2 class="text-emerald" style="margin:0.3rem 0 0;">
                <?= (float) ($prog['best_speed'] ?? 0) > 0 ? (number_format((float) $prog['best_speed'], 2) . 's / Row') : '—' ?>
            </h2>
        </div>
        <div class="panel-card">
            <span class="mono-label">PERFECT 100% SCORES</span>
            <h2 class="text-cyan" style="margin:0.3rem 0 0;">🏆 <?= (int) ($prog['perfect_cnt'] ?? 0) ?></h2>
        </div>
    </div>

    <!-- Gamified Achievement Badges Grid -->
    <div class="grid-4-col" style="margin-bottom: 1.75rem;">
        <?php foreach ($badges as $b): ?>
            <?php $u = ! empty($b['unlocked']); ?>
            <div class="aps-badge-card <?= $u ? 'is-unlocked' : 'is-locked' ?>">
                <div class="aps-badge-icon"><?= $b['icon'] ?></div>
                <div>
                    <div class="identity-name"><?= esc($b['title']) ?></div>
                    <p class="muted-cell" style="font-size:0.78rem; margin:0.2rem 0 0.45rem;"><?= esc($b['desc']) ?></p>
                    <span class="role-badge <?= $u ? 'text-emerald' : '' ?>">
                        <?= $u ? '✓ UNLOCKED' : '🔒 LOCKED' ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Practice Attempts Table -->
    <section class="console-card">
        <div class="card-head aps-flex-between">
            <div>
                <h3>Recent Abacus &amp; Flash Anzan Practice Log</h3>
            </div>
            <a href="<?= base_url('abacus/anzan') ?>" class="btn-primary btn-sm">⚡ Start New Speed Drill →</a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <?php if (! empty($isParentOverview)): ?>
                            <th>Child</th>
                        <?php endif; ?>
                        <th>Practice Mode</th>
                        <th>Difficulty &amp; Speed</th>
                        <th>Accuracy Score</th>
                        <th>Time Taken</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attempts)): ?>
                        <tr><td colspan="<?= ! empty($isParentOverview) ? '6' : '5' ?>" class="muted-cell" style="text-align:center; padding:2rem;">No practice drills recorded yet. Start a Soroban Challenge or Flash Anzan drill!</td></tr>
                    <?php else: ?>
                        <?php foreach ($attempts as $at): ?>
                            <tr class="js-aps-child-row" data-child-id="<?= (int) $at['student_user_id'] ?>">
                                <?php if (! empty($isParentOverview)): ?>
                                    <td class="identity-name">🎓 <?= esc($at['student_name']) ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="role-badge text-emerald"><?= strtoupper(str_replace('_', ' ', esc($at['drill_mode']))) ?></span>
                                </td>
                                <td class="mono-cell">
                                    <?= (int) $at['digits_count'] ?>-Digit • <?= (int) $at['rows_count'] ?> Rows @ <?= number_format((float) $at['speed_seconds'], 1) ?>s
                                </td>
                                <td class="mono-cell text-cyan">
                                    <strong><?= number_format((float) $at['accuracy_pct'], 1) ?>%</strong>
                                </td>
                                <td class="mono-cell"><?= (int) $at['time_taken_sec'] ?>s</td>
                                <td class="mono-cell muted-cell"><?= esc(substr((string) $at['created_at'], 0, 16)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lhr-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <section class="console-card">
        <div class="card-head lhr-flex-between">
            <div>
                <h3>Courses &amp; Curriculum Levels</h3>
                <p class="field-hint" style="margin:0;">Manage your programs, sub-levels, and enrollable courses. Live batch teachers and schedules are managed in the Timetable module.</p>
            </div>
            <?php if (! empty($canCreate)): ?>
                <a href="<?= base_url('addons/lms-hierarchy/builder') ?>" class="btn-primary btn-sm">+ Add New Course / Level</a>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Course / Level Title</th>
                        <th>Type</th>
                        <th>Display Order</th>
                        <th>Fee Plans</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($nodesById)): ?>
                        <tr><td colspan="5" class="muted-cell">No courses created yet. Click "+ Add New Course / Level" above.</td></tr>
                    <?php else: ?>
                        <?php foreach ($nodesById as $nid => $node): ?>
                            <?php
                                $isLeaf = ((int) ($node['is_leaf_node'] ?? 0) === 1);
                                $depth  = (int) ($node['level_depth'] ?? 1);
                                $indent = max(0, ($depth - 1) * 20);
                                $prices = $node['pricing_by_country'] ?? [];
                            ?>
                            <tr>
                                <td style="padding-left: <?= 14 + $indent ?>px;">
                                    <div style="display:flex; align-items:center; gap:0.65rem;">
                                        <?php if (! empty($node['resolved_thumbnail'])): ?>
                                            <img src="<?= esc($node['resolved_thumbnail']) ?>" alt="Thumb" class="lhr-tree-thumb" loading="lazy">
                                        <?php else: ?>
                                            <div class="lhr-tree-icon"><?= $isLeaf ? '🎓' : '📂' ?></div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="identity-name"><?= esc($node['title']) ?></div>
                                            <div class="mono-cell muted-cell"><?= esc($node['breadcrumb_path']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isLeaf): ?>
                                        <span class="role-badge text-emerald">🎓 Enrollable Course</span>
                                    <?php else: ?>
                                        <span class="role-badge">📂 Program Category (<?= count($node['children_ids'] ?? []) ?> sub-levels)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="mono-cell"><?= (int) ($node['sort_order'] ?? 10) ?></td>
                                <td>
                                    <?php if ($isLeaf): ?>
                                        <a href="<?= base_url('addons/lms-hierarchy/pricing?node_id=' . (int) $nid) ?>" class="btn-action text-cyan" style="text-decoration:none;">
                                            💳 <?= count($prices) ?> Country Plans →
                                        </a>
                                    <?php else: ?>
                                        <span class="muted-cell">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="<?= base_url('courses/view/' . (int) $nid) ?>" target="_blank" class="btn-action" style="text-decoration:none;">↗ View Page</a>
                                        <a href="<?= base_url('addons/lms-hierarchy/builder?edit_id=' . (int) $nid) ?>" class="btn-action" style="text-decoration:none;">✎ Edit</a>
                                        <?php if (! $isLeaf): ?>
                                            <a href="<?= base_url('addons/lms-hierarchy/builder?parent_id=' . (int) $nid) ?>" class="btn-action text-emerald" style="text-decoration:none;">+ Sub-Level</a>
                                        <?php endif; ?>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= base_url('addons/lms-hierarchy/delete-node') ?>" method="post" style="display:inline;" onsubmit="return confirm('Delete this course level?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $nid ?>">
                                                <button type="submit" class="btn-action text-danger">✕</button>
                                            </form>
                                        <?php endif; ?>
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

<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $defaultLeafId = ! empty($leafBatchesMap) ? (int) array_key_first($leafBatchesMap) : 15;
?>
<div class="lhr-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <!-- Customizable HTML Block & Shortcode Embed Builder -->
    <section class="console-card lhr-shortcode-strip" style="margin-bottom: 1.25rem;">
        <div class="lhr-flex-between">
            <div>
                <span class="lhr-badge lhr-badge-cyan">⚡ CUSTOMIZABLE COURSE PRODUCT EMBEDDER</span>
                <h4 style="margin:0.3rem 0 0.15rem;">Embed Course Cards Anywhere in Page Builder (Split 50/50 Box, Grid Card, or Row)</h4>
                <p class="field-hint" style="margin:0;">Enter one ID (e.g. <code><?= $defaultLeafId ?></code>) or comma-separated IDs (e.g. <code>14,15,17</code>) and choose your preferred card design below.</p>
            </div>
            <div class="lhr-embed-controls">
                <input type="text" id="js-lhr-custom-ids" class="field-input mono-input" value="<?= $defaultLeafId ?>" placeholder="IDs e.g. 14,15" style="width:120px; height:34px;">
                <select id="js-lhr-custom-layout" class="field-select" style="width:auto; min-width:225px; height:34px; padding:0 0.65rem; font-size:0.79rem;">
                    <option value="split" selected>🖼️ Split Card (Half Image + Half Content)</option>
                    <option value="grid">🃏 Vertical Grid Card (Image Top + Content Below)</option>
                    <option value="row">📏 Compact Full-Width Row Strip</option>
                </select>
                <button type="button" class="lhr-btn lhr-btn-cyan lhr-btn-sm" id="js-lhr-copy-shortcode-btn">📋 Copy Shortcode</button>
                <button type="button" class="lhr-btn lhr-btn-emerald lhr-btn-sm" id="js-lhr-copy-html-btn">&lt;/&gt; Copy HTML Block</button>
            </div>
        </div>
        <div class="lhr-shortcode-preview-row">
            <div><small class="muted-cell">Shortcode:</small> <code id="js-lhr-sc-preview">&#91;lms_course ids="<?= $defaultLeafId ?>" layout="split"&#93;</code></div>
            <div><small class="muted-cell">Raw HTML Block:</small> <code id="js-lhr-html-preview">&lt;div class="lhr-embed-courses" data-ids="<?= $defaultLeafId ?>" data-layout="split"&gt;&lt;/div&gt;</code></div>
        </div>
    </section>

    <section class="console-card">
        <div class="card-head lhr-flex-between">
            <div>
                <h3>Courses &amp; Curriculum Levels (<span id="js-lhr-tree-cnt"><?= count($nodesById ?? []) ?></span>)</h3>
                <p class="field-hint" style="margin:0;">Manage programs, sub-levels, fee plans, and 1-click copy embed codes for any course.</p>
            </div>
            <div class="lhr-action-bar">
                <div class="lhr-filter-pills" id="js-lhr-tree-tabs">
                    <button type="button" class="lhr-filter-tab is-active" data-type="all">All (<?= count($nodesById ?? []) ?>)</button>
                    <button type="button" class="lhr-filter-tab" data-type="leaf">🎓 Enrollable (<?= count($leafBatchesMap ?? []) ?>)</button>
                    <button type="button" class="lhr-filter-tab" data-type="category">📂 Categories</button>
                </div>
                <?php if (! empty($canCreate)): ?>
                    <a href="<?= base_url('addons/lms-hierarchy/builder') ?>" class="lhr-btn lhr-btn-emerald lhr-btn-sm">+ Add New Course</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="lhr-toolbar-strip">
            <div class="lhr-toolbar-left">
                <input type="search" id="js-lhr-tree-search" class="lhr-search-input" placeholder="🔍 Instant search by ID, course title, or breadcrumb path...">
            </div>
            <div class="lhr-toolbar-right">
                <label class="mono-cell muted-cell">Rows:</label>
                <select id="js-lhr-tree-pagesize" class="lhr-pagesize-select">
                    <option value="10">10 / page</option>
                    <option value="25" selected>25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="9999">All</option>
                </select>
                <button type="button" id="js-lhr-tree-csv" class="lhr-btn lhr-btn-outline lhr-btn-sm">⬇ CSV</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table" id="js-lhr-tree-table">
                <thead>
                    <tr>
                        <th style="width:64px;">ID</th>
                        <th>Course / Level Title</th>
                        <th>Type &amp; Demo Status</th>
                        <th>Quick Embed Copy</th>
                        <th>Fee Plans</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($nodesById)): ?>
                        <tr><td colspan="6" class="muted-cell" style="text-align:center; padding:2rem;">No courses created yet. Click "+ Add New Course" above.</td></tr>
                    <?php else: ?>
                        <?php foreach ($nodesById as $nid => $node): ?>
                            <?php
                                $idInt     = (int) $nid;
                                $isLeaf    = ((int) ($node['is_leaf_node'] ?? 0) === 1);
                                $allowDemo = ((int) ($node['allow_demo_booking'] ?? 0) === 1);
                                $depth     = (int) ($node['level_depth'] ?? 1);
                                $indent    = max(0, ($depth - 1) * 16);
                                $prices    = $node['pricing_by_country'] ?? [];
                            ?>
                            <tr class="js-lhr-tree-row"
                                data-type="<?= $isLeaf ? 'leaf' : 'category' ?>"
                                data-csv-id="<?= $idInt ?>"
                                data-csv-title="<?= esc($node['title']) ?>"
                                data-csv-path="<?= esc($node['breadcrumb_path']) ?>"
                                data-csv-type="<?= $isLeaf ? 'Enrollable Course' : 'Program Category' ?>">
                                <td class="mono-cell">
                                    <span class="lhr-badge lhr-badge-info">#<?= $idInt ?></span>
                                </td>
                                <td class="lhr-indent-cell" style="--lhr-indent: <?= $indent ?>px;">
                                    <div class="lhr-course-row-Identity">
                                        <?php if (! empty($node['resolved_thumbnail'])): ?>
                                            <img src="<?= esc($node['resolved_thumbnail']) ?>" alt="Thumb" class="lhr-tree-thumb" loading="lazy" decoding="async">
                                        <?php else: ?>
                                            <div class="lhr-tree-icon"><?= $isLeaf ? '🎓' : '📂' ?></div>
                                        <?php endif; ?>
                                        <div style="min-width:0;">
                                            <div class="identity-name"><?= esc($node['title']) ?></div>
                                            <div class="mono-cell muted-cell" style="white-space:normal;"><?= esc($node['breadcrumb_path']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="lhr-action-bar">
                                        <?php if ($isLeaf): ?>
                                            <span class="lhr-badge lhr-badge-emerald">🎓 Enrollable</span>
                                            <?php if ($allowDemo): ?>
                                                <span class="lhr-badge lhr-badge-cyan">🎯 Demo ON</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="lhr-badge lhr-badge-info">📂 Category (<?= count($node['children_ids'] ?? []) ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="lhr-action-bar">
                                        <button type="button" class="lhr-btn lhr-btn-cyan lhr-btn-sm js-lhr-row-copy-sc" data-node-id="<?= $idInt ?>" title="Copy Shortcode for ID #<?= $idInt ?>">
                                            📋 Shortcode
                                        </button>
                                        <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-row-copy-html" data-node-id="<?= $idInt ?>" title="Copy HTML Block for ID #<?= $idInt ?>">
                                            &lt;/&gt; HTML
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isLeaf): ?>
                                        <a href="<?= base_url('addons/lms-hierarchy/pricing?node_id=' . $idInt) ?>" class="lhr-btn lhr-btn-outline lhr-btn-sm text-cyan">
                                            💳 <?= count($prices) ?> Plans →
                                        </a>
                                    <?php else: ?>
                                        <span class="muted-cell">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="lhr-action-bar">
                                        <a href="<?= base_url('courses/view/' . $idInt) ?>" target="_blank" class="lhr-btn lhr-btn-outline lhr-btn-sm">↗ View</a>
                                        <a href="<?= base_url('addons/lms-hierarchy/builder?edit_id=' . $idInt) ?>" class="lhr-btn lhr-btn-outline lhr-btn-sm">✎ Edit</a>
                                        <?php if (! $isLeaf): ?>
                                            <a href="<?= base_url('addons/lms-hierarchy/builder?parent_id=' . $idInt) ?>" class="lhr-btn lhr-btn-emerald lhr-btn-sm">+ Sub-Level</a>
                                        <?php endif; ?>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= base_url('addons/lms-hierarchy/delete-node') ?>" method="post" onsubmit="return confirm('Delete this course level?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $idInt ?>">
                                                <button type="submit" class="lhr-btn lhr-btn-rose lhr-btn-sm">✕</button>
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

        <div class="lhr-pagination-bar" id="js-lhr-tree-pager"></div>
    </section>
</div>
<?= $this->endSection() ?>

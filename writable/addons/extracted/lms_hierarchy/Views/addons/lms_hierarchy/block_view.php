<?php
    $hd        = $data ?? [];
    $modelFile = WRITEPATH . 'addons/extracted/lms_hierarchy/Models/Addons/LmsHierarchy/LmsHierarchyModel.php';
    if (! class_exists(\App\Models\Addons\LmsHierarchy\LmsHierarchyModel::class) && is_file($modelFile)) {
        require_once $modelFile;
    }

    $hrModel   = new \App\Models\Addons\LmsHierarchy\LmsHierarchyModel();
    $treeData  = $hrModel->getFullHierarchyData();
    $nodesById = $treeData['nodes_by_id'] ?? [];
    $defCode   = $hrModel->detectVisitorCountryCode();

    $displayMode    = trim((string) ($hd['display_mode'] ?? 'drilldown_tree')) ?: 'drilldown_tree';
    $cardLayout     = trim((string) ($hd['card_layout'] ?? 'split')) ?: 'split';
    $selectedIdsRaw = trim((string) ($hd['selected_node_ids'] ?? ''));
    $showDemoBadge  = ((string) ($hd['show_demo_badge'] ?? '1')) !== '0';
?>
<link rel="stylesheet" href="<?= base_url('assets/addons/lms_hierarchy/lms_hierarchy.css?v=8.5') ?>">
<script src="<?= base_url('assets/addons/lms_hierarchy/lms_hierarchy.js?v=8.5') ?>" defer></script>

<section class="<?= esc($blockClass ?? 'cms-block') ?> <?= esc($blockScopeClass ?? '') ?> lhr-explorer-block is-revealed"
         data-default-country="<?= esc($defCode) ?>"
         data-detect-country-url="<?= base_url('addons/lms-hierarchy/detect-country-json') ?>"
         data-course-view-url="<?= base_url('courses/view') ?>"
         data-demo-book-url="<?= base_url('demo/book') ?>"
         data-enroll-url="<?= base_url('finance/enroll-checkout') ?>"
         data-display-mode="<?= esc($displayMode) ?>"
         data-card-layout="<?= esc($cardLayout) ?>"
         data-selected-ids="<?= esc($selectedIdsRaw) ?>"
         data-show-demo="<?= $showDemoBadge ? '1' : '0' ?>">

    <div class="cms-inner-container">
        <?php if ($displayMode !== 'direct_products' && (! empty($hd['heading']) || ! empty($hd['badge']))): ?>
        <div class="cms-section-head" style="text-align:center; margin-bottom:1.5rem;">
            <?php if (! empty($hd['badge'])): ?>
                <div class="status-pill"><span class="pulse-dot"></span><span class="mono-label"><?= esc($hd['badge']) ?></span></div>
            <?php endif; ?>
            <?php if (! empty($hd['heading'])): ?>
                <h2><?= esc($hd['heading']) ?></h2>
            <?php endif; ?>
            <?php if (! empty($hd['subheading'])): ?>
                <p class="muted-cell"><?= esc($hd['subheading']) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="lhr-breadcrumb-bar is-hidden js-lhr-breadcrumb-bar"></div>
        <div class="lhr-cards-auto-grid lhr-cards-stage js-lhr-cards-stage"></div>

        <script type="application/json" class="js-lhr-tree-payload"><?= json_encode(array_values($nodesById), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    </div>
</section>

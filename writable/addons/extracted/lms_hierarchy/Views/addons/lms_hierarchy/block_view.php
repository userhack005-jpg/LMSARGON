<?php
    $hd        = $data ?? [];
    $modelFile = WRITEPATH . 'addons/extracted/lms_hierarchy/Models/Addons/LmsHierarchy/LmsHierarchyModel.php';
    if (! class_exists(\App\Models\Addons\LmsHierarchy\LmsHierarchyModel::class) && is_file($modelFile)) {
        require_once $modelFile;
    }

    if (! isset($nodesById) || ! is_array($nodesById) || empty($nodesById)) {
        $nodesById = [];
        if (class_exists(\App\Models\Addons\LmsHierarchy\LmsHierarchyModel::class)) {
            $hrModel   = new \App\Models\Addons\LmsHierarchy\LmsHierarchyModel();
            $treeData  = $hrModel->getFullHierarchyData();
            $nodesById = $treeData['nodes_by_id'] ?? [];
        }
    }

    $displayMode    = trim((string) ($hd['display_mode'] ?? 'drilldown_tree')) ?: 'drilldown_tree';
    $selectedIdsRaw = trim((string) ($hd['selected_node_ids'] ?? ''));
    $showDemoBadge  = ((string) ($hd['show_demo_badge'] ?? '1')) !== '0';
?>
<section class="<?= esc($blockClass ?? 'cms-block') ?> <?= esc($blockScopeClass ?? '') ?> lhr-explorer-block is-revealed"
         data-detect-country-url="<?= base_url('addons/lms-hierarchy/detect-country-json') ?>"
         data-course-view-url="<?= base_url('courses/view') ?>"
         data-display-mode="<?= esc($displayMode) ?>"
         data-selected-ids="<?= esc($selectedIdsRaw) ?>"
         data-show-demo="<?= $showDemoBadge ? '1' : '0' ?>">

    <div class="cms-inner-container">
        <?php if (! empty($hd['heading']) || ! empty($hd['badge'])): ?>
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

        <!-- Back Navigation Trail (Hidden at root level; only appears when user drills into a sub-level!) -->
        <div class="lhr-breadcrumb-bar is-hidden js-lhr-breadcrumb-bar"></div>

        <!-- Dynamic Course Cards Grid -->
        <div class="grid-3-col lhr-cards-stage js-lhr-cards-stage"></div>

        <script type="application/json" class="js-lhr-tree-payload"><?= json_encode(array_values($nodesById), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    </div>
</section>

<?php
    $ad         = $data ?? [];
    $bgCol      = ! empty($ad['bg_color']) ? $ad['bg_color'] : '#10b981';
    $txtCol     = ! empty($ad['text_color']) ? $ad['text_color'] : '#04130d';
    $bBg        = ! empty($ad['badge_bg']) ? $ad['badge_bg'] : '#04130d';
    $bCol       = ! empty($ad['badge_color']) ? $ad['badge_color'] : '#10b981';
    $filterMode = ! empty($ad['filter_mode']) ? $ad['filter_mode'] : 'category';
    $catFilter  = trim((string) ($ad['category'] ?? 'all'));
    if ($catFilter === '') {
        $catFilter = 'all';
    }
    $singleId   = (int) ($ad['announcement_id'] ?? 0);
    $scrollMode = ! empty($ad['scroll_mode']) ? $ad['scroll_mode'] : 'rtl';
    $speedSec   = max(8, (int) ($ad['scroll_speed'] ?? 22));

    // सबसे पहले सीधे JSON कैश से पढ़ें (0 DB Queries on Frontend)
    $cacheFile  = WRITEPATH . 'addons/announcements_cache.json';
    $activeRows = null;
    if (is_file($cacheFile)) {
        $decoded = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($decoded)) {
            $activeRows = $decoded;
        }
    }

    // यदि कैश फ़ाइल अभी तक नहीं बनी है, तभी एक बार मॉडल लोड करें
    if ($activeRows === null) {
        $modelPath = WRITEPATH . 'addons/extracted/announcements/Models/Addons/Announcements/AnnouncementModel.php';
        if (! class_exists(\App\Models\Addons\Announcements\AnnouncementModel::class) && is_file($modelPath)) {
            require_once $modelPath;
        }
        if (class_exists(\App\Models\Addons\Announcements\AnnouncementModel::class)) {
            $annModel   = new \App\Models\Addons\Announcements\AnnouncementModel();
            $activeRows = $annModel->getActiveFromCache();
        } else {
            $activeRows = [];
        }
    }

    $matchedItems = [];
    if (! empty($activeRows)) {
        if ($filterMode === 'single_id') {
            $targetId = $singleId > 0 ? $singleId : (int) preg_replace('/[^0-9]/', '', $catFilter);
            if ($targetId > 0) {
                $matchedItems = array_values(array_filter($activeRows, static fn($r) => (int) ($r['id'] ?? 0) === $targetId));
            }
        } elseif (strtolower($catFilter) !== 'all') {
            $targetCat    = strtolower($catFilter);
            $matchedItems = array_values(array_filter($activeRows, static fn($r) => strtolower(trim((string) ($r['category'] ?? ''))) === $targetCat));
        } else {
            $matchedItems = $activeRows;
        }

        if (empty($matchedItems)) {
            $matchedItems = $activeRows;
        }
    }
?>
<?php if (! empty($matchedItems)): ?>
<div class="cms-announcement-bar is-revealed"
     style="--ann-bg:<?= esc($bgCol) ?>;--ann-text:<?= esc($txtCol) ?>;--ann-badge-bg:<?= esc($bBg) ?>;--ann-badge-color:<?= esc($bCol) ?>;--ann-speed:<?= esc((string) $speedSec) ?>s;"
     data-bg-color="<?= esc($bgCol) ?>"
     data-text-color="<?= esc($txtCol) ?>"
     data-badge-bg="<?= esc($bBg) ?>"
     data-badge-color="<?= esc($bCol) ?>"
     data-scroll-speed="<?= esc((string) $speedSec) ?>">
    <div class="cms-announcement-inner">

        <?php if (! empty($ad['badge_text'])): ?>
            <span class="cms-ann-badge">
                <?= esc($ad['badge_text']) ?>
            </span>
        <?php endif; ?>

        <div class="cms-ann-viewport mode-<?= esc($scrollMode) ?>">
            <?php if ($scrollMode === 'static'): ?>
                <div class="cms-ann-static-list">
                    <?php foreach ($matchedItems as $idxItem => $item): ?>
                        <span class="cms-ann-item">
                            <span><?= esc($item['message']) ?></span>
                            <?php if (! empty($item['link_text'])): ?>
                                <a href="<?= esc($item['link_url'] ?: '#') ?>" class="cms-ann-inline-link">
                                    <?= esc($item['link_text']) ?>
                                </a>
                            <?php endif; ?>
                        </span>
                        <?php if ($idxItem < count($matchedItems) - 1): ?>
                            <span class="cms-ann-sep">•</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="cms-ann-ticker-track dir-<?= esc($scrollMode) ?>">
                    <?php for ($loop = 0; $loop < 2; $loop++): ?>
                        <div class="cms-ann-ticker-group" <?= $loop === 1 ? 'aria-hidden="true"' : '' ?>>
                            <?php foreach ($matchedItems as $item): ?>
                                <span class="cms-ann-item">
                                    <span><?= esc($item['message']) ?></span>
                                    <?php if (! empty($item['link_text'])): ?>
                                        <a href="<?= esc($item['link_url'] ?: '#') ?>" class="cms-ann-inline-link">
                                            <?= esc($item['link_text']) ?>
                                        </a>
                                    <?php endif; ?>
                                </span>
                                <span class="cms-ann-sep">✦</span>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (($ad['dismissible'] ?? '1') === '1'): ?>
            <button type="button" class="cms-ann-close js-close-ann" aria-label="Close">✕</button>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>
import os
import re
import zipfile

def pack_addon_zip(src_dir, zip_filename):
    if not os.path.exists(src_dir):
        return
    if os.path.exists(zip_filename):
        os.remove(zip_filename)
    with zipfile.ZipFile(zip_filename, "w", zipfile.ZIP_DEFLATED) as zf:
        for root, dirs, files in os.walk(src_dir):
            for file in files:
                full_p = os.path.join(root, file)
                rel_p = os.path.relpath(full_p, src_dir).replace("\\", "/")
                zf.write(full_p, rel_p)
    print(f"[RE-ZIPPED] Created clean archive: {zip_filename}")

print("================================================================")
print("STEP 2: INSTALLING SAFE, BUG-FREE ZERO-FLASH CSS ENGINE")
print("================================================================")

# 1. SAFELY UPDATE `head_meta.php`
head_meta_path = os.path.join("app", "Views", "layouts", "partials", "head_meta.php")
if not os.path.exists(head_meta_path):
    raise FileNotFoundError(f"Cannot find {head_meta_path}")

with open(head_meta_path, "r", encoding="utf-8", errors="ignore") as f:
    hm_code = f.read()

# Safely remove ONLY the binder blocks (without wiping out the rest of the file)
hm_code = re.sub(r"<!-- ZERO-FLASH UNIVERSAL COLOR BINDER[\s\S]*?</script>\s*", "", hm_code)
hm_code = re.sub(r"<!-- SERVER-GENERATED-ADDON-VARS:[\s\S]*?\?>\s*", "", hm_code)

safe_server_head_compiler = """
<!-- SERVER-GENERATED-ADDON-VARS: Safe Pre-Paint Scoped CSS Variables -->
<?php
if (! empty($blocks) && is_array($blocks)) {$compiledRules = [];
    $isSafeCssVal = static function ($val): bool {
        return (bool) preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\\([^;{}<>]+\\)|hsla?\\([^;{}<>]+\\)|var\\(--[a-zA-Z0-9_-]+\\)|[0-9]+(\\.[0-9]+)?(s|ms|px|rem|em|%)?)$/',$val);
    };

    foreach ($blocks as $bIdx =>$blkItem) {
        $bData =$blkItem['data'] ?? [];
        if (empty($bData) \vert{}\vert{} ! is_array($bData)) {
            continue;
        }
        $scopeSelector = '.cms-scope-blk-' . (int)$bIdx;
        $varsList      = [];

        // 1. Universal Automatic Mapping
        foreach ($bData as $k =>$v) {
            if (! is_scalar($v)) {
                continue;
            }
            $valStr = trim((string)$v);
            if ($valStr === '' || ! $isSafeCssVal($valStr)) {
                continue;
            }
            $cleanKey = preg_replace('/[^a-z0-9_-]/i', '-', strtolower((string)$k));
            if ($cleanKey === 'scroll-speed' && is_numeric($valStr)) {
                $varsList[] = '--cms-scroll-speed:' .$valStr . 's';
            } else {
                $varsList[] = '--cms-' . $cleanKey . ':' .$valStr;
            }
        }

        // 2. Direct Add-On Variable Aliases (Validated for CSS safety)
        $aliasMap = [
            'bg_color'      => ['--ann-bg'],
            'text_color'    => ['--ann-text'],
            'badge_bg'      => ['--ann-badge-bg'],
            'badge_color'   => ['--ann-badge-color'],
            'accent_color'  => ['--faq-accent', '--faq-active-accent'],
            'border_color'  => ['--faq-border'],
            'card_bg_dark'  => ['--rev-card-bg-dark', '--faq-card-bg-dark'],
            'text_dark'     => ['--rev-text-dark', '--faq-text-dark'],
            'star_dark'     => ['--rev-star-dark'],
            'accent_dark'   => ['--faq-accent-dark'],
            'card_bg_light' => ['--rev-card-bg-light', '--faq-card-bg-light'],
            'text_light'    => ['--rev-text-light', '--faq-text-light'],
            'star_light'    => ['--rev-star-light'],
            'accent_light'  => ['--faq-accent-light'],
        ];

        foreach ($aliasMap as $dataKey =>$cssVars) {
            if (! empty($bData[$dataKey]) && is_scalar($bData[$dataKey])) {$cVal = trim((string) $bData[$dataKey]);
                if ($isSafeCssVal($cVal)) {
                    foreach ($cssVars as $cssVar) {$varsList[] = $cssVar . ':' .$cVal;
                    }
                }
            }
        }

        if (! empty($bData['scroll_speed']) && is_numeric($bData['scroll_speed'])) {
            $spd = max(4, (int)$bData['scroll_speed']) . 's';
            $varsList[] = '--ann-speed:' .$spd;
            $varsList[] = '--rev-speed:' .$spd;
        }

        if (($bData['_bg_mode'] ?? '') === 'custom' && ! empty($bData['_bg_color'])) {
            $bgCustom = trim((string)$bData['_bg_color']);
            if ($isSafeCssVal($bgCustom)) {
                $varsList[] = 'background-color:' .$bgCustom;
            }
        }

        if (! empty($varsList)) {$compiledRules[] = $scopeSelector . '{' . implode(';', array_unique($varsList)) . ';}';
        }
    }

    if (! empty($compiledRules)) {
        echo '<style id="cms-prepaint-scoped-vars">' . "\\n" . implode("\\n", $compiledRules) . "\\n" . '</style>' . "\\n";
    }
}
?>
"""

with open(head_meta_path, "w", encoding="utf-8", newline="\n") as f:
    f.write(hm_code.rstrip() + "\n" + safe_server_head_compiler)
print("[OK] Safe Server-Side CSS Compiler installed in head_meta.php")

# 2. SAFELY UPDATE `frontend_page_view.php`
top_hook_with_scope = """<?php
        $globalWidth    =$settings['site_layout_width'] ?? 'boxed';
        $globalSurface  =$settings['default_block_surface'] ?? 'card';
        $floatingCtas   = [];$floatingBlocks = [];

        // MODULAR TOP HOOK: Render `announcement_bar` with Pre-Paint Scope Class
        $isAnnAddonEnabled = false;
        try {
            $dbAnnCheck = \\Config\\Database::connect();
            if ($dbAnnCheck->tableExists('addons')) {
                $annAddonRow =$dbAnnCheck->table('addons')->where('slug', 'announcements')->get()->getRowArray();
                if ($annAddonRow) {$isAnnAddonEnabled = true;
                    if (isset($annAddonRow['is_active']) && (int) $annAddonRow['is_active'] === 0) {$isAnnAddonEnabled = false;
                    } elseif (isset($annAddonRow['is_enabled']) && (int) $annAddonRow['is_enabled'] === 0) {$isAnnAddonEnabled = false;
                    } elseif (isset($annAddonRow['status']) && strtolower((string) $annAddonRow['status']) !== 'active') {$isAnnAddonEnabled = false;
                    }
                }
            }
        } catch (\\Throwable $e) {$isAnnAddonEnabled = false;
        }

        $extractedAnnBlock = WRITEPATH . 'addons/extracted/announcements/Views/addons/announcements/block_view.php';
        if ($isAnnAddonEnabled && is_file($extractedAnnBlock)) {
            foreach ($blocks as $bIdx =>$blk) {
                if (($blk['type'] ?? '') === 'announcement_bar') {
                    $data            =$blk['data'] ?? [];
                    $blockScopeClass = 'cms-scope-blk-' . (int)$bIdx;
                    include $extractedAnnBlock;
                }
            }
        }
    ?>"""

for fe_path in [
    os.path.join("app", "Views", "addons", "page_builder", "frontend_page_view.php"),
    os.path.join("writable", "addons", "extracted", "page_builder", "Views", "addons", "page_builder", "frontend_page_view.php")
]:
    if os.path.exists(fe_path):
        with open(fe_path, "r", encoding="utf-8", errors="ignore") as f:
            fe_code = f.read()

        pattern_top = r"<\?php\s+\$globalWidth\s*=\s*\$settings\['site_layout_width'\][\s\S]*?(?=<!--\s*Frontend Website Sticky Navigation Bar\s*-->)"
        if re.search(pattern_top, fe_code):
            fe_code = re.sub(pattern_top, lambda m: top_hook_with_scope + "\n\n    ", fe_code, count=1)

        fe_code = fe_code.replace("foreach ($blocks as $blk):", "foreach ($blocks as $bIdx =>$blk):")
        if "cms-scope-blk-" not in fe_code.split("<!-- Frontend Website Sticky Navigation Bar -->")[-1]:
            fe_code = fe_code.replace(
                "$wrapperClass = 'cms-block-wrapper js-lazy-reveal';",
                "$wrapperClass = 'cms-block-wrapper js-lazy-reveal cms-scope-blk-' . (int) ($bIdx ?? 0);\n            $blockClass .= ' cms-scope-blk-' . (int) ($bIdx ?? 0);"
            )

        with open(fe_path, "w", encoding="utf-8", newline="\n") as f:
            f.write(fe_code)
        print(f"[OK] Updated Scoped Block Classes in: {fe_path}")

# 3. SAFELY UPDATE `announcements/block_view.php` (With proper PHP spacing)
ann_dir = os.path.join("writable", "addons", "extracted", "announcements")
if os.path.exists(ann_dir):
    ann_block_view = os.path.join(ann_dir, "Views", "addons", "announcements", "block_view.php")
    ann_bv_code = """<?php
    $modelPath = WRITEPATH . 'addons/extracted/announcements/Models/Addons/Announcements/AnnouncementModel.php';
    if (! class_exists(\\App\\Models\\Addons\\Announcements\\AnnouncementModel::class) && is_file($modelPath)) {
        require_once $modelPath;
    }

    $ad         =$data ?? [];
    $scopeCls   = $blockScopeClass ?? ($blockClass ?? '');
    $bgCol      = ! empty($ad['bg_color']) ?$ad['bg_color'] : '#10b981';
    $txtCol     = ! empty($ad['text_color']) ?$ad['text_color'] : '#04130d';
    $bBg        = ! empty($ad['badge_bg']) ?$ad['badge_bg'] : '#04130d';
    $bCol       = ! empty($ad['badge_color']) ?$ad['badge_color'] : '#10b981';
    $filterMode = ! empty($ad['filter_mode']) ?$ad['filter_mode'] : 'category';
    $catFilter  = trim((string) ($ad['category'] ?? 'all'));
    if ($catFilter === '') {$catFilter = 'all';
    }
    $singleId   = (int) ($ad['announcement_id'] ?? 0);$scrollMode = ! empty($ad['scroll_mode']) ?$ad['scroll_mode'] : 'rtl';
    $speedSec   = max(8, (int) ($ad['scroll_speed'] ?? 22));

    $matchedItems = [];
    if (class_exists(\\App\\Models\\Addons\\Announcements\\AnnouncementModel::class)) {
        $annModel     = new \\App\\Models\\Addons\\Announcements\\AnnouncementModel();$matchedItems = $annModel->getFiltered($filterMode, $catFilter,$singleId);
        if (empty($matchedItems) && strtolower($catFilter) !== 'all') {
            $matchedItems =$annModel->getFiltered('category', 'all', 0);
        }
    }
?>
<?php if (! empty($matchedItems)): ?>
<div class="cms-announcement-bar is-revealed <?= esc($scopeCls) ?>"
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
                    <?php foreach ($matchedItems as $idxItem =>$item): ?>
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
                            <?php foreach ($matchedItems as$item): ?>
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
"""
    with open(ann_block_view, "w", encoding="utf-8", newline="\n") as f:
        f.write(ann_bv_code)
    pack_addon_zip(ann_dir, "announcements_addon.zip")
    print("[OK] Cleanly updated announcements block_view.php")
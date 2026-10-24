<?php
    $siteTitleVal = $settings['site_title'] ?? ($settings['site_name'] ?? 'Portal');
    $rawPageTitle = $pageTitle ?? ($page['meta_title'] ?? ($page['title'] ?? ($page['page_title'] ?? $siteTitleVal)));
    $metaDescVal  = $metaDescription ?? ($page['meta_description'] ?? ($settings['meta_description'] ?? ''));
    $metaKeysVal  = $page['meta_keywords'] ?? ($settings['meta_keywords'] ?? '');
    $faviconUrl   = ! empty($settings['site_favicon'])
        ? base_url($settings['site_favicon'])
        : (! empty($settings['favicon_url']) ? base_url($settings['favicon_url']) : base_url('favicon.ico'));

    $coreCssFile = FCPATH . 'assets/css/app.css';
    $coreCssVer  = is_file($coreCssFile) ? (string) @filemtime($coreCssFile) : '1.0';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= esc($rawPageTitle) ?></title>
<?php if ($metaDescVal !== ''): ?>
<meta name="description" content="<?= esc($metaDescVal) ?>">
<?php endif; ?>
<?php if ($metaKeysVal !== ''): ?>
<meta name="keywords" content="<?= esc($metaKeysVal) ?>">
<?php endif; ?>
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<meta name="csrf-header" content="<?= csrf_header() ?>">
<link rel="icon" href="<?= esc($faviconUrl) ?>">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Core Application Stylesheet -->
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= esc($coreCssVer) ?>">

<!-- 1. DYNAMIC ADD-ON EXTERNAL CSS & JS LOADER (Only Installed & Active Add-Ons) -->
<?php
try {
    $dbAddonAssets = \Config\Database::connect();
    if ($dbAddonAssets->tableExists('addons')) {
        $addonRows = $dbAddonAssets->table('addons')->get()->getResultArray();
        foreach ($addonRows as $actAddon) {
            $slug = trim((string) ($actAddon['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $isActive = true;
            if (isset($actAddon['is_active']) && (int) $actAddon['is_active'] === 0) {
                $isActive = false;
            } elseif (isset($actAddon['is_enabled']) && (int) $actAddon['is_enabled'] === 0) {
                $isActive = false;
            } elseif (isset($actAddon['status']) && strtolower((string) $actAddon['status']) !== 'active') {
                $isActive = false;
            }

            if (! $isActive) {
                continue;
            }

            $addonExtractDir = WRITEPATH . 'addons/extracted/' . $slug;
            $manifestFile    = $addonExtractDir . '/addon.json';

            if (is_file($manifestFile)) {
                $rawJson      = ltrim((string) file_get_contents($manifestFile), "\xEF\xBB\xBF");
                $manifestData = json_decode($rawJson, true);

                if (is_array($manifestData)) {
                    foreach (($manifestData['assets']['css'] ?? []) as $cssRelUrl) {
                        $cssFileName = basename((string) $cssRelUrl);
                        $localCss    = $addonExtractDir . '/assets/' . $cssFileName;
                        $cssVer      = is_file($localCss) ? (string) @filemtime($localCss) : '1.0';
                        echo '<link rel="stylesheet" href="' . base_url(esc((string) $cssRelUrl)) . '?v=' . esc($cssVer) . '">' . "\n";
                    }

                    foreach (($manifestData['assets']['js'] ?? []) as $jsRelUrl) {
                        $jsFileName = basename((string) $jsRelUrl);
                        $localJs    = $addonExtractDir . '/assets/' . $jsFileName;
                        $jsVer      = is_file($localJs) ? (string) @filemtime($localJs) : '1.0';
                        echo '<script src="' . base_url(esc((string) $jsRelUrl)) . '?v=' . esc($jsVer) . '" defer></script>' . "\n";
                    }
                }
            }
        }
    }
} catch (\Throwable $e) {
}
?>

<!-- 2. RESPONSIVE COMPONENT FIXES (Split Hero, Newsletter Grid, Card Grid & Mobile Viewports) -->
<style id="cms-responsive-component-fixes">
    /* Split Hero Desktop & Tablet/Mobile Polish */
    .cms-split-hero-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
        align-items: center;
        gap: clamp(1.75rem, 4vw, 3.25rem);
    }
    .cms-split-left,
    .cms-split-right {
        min-width: 0;
        width: 100%;
    }
    .cms-hero-visual-stage {
        position: relative;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .cms-split-img {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 1rem;
        object-fit: cover;
    }
    @media (max-width: 991px) {
        .cms-split-hero-grid {
            grid-template-columns: minmax(0, 1fr) !important;
            text-align: center;
        }
        .cms-split-left {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .cms-split-left .action-btn-group {
            justify-content: center !important;
            flex-wrap: wrap;
        }
        .cms-split-right {
            max-width: 34rem;
            margin: 0 auto;
        }
    }
    @media (max-width: 640px) {
        .cms-split-title,
        .cms-hero-title {
            font-size: clamp(1.65rem, 7vw, 2.25rem) !important;
            line-height: 1.25 !important;
            word-break: break-word;
        }
        .cms-split-left .action-btn-group {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
        }
        .cms-split-left .action-btn-group > a {
            width: 100%;
            justify-content: center;
            text-align: center;
            box-sizing: border-box;
        }
        /* Keep floating badges inside viewport on small mobile screens */
        .cms-hero-visual-stage {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .cms-floating-badge.badge-top-left,
        .cms-floating-badge.badge-bottom-right {
            position: static !important;
            transform: none !important;
            width: 100% !important;
            box-sizing: border-box;
            justify-content: center;
            text-align: center;
        }
        /* Newsletter & CTA Strip Mobile Stacking */
        .cms-newsletter-grid,
        .cms-cta-strip {
            grid-template-columns: minmax(0, 1fr) !important;
            flex-direction: column !important;
            align-items: stretch !important;
            text-align: center;
            gap: 1.25rem !important;
        }
        .cms-lead-input-group {
            flex-direction: column !important;
            width: 100% !important;
            gap: 0.65rem !important;
        }
        .cms-lead-input-group .field-input,
        .cms-lead-input-group .btn-primary {
            width: 100% !important;
            box-sizing: border-box;
            justify-content: center;
        }
        /* Multi-column Grids on Mobile */
        .cms-dynamic-grid,
        .grid-3-col,
        .grid-4-col,
        .cms-footer-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }
</style>

<!-- 3. SERVER-SIDE PRE-PAINT SCOPED CSS VARIABLES (0.00ms Instant Custom Colors, Zero Flash!) -->
<?php
$compileScopeRule = static function (string $s, array $bData): array {
    $rules = [];
    $targetSelector = $s . ', '
                    . $s . '.cms-announcement-bar, '
                    . $s . ' .cms-announcement-bar, '
                    . $s . '.cms-faq-section, '
                    . $s . ' .cms-faq-section, '
                    . $s . '.cms-reviews-section, '
                    . $s . ' .cms-reviews-section, '
                    . $s . '.cms-newsletter-section, '
                    . $s . ' .cms-newsletter-section';

    $varsList = [];

    foreach ($bData as $k => $v) {
        if (! is_scalar($v)) {
            continue;
        }
        $valStr = trim((string) $v);
        if ($valStr === '') {
            continue;
        }
        if (preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|var\([^)]+\)|[0-9]+(\.[0-9]+)?(s|rem|em|%)?)$/', $valStr)) {
            $cleanKey = preg_replace('/[^a-z0-9_-]/i', '-', strtolower((string) $k));
            if ($cleanKey === 'scroll-speed' && is_numeric($valStr)) {
                $varsList[] = '--cms-scroll-speed:' . $valStr . 's';
            } else {
                $varsList[] = '--cms-' . $cleanKey . ':' . $valStr;
            }
        }
    }

    if (! empty($bData['bg_color'])) {
        $varsList[] = '--ann-bg:' . trim((string) $bData['bg_color']);
    }
    if (! empty($bData['text_color'])) {
        $varsList[] = '--ann-text:' . trim((string) $bData['text_color']);
    }
    if (! empty($bData['badge_bg'])) {
        $varsList[] = '--ann-badge-bg:' . trim((string) $bData['badge_bg']);
    }
    if (! empty($bData['badge_color'])) {
        $varsList[] = '--ann-badge-color:' . trim((string) $bData['badge_color']);
    }
    if (! empty($bData['scroll_speed']) && is_numeric($bData['scroll_speed'])) {
        $spd = max(4, (int) $bData['scroll_speed']) . 's';
        $varsList[] = '--ann-speed:' . $spd;
        $varsList[] = '--rev-speed:' . $spd;
    }
    if (! empty($bData['accent_color'])) {
        $varsList[] = '--faq-accent:' . trim((string) $bData['accent_color']);
        $varsList[] = '--faq-active-accent:' . trim((string) $bData['accent_color']);
    }
    if (! empty($bData['border_color'])) {
        $varsList[] = '--faq-border:' . trim((string) $bData['border_color']);
    }
    if (! empty($bData['card_bg'])) {
        $varsList[] = '--rev-card-bg-dark:' . trim((string) $bData['card_bg']);
    }
    if (! empty($bData['star_color'])) {
        $varsList[] = '--rev-star-dark:' . trim((string) $bData['star_color']);
    }
    if (! empty($bData['card_bg_dark'])) {
        $varsList[] = '--rev-card-bg-dark:' . trim((string) $bData['card_bg_dark']);
        $varsList[] = '--faq-card-bg-dark:' . trim((string) $bData['card_bg_dark']);
    }
    if (! empty($bData['text_dark'])) {
        $varsList[] = '--rev-text-dark:' . trim((string) $bData['text_dark']);
        $varsList[] = '--faq-text-dark:' . trim((string) $bData['text_dark']);
    }
    if (! empty($bData['star_dark'])) {
        $varsList[] = '--rev-star-dark:' . trim((string) $bData['star_dark']);
    }
    if (! empty($bData['accent_dark'])) {
        $varsList[] = '--faq-accent-dark:' . trim((string) $bData['accent_dark']);
    }
    if (! empty($bData['card_bg_light'])) {
        $varsList[] = '--rev-card-bg-light:' . trim((string) $bData['card_bg_light']);
        $varsList[] = '--faq-card-bg-light:' . trim((string) $bData['card_bg_light']);
    }
    if (! empty($bData['text_light'])) {
        $varsList[] = '--rev-text-light:' . trim((string) $bData['text_light']);
        $varsList[] = '--faq-text-light:' . trim((string) $bData['text_light']);
    }
    if (! empty($bData['star_light'])) {
        $varsList[] = '--rev-star-light:' . trim((string) $bData['star_light']);
    }
    if (! empty($bData['accent_light'])) {
        $varsList[] = '--faq-accent-light:' . trim((string) $bData['accent_light']);
    }

    if (! empty($varsList)) {
        $rules[] = $targetSelector . ' {' . implode('; ', array_unique($varsList)) . ';}';
    }

    if (($bData['_bg_mode'] ?? '') === 'custom' && ! empty($bData['_bg_color'])) {
        $rules[] = $s . '.cms-block, ' . $s . ' .cms-block {background-color: ' . trim((string) $bData['_bg_color']) . ' !important;}';
    }

    return $rules;
};

$allPrepaintRules = [];

if (! empty($blocks) && is_array($blocks)) {
    foreach ($blocks as $bIdx => $blkItem) {
        $bData = $blkItem['data'] ?? [];
        if (is_array($bData) && ! empty($bData)) {
            $allPrepaintRules = array_merge($allPrepaintRules, $compileScopeRule('.cms-scope-blk-' . (int) $bIdx, $bData));
        }
    }
}

if (! empty($activeFooter['sections_json'])) {
    $fSecs = is_string($activeFooter['sections_json']) ? json_decode($activeFooter['sections_json'], true) : $activeFooter['sections_json'];
    if (is_array($fSecs)) {
        foreach ($fSecs as $cIdx => $colArr) {
            if (is_array($colArr)) {
                foreach ($colArr as $fbIdx => $fBlkItem) {
                    $fbData = $fBlkItem['data'] ?? [];
                    if (is_array($fbData) && ! empty($fbData)) {
                        $fScope = '.cms-scope-fblk-' . (int) $cIdx . '-' . (int) $fbIdx;
                        $allPrepaintRules = array_merge($allPrepaintRules, $compileScopeRule($fScope, $fbData));
                    }
                }
            }
        }
    }
}

if (! empty($allPrepaintRules)) {
    echo '<style id="cms-prepaint-scoped-vars">' . "\n" . implode("\n", $allPrepaintRules) . "\n" . '</style>' . "\n";
}
?>

<?php if (! empty($settings['head_scripts'])): ?>
    <?= $settings['head_scripts'] ?>
<?php endif; ?>
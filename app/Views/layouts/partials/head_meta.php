<?php
    $siteTitleVal = $settings['site_title'] ?? ($settings['site_name'] ?? 'Portal');
    $rawPageTitle = $pageTitle ?? ($page['meta_title'] ?? ($page['title'] ?? ($page['page_title'] ?? $siteTitleVal)));
    $metaDescVal  = $metaDescription ?? ($page['meta_description'] ?? ($settings['meta_description'] ?? ''));
    $metaKeysVal  = $page['meta_keywords'] ?? ($settings['meta_keywords'] ?? '');
    $robotsVal    = $settings['robots_txt'] ?? 'index, follow';
    $canonicalVal = $settings['canonical_url'] ?? '';
    $faviconUrl   = ! empty($settings['site_favicon'])
        ? base_url($settings['site_favicon'])
        : (! empty($settings['favicon_url']) ? base_url($settings['favicon_url']) : base_url('favicon.ico'));

    $coreCssFile = FCPATH . 'assets/css/app.css';
    $coreCssVer  = is_file($coreCssFile) ? (string) @filemtime($coreCssFile) : '1.0';

    // Detect Frontend vs Backend & Resolve Active Theme Mode
    $isFrontendPage  = isset($blocks) && is_array($blocks);
    $frontThemeMode  = strtolower(trim((string) ($settings['active_theme_mode'] ?? 'dark')));
    $adminThemeMode  = strtolower(trim((string) ($settings['admin_theme_mode'] ?? $frontThemeMode)));
    $activeThemeMode = $isFrontendPage ? $frontThemeMode : $adminThemeMode;
    if (! in_array($activeThemeMode, ['dark', 'light'], true)) {
        $activeThemeMode = 'dark';
    }

    // Fonts & Brand Accents from Settings
    $fontSans  = trim((string) ($settings['font_family_sans'] ?? 'Inter')) ?: 'Inter';
    $fontMono  = trim((string) ($settings['font_family_mono'] ?? 'JetBrains Mono')) ?: 'JetBrains Mono';
    $priAccent = trim((string) ($settings['primary_accent_color'] ?? '#10b981')) ?: '#10b981';
    $secAccent = trim((string) ($settings['secondary_accent_color'] ?? '#06b6d4')) ?: '#06b6d4';

    // Dark Palette from Settings
    $dCanvas  = trim((string) ($settings['dark_bg_canvas'] ?? '#090a0f')) ?: '#090a0f';
    $dSurface = trim((string) ($settings['dark_bg_surface'] ?? '#11131c')) ?: '#11131c';
    $dText    = trim((string) ($settings['dark_text_primary'] ?? '#f3f4f6')) ?: '#f3f4f6';
    $dBorder  = trim((string) ($settings['dark_border_color'] ?? '#232738')) ?: '#232738';

    // Light Palette from Settings
    $lCanvas  = trim((string) ($settings['light_bg_canvas'] ?? '#f8fafc')) ?: '#f8fafc';
    $lSurface = trim((string) ($settings['light_bg_surface'] ?? '#ffffff')) ?: '#ffffff';
    $lText    = trim((string) ($settings['light_text_primary'] ?? '#0f172a')) ?: '#0f172a';
    $lBorder  = trim((string) ($settings['light_border_color'] ?? '#e2e8f0')) ?: '#e2e8f0';

    // Resolved Active Palette for Current Mode
    $activeCanvas  = ($activeThemeMode === 'light') ? $lCanvas  : $dCanvas;
    $activeSurface = ($activeThemeMode === 'light') ? $lSurface : $dSurface;
    $activeText    = ($activeThemeMode === 'light') ? $lText    : $dText;
    $activeBorder  = ($activeThemeMode === 'light') ? $lBorder  : $dBorder;
    $activeMuted   = ($activeThemeMode === 'light') ? '#475569' : '#9ca3af';

    $googleFontUrl = 'https://fonts.googleapis.com/css2?family='
        . urlencode($fontSans) . ':wght@400;500;600;700;800&family='
        . urlencode($fontMono) . ':wght@400;500;600;700&display=swap';
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
<?php if ($robotsVal !== ''): ?>
<meta name="robots" content="<?= esc($robotsVal) ?>">
<?php endif; ?>
<?php if ($canonicalVal !== ''): ?>
<link rel="canonical" href="<?= esc($canonicalVal) ?>">
<?php endif; ?>
<?php if (! empty($settings['google_site_verification'])): ?>
<meta name="google-site-verification" content="<?= esc($settings['google_site_verification']) ?>">
<?php endif; ?>
<?php if (! empty($settings['bing_site_verification'])): ?>
<meta name="msvalidate.01" content="<?= esc($settings['bing_site_verification']) ?>">
<?php endif; ?>
<?php if (! empty($settings['meta_domain_verification'])): ?>
<meta name="facebook-domain-verification" content="<?= esc($settings['meta_domain_verification']) ?>">
<?php endif; ?>
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<meta name="csrf-header" content="<?= csrf_header() ?>">
<link rel="icon" href="<?= esc($faviconUrl) ?>">

<!-- Dynamic Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="<?= esc($googleFontUrl) ?>" rel="stylesheet">

<!-- Core Application Stylesheet -->
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= esc($coreCssVer) ?>">

<!-- 1. DYNAMIC ADD-ON EXTERNAL CSS & JS LOADER (Supports Public Mirror & Default Route) -->
<?php
try {
    $addonRows = class_exists('\App\Models\Addons\AddonModel')
        ? (new \App\Models\Addons\AddonModel())->getActiveAddons()
        : [];

    $isMirrorEngineActive = false;
    foreach ($addonRows as $chkRow) {
        if (($chkRow['slug'] ?? '') === 'asset_mirror' && is_dir(WRITEPATH . 'addons/extracted/asset_mirror')) {
            $isMirrorEngineActive = true;
            break;
        }
    }

    if (! $isMirrorEngineActive && is_dir(FCPATH . 'assets/addons')) {
        $cleanDir = static function (string $dirPath) use (&$cleanDir): void {
            if (! is_dir($dirPath)) return;
            foreach (scandir($dirPath) ?: [] as $item) {
                if ($item === '.' || $item === '..') continue;
                $p = $dirPath . DIRECTORY_SEPARATOR . $item;
                is_dir($p) ? $cleanDir($p) : @unlink($p);
            }
            @rmdir($dirPath);
        };
        $cleanDir(FCPATH . 'assets/addons');
    }

    $loadedAssetKeys = [];

    foreach ($addonRows as $actAddon) {
        $slug = trim((string) ($actAddon['slug'] ?? ''));
        if ($slug === '' || $slug === 'page_builder') {
            continue;
        }

        $addonExtractDir = WRITEPATH . 'addons/extracted/' . $slug;
        $manifestFile    = $addonExtractDir . '/addon.json';

        if (is_file($manifestFile)) {
            $rawJson      = ltrim((string) file_get_contents($manifestFile), "\xEF\xBB\xBF");
            $manifestData = json_decode($rawJson, true);

            if (is_array($manifestData)) {
                foreach (($manifestData['assets']['css'] ?? []) as $cssRelUrl) {
                    $cssRelStr = trim((string) $cssRelUrl);
                    if ($cssRelStr === '' || isset($loadedAssetKeys[$cssRelStr])) {
                        continue;
                    }
                    $loadedAssetKeys[$cssRelStr] = true;

                    $cssFileName = basename($cssRelStr);
                    $localCss    = $addonExtractDir . '/assets/' . $cssFileName;
                    $publicCss   = FCPATH . 'assets/addons/' . $slug . '/' . $cssFileName;

                    if ($isMirrorEngineActive && is_file($localCss)) {
                        if (! is_file($publicCss) || @filemtime($localCss) > @filemtime($publicCss)) {
                            @is_dir(dirname($publicCss)) || @mkdir(dirname($publicCss), 0775, true);
                            @copy($localCss, $publicCss);
                        }
                    }

                    if ($isMirrorEngineActive && is_file($publicCss)) {
                        $hrefUrl = base_url('assets/addons/' . $slug . '/' . $cssFileName);
                        $cssVer  = (string) @filemtime($publicCss);
                    } else {
                        $hrefUrl = base_url(esc($cssRelStr));
                        $cssVer  = is_file($localCss) ? (string) @filemtime($localCss) : '1.0';
                    }

                    echo '<link rel="stylesheet" href="' . $hrefUrl . '?v=' . esc($cssVer) . '">' . "\n";
                }

                foreach (($manifestData['assets']['js'] ?? []) as $jsRelUrl) {
                    $jsRelStr = trim((string) $jsRelUrl);
                    if ($jsRelStr === '' || isset($loadedAssetKeys[$jsRelStr])) {
                        continue;
                    }
                    $loadedAssetKeys[$jsRelStr] = true;

                    $jsFileName = basename($jsRelStr);
                    $localJs    = $addonExtractDir . '/assets/' . $jsFileName;
                    $publicJs   = FCPATH . 'assets/addons/' . $slug . '/' . $jsFileName;

                    if ($isMirrorEngineActive && is_file($localJs)) {
                        if (! is_file($publicJs) || @filemtime($localJs) > @filemtime($publicJs)) {
                            @is_dir(dirname($publicJs)) || @mkdir(dirname($publicJs), 0775, true);
                            @copy($localJs, $publicJs);
                        }
                    }

                    if ($isMirrorEngineActive && is_file($publicJs)) {
                        $srcUrl = base_url('assets/addons/' . $slug . '/' . $jsFileName);
                        $jsVer  = (string) @filemtime($publicJs);
                    } else {
                        $srcUrl = base_url(esc($jsRelStr));
                        $jsVer  = is_file($localJs) ? (string) @filemtime($localJs) : '1.0';
                    }

                    echo '<script src="' . $srcUrl . '?v=' . esc($jsVer) . '" defer></script>' . "\n";
                }
            }
        }
    }
} catch (\Throwable $e) {
}
?>

<!-- 2. SERVER-SIDE PRE-PAINT SCOPED CSS VARIABLES (Theme-Aware for Both Dark & Light Modes!) -->
<?php
// Default dark hex values saved by Page Builder blocks that should automatically adapt when Light Mode is active
$defaultDarkBgHexes  = ['#090a0f', '#11131c', '#171a26', '#0d1f1a', '#0f172a'];
$defaultDarkTxtHexes = ['#f3f4f6', '#f8fafc', '#ffffff', '#e5e7eb'];
$defaultDarkBdrHexes = ['#232738', '#1e293b', '#334155'];

$compileScopeRule = static function (string $s, array $bData) use (
    $activeThemeMode,
    $lSurface,
    $lText,
    $lBorder,
    $defaultDarkBgHexes,
    $defaultDarkTxtHexes,
    $defaultDarkBdrHexes
): array {
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

    $varsList  = [];
    $blockMode = strtolower(trim((string) ($bData['color_mode'] ?? 'auto')));
    $useLightForBlock = ($blockMode === 'light') || ($blockMode === 'auto' && $activeThemeMode === 'light');

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

    // Announcement Bar always uses its own explicit bar colors
    if (! empty($bData['bg_color'])) {
        $rawBg = trim((string) $bData['bg_color']);
        $varsList[] = '--ann-bg:' . $rawBg;

        // For Newsletter/Blocks: if Light mode is active, use card_bg_light instead of dark #0d1f1a
        if ($useLightForBlock) {
            $nlLightBg = ! empty($bData['card_bg_light']) ? trim((string) $bData['card_bg_light']) : (in_array(strtolower($rawBg), $defaultDarkBgHexes, true) ? $lSurface : $rawBg);
            $varsList[] = '--nl-bg:' . $nlLightBg;
        } else {
            $varsList[] = '--nl-bg:' . $rawBg;
        }
    }
    if (! empty($bData['text_color'])) {
        $rawTxt = trim((string) $bData['text_color']);
        $varsList[] = '--ann-text:' . $rawTxt;

        if ($useLightForBlock) {
            $nlLightTxt = ! empty($bData['text_light']) ? trim((string) $bData['text_light']) : (in_array(strtolower($rawTxt), $defaultDarkTxtHexes, true) ? $lText : $rawTxt);
            $varsList[] = '--nl-text:' . $nlLightTxt;
        } else {
            $varsList[] = '--nl-text:' . $rawTxt;
        }
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
        $rawBdr = trim((string) $bData['border_color']);
        $effectiveBdr = ($useLightForBlock && in_array(strtolower($rawBdr), $defaultDarkBdrHexes, true)) ? $lBorder : $rawBdr;
        $varsList[] = '--faq-border:' . $effectiveBdr;
        $varsList[] = '--nl-border:' . $effectiveBdr;
    }
    if (! empty($bData['btn_bg'])) {
        $varsList[] = '--nl-btn-bg:' . trim((string) $bData['btn_bg']);
    }
    if (! empty($bData['btn_text_col'])) {
        $varsList[] = '--nl-btn-txt:' . trim((string) $bData['btn_text_col']);
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

<!-- 3. GLOBAL THEME, PALETTE, COMPONENT LIGHT/DARK ADAPTATION & RESPONSIVE ENGINE -->
<style id="cms-global-theme-and-responsive-engine">
    :root,
    html,
    body,
    body.theme-obsidian,
    html[data-theme="dark"],
    html[data-theme="light"],
    body.theme-dark,
    body.theme-light {
        --font-sans: '<?= esc($fontSans) ?>', -apple-system, BlinkMacSystemFont, sans-serif !important;
        --font-mono: '<?= esc($fontMono) ?>', monospace !important;

        --accent-emerald: <?= esc($priAccent) ?> !important;
        --accent-primary: <?= esc($priAccent) ?> !important;
        --primary-accent: <?= esc($priAccent) ?> !important;
        --accent-cyan: <?= esc($secAccent) ?> !important;
        --accent-secondary: <?= esc($secAccent) ?> !important;

        --bg-canvas: <?= esc($activeCanvas) ?> !important;
        --bg-primary: <?= esc($activeCanvas) ?> !important;
        --bg-body: <?= esc($activeCanvas) ?> !important;
        --bg-surface: <?= esc($activeSurface) ?> !important;
        --bg-elevated: <?= esc($activeSurface) ?> !important;
        --bg-card: <?= esc($activeSurface) ?> !important;
        --bg-panel: <?= esc($activeSurface) ?> !important;

        --text-primary: <?= esc($activeText) ?> !important;
        --text-main: <?= esc($activeText) ?> !important;
        --text-heading: <?= esc($activeText) ?> !important;
        --text-muted: <?= esc($activeMuted) ?> !important;
        --text-secondary: <?= esc($activeMuted) ?> !important;

        --border-subtle: <?= esc($activeBorder) ?> !important;
        --border-color: <?= esc($activeBorder) ?> !important;

        --cms-card-bg-dark: <?= esc($dSurface) ?>;
        --cms-text-dark: <?= esc($dText) ?>;
        --cms-card-bg-light: <?= esc($lSurface) ?>;
        --cms-text-light: <?= esc($lText) ?>;
    }

    html,
    body,
    body.theme-obsidian {
        background-color: <?= esc($activeCanvas) ?> !important;
        color: <?= esc($activeText) ?> !important;
        font-family: var(--font-sans) !important;
    }

    <?php if ($activeThemeMode === 'light'): ?>
    /* ==========================================================================
       COMPLETE LIGHT MODE ENGINE (Frontend Components + Backend Admin Workspace)
       Automatically converts hardcoded dark block backgrounds (#171a26, #11131c)
       ========================================================================== */
    .cms-public-navbar,
    .app-header,
    .app-topbar,
    .app-sidebar {
        background-color: <?= esc($lSurface) ?> !important;
        border-color: <?= esc($lBorder) ?> !important;
        color: <?= esc($lText) ?> !important;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05) !important;
    }
    .cms-block,
    .cms-block.surface-card,
    .panel-card,
    .console-card,
    .asset-box,
    .cms-course-card,
    .cms-quiz-card,
    .cms-video-stage,
    .cms-hero-abstract-box,
    .cms-floating-badge,
    .cms-faq-item,
    .cms-review-card,
    .cms-testimonial-card,
    .cms-public-footer {
        background-color: <?= esc($lSurface) ?> !important;
        color: <?= esc($lText) ?> !important;
        border-color: <?= esc($lBorder) ?> !important;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06) !important;
    }
    .cms-quiz-opt,
    .field-input,
    .field-select,
    .field-textarea,
    .data-table th,
    .role-table th {
        background-color: <?= esc($lCanvas) ?> !important;
        color: <?= esc($lText) ?> !important;
        border-color: <?= esc($lBorder) ?> !important;
    }
    .data-table td,
    .role-table td,
    .data-table tr,
    .role-table tr {
        color: <?= esc($lText) ?> !important;
        border-color: <?= esc($lBorder) ?> !important;
    }
    .cms-hero-title,
    .cms-split-title,
    .cms-brand-title,
    .cms-quiz-question,
    .cms-faq-question,
    .panel-title,
    .sidebar-title,
    .identity-name,
    .cms-prose h1, .cms-prose h2, .cms-prose h3, .cms-prose h4,
    h1, h2, h3, h4, h5, h6 {
        color: <?= esc($lText) ?> !important;
    }
    .cms-prose,
    .cms-prose p,
    .cms-prose li,
    .cms-nav-item,
    .sidebar-link,
    .cms-hero-sub,
    .cms-split-sub,
    .cms-faq-answer,
    .muted-cell,
    .field-label,
    .field-hint {
        color: #334155 !important;
    }
    .btn-secondary,
    .btn-action {
        background-color: <?= esc($lCanvas) ?> !important;
        color: <?= esc($lText) ?> !important;
        border: 1px solid <?= esc($lBorder) ?> !important;
    }
    .cms-newsletter-section[data-color-mode="auto"],
    .cms-newsletter-box[data-color-mode="auto"] {
        --nl-bg: <?= esc($lSurface) ?> !important;
        --nl-text: <?= esc($lText) ?> !important;
    }
    .cms-newsletter-section[data-color-mode="auto"] .cms-nl-field {
        background-color: <?= esc($lCanvas) ?> !important;
        color: <?= esc($lText) ?> !important;
        border-color: <?= esc($lBorder) ?> !important;
    }
    .ambient-grid {
        opacity: 0.12 !important;
    }
    <?php endif; ?>

    /* Responsive Component Fixes */
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
        .cms-dynamic-grid,
        .grid-3-col,
        .grid-4-col,
        .cms-footer-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }
</style>
<script>
    (function() {
        var mode = '<?= esc($activeThemeMode) ?>';
        document.documentElement.setAttribute('data-theme', mode);
        try { localStorage.setItem('theme', mode); localStorage.setItem('cms_theme_mode', mode); } catch (e) {}
        var syncBodyTheme = function() {
            if (document.body) {
                document.body.setAttribute('data-theme', mode);
                document.body.classList.remove('theme-dark', 'theme-light');
                document.body.classList.add('theme-' + mode);
            }
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', syncBodyTheme);
        } else {
            syncBodyTheme();
        }
    })();
</script>

<?php if (! empty($settings['custom_css'])): ?>
<style id="cms-global-custom-css">
<?= $settings['custom_css'] ?>
</style>
<?php endif; ?>

<?php
    $rawHeaderScripts = $settings['header_scripts'] ?? ($settings['head_scripts'] ?? '');
    if (! empty($rawHeaderScripts)):
?>
    <?= $rawHeaderScripts ?>
<?php endif; ?>
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

<!-- 1. ZERO-LOCK STATIC ASSET MIRROR LOADER (0 PHP Controller Hits for Add-On CSS/JS!) -->
<?php
try {
    $addonRows = class_exists('\App\Models\Addons\AddonModel')
        ? (new \App\Models\Addons\AddonModel())->getActiveAddons()
        : [];

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

                    if (is_file($localCss)) {
                        if (! is_file($publicCss) || @filesize($localCss) !== @filesize($publicCss) || @filemtime($localCss) > @filemtime($publicCss)) {
                            @is_dir(dirname($publicCss)) || @mkdir(dirname($publicCss), 0775, true);
                            @copy($localCss, $publicCss);
                        }
                    }

                    if (is_file($publicCss)) {
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

                    if (is_file($localJs)) {
                        if (! is_file($publicJs) || @filesize($localJs) !== @filesize($publicJs) || @filemtime($localJs) > @filemtime($publicJs)) {
                            @is_dir(dirname($publicJs)) || @mkdir(dirname($publicJs), 0775, true);
                            @copy($localJs, $publicJs);
                        }
                    }

                    if (is_file($publicJs)) {
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

<!-- 2. SERVER-SIDE PRE-PAINT DUAL-THEME (DARK + LIGHT PALETTE) DESIGN STUDIO COMPILER -->
<?php
$defaultDarkBgHexes  = ['#090a0f', '#11131c', '#171a26', '#0d1f1a', '#0f172a'];
$defaultDarkTxtHexes = ['#f3f4f6', '#f8fafc', '#ffffff', '#e5e7eb'];
$defaultDarkBdrHexes = ['#232738', '#1e293b', '#334155'];

$hexToRgba = static function (string $hex, float $alpha): string {
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
        return 'rgba(9, 10, 15, ' . max(0, min(1, $alpha)) . ')';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba({$r}, {$g}, {$b}, " . round(max(0, min(1, $alpha)), 2) . ")";
};

/**
 * Compiles Dual-Theme (Dark Palette + Light Palette) Scoped Rules for Every Page & Footer Block
 */
$compileScopeRule = static function (string $s, array $bData, bool $isEagerAboveFold = false) use (
    $activeThemeMode,
    $dSurface,
    $dText,
    $dBorder,
    $lSurface,
    $lText,
    $lBorder,
    $defaultDarkBgHexes,
    $defaultDarkTxtHexes,
    $defaultDarkBdrHexes,
    $hexToRgba
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
        if (preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|var\([^)]+\)|[0-9]+(\.[0-9]+)?(s|rem|em|px|%)?)$/', $valStr)) {
            $cleanKey = preg_replace('/[^a-z0-9_-]/i', '-', strtolower((string) $k));
            if ($cleanKey === 'scroll-speed' && is_numeric($valStr)) {
                $varsList[] = '--cms-scroll-speed:' . $valStr . 's';
            } else {
                $varsList[] = '--cms-' . $cleanKey . ':' . $valStr;
            }
        }
    }

    if (! empty($bData['bg_color'])) {
        $rawBg = trim((string) $bData['bg_color']);
        $varsList[] = '--ann-bg:' . $rawBg;

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

    // =========================================================================
    // UNIVERSAL DESIGN STUDIO COMPILER (THEME-AGNOSTIC GEOMETRY + DUAL PALETTE)
    // =========================================================================
    $blkTarget = $s . '.cms-block, ' . $s . ' > .cms-block, ' . $s . '.cms-footer-block-box';
    $geomDecls = [];

    $bgMode = trim((string) ($bData['_bg_mode'] ?? 'default'));
    if ($bgMode === 'image' && ! empty($bData['_bg_img_url'])) {
        $bgSize = trim((string) ($bData['_bg_img_size'] ?? 'cover')) ?: 'cover';
        $bgPos  = trim((string) ($bData['_bg_img_pos'] ?? 'center center')) ?: 'center center';
        $bgRep  = trim((string) ($bData['_bg_img_repeat'] ?? 'no-repeat')) ?: 'no-repeat';
        $geomDecls[] = "background-size: {$bgSize} !important";
        $geomDecls[] = "background-position: {$bgPos} !important";
        if ($bgRep === 'fixed') {
            $geomDecls[] = 'background-repeat: no-repeat !important';
            $geomDecls[] = 'background-attachment: fixed !important';
        } else {
            $geomDecls[] = "background-repeat: {$bgRep} !important";
        }
    }

    $bdrStyle  = trim((string) ($bData['_bdr_style'] ?? ''));
    $bdrWidth  = trim((string) ($bData['_bdr_width'] ?? ''));
    $bdrRadius = trim((string) ($bData['_bdr_radius'] ?? ''));
    if ($bdrStyle === 'none') {
        $geomDecls[] = 'border: none !important';
    } elseif ($bdrWidth !== '' && (int) $bdrWidth > 0) {
        $bw = (int) $bdrWidth . 'px';
        $bs = ($bdrStyle !== '' && $bdrStyle !== 'default') ? $bdrStyle : 'solid';
        $geomDecls[] = "border-width: {$bw} !important; border-style: {$bs} !important";
    }
    if ($bdrRadius !== '' && $bdrRadius !== 'default') {
        $geomDecls[] = 'border-radius: ' . $bdrRadius . ' !important';
    }

    $padY = trim((string) ($bData['_pad_y'] ?? ''));
    $padX = trim((string) ($bData['_pad_x'] ?? ''));
    if ($padY !== '' && $padY !== 'default') {
        $geomDecls[] = "padding-top: {$padY} !important; padding-bottom: {$padY} !important";
    }
    if ($padX !== '' && $padX !== 'default') {
        $geomDecls[] = "padding-left: {$padX} !important; padding-right: {$padX} !important";
    }

    $txtFont  = trim((string) ($bData['_txt_font'] ?? ''));
    $txtAlign = trim((string) ($bData['_txt_align'] ?? ''));
    if ($txtFont !== '' && $txtFont !== 'default') {
        $geomDecls[] = "font-family: '{$txtFont}', var(--font-sans) !important";
    }
    if ($txtAlign !== '' && $txtAlign !== 'default') {
        $geomDecls[] = "text-align: {$txtAlign} !important";
    }

    if (! empty($geomDecls)) {
        $rules[] = $blkTarget . ' {' . implode('; ', $geomDecls) . ';}';
    }

    // Typography Geometry (Weight, Transform, Scale)
    $headGeom = [];
    if (! empty($bData['_txt_weight']) && $bData['_txt_weight'] !== 'default') {
        $headGeom[] = 'font-weight: ' . trim((string) $bData['_txt_weight']) . ' !important';
    }
    if (! empty($bData['_txt_transform']) && $bData['_txt_transform'] !== 'default') {
        $headGeom[] = 'text-transform: ' . trim((string) $bData['_txt_transform']) . ' !important';
    }
    if (! empty($bData['_txt_scale']) && $bData['_txt_scale'] !== 'default' && is_numeric($bData['_txt_scale'])) {
        $headGeom[] = 'zoom: ' . (float) $bData['_txt_scale'];
    }
    if (! empty($headGeom)) {
        $rules[] = "{$s} h1, {$s} h2, {$s} h3, {$s} h4, {$s} .cms-hero-title, {$s} .cms-split-title, {$s} .cms-quiz-question, {$s} .cms-faq-question {" . implode('; ', $headGeom) . ";}";
    }

    // Helper closure to build Palette-Specific Rules for a given mode ('dark' or 'light')
    $buildPaletteRulesForMode = static function (string $paletteMode, string $scopePrefix) use (
        $s,
        $bData,
        $bgMode,
        $isEagerAboveFold,
        $hexToRgba,
        $dSurface,
        $lSurface,
        $defaultDarkBgHexes
    ): array {
        $pRules    = [];
        $isLight   = ($paletteMode === 'light');
        $pfx       = $isLight ? '_light_' : '_dark_';
        $scopedBlk = ($scopePrefix !== '' ? $scopePrefix . ' ' : '') . $s . '.cms-block, '
                   . ($scopePrefix !== '' ? $scopePrefix . ' ' : '') . $s . ' > .cms-block, '
                   . ($scopePrefix !== '' ? $scopePrefix . ' ' : '') . $s . '.cms-footer-block-box';
        $scopedRoot = ($scopePrefix !== '' ? $scopePrefix . ' ' : '') . $s;

        $boxDecls = [];

        // 1. Background Color / Gradient / Image with Mode-Specific Overlay
        if ($bgMode === 'custom') {
            $rawSolid = trim((string) ($bData[$pfx . 'bg_color'] ?? ($bData['_bg_color'] ?? '')));
            if ($rawSolid !== '') {
                if ($isLight && empty($bData['_light_bg_color']) && in_array(strtolower($rawSolid), $defaultDarkBgHexes, true)) {
                    $rawSolid = $lSurface;
                }
                $boxDecls[] = "background-color: {$rawSolid} !important";
            }
        } elseif ($bgMode === 'gradient') {
            $gStart = trim((string) ($bData[$pfx . 'grad_start'] ?? ($bData['_bg_grad_start'] ?? ($isLight ? '#ecfdf5' : '#0d1f1a'))));
            $gEnd   = trim((string) ($bData[$pfx . 'grad_end']   ?? ($bData['_bg_grad_end']   ?? ($isLight ? '#ffffff' : '#11131c'))));
            $gDir   = trim((string) ($bData['_bg_grad_dir'] ?? '135deg')) ?: '135deg';
            if ($gDir === 'radial') {
                $boxDecls[] = "background: radial-gradient(circle at center, {$gStart}, {$gEnd}) !important";
            } else {
                $boxDecls[] = "background: linear-gradient({$gDir}, {$gStart}, {$gEnd}) !important";
            }
        } elseif ($bgMode === 'image' && ! empty($bData['_bg_img_url'])) {
            $imgUrl    = trim((string) $bData['_bg_img_url']);
            $safeUrl   = str_replace(["'", '"', '(', ')', ';'], '', $imgUrl);
            $ovColor   = trim((string) ($bData[$pfx . 'overlay_color'] ?? ($bData['_bg_overlay_color'] ?? ($isLight ? '#ffffff' : '#090a0f'))));
            $ovOpacity = (int) ($bData[$pfx . 'overlay_opacity'] ?? ($bData['_bg_overlay_opacity'] ?? 55));
            $rgbaStr   = $hexToRgba($ovColor, $ovOpacity / 100);

            $fallbackBg = trim((string) ($bData[$pfx . 'bg_color'] ?? ($bData['_bg_color'] ?? ($isLight ? $lSurface : $dSurface))));
            $boxDecls[] = "background-color: {$fallbackBg} !important";

            if ($isEagerAboveFold) {
                if ($ovOpacity > 0) {
                    $boxDecls[] = "background-image: linear-gradient({$rgbaStr}, {$rgbaStr}), url('{$safeUrl}') !important";
                } else {
                    $boxDecls[] = "background-image: url('{$safeUrl}') !important";
                }
            }
        }

        // 2. Border Color & Box Shadow for Mode
        $bdrCol = trim((string) ($bData[$pfx . 'bdr_color'] ?? ($bData['_bdr_color'] ?? '')));
        if ($bdrCol !== '') {
            $boxDecls[] = "border-color: {$bdrCol} !important";
        }

        $shadowPreset = trim((string) ($bData['_shadow_preset'] ?? 'default'));
        $shadowColor  = trim((string) ($bData[$pfx . 'shadow_color'] ?? ($bData['_shadow_color'] ?? '#10b981'))) ?: '#10b981';
        if ($shadowPreset === 'none') {
            $boxDecls[] = 'box-shadow: none !important';
        } elseif ($shadowPreset === 'soft') {
            $boxDecls[] = $isLight
                ? 'box-shadow: 0 10px 28px -6px rgba(15, 23, 42, 0.08) !important'
                : 'box-shadow: 0 10px 28px -6px rgba(0, 0, 0, 0.28) !important';
        } elseif ($shadowPreset === 'medium') {
            $boxDecls[] = $isLight
                ? 'box-shadow: 0 18px 42px -8px rgba(15, 23, 42, 0.14) !important'
                : 'box-shadow: 0 18px 42px -8px rgba(0, 0, 0, 0.45) !important';
        } elseif ($shadowPreset === 'deep') {
            $boxDecls[] = $isLight
                ? 'box-shadow: 0 28px 65px -10px rgba(15, 23, 42, 0.22) !important'
                : 'box-shadow: 0 28px 65px -10px rgba(0, 0, 0, 0.68) !important';
        } elseif ($shadowPreset === 'glow') {
            $glowRgba   = $hexToRgba($shadowColor, $isLight ? 0.32 : 0.45);
            $boxDecls[] = "box-shadow: 0 0 38px 2px {$glowRgba} !important";
        }

        if (! empty($boxDecls)) {
            $pRules[] = $scopedBlk . ' {' . implode('; ', $boxDecls) . ';}';
        }

        // 3. Wave / Shape Divider Fill Color for Mode
        $divCol = trim((string) ($bData[$pfx . 'divider_color'] ?? ($bData['_divider_color'] ?? '')));
        if ($divCol !== '') {
            $pRules[] = "{$scopedRoot} .cms-shape-divider svg path, {$scopedRoot} .cms-shape-divider svg polygon {fill: {$divCol} !important;}";
        }

        // 4. Internal Cards (Course Grid / Feature Cards) Dark vs Light Surface
        $intCardBg  = trim((string) ($bData[$isLight ? 'card_bg_light' : 'card_bg_dark'] ?? ($isLight ? '' : ($bData['card_bg'] ?? ''))));
        $intCardBdr = trim((string) ($bData[$pfx . 'bdr_color'] ?? ($isLight ? '' : ($bData['border_color'] ?? ''))));
        if ($intCardBg !== '' || $intCardBdr !== '') {
            $cDecls = [];
            if ($intCardBg !== '') $cDecls[] = "background-color: {$intCardBg} !important";
            if ($intCardBdr !== '') $cDecls[] = "border-color: {$intCardBdr} !important";
            $pRules[] = "{$scopedRoot} .cms-course-card, {$scopedRoot} .panel-card, {$scopedRoot} .cms-testimonial-slide {" . implode('; ', $cDecls) . ";}";
        }

        // 5. Heading, Body & Accent Text Colors for Mode
        $hCol = trim((string) ($bData[$pfx . 'txt_heading'] ?? ($bData['_txt_heading_color'] ?? '')));
        if ($hCol !== '') {
            $pRules[] = "{$scopedRoot} h1, {$scopedRoot} h2, {$scopedRoot} h3, {$scopedRoot} h4, {$scopedRoot} .cms-hero-title, {$scopedRoot} .cms-split-title, {$scopedRoot} .cms-quiz-question, {$scopedRoot} .cms-faq-question {color: {$hCol} !important;}";
        }

        $bCol = trim((string) ($bData[$pfx . 'txt_body'] ?? ($bData[$isLight ? 'text_light' : 'text_dark'] ?? ($bData['_txt_body_color'] ?? ''))));
        if ($bCol !== '') {
            $pRules[] = "{$scopedRoot}, {$scopedRoot} p, {$scopedRoot} li, {$scopedRoot} .cms-prose, {$scopedRoot} .cms-hero-sub, {$scopedRoot} .cms-split-sub, {$scopedRoot} .muted-cell, {$scopedRoot} .cms-faq-answer, {$scopedRoot} .cms-quote-text {color: {$bCol} !important;}";
        }

        $aCol = trim((string) ($bData[$pfx . 'txt_accent'] ?? ($bData['_txt_accent_color'] ?? '')));
        if ($aCol !== '') {
            $pRules[] = "{$scopedRoot} .btn-primary {background-color: {$aCol} !important; border-color: {$aCol} !important;} {$scopedRoot} .text-emerald, {$scopedRoot} .pulse-dot {color: {$aCol} !important;}";
        }

        return $pRules;
    };

    // Emit Palette Rules based on Component Color Mode (`auto`, `dark`, or `light`)
    if ($blockMode === 'dark') {
        $rules = array_merge($rules, $buildPaletteRulesForMode('dark', ''));
    } elseif ($blockMode === 'light') {
        $rules = array_merge($rules, $buildPaletteRulesForMode('light', ''));
    } else {
        // `auto`: Emit both Dark and Light scoped rules so switching theme updates every component automatically!
        $rules = array_merge($rules, $buildPaletteRulesForMode('dark', 'html[data-theme="dark"]'));
        $rules = array_merge($rules, $buildPaletteRulesForMode('light', 'html[data-theme="light"]'));
    }

    return $rules;
};

$allPrepaintRules = [];

if (! empty($blocks) && is_array($blocks)) {
    foreach ($blocks as $bIdx => $blkItem) {
        $bData = $blkItem['data'] ?? [];
        if (is_array($bData) && ! empty($bData)) {
            $allPrepaintRules = array_merge($allPrepaintRules, $compileScopeRule('.cms-scope-blk-' . (int) $bIdx, $bData, ((int) $bIdx <= 1)));
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
                        $allPrepaintRules = array_merge($allPrepaintRules, $compileScopeRule($fScope, $fbData, false));
                    }
                }
            }
        }
    }
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
    .cms-public-navbar,
    .app-header,
    .app-topbar,
    .app-sidebar {
        background-color: <?= esc($lSurface) ?> !important;
        border-color: <?= esc($lBorder) ?> !important;
        color: <?= esc($lText) ?> !important;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05) !important;
    }
    .cms-block:not(.has-custom-bg),
    .cms-block.surface-card:not(.has-custom-bg),
    .panel-card,
    .console-card,
    .asset-box,
    .cms-course-card,
    .cms-quiz-card:not(.has-custom-bg),
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
    .cms-block:not(.has-custom-text) .cms-hero-title,
    .cms-block:not(.has-custom-text) .cms-split-title,
    .cms-brand-title,
    .cms-block:not(.has-custom-text) .cms-quiz-question,
    .cms-block:not(.has-custom-text) .cms-faq-question,
    .panel-title,
    .sidebar-title,
    .identity-name,
    .cms-block:not(.has-custom-text) .cms-prose h1,
    .cms-block:not(.has-custom-text) .cms-prose h2,
    .cms-block:not(.has-custom-text) .cms-prose h3,
    .cms-block:not(.has-custom-text) .cms-prose h4,
    .cms-block:not(.has-custom-text) h1,
    .cms-block:not(.has-custom-text) h2,
    .cms-block:not(.has-custom-text) h3,
    .cms-block:not(.has-custom-text) h4 {
        color: <?= esc($lText) ?> !important;
    }
    .cms-block:not(.has-custom-text) .cms-prose,
    .cms-block:not(.has-custom-text) .cms-prose p,
    .cms-block:not(.has-custom-text) .cms-prose li,
    .cms-nav-item,
    .sidebar-link,
    .cms-block:not(.has-custom-text) .cms-hero-sub,
    .cms-block:not(.has-custom-text) .cms-split-sub,
    .cms-block:not(.has-custom-text) .cms-faq-answer,
    .cms-block:not(.has-custom-text) .muted-cell,
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

<?php
// Output Dual-Theme Scoped Block Rules AFTER Global Theme Rules
if (! empty($allPrepaintRules)) {
    echo '<style id="cms-prepaint-scoped-vars">' . "\n" . implode("\n", $allPrepaintRules) . "\n" . '</style>' . "\n";
}
?>

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
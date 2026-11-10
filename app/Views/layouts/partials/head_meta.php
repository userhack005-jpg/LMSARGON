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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= esc($coreCssVer) ?>">

<!-- 1. ZERO-QUERY (0ms) ADD-ON ASSET LOADER (Uses Pre-Compiled JSON Cache + Public Static Mirror) -->
<?php
try {
    $compiledAssetCacheFile = WRITEPATH . 'addons/asset_mirror_compiled_tags.json';
    $activeSlugsMap         = function_exists('cms_get_active_addon_slugs') ? (array) cms_get_active_addon_slugs() : [];
    $activeSlugsList        = array_keys($activeSlugsMap);
    sort($activeSlugsList);
    $currentAddonsHash      = ! empty($activeSlugsList) ? md5(implode(',', $activeSlugsList)) : '';

    $assetTagsPayload = null;
    if (is_file($compiledAssetCacheFile)) {
        $decodedTags = json_decode((string) @file_get_contents($compiledAssetCacheFile), true);
        if (is_array($decodedTags) && ($decodedTags['addons_hash'] ?? '') === $currentAddonsHash) {
            $assetTagsPayload = $decodedTags;
        }
    }

    if ($assetTagsPayload === null) {
        $cssLinks = [];
        $jsLinks  = [];
        $seenKeys = [];

        if (empty($activeSlugsList)) {
            $dbAddonAssets = \Config\Database::connect();
            if ($dbAddonAssets->tableExists('addons')) {
                foreach ($dbAddonAssets->table('addons')->where('is_active', 1)->get()->getResultArray() as $ar) {
                    $s = trim((string) ($ar['slug'] ?? ''));
                    if ($s !== '') $activeSlugsList[] = $s;
                }
                sort($activeSlugsList);
                $currentAddonsHash = md5(implode(',', $activeSlugsList));
            }
        }

        foreach ($activeSlugsList as $slug) {
            if ($slug === '' || $slug === 'page_builder' || $slug === 'asset_mirror' || $slug === 'page_cache') {
                continue;
            }
            $addonExtractDir = WRITEPATH . 'addons/extracted/' . $slug;
            $manifestFile    = $addonExtractDir . '/addon.json';
            if (! is_file($manifestFile)) {
                continue;
            }
            $manifestData = json_decode(ltrim((string) @file_get_contents($manifestFile), "\xEF\xBB\xBF"), true);
            if (! is_array($manifestData)) {
                continue;
            }

            foreach (($manifestData['assets']['css'] ?? []) as $cssRelUrl) {
                $cssRelStr = trim((string) $cssRelUrl);
                if ($cssRelStr === '' || isset($seenKeys[$cssRelStr])) continue;
                $seenKeys[$cssRelStr] = true;

                $cssFileName = basename($cssRelStr);
                $localCss    = $addonExtractDir . '/assets/' . $cssFileName;
                $publicCss   = FCPATH . 'assets/addons/' . $slug . '/' . $cssFileName;

                if (is_file($localCss) && (! is_file($publicCss) || @filemtime($localCss) > @filemtime($publicCss))) {
                    @is_dir(dirname($publicCss)) || @mkdir(dirname($publicCss), 0775, true);
                    @copy($localCss, $publicCss);
                }

                if (is_file($publicCss)) {
                    $cssLinks[] = 'assets/addons/' . $slug . '/' . $cssFileName . '?v=' . (int) @filemtime($publicCss);
                } else {
                    $cssLinks[] = $cssRelStr . '?v=' . (is_file($localCss) ? (int) @filemtime($localCss) : 1);
                }
            }

            foreach (($manifestData['assets']['js'] ?? []) as $jsRelUrl) {
                $jsRelStr = trim((string) $jsRelUrl);
                if ($jsRelStr === '' || isset($seenKeys[$jsRelStr])) continue;
                $seenKeys[$jsRelStr] = true;

                $jsFileName = basename($jsRelStr);
                $localJs    = $addonExtractDir . '/assets/' . $jsFileName;
                $publicJs   = FCPATH . 'assets/addons/' . $slug . '/' . $jsFileName;

                if (is_file($localJs) && (! is_file($publicJs) || @filemtime($localJs) > @filemtime($publicJs))) {
                    @is_dir(dirname($publicJs)) || @mkdir(dirname($publicJs), 0775, true);
                    @copy($localJs, $publicJs);
                }

                if (is_file($publicJs)) {
                    $jsLinks[] = 'assets/addons/' . $slug . '/' . $jsFileName . '?v=' . (int) @filemtime($publicJs);
                } else {
                    $jsLinks[] = $jsRelStr . '?v=' . (is_file($localJs) ? (int) @filemtime($localJs) : 1);
                }
            }
        }

        $assetTagsPayload = [
            'addons_hash' => $currentAddonsHash,
            'css'         => $cssLinks,
            'js'          => $jsLinks,
        ];
        @is_dir(dirname($compiledAssetCacheFile)) || @mkdir(dirname($compiledAssetCacheFile), 0775, true);
        @file_put_contents($compiledAssetCacheFile, json_encode($assetTagsPayload, JSON_UNESCAPED_SLASHES));
    }

    foreach (($assetTagsPayload['css'] ?? []) as $cHref) {
        echo '<link rel="stylesheet" href="' . base_url($cHref) . '">' . "\n";
    }
    foreach (($assetTagsPayload['js'] ?? []) as $jSrc) {
        echo '<script src="' . base_url($jSrc) . '" defer></script>' . "\n";
    }
} catch (\Throwable $e) {
}
?>

<!-- 2. RESPONSIVE COMPONENT FIXES -->
<style id="cms-responsive-component-fixes">
    .cms-split-hero-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
        align-items: center;
        gap: clamp(1.75rem, 4vw, 3.25rem);
    }
    .cms-split-left, .cms-split-right { min-width: 0; width: 100%; }
    .cms-hero-visual-stage { position: relative; width: 100%; max-width: 100%; box-sizing: border-box; }
    .cms-split-img { width: 100%; height: auto; display: block; border-radius: 1rem; object-fit: cover; }
    @media (max-width: 991px) {
        .cms-split-hero-grid { grid-template-columns: minmax(0, 1fr) !important; text-align: center; }
        .cms-split-left { display: flex; flex-direction: column; align-items: center; }
        .cms-split-left .action-btn-group { justify-content: center !important; flex-wrap: wrap; }
        .cms-split-right { max-width: 34rem; margin: 0 auto; }
    }
    @media (max-width: 640px) {
        .cms-split-title, .cms-hero-title { font-size: clamp(1.65rem, 7vw, 2.25rem) !important; line-height: 1.25 !important; word-break: break-word; }
        .cms-split-left .action-btn-group { width: 100%; flex-direction: column; align-items: stretch; }
        .cms-split-left .action-btn-group > a { width: 100%; justify-content: center; text-align: center; box-sizing: border-box; }
        .cms-hero-visual-stage { display: flex; flex-direction: column; gap: 0.75rem; }
        .cms-floating-badge.badge-top-left, .cms-floating-badge.badge-bottom-right {
            position: static !important; transform: none !important; width: 100% !important; box-sizing: border-box; justify-content: center; text-align: center;
        }
        .cms-newsletter-grid, .cms-cta-strip { grid-template-columns: minmax(0, 1fr) !important; flex-direction: column !important; align-items: stretch !important; text-align: center; gap: 1.25rem !important; }
        .cms-lead-input-group { flex-direction: column !important; width: 100% !important; gap: 0.65rem !important; }
        .cms-lead-input-group .field-input, .cms-lead-input-group .btn-primary { width: 100% !important; box-sizing: border-box; justify-content: center; }
        .cms-dynamic-grid, .grid-3-col, .grid-4-col, .cms-footer-grid { grid-template-columns: minmax(0, 1fr) !important; }
    }
</style>

<!-- 3. SERVER-SIDE PRE-PAINT SCOPED CSS VARIABLES (0.00ms Instant Custom Colors) -->
<?php
$compileScopeRule = static function (string $s, array $bData): array {
    $rules = [];
    $targetSelector = $s . ', ' . $s . '.cms-announcement-bar, ' . $s . ' .cms-announcement-bar, '
                    . $s . '.cms-faq-section, ' . $s . ' .cms-faq-section, '
                    . $s . '.cms-reviews-section, ' . $s . ' .cms-reviews-section, '
                    . $s . '.cms-newsletter-section, ' . $s . ' .cms-newsletter-section';
    $varsList = [];
    foreach ($bData as $k => $v) {
        if (! is_scalar($v)) continue;
        $valStr = trim((string) $v);
        if ($valStr === '') continue;
        if (preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|var\([^)]+\)|[0-9]+(\.[0-9]+)?(s|rem|em|%)?)$/', $valStr)) {
            $cleanKey = preg_replace('/[^a-z0-9_-]/i', '-', strtolower((string) $k));
            $varsList[] = ($cleanKey === 'scroll-speed' && is_numeric($valStr)) ? ('--cms-scroll-speed:' . $valStr . 's') : ('--cms-' . $cleanKey . ':' . $valStr);
        }
    }
    if (! empty($bData['bg_color'])) { $varsList[] = '--ann-bg:' . trim((string) $bData['bg_color']); $varsList[] = '--nl-bg:' . trim((string) $bData['bg_color']); }
    if (! empty($bData['text_color'])) { $varsList[] = '--ann-text:' . trim((string) $bData['text_color']); $varsList[] = '--nl-text:' . trim((string) $bData['text_color']); }
    if (! empty($bData['badge_bg'])) $varsList[] = '--ann-badge-bg:' . trim((string) $bData['badge_bg']);
    if (! empty($bData['badge_color'])) $varsList[] = '--ann-badge-color:' . trim((string) $bData['badge_color']);
    if (! empty($bData['scroll_speed']) && is_numeric($bData['scroll_speed'])) {
        $spd = max(4, (int) $bData['scroll_speed']) . 's';
        $varsList[] = '--ann-speed:' . $spd;
        $varsList[] = '--rev-speed:' . $spd;
    }
    if (! empty($bData['accent_color'])) { $varsList[] = '--faq-accent:' . trim((string) $bData['accent_color']); $varsList[] = '--faq-active-accent:' . trim((string) $bData['accent_color']); }
    if (! empty($bData['border_color'])) { $varsList[] = '--faq-border:' . trim((string) $bData['border_color']); $varsList[] = '--nl-border:' . trim((string) $bData['border_color']); }
    if (! empty($bData['btn_bg'])) $varsList[] = '--nl-btn-bg:' . trim((string) $bData['btn_bg']);
    if (! empty($bData['btn_text_col'])) $varsList[] = '--nl-btn-txt:' . trim((string) $bData['btn_text_col']);
    if (! empty($varsList)) $rules[] = $targetSelector . ' {' . implode('; ', array_unique($varsList)) . ';}';
    if (($bData['_bg_mode'] ?? '') === 'custom' && ! empty($bData['_bg_color'])) {
        $rules[] = $s . '.cms-block, ' . $s . ' .cms-block {background-color: ' . trim((string) $bData['_bg_color']) . ' !important;}';
    }
    return $rules;
};

$allPrepaintRules = [];
if (! empty($blocks) && is_array($blocks)) {
    foreach ($blocks as $bIdx => $blkItem) {
        if (! empty($blkItem['data']) && is_array($blkItem['data'])) {
            $allPrepaintRules = array_merge($allPrepaintRules, $compileScopeRule('.cms-scope-blk-' . (int) $bIdx, $blkItem['data']));
        }
    }
}
if (! empty($allPrepaintRules)) {
    echo '<style id="cms-prepaint-scoped-vars">' . "\n" . implode("\n", $allPrepaintRules) . "\n" . '</style>' . "\n";
}
?>
<?php if (! empty($settings['custom_css'])): ?>
<style id="cms-global-custom-css"><?= $settings['custom_css'] ?></style>
<?php endif; ?>
<?php
    $rawHeaderScripts = $settings['header_scripts'] ?? ($settings['head_scripts'] ?? '');
    if (! empty($rawHeaderScripts)) echo $rawHeaderScripts;
?>

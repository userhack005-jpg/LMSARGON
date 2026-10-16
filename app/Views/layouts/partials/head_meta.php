<?php
    $metaTitle = ! empty($settings['meta_title'])
        ? $settings['meta_title']
        : ($pageTitle ?? ($settings['site_title'] ?? 'System Core'));
?>
<title><?= esc($metaTitle) ?></title>

<?php if (! empty($settings['site_favicon'])): ?>
    <link rel="icon" href="<?= base_url(esc($settings['site_favicon'])) ?>">
<?php endif; ?>

<!-- SEO Meta Tags -->
<meta name="robots" content="<?= esc($settings['robots_txt'] ?? 'index, follow') ?>">
<?php if (! empty($settings['meta_description'])): ?>
    <meta name="description" content="<?= esc($settings['meta_description']) ?>">
    <meta property="og:description" content="<?= esc($settings['meta_description']) ?>">
<?php endif; ?>
<?php if (! empty($settings['meta_keywords'])): ?>
    <meta name="keywords" content="<?= esc($settings['meta_keywords']) ?>">
<?php endif; ?>
<meta property="og:title" content="<?= esc($metaTitle) ?>">
<meta property="og:type" content="website">
<?php if (! empty($settings['canonical_url'])): ?>
    <link rel="canonical" href="<?= esc($settings['canonical_url']) ?>">
    <meta property="og:url" content="<?= esc($settings['canonical_url']) ?>">
<?php endif; ?>
<?php if (! empty($settings['og_image'])): ?>
    <meta property="og:image" content="<?= base_url(esc($settings['og_image'])) ?>">
<?php endif; ?>

<!-- Search Engine & Domain Verification Meta Tags -->
<?php if (! empty($settings['google_site_verification'])): ?>
    <meta name="google-site-verification" content="<?= esc($settings['google_site_verification']) ?>">
<?php endif; ?>
<?php if (! empty($settings['meta_domain_verification'])): ?>
    <meta name="facebook-domain-verification" content="<?= esc($settings['meta_domain_verification']) ?>">
<?php endif; ?>
<?php if (! empty($settings['bing_site_verification'])): ?>
    <meta name="msvalidate.01" content="<?= esc($settings['bing_site_verification']) ?>">
<?php endif; ?>

<!-- Google Analytics 4 (GA4) Auto-Snippet -->
<?php if (! empty($settings['google_analytics_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($settings['google_analytics_id']) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= esc($settings['google_analytics_id']) ?>');
    </script>
<?php endif; ?>

<!-- Google Tag Manager (GTM) Auto-Snippet -->
<?php if (! empty($settings['google_tag_manager_id'])): ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?= esc($settings['google_tag_manager_id']) ?>');</script>
<?php endif; ?>

<!-- Meta / Facebook Pixel Auto-Snippet -->
<?php if (! empty($settings['meta_pixel_id'])): ?>
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?= esc($settings['meta_pixel_id']) ?>');
    fbq('track', 'PageView');
    </script>
<?php endif; ?>

<?php
    $themeMode   = ($settings['active_theme_mode'] ?? 'dark') === 'light' ? 'light' : 'dark';
    $fontSans    = $settings['font_family_sans'] ?? 'Inter';
    $fontMono    = $settings['font_family_mono'] ?? 'JetBrains Mono';
    $accentMain  = $settings['primary_accent_color'] ?? '#10b981';
    $accentSec   = $settings['secondary_accent_color'] ?? '#06b6d4';

    if ($themeMode === 'light') {
        $bgCanvas    = $settings['light_bg_canvas'] ?? '#f8fafc';
        $bgSurface   = $settings['light_bg_surface'] ?? '#ffffff';
        $bgElevated  = '#f1f5f9';
        $bgInput     = '#ffffff';
        $txtPrimary  = $settings['light_text_primary'] ?? '#0f172a';
        $txtSecond   = '#475569';
        $txtMuted    = '#64748b';
        $borderCol   = $settings['light_border_color'] ?? '#e2e8f0';
    } else {
        $bgCanvas    = $settings['dark_bg_canvas'] ?? '#090a0f';
        $bgSurface   = $settings['dark_bg_surface'] ?? '#11131c';
        $bgElevated  = '#171a26';
        $bgInput     = '#0c0e16';
        $txtPrimary  = $settings['dark_text_primary'] ?? '#f3f4f6';
        $txtSecond   = '#9ca3af';
        $txtMuted    = '#6b7280';
        $borderCol   = $settings['dark_border_color'] ?? '#232738';
    }

    $googleFontUrl = 'https://fonts.googleapis.com/css2?family='
        . urlencode($fontSans) . ':wght@400;500;600;700&family='
        . urlencode($fontMono) . ':wght@400;500;600&display=swap';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="<?= esc($googleFontUrl) ?>" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css?v=' . time()) ?>">

<!-- Dynamic Theme, Color Palette & Typography Engine -->
<style id="portal-theme-variables">
    :root {
        --bg-canvas: <?= esc($bgCanvas) ?>;
        --bg-surface: <?= esc($bgSurface) ?>;
        --bg-elevated: <?= esc($bgElevated) ?>;
        --bg-input: <?= esc($bgInput) ?>;
        --border-subtle: <?= esc($borderCol) ?>;
        --border-focus: <?= esc($accentMain) ?>;
        --text-primary: <?= esc($txtPrimary) ?>;
        --text-secondary: <?= esc($txtSecond) ?>;
        --text-muted: <?= esc($txtMuted) ?>;
        --accent-emerald: <?= esc($accentMain) ?>;
        --accent-cyan: <?= esc($accentSec) ?>;
        --font-sans: '<?= esc($fontSans) ?>', -apple-system, BlinkMacSystemFont, sans-serif;
        --font-mono: '<?= esc($fontMono) ?>', monospace;
    }
    <?php if ($themeMode === 'light'): ?>
    .app-header, .cms-public-navbar {
        background: rgba(255, 255, 255, 0.88) !important;
    }
    .ambient-grid {
        background-image: 
            linear-gradient(to right, rgba(15, 23, 42, 0.04) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(15, 23, 42, 0.04) 1px, transparent 1px) !important;
    }
    <?php endif; ?>
</style>

<script src="<?= base_url('assets/js/app.js?v=' . time()) ?>" defer></script>

<?php if (! empty($settings['custom_css'])): ?>
    <style id="portal-custom-css"><?= $settings['custom_css'] ?></style>
<?php endif; ?>

<?php if (! empty($settings['header_scripts'])): ?>
    <?= $settings['header_scripts'] ?>
<?php endif; ?>


<!-- Admin-Defined Custom CSS Override -->
<?php if (! empty($settings['custom_css'])): ?>
    <style id="portal-custom-css"><?= $settings['custom_css'] ?></style>
<?php endif; ?>

<!-- Admin-Defined Custom Header Scripts -->
<?php if (! empty($settings['header_scripts'])): ?>
    <?= $settings['header_scripts'] ?>
<?php endif; ?>
<script src="<?= base_url('assets/js/app.js?v=' . time()) ?>" defer></script>
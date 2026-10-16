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

<!-- Core Fonts & Stylesheet -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<?php $assetRoot = rtrim( str_replace('/index.php', '', site_url()), '/' ); ?>
<link rel="stylesheet" href="<?= $assetRoot ?>/assets/css/app.css?v=<?= time() ?>">



<!-- Admin-Defined Custom CSS Override -->
<?php if (! empty($settings['custom_css'])): ?>
    <style id="portal-custom-css"><?= $settings['custom_css'] ?></style>
<?php endif; ?>

<!-- Admin-Defined Custom Header Scripts -->
<?php if (! empty($settings['header_scripts'])): ?>
    <?= $settings['header_scripts'] ?>
<?php endif; ?>
<script src="<?= base_url('assets/js/app.js?v=' . time()) ?>" defer></script>
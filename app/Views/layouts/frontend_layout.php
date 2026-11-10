<!DOCTYPE html>
<?php
    $db       = \Config\Database::connect();
    $settings = is_array($settings ?? null) ? $settings : [];
    if (empty($settings) && $db->tableExists('settings')) {
        foreach ($db->table('settings')->get()->getResultArray() as $sRow) {
            $settings[$sRow['setting_key']] = $sRow['setting_value'];
        }
    }
    $themeMode = strtolower(trim((string) ($settings['active_theme_mode'] ?? 'dark')));
    if (! in_array($themeMode, ['dark', 'light'], true)) {
        $themeMode = 'dark';
    }
    $siteTitle = $settings['site_title'] ?? 'MomoMinds';

    // Auto-hydrate $navPages and $activeFooter so /demo/book/*, /finance/enroll-checkout/*, and /courses/view/*
    // ALWAYS render the exact same Website Topbar and Footer as the Page Builder!
    if (! isset($navPages) || ! is_array($navPages)) {
        $navPages = [];
        if ($db->tableExists('cms_pages')) {
            $navPages = $db->table('cms_pages')
                ->where('status', 'published')
                ->where('show_in_nav', 1)
                ->orderBy('is_homepage', 'DESC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        }
    }
    if (! isset($activeFooter) || ! is_array($activeFooter)) {
        $activeFooter = null;
        if ($db->tableExists('cms_footers')) {
            $activeFooter = $db->table('cms_footers')->where('is_active', 1)->get()->getRowArray();
            if ($activeFooter && ! empty($activeFooter['sections_json'])) {
                $activeFooter['sections'] = json_decode((string) $activeFooter['sections_json'], true) ?: [];
            }
        }
    }
    $footer = $activeFooter;
?>
<html lang="en" data-theme="<?= esc($themeMode) ?>" class="theme-<?= esc($themeMode) ?>">
<head>
    <?= view('layouts/partials/head_meta', [
        'settings'  => $settings,
        'pageTitle' => ($pageTitle ?? $siteTitle) . ' | ' . $siteTitle,
    ]) ?>
    <?php if (is_file(FCPATH . 'assets/css/argon_core.css')): ?>
        <link rel="stylesheet" href="<?= base_url('assets/css/argon_core.css') ?>">
    <?php endif; ?>
    <?php if (is_file(FCPATH . 'assets/addons/page_builder/page_builder.css')): ?>
        <link rel="stylesheet" href="<?= base_url('assets/addons/page_builder/page_builder.css') ?>?v=<?= @filemtime(FCPATH . 'assets/addons/page_builder/page_builder.css') ?>">
    <?php endif; ?>
    <?php if (is_file(FCPATH . 'assets/addons/page_builder/page_builder.js')): ?>
        <script src="<?= base_url('assets/addons/page_builder/page_builder.js') ?>?v=<?= @filemtime(FCPATH . 'assets/addons/page_builder/page_builder.js') ?>" defer></script>
    <?php endif; ?>
</head>
<body class="cms-public-body theme-<?= esc($themeMode) ?>" data-theme="<?= esc($themeMode) ?>" data-base-url="<?= base_url() ?>">
    <?php
        $pbHeaderCandidates = [
            WRITEPATH . 'addons/extracted/page_builder/Views/addons/page_builder/partials/public_header.php',
            APPPATH . 'Views/addons/page_builder/partials/public_header.php',
        ];
        $headerLoaded = false;
        foreach ($pbHeaderCandidates as $cand) {
            if (is_file($cand)) {
                include $cand;
                $headerLoaded = true;
                break;
            }
        }
        if (! $headerLoaded):
    ?>
        <header style="background:var(--bg-surface,#111726);border-bottom:1px solid var(--border-subtle,#232738);padding:0.85rem 1.5rem;">
            <div style="max-width:1180px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <a href="<?= base_url('/') ?>" style="font-weight:800;font-size:1.15rem;color:var(--text-primary,#f8fafc);text-decoration:none;"><?= esc($siteTitle) ?></a>
                <nav style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                    <?php foreach ($navPages as $np): ?>
                        <a href="<?= base_url($np['is_homepage'] ? '/' : esc($np['slug'])) ?>" style="color:var(--text-muted,#94a3b8);text-decoration:none;font-weight:600;font-size:0.9rem;"><?= esc($np['title']) ?></a>
                    <?php endforeach; ?>
                    <?php if (session()->get('isLoggedIn')): ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn-primary btn-sm" style="text-decoration:none;">Dashboard →</a>
                    <?php else: ?>
                        <a href="<?= base_url('login') ?>" class="btn-primary btn-sm" style="text-decoration:none;">Login →</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>
    <?php endif; ?>

    <main class="cms-public-main" style="min-height:calc(100vh - 180px);">
        <?= $this->renderSection('content') ?>
    </main>

    <?php
        $pbFooterCandidates = [
            WRITEPATH . 'addons/extracted/page_builder/Views/addons/page_builder/partials/public_footer.php',
            APPPATH . 'Views/addons/page_builder/partials/public_footer.php',
        ];
        foreach ($pbFooterCandidates as $fCand) {
            if (is_file($fCand)) {
                include $fCand;
                break;
            }
        }
    ?>
</body>
</html>

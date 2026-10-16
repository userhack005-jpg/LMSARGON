<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $this->include('layouts/partials/head_meta') ?>
</head>
<body class="theme-obsidian" data-base-url="<?= base_url() ?>">

    <?php if (! empty($settings['body_scripts'])): ?>
        <?= $settings['body_scripts'] ?>
    <?php endif; ?>

    <div class="ambient-grid"></div>

    <!-- Frontend Website Public Navigation Bar -->
    <header class="cms-public-navbar">
        <div class="cms-nav-inner">
            <a href="<?= base_url() ?>" class="cms-brand-link">
                <?php if (! empty($settings['site_logo'])): ?>
                    <img src="<?= base_url(esc($settings['site_logo'])) ?>" alt="Logo" class="brand-logo-sm">
                <?php else: ?>
                    <div class="brand-monogram-sm"><?= esc(strtoupper(substr($settings['site_title'] ?? 'S', 0, 2))) ?></div>
                <?php endif; ?>
                <span class="cms-brand-title"><?= esc($settings['site_title'] ?? 'Portal') ?></span>
            </a>

            <nav class="cms-nav-links">
                <?php foreach ($navPages as$np): ?>
                    <a href="<?= base_url(esc($np['slug'])) ?>" class="cms-nav-item <?= ($np['id'] ===$page['id']) ? 'is-active' : '' ?>">
                        <?= esc($np['title']) ?>
                    </a>
                <?php endforeach; ?>

                <?php if (session()->get('isLoggedIn')): ?>
                    <a href="<?= base_url('addons/page-builder/editor/' . $page['id']) ?>" class="btn-secondary btn-sm">✎ Edit This Page</a>
                    <a href="<?= base_url('dashboard') ?>" class="btn-primary btn-sm">Dashboard →</a>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="btn-primary btn-sm">Sign In →</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Dynamic Blocks Renderer -->
   <?php
        $globalWidth   = $settings['site_layout_width'] ?? 'boxed';     // 'boxed' or 'full'
        $globalSurface = $settings['default_block_surface'] ?? 'card';  // 'card' or 'seamless'
        $totalBlocks   = count($blocks);
    ?>
    <main class="cms-public-main layout-<?= esc($globalWidth) ?>">
        <?php if (empty($blocks)): ?>
            <div class="cms-block-wrapper is-boxed">
                <section class="console-card">
                    <h2><?= esc($page['title']) ?></h2>
                    <p class="muted-cell">This page has no blocks yet. Open the Visual Page Builder to add sections.</p>
                </section>
            </div>
        <?php else: ?>
            <?php foreach ($blocks as $idx => $blk): ?>
                <?php
                    $type = $blk['type'] ?? 'rich_text';
                    $data = $blk['data'] ?? [];

                    // 1. Resolve Gap (Normal vs No-Gap Merged)
                    $gapMode      = $data['_gap'] ?? 'normal';
                    $prevGapMode  = ($idx > 0) ? ($blocks[$idx - 1]['data']['_gap'] ?? 'normal') : 'normal';

                    // 2. Resolve Width (Centered Boxed vs 100% Full-Width)
                    $wMode        = $data['_width'] ?? 'default';
                    $effectiveW   = ($wMode === 'default') ? $globalWidth : $wMode;

                    // 3. Resolve Surface / Background (Card Box vs Seamless Unified vs Custom Color)
                    $bgMode       = $data['_bg_mode'] ?? 'default';
                    $effectiveBg  = ($bgMode === 'default') ? $globalSurface : $bgMode;
                    $customBgCss  = ($effectiveBg === 'custom' && ! empty($data['_bg_color']))
                        ? 'background-color: ' . esc($data['_bg_color']) . ' !important;'
                        : '';

                    // Classes for seamless merging & width
                    $wrapperClass = 'cms-block-wrapper'
                        . ($effectiveW === 'full' ? ' is-fullwidth' : ' is-boxed')
                        . ($gapMode === 'none' ? ' has-no-bottom-gap' : '')
                        . ($prevGapMode === 'none' ? ' is-merged-from-top' : '');

                    $blockClass = 'cms-block'
                        . ($effectiveBg === 'seamless' ? ' surface-seamless' : ' surface-card')
                        . ($gapMode === 'none' ? ' merge-bottom-radius' : '')
                        . ($prevGapMode === 'none' ? ' merge-top-radius' : '');
                ?>

                <div class="<?= $wrapperClass ?>">
                    <?php if ($type === 'hero_banner'): ?>
                        <section class="<?= $blockClass ?> cms-hero align-<?= esc($data['align'] ?? 'center') ?>" style="<?= $customBgCss ?>">
                            <div class="cms-inner-container">
                                <?php if (! empty($data['badge'])): ?>
                                    <div class="status-pill"><span class="pulse-dot"></span><span class="mono-label"><?= esc($data['badge']) ?></span></div>
                                <?php endif; ?>
                                <h1 class="cms-hero-title"><?= esc($data['heading'] ?? '') ?></h1>
                                <?php if (! empty($data['subheading'])): ?>
                                    <p class="cms-hero-sub"><?= esc($data['subheading']) ?></p>
                                <?php endif; ?>
                                <?php if (! empty($data['btn_text'])): ?>
                                    <div style="margin-top: 1.5rem;">
                                        <a href="<?= esc($data['btn_url'] ?? '#') ?>" class="<?= ($data['btn_style'] ?? 'primary') === 'secondary' ? 'btn-secondary' : 'btn-primary' ?>">
                                            <span><?= esc($data['btn_text']) ?></span>
                                            <span class="btn-arrow">→</span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>

                    <?php elseif ($type === 'rich_text'): ?>
                        <section class="<?= $blockClass ?> cms-richtext-box" style="<?= $customBgCss ?>">
                            <div class="cms-inner-container cms-prose">
                                <?= $data['html'] ?? '' ?>
                            </div>
                        </section>

                    <?php elseif ($type === 'image_media'): ?>
                        <section class="<?= $blockClass ?> cms-media-box" style="<?= $customBgCss ?>">
                            <div class="cms-inner-container">
                                <?php if (! empty($data['image_url'])): ?>
                                    <figure class="cms-figure">
                                        <img src="<?= esc($data['image_url']) ?>" alt="<?= esc($data['alt_text'] ?? 'Image') ?>" style="max-width: <?= esc($data['width'] ?? '100%') ?>;">
                                        <?php if (! empty($data['caption'])): ?>
                                            <figcaption class="field-hint"><?= esc($data['caption']) ?></figcaption>
                                        <?php endif; ?>
                                    </figure>
                                <?php endif; ?>
                            </div>
                        </section>

                    <?php elseif ($type === 'cta_buttons'): ?>
                        <section class="<?= $blockClass ?>" style="<?= $customBgCss ?>">
                            <div class="cms-inner-container cms-cta-strip">
                                <h3><?= esc($data['title'] ?? 'Take the next step') ?></h3>
                                <div class="action-btn-group">
                                    <?php if (! empty($data['primary_text'])): ?>
                                        <a href="<?= esc($data['primary_url'] ?? '#') ?>" class="btn-primary"><?= esc($data['primary_text']) ?></a>
                                    <?php endif; ?>
                                    <?php if (! empty($data['secondary_text'])): ?>
                                        <a href="<?= esc($data['secondary_url'] ?? '#') ?>" class="btn-secondary"><?= esc($data['secondary_text']) ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>

                    <?php elseif ($type === 'interactive_quiz'): ?>
                        <section class="<?= $blockClass ?> cms-quiz-card" style="<?= $customBgCss ?>" data-correct="<?= esc(strtoupper($data['correct_index'] ?? 'A')) ?>" data-explanation="<?= esc($data['explanation'] ?? 'Correct Answer!') ?>">
                            <div class="cms-inner-container">
                                <div class="status-pill"><span class="mono-label">INTERACTIVE KNOWLEDGE CHECK</span></div>
                                <h3 class="cms-quiz-question"><?= esc($data['question'] ?? '') ?></h3>
                                <div class="cms-quiz-options">
                                    <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $letter => $key): ?>
                                        <?php if (! empty($data[$key])): ?>
                                            <button type="button" class="cms-quiz-opt js-quiz-answer" data-choice="<?= $letter ?>">
                                                <span class="step-index"><?= $letter ?></span>
                                                <span><?= esc($data[$key]) ?></span>
                                            </button>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <div class="alert-banner is-hidden js-quiz-feedback" style="margin-top: 1rem;"></div>
                            </div>
                        </section>

                    <?php elseif ($type === 'feature_grid'): ?>
                        <section class="<?= $blockClass ?>" style="<?= $customBgCss ?>">
                            <div class="cms-inner-container">
                                <?php if (! empty($data['section_title'])): ?>
                                    <h2 style="margin-bottom: 1.25rem; text-align: center;"><?= esc($data['section_title']) ?></h2>
                                <?php endif; ?>
                                <div class="grid-3-col">
                                    <div class="panel-card">
                                        <h4 style="color: var(--accent-emerald); margin-bottom: 0.4rem;"><?= esc($data['card1_title'] ?? '') ?></h4>
                                        <p class="muted-cell"><?= esc($data['card1_desc'] ?? '') ?></p>
                                    </div>
                                    <div class="panel-card">
                                        <h4 style="color: var(--accent-cyan); margin-bottom: 0.4rem;"><?= esc($data['card2_title'] ?? '') ?></h4>
                                        <p class="muted-cell"><?= esc($data['card2_desc'] ?? '') ?></p>
                                    </div>
                                    <div class="panel-card">
                                        <h4 style="color: var(--accent-amber); margin-bottom: 0.4rem;"><?= esc($data['card3_title'] ?? '') ?></h4>
                                        <p class="muted-cell"><?= esc($data['card3_desc'] ?? '') ?></p>
                                    </div>
                                </div>
                            </div>
                        </section>

                    <?php elseif ($type === 'custom_html'): ?>
                        <section class="<?= $blockClass ?>" style="<?= $customBgCss ?>">
                            <div class="cms-inner-container">
                                <?= $data['raw_code'] ?? '' ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>
    </main>
<?php
        $fCols     = (int) ($activeFooter['columns_count'] ?? 3);
        $fSections = ! empty($activeFooter['sections_json']) ? (json_decode($activeFooter['sections_json'], true) ?: []) : [];
        $fStyle    = $activeFooter['style_key'] ?? 'style_3col';
    ?>
    <footer class="cms-public-footer cms-footer-<?= esc($fStyle) ?>">
        <div class="cms-footer-inner">
            <?php if (! empty($fSections)): ?>
                <div class="cms-footer-grid cols-<?= $fCols ?>">
                    <?php for ($c = 0; $c < $fCols; $c++): ?>
                        <div class="cms-footer-col">
                            <?php foreach (($fSections[$c] ?? []) as $fBlk): ?>
                                <?php
                                    $bType = $fBlk['type'] ?? 'rich_text';
                                    $bData = $fBlk['data'] ?? [];
                                ?>

                                <?php if ($bType === 'hero_banner'): ?>
                                    <div class="cms-footer-hero" style="margin-bottom: 1rem;">
                                        <?php if (! empty($bData['badge'])): ?>
                                            <div class="status-pill"><span class="pulse-dot"></span><span class="mono-label"><?= esc($bData['badge']) ?></span></div>
                                        <?php endif; ?>
                                        <h3 style="margin: 0.4rem 0;"><?= esc($bData['heading'] ?? '') ?></h3>
                                        <?php if (! empty($bData['subheading'])): ?>
                                            <p class="muted-cell" style="font-size: 0.86rem; margin-bottom: 0.75rem;"><?= esc($bData['subheading']) ?></p>
                                        <?php endif; ?>
                                        <?php if (! empty($bData['btn_text'])): ?>
                                            <a href="<?= esc($bData['btn_url'] ?? '#') ?>" class="btn-primary btn-sm"><?= esc($bData['btn_text']) ?> →</a>
                                        <?php endif; ?>
                                    </div>

                                <?php elseif ($bType === 'rich_text'): ?>
                                    <div class="cms-prose"><?= $bData['html'] ?? '' ?></div>

                                <?php elseif ($bType === 'image_media'): ?>
                                    <?php if (! empty($bData['image_url'])): ?>
                                        <figure class="cms-figure" style="margin-bottom: 0.75rem;">
                                            <img src="<?= esc($bData['image_url']) ?>" alt="<?= esc($bData['alt_text'] ?? 'Image') ?>" style="max-width: <?= esc($bData['width'] ?? '100%') ?>; border-radius: 8px;">
                                            <?php if (! empty($bData['caption'])): ?>
                                                <figcaption class="field-hint"><?= esc($bData['caption']) ?></figcaption>
                                            <?php endif; ?>
                                        </figure>
                                    <?php endif; ?>

                                <?php elseif ($bType === 'cta_buttons'): ?>
                                    <div class="cms-footer-cta" style="margin-bottom: 0.75rem;">
                                        <?php if (! empty($bData['title'])): ?><h4 style="margin-bottom: 0.5rem;"><?= esc($bData['title']) ?></h4><?php endif; ?>
                                        <div class="action-btn-group" style="justify-content: flex-start; flex-wrap: wrap;">
                                            <?php if (! empty($bData['primary_text'])): ?>
                                                <a href="<?= esc($bData['primary_url'] ?? '#') ?>" class="btn-primary btn-sm"><?= esc($bData['primary_text']) ?></a>
                                            <?php endif; ?>
                                            <?php if (! empty($bData['secondary_text'])): ?>
                                                <a href="<?= esc($bData['secondary_url'] ?? '#') ?>" class="btn-secondary btn-sm"><?= esc($bData['secondary_text']) ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                <?php elseif ($bType === 'interactive_quiz'): ?>
                                    <div class="cms-quiz-card" data-correct="<?= esc(strtoupper($bData['correct_index'] ?? 'A')) ?>" data-explanation="<?= esc($bData['explanation'] ?? 'Correct!') ?>" style="margin-bottom: 0.75rem;">
                                        <span class="mono-label">QUICK QUIZ</span>
                                        <h4 style="margin: 0.35rem 0 0.65rem;"><?= esc($bData['question'] ?? '') ?></h4>
                                        <div class="cms-quiz-options" style="grid-template-columns: 1fr;">
                                            <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $letter => $key): ?>
                                                <?php if (! empty($bData[$key])): ?>
                                                    <button type="button" class="cms-quiz-opt js-quiz-answer" data-choice="<?= $letter ?>" style="padding: 0.5rem 0.75rem; font-size: 0.82rem;">
                                                        <span class="step-index"><?= $letter ?></span>
                                                        <span><?= esc($bData[$key]) ?></span>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="alert-banner is-hidden js-quiz-feedback" style="margin-top: 0.65rem;"></div>
                                    </div>

                                <?php elseif ($bType === 'feature_grid'): ?>
                                    <div style="display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 0.75rem;">
                                        <?php if (! empty($bData['section_title'])): ?>
                                            <h4><?= esc($bData['section_title']) ?></h4>
                                        <?php endif; ?>
                                        <?php foreach ([1, 2, 3] as $n): ?>
                                            <?php if (! empty($bData["card{$n}_title"])): ?>
                                                <div style="padding: 0.6rem; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 6px;">
                                                    <strong style="color: var(--accent-emerald); font-size: 0.84rem;"><?= esc($bData["card{$n}_title"]) ?></strong>
                                                    <p class="muted-cell" style="font-size: 0.78rem; margin-top: 0.15rem;"><?= esc($bData["card{$n}_desc"] ?? '') ?></p>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($bType === 'custom_html'): ?>
                                    <div><?= $bData['raw_code'] ?? '' ?></div>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

            <div class="cms-footer-bottom">
                <span class="mono-note"><?= esc($activeFooter['copyright_text'] ?? ('© ' . date('Y') . ' ' . ($settings['site_title'] ?? 'Platform'))) ?></span>
                <span class="mono-note"><?= esc($settings['site_title'] ?? 'Portal') ?> // DYNAMIC ENGINE</span>
            </div>
        </div>
    </footer>
	

    <?php if (! empty($settings['footer_scripts'])): ?>
        <?= $settings['footer_scripts'] ?>
    <?php endif; ?>
</body>
</html>
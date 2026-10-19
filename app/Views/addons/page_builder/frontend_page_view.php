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
    <main class="cms-public-main">
        <?php if (empty($blocks)): ?>
            <section class="console-card">
                <h2><?= esc($page['title']) ?></h2>
                <p class="muted-cell">This page has no blocks yet. Open the Visual Page Builder to add sections.</p>
            </section>
        <?php else: ?>
            <?php foreach ($blocks as $idx =>$blk): ?>
                <?php
                    $type =$blk['type'] ?? 'rich_text';
                    $data =$blk['data'] ?? [];
                ?>

                <?php if ($type === 'hero_banner'): ?>
                    <section class="cms-block cms-hero align-<?= esc($data['align'] ?? 'center') ?>">
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
                    </section>

                <?php elseif ($type === 'rich_text'): ?>
                    <section class="cms-block cms-richtext-box">
                        <div class="cms-prose">
                            <?= $data['html'] ?? '' ?>
                        </div>
                    </section>

                <?php elseif ($type === 'image_media'): ?>
                    <section class="cms-block cms-media-box">
                        <?php if (! empty($data['image_url'])): ?>
                            <figure class="cms-figure">
                                <img src="<?= esc($data['image_url']) ?>" alt="<?= esc($data['alt_text'] ?? 'Image') ?>" style="max-width: <?= esc($data['width'] ?? '100%') ?>;">
                                <?php if (! empty($data['caption'])): ?>
                                    <figcaption class="field-hint"><?= esc($data['caption']) ?></figcaption>
                                <?php endif; ?>
                            </figure>
                        <?php endif; ?>
                    </section>

                <?php elseif ($type === 'cta_buttons'): ?>
                    <section class="cms-block cms-cta-strip">
                        <h3><?= esc($data['title'] ?? 'Take the next step') ?></h3>
                        <div class="action-btn-group">
                            <?php if (! empty($data['primary_text'])): ?>
                                <a href="<?= esc($data['primary_url'] ?? '#') ?>" class="btn-primary"><?= esc($data['primary_text']) ?></a>
                            <?php endif; ?>
                            <?php if (! empty($data['secondary_text'])): ?>
                                <a href="<?= esc($data['secondary_url'] ?? '#') ?>" class="btn-secondary"><?= esc($data['secondary_text']) ?></a>
                            <?php endif; ?>
                        </div>
                    </section>

                <?php elseif ($type === 'interactive_quiz'): ?>
                    <section class="cms-block cms-quiz-card" data-correct="<?= esc(strtoupper($data['correct_index'] ?? 'A')) ?>" data-explanation="<?= esc($data['explanation'] ?? 'Correct Answer!') ?>">
                        <div class="status-pill"><span class="mono-label">INTERACTIVE KNOWLEDGE CHECK</span></div>
                        <h3 class="cms-quiz-question"><?= esc($data['question'] ?? '') ?></h3>
                        <div class="cms-quiz-options">
                            <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $letter =>$key): ?>
                                <?php if (! empty($data[$key])): ?>
                                    <button type="button" class="cms-quiz-opt js-quiz-answer" data-choice="<?= $letter ?>">
                                        <span class="step-index"><?= $letter ?></span>
                                        <span><?= esc($data[$key]) ?></span>
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="alert-banner is-hidden js-quiz-feedback" style="margin-top: 1rem;"></div>
                    </section>

                <?php elseif ($type === 'feature_grid'): ?>
                    <section class="cms-block">
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
                    </section>

                <?php elseif ($type === 'custom_html'): ?>
                    <section class="cms-block">
                        <?= $data['raw_code'] ?? '' ?>
                    </section>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <footer class="app-footer" style="max-width: 1180px; margin: 3rem auto 0; border-radius: 12px 12px 0 0;">
        <span class="mono-note">© <?= date('Y') ?> <?= esc($settings['site_title'] ?? 'Platform') ?></span>
        <span class="mono-note">POWERED BY MODULAR PAGE BUILDER</span>
    </footer>

    <?php if (! empty($settings['footer_scripts'])): ?>
        <?= $settings['footer_scripts'] ?>
    <?php endif; ?>
</body>
</html>
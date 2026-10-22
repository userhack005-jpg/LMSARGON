<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $this->include('layouts/partials/head_meta') ?>
</head>
<body class="theme-obsidian" data-base-url="<?= base_url() ?>">

    <!-- EDUSTRAY-STYLE ULTRA-FAST TOP LOADING PROGRESS BAR -->
    <div id="edustray-top-loader" class="edustray-loader-bar"></div>

    <?php if (! empty($settings['body_scripts'])): ?>
        <?= $settings['body_scripts'] ?>
    <?php endif; ?>

    <div class="ambient-grid"></div>

    <?php
        $globalWidth    =$settings['site_layout_width'] ?? 'boxed';
        $globalSurface  =$settings['default_block_surface'] ?? 'card';
        $floatingBlocks = [];$floatingCtas   = [];

        // Reusable Helper Closure to check if an Add-On is active in `addons` table
        $isAddonActive = static function (string$addonSlug): bool {
            if ($addonSlug === '' or$addonSlug === 'page_builder') {
                return true;
            }
            if (! is_dir(WRITEPATH . 'addons/extracted/' . $addonSlug)) {
                return false;
            }
            try {
                $dbCheck = \Config\Database::connect();
                if ($dbCheck->tableExists('addons')) {$addonRow = $dbCheck->table('addons')->where('slug',$addonSlug)->get()->getRowArray();
                    if (! $addonRow) {
                        return false;
                    }
                    if (isset($addonRow['is_active']) && (int)$addonRow['is_active'] === 0) {
                        return false;
                    }
                    if (isset($addonRow['is_enabled']) && (int)$addonRow['is_enabled'] === 0) {
                        return false;
                    }
                    if (isset($addonRow['status']) && strtolower((string)$addonRow['status']) !== 'active') {
                        return false;
                    }
                }
            } catch (\Throwable $e) {
                return false;
            }
            return true;
        };

        // MODULAR TOP HOOK: Render `announcement_bar` from `writable/addons/extracted/announcements` if active!
        // Passes `$blockScopeClass = 'cms-scope-blk-' . $annIdx` so <head> CSS variables apply in 0.00ms with ZERO Color Flash!
        $extractedAnnBlock = WRITEPATH . 'addons/extracted/announcements/Views/addons/announcements/block_view.php';
        if ($isAddonActive('announcements') && is_file($extractedAnnBlock) && ! empty($blocks)) {
            foreach ($blocks as $annIdx =>$blk) {
                if (($blk['type'] ?? '') === 'announcement_bar') {
                    $data            =$blk['data'] ?? [];
                    $blockScopeClass = 'cms-scope-blk-' . (int)$annIdx;
                    $blockClass      = 'cms-scope-blk-' . (int)$annIdx;
                    include $extractedAnnBlock;
                }
            }
        }
    ?>

    <!-- Frontend Website Sticky Navigation Bar -->
    <header class="cms-public-navbar">
        <div class="cms-nav-inner">
            <a href="<?= base_url() ?>" class="cms-brand-link">
                <?php if (! empty($settings['site_logo'])): ?>
                    <img src="<?= base_url(esc($settings['site_logo'])) ?>" alt="<?= esc($settings['site_title'] ?? 'Logo') ?>" class="brand-logo-sm" decoding="async">
                <?php else: ?>
                    <div class="brand-monogram-sm"><?= esc(strtoupper(substr($settings['site_title'] ?? 'S', 0, 2))) ?></div>
                <?php endif; ?>
                <span class="cms-brand-title"><?= esc($settings['site_title'] ?? 'Portal') ?></span>
            </a>

            <nav class="cms-nav-links">
                <?php foreach (($navPages ?? []) as $np): ?>
                    <a href="<?= base_url(esc($np['slug'] ?? '')) ?>" class="cms-nav-item <?= (($np['id'] ?? 0) === ($page['id'] ?? -1)) ? 'is-active' : '' ?>">
                        <?= esc($np['title'] ?? ($np['page_title'] ?? 'Page')) ?>
                    </a>
                <?php endforeach; ?>

                <?php if (session()->get('isLoggedIn')): ?>
                    <a href="<?= base_url('addons/page-builder/editor/' . ($page['id'] ?? 0)) ?>" class="btn-secondary btn-sm">✎ Edit Page</a>
                    <a href="<?= base_url('dashboard') ?>" class="btn-primary btn-sm">Dashboard →</a>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="btn-primary btn-sm">Sign In →</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Dynamic Blocks Main Content -->
    <main class="cms-public-main layout-<?= esc($globalWidth) ?>">
        <?php foreach (($blocks ?? []) as $idx =>$blk): ?>
            <?php
                $type =$blk['type'] ?? 'rich_text';
                $data =$blk['data'] ?? [];

                // Skip announcement_bar here because it was rendered at the very top
                if ($type === 'announcement_bar') {
                    continue;
                }

				if (! empty($data['is_floating']) && (string) $data['is_floating'] === '1') {
					$floatingCtas[] = ['type' => $type, 'data' => $data, 'idx' => $idx];
					continue;
				}

                $gapMode     = $data['_gap'] ?? 'normal';
                $prevGapMode = ($idx > 0) ? ($blocks[$idx - 1]['data']['_gap'] ?? 'normal') : 'normal';
                $wMode       = $data['_width'] ?? 'default';
                $effectiveW  = ($wMode === 'default' or $wMode === 'inherit' or $wMode === '') ? $globalWidth : $wMode;
                $bgMode      = $data['_bg_mode'] ?? 'default';
                $effectiveBg = ($bgMode === 'default' or $bgMode === 'inherit' or $bgMode === '') ? $globalSurface : $bgMode;
                $customBgCss = ($effectiveBg === 'custom' && ! empty($data['_bg_color']))
                    ? 'background-color: ' . esc($data['_bg_color']) . ' !important;'
                    : '';

                $blockScopeClass = 'cms-scope-blk-' . (int) $idx;

                $wrapperClass = 'cms-block-wrapper js-lazy-reveal ' . $blockScopeClass
                    . ($effectiveW === 'full' ? ' is-fullwidth' : ' is-boxed')
                    . ($gapMode === 'none' ? ' has-no-bottom-gap' : '')
                    . ($prevGapMode === 'none' ? ' is-merged-from-top' : '');

                $blockClass = 'cms-block ' . $blockScopeClass
                    . ($effectiveBg === 'seamless' ? ' surface-seamless' : ' surface-card')
                    . ($gapMode === 'none' ? ' merge-bottom-radius' : '')
                    . ($prevGapMode === 'none' ? ' merge-top-radius' : '');
            ?>


			<?php 
									// Universal Dynamic Component Map Loader (Loads from cms_components if not passed by controller)
					if (empty($componentMap) || ! isset(reset($componentMap)['addon_slug'])) {
						$componentMap = [];
						try {
							$dbComp = \Config\Database::connect();
							if ($dbComp->tableExists('cms_components')) {
								foreach ($dbComp->table('cms_components')->get()->getResultArray() as $cRow) {
									$componentMap[$cRow['component_key']] = $cRow;
								}
							}
						} catch (\Throwable $e) {}
					}

					?>
				
            <div class="<?= $wrapperClass ?> is-revealed dev">
                <?php
                    // UNIVERSAL MODULAR ADD-ON BLOCK LOADER (Checks writable/addons/extracted first!)
              //dev  $targetAddonSlug = $componentMap[$type]['addon_slug'] ?? ($componentMap[$type]['category'] ?? '');
			  
				// Fallback convention: announcements -> announcement_bar, faqs -> faq_accordion, reviews -> testimonial_slider, newsletter -> newsletter_cta
			//dev	if ($targetAddonSlug === '' && ! empty($componentMap[$type]['view_path'])) {
				//dev	$customViewFile = WRITEPATH . 'addons/extracted/' . ltrim($componentMap[$type]['view_path'], '/');
					$targetAddonSlug = $componentMap[$type]['addon_slug'] ?? '';
				if ($targetAddonSlug === '') {
				 foreach (glob(WRITEPATH . 'addons/extracted/*/Views/addons/*/block_view.php') as $bv) {
					if (strpos($bv, '/' . $type . '/') !== false) {
						include $bv;
						continue;
					}
				 }
				} else {
					$customViewFile = WRITEPATH . 'addons/extracted/' . $targetAddonSlug . '/Views/addons/' . $targetAddonSlug . '/block_view.php';
				}
				
				$customViewFile = WRITEPATH . 'addons/extracted/' . $targetAddonSlug . '/Views/addons/' . $targetAddonSlug . '/block_view.php';
				if ($targetAddonSlug !== '' && $isAddonActive($targetAddonSlug) && is_file($customViewFile)) {
					include $customViewFile;
					continue;
				}	
			/* dev 	if ($targetAddonSlug !== '' && $isAddonActive($targetAddonSlug) && is_file($customViewFile)) {
					include $customViewFile;
					continue;
				} */ ?>
				
				
								 
                <?php if ($type === 'split_hero'): ?>
                    <?php
                        $accCol  = $data['accent_color'] ?? 'var(--accent-emerald)';
                        $rawHead = esc($data['heading'] ?? '');
                        $hiWord  = esc($data['highlight_word'] ?? '');
                        if ($hiWord !== '') {
                            $rawHead = str_replace($hiWord, '<span style="color:' . esc($accCol) . ';">' . $hiWord . '</span>', $rawHead);
                        }
                    ?>
                    <section class="<?= $blockClass ?> cms-split-hero" style="<?= $customBgCss ?>">
                        <div class="cms-inner-container cms-split-hero-grid">
                            <div class="cms-split-left">
                                <?php if (! empty($data['badge'])): ?>
                                    <div class="status-pill"><span class="pulse-dot" style="background:<?= esc($accCol) ?>;"></span><span class="mono-label"><?= esc($data['badge']) ?></span></div>
                                <?php endif; ?>
                                <h1 class="cms-split-title"><?= $rawHead ?></h1>
                                <?php if (! empty($data['subheading'])): ?>
                                    <p class="cms-split-sub"><?= esc($data['subheading']) ?></p>
                                <?php endif; ?>

                                <div class="action-btn-group" style="justify-content:flex-start; margin-top:1.75rem; gap:0.85rem;">
                                    <?php if (! empty($data['primary_text'])): ?>
                                        <a href="<?= esc($data['primary_url'] ?? '#') ?>" class="btn-primary" style="background:<?= esc($accCol) ?>;">
                                            <span><?= esc($data['primary_text']) ?></span><span class="btn-arrow">→</span>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (! empty($data['secondary_text'])): ?>
                                        <a href="<?= esc($data['secondary_url'] ?? '#') ?>" class="btn-secondary">
                                            <span><?= esc($data['secondary_text']) ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <?php if (! empty($data['trust_text'])): ?>
                                    <div class="cms-trust-caption">
                                        <span class="text-emerald">✓</span> <span><?= esc($data['trust_text']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="cms-split-right">
                                <div class="cms-hero-visual-stage">
                                    <?php if (! empty($data['image_url'])): ?>
                                        <img src="<?= esc($data['image_url']) ?>" alt="<?= esc($data['heading'] ?? 'Hero') ?>" class="cms-split-img" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <div class="cms-hero-abstract-box" style="border-color: <?= esc($accCol) ?>;">
                                            <div class="mono-label">INTERACTIVE LEARNING ENGINE</div>
                                            <div style="font-size:2.4rem; font-weight:700; color:<?= esc($accCol) ?>; margin:0.5rem 0;">99.4%</div>
                                            <div class="muted-cell">Accuracy & Speed Mastery</div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (! empty($data['stat1_val'])): ?>
                                        <div class="cms-floating-badge badge-top-left" style="border-color: <?= esc($accCol) ?>;">
                                            <strong style="color: <?= esc($accCol) ?>;"><?= esc($data['stat1_val']) ?></strong>
                                            <span><?= esc($data['stat1_label'] ?? '') ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (! empty($data['stat2_val'])): ?>
                                        <div class="cms-floating-badge badge-bottom-right" style="border-color: <?= esc($accCol) ?>;">
                                            <strong style="color: #f59e0b;"><?= esc($data['stat2_val']) ?></strong>
                                            <span><?= esc($data['stat2_label'] ?? '') ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </section>

                <!-- 2. VIDEO POPUP HERO SECTION -->
                <?php elseif ($type === 'video_hero'): ?>
                    <?php $pulseCol = $data['pulse_color'] ?? '#10b981'; ?>
                    <section class="<?= $blockClass ?> cms-video-hero" style="<?= $customBgCss ?>">
                        <div class="cms-inner-container" style="text-align:center;">
                            <?php if (! empty($data['badge'])): ?>
                                <div class="status-pill"><span class="pulse-dot" style="background:<?= esc($pulseCol) ?>;"></span><span class="mono-label"><?= esc($data['badge']) ?></span></div>
                            <?php endif; ?>
                            <h2 class="cms-hero-title"><?= esc($data['title'] ?? '') ?></h2>
                            <?php if (! empty($data['subtitle'])): ?>
                                <p class="cms-hero-sub"><?= esc($data['subtitle']) ?></p>
                            <?php endif; ?>

                            <div class="cms-video-stage" style="background-color: <?= esc($data['overlay_color'] ?? '#11131c') ?>;">
                                <?php if (! empty($data['poster_url'])): ?>
                                    <img src="<?= esc($data['poster_url']) ?>" alt="Video Preview" class="cms-video-poster" loading="lazy" decoding="async">
                                <?php endif; ?>
                                <button type="button"
                                        class="cms-video-play-btn js-open-video-modal"
                                        data-video-url="<?= esc($data['video_url'] ?? '') ?>"
                                        style="background: <?= esc($pulseCol) ?>;">
                                    <span class="play-triangle">▶</span>
                                    <span><?= esc($data['btn_text'] ?? 'Watch Video') ?></span>
                                </button>
                            </div>
                        </div>
                    </section>

                <!-- 3. MULTI-COLUMN CARD GRID (COURSES / SERVICES / FEATURES) -->
                <?php elseif ($type === 'card_grid'): ?>
                    <?php
                        $colsCount = max(2, min(4, (int) ($data['columns'] ?? 3)));
                        $cardBg    = $data['card_bg'] ?? 'var(--bg-elevated)';
                        $cardBdr   = $data['border_color'] ?? 'var(--border-subtle)';
                        $cardAcc   = $data['accent_color'] ?? 'var(--accent-emerald)';
                    ?>
                    <section class="<?= $blockClass ?>" style="<?= $customBgCss ?>">
                        <div class="cms-inner-container">
                            <div class="cms-section-head">
                                <?php if (! empty($data['badge'])): ?>
                                    <div class="status-pill"><span class="mono-label" style="color:<?= esc($cardAcc) ?>;"><?= esc($data['badge']) ?></span></div>
                                <?php endif; ?>
                                <h2><?= esc($data['section_title'] ?? '') ?></h2>
                                <?php if (! empty($data['subtitle'])): ?>
                                    <p class="muted-cell"><?= esc($data['subtitle']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="cms-dynamic-grid cols-<?= $colsCount ?>">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <?php if (! empty($data["c{$i}_title"])): ?>
                                        <article class="cms-course-card" style="background: <?= esc($cardBg) ?>; border-color: <?= esc($cardBdr) ?>;">
                                            <?php if (! empty($data["c{$i}_img"])): ?>
                                                <img src="<?= esc($data["c{$i}_img"]) ?>" alt="<?= esc($data["c{$i}_title"]) ?>" class="cms-course-thumb" loading="lazy" decoding="async">
                                            <?php endif; ?>
                                            <div class="cms-course-body">
                                                <div class="cms-course-meta">
                                                    <?php if (! empty($data["c{$i}_tag"])): ?>
                                                        <span class="role-badge" style="color: <?= esc($cardAcc) ?>; border: 1px solid <?= esc($cardAcc) ?>;"><?= esc($data["c{$i}_tag"]) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (! empty($data["c{$i}_price"])): ?>
                                                        <strong class="mono-cell" style="color: <?= esc($cardAcc) ?>;"><?= esc($data["c{$i}_price"]) ?></strong>
                                                    <?php endif; ?>
                                                </div>
                                                <h3><?= esc($data["c{$i}_title"]) ?></h3>
                                                <p class="muted-cell"><?= esc($data["c{$i}_desc"] ?? '') ?></p>
                                                <?php if (! empty($data["c{$i}_btn"])): ?>
                                                    <a href="<?= esc($data["c{$i}_url"] ?? '#') ?>" class="btn-secondary btn-sm btn-full" style="margin-top:auto; border-color: <?= esc($cardBdr) ?>;">
                                                        <span><?= esc($data["c{$i}_btn"]) ?></span><span>→</span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </article>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </section>

            
                <!-- EXISTING CORE BLOCKS (Hero, Rich Text, Image, CTA, Quiz, Feature Grid, Custom HTML) -->
                <?php elseif ($type === 'hero_banner'): ?>
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
                                        <span><?= esc($data['btn_text']) ?></span><span class="btn-arrow">→</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                <?php elseif ($type === 'rich_text'): ?>
                    <section class="<?= $blockClass ?> cms-richtext-box" style="<?= $customBgCss ?>">
                        <div class="cms-inner-container cms-prose"><?= $data['html'] ?? '' ?></div>
                    </section>

                <?php elseif ($type === 'image_media'): ?>
                    <section class="<?= $blockClass ?> cms-media-box" style="<?= $customBgCss ?>">
                        <div class="cms-inner-container">
                            <?php if (! empty($data['image_url'])): ?>
                                <figure class="cms-figure">
                                    <img src="<?= esc($data['image_url']) ?>" alt="<?= esc($data['alt_text'] ?? 'Image') ?>" style="max-width: <?= esc($data['width'] ?? '100%') ?>;" loading="lazy" decoding="async">
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
                                            <span class="step-index"><?= $letter ?></span><span><?= esc($data[$key]) ?></span>
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
                                <div class="panel-card"><h4 style="color: var(--accent-emerald); margin-bottom: 0.4rem;"><?= esc($data['card1_title'] ?? '') ?></h4><p class="muted-cell"><?= esc($data['card1_desc'] ?? '') ?></p></div>
                                <div class="panel-card"><h4 style="color: var(--accent-cyan); margin-bottom: 0.4rem;"><?= esc($data['card2_title'] ?? '') ?></h4><p class="muted-cell"><?= esc($data['card2_desc'] ?? '') ?></p></div>
                                <div class="panel-card"><h4 style="color: var(--accent-amber); margin-bottom: 0.4rem;"><?= esc($data['card3_title'] ?? '') ?></h4><p class="muted-cell"><?= esc($data['card3_desc'] ?? '') ?></p></div>
                            </div>
                        </div>
                    </section>

                <?php elseif ($type === 'custom_html'): ?>
                    <section class="<?= $blockClass ?>" style="<?= $customBgCss ?>">
                        <div class="cms-inner-container"><?= $data['raw_code'] ?? '' ?></div>
                    </section>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </main>

    <!-- EDUSTRAY-STYLE FLOATING NEWSLETTER / LEAD MAGNET BOX (OVERLAPPING ABOVE FOOTER!) -->
		  <?php foreach ($floatingCtas as $fItem): ?>
			<?php
				$fType = $fItem['type'] ?? 'newsletter_cta';
				$data  = $fItem['data'] ?? $fItem;
				$fSlug = $componentMap[$fType]['addon_slug'] ?? 'newsletter';
				$fView = WRITEPATH . 'addons/extracted/' . $fSlug . '/Views/addons/' . $fSlug . '/block_view.php';
				$blockScopeClass = 'cms-scope-blk-' . (int) ($fItem['idx'] ?? 0);
				$blockClass      = 'cms-block is-floating-overlap ' . $blockScopeClass;
			?>
			<?php if ($isAddonActive($fSlug) && is_file($fView)): ?>
				<div class="cms-floating-cta-wrap <?= esc($blockScopeClass) ?>">
					<?php include $fView; ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>

    <!-- VIDEO POPUP MODAL (ULTRA-LIGHTWEIGHT, LOADS IFRAME ONLY ON CLICK) -->
    <div id="cms-video-modal" class="cms-video-modal is-hidden">
        <div class="cms-video-modal-Dialog">
            <button type="button" class="cms-video-close js-close-video-modal">✕ Close</button>
            <div class="cms-video-iframe-wrap" id="cms-video-iframe-container"></div>
        </div>
    </div>

    <!-- Dynamic Multi-Section Website Footer (Supports ALL Core & Add-On Block Types inside $activeFooter) -->
    <?php
        $fCols     = (int) ($activeFooter['columns_count'] ?? 3);
        $fSections = ! empty($activeFooter['sections_json'])
            ? (is_string($activeFooter['sections_json']) ? (json_decode($activeFooter['sections_json'], true) ?: []) : $activeFooter['sections_json'])
            : [];
        $fStyle    = $activeFooter['style_key'] ?? 'style_3col';
    ?>
    <footer class="cms-public-footer cms-footer-<?= esc($fStyle) ?> <?= ! empty($floatingCtas) ? 'has-floating-cta-above' : '' ?>">
        <div class="cms-footer-inner">
            <?php if (! empty($fSections)): ?>
                <div class="cms-footer-grid cols-<?= $fCols ?>">
                    <?php for ($c = 0; $c < $fCols; $c++): ?>
                        <div class="cms-footer-col">
                            <?php foreach (($fSections[$c] ?? []) as $fbIdx => $fBlk): ?>
                                <?php
                                    $bType     = $fBlk['type'] ?? 'rich_text';
                                    $bData     = $fBlk['data'] ?? [];
                                    $fScopeCls = 'cms-scope-fblk-' . (int) $c . '-' . (int) $fbIdx;
                                ?>
                                <?php if ($bType === 'faq_accordion'): ?>
                                    <?php
                                        $faqViewFile = WRITEPATH . 'addons/extracted/faqs/Views/addons/faqs/block_view.php';
                                        if ($isAddonActive('faqs') && is_file($faqViewFile)) {
                                            $data            = $bData;
                                            $blockScopeClass = $fScopeCls;
                                            $blockClass      = 'cms-block ' . $fScopeCls;
                                            include $faqViewFile;
                                        }
                                    ?>

                               
                             

                                <?php elseif ($bType === 'rich_text'): ?>
                                    <div class="cms-prose <?= $fScopeCls ?>">
                                        <?php if (! empty($bData['title'])): ?>
                                            <h4 style="margin-bottom: 0.4rem;"><?= esc($bData['title']) ?></h4>
                                        <?php endif; ?>
                                        <?= ! empty($bData['html']) ? $bData['html'] : nl2br(esc($bData['body'] ?? '')) ?>
                                    </div>

                                <?php elseif ($bType === 'cta_buttons' or $bType === 'cta_banner'): ?>
                                    <div class="cms-footer-cta <?= $fScopeCls ?>">
                                        <?php if (! empty($bData['title']) or ! empty($bData['heading'])): ?>
                                            <h4 style="margin-bottom:0.5rem;"><?= esc($bData['title'] ?? $bData['heading']) ?></h4>
                                        <?php endif; ?>
                                        <?php if (! empty($bData['subtext'])): ?>
                                            <p class="muted-cell" style="margin-bottom:0.6rem;"><?= esc($bData['subtext']) ?></p>
                                        <?php endif; ?>
                                        <div class="action-btn-group" style="justify-content:flex-start; flex-wrap:wrap;">
                                            <?php if (! empty($bData['primary_text']) or ! empty($bData['btn_label'])): ?>
                                                <a href="<?= esc($bData['primary_url'] ?? ($bData['btn_link'] ?? '#')) ?>" class="btn-primary btn-sm">
                                                    <?= esc($bData['primary_text'] ?? $bData['btn_label']) ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (! empty($bData['secondary_text'])): ?>
                                                <a href="<?= esc($bData['secondary_url'] ?? '#') ?>" class="btn-secondary btn-sm"><?= esc($bData['secondary_text']) ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                <?php elseif ($bType === 'hero_banner' or $bType === 'split_hero'): ?>
                                    <div class="cms-footer-cta <?= $fScopeCls ?>">
                                        <?php if (! empty($bData['badge'])): ?>
                                            <span class="mono-label text-emerald"><?= esc($bData['badge']) ?></span>
                                        <?php endif; ?>
                                        <h4 style="margin:0.35rem 0;"><?= esc($bData['heading'] ?? ($bData['headline'] ?? '')) ?></h4>
                                        <?php if (! empty($bData['subheading']) or ! empty($bData['subheadline'])): ?>
                                            <p class="muted-cell" style="margin-bottom:0.6rem;"><?= esc($bData['subheading'] ?? ($bData['subheadline'] ?? '')) ?></p>
                                        <?php endif; ?>
                                        <?php if (! empty($bData['btn_text']) or ! empty($bData['primary_btn_text'])): ?>
                                            <a href="<?= esc($bData['btn_url'] ?? ($bData['primary_btn_url'] ?? '#')) ?>" class="btn-primary btn-sm">
                                                <?= esc($bData['btn_text'] ?? $bData['primary_btn_text']) ?> →
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                <?php elseif ($bType === 'feature_grid'): ?>
                                    <div class="cms-footer-cta <?= $fScopeCls ?>">
                                        <?php if (! empty($bData['section_title'])): ?>
                                            <h4 style="margin-bottom:0.5rem;"><?= esc($bData['section_title']) ?></h4>
                                        <?php endif; ?>
                                        <?php for ($fi = 1; $fi <= 3; $fi++): ?>
                                            <?php if (! empty($bData["card{$fi}_title"])): ?>
                                                <div style="margin-bottom:0.4rem;">
                                                    <strong class="text-emerald"><?= esc($bData["card{$fi}_title"]) ?>:</strong>
                                                    <span class="muted-cell"><?= esc($bData["card{$fi}_desc"] ?? '') ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>

                                <?php elseif ($bType === 'stats_counter'): ?>
                                    <div class="cms-footer-cta <?= $fScopeCls ?>">
                                        <?php for ($si = 1; $si <= 4; $si++): ?>
                                            <?php if (! empty($bData["stat{$si}_num"]) or ! empty($bData["stat{$si}_val"])): ?>
                                                <div style="margin-bottom:0.35rem;">
                                                    <strong class="text-emerald"><?= esc($bData["stat{$si}_num"] ?? $bData["stat{$si}_val"]) ?></strong>
                                                    <span class="mono-label"> — <?= esc($bData["stat{$si}_label"] ?? '') ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>

                                <?php elseif ($bType === 'video_showcase' && (! empty($bData['embed_url']) or ! empty($bData['video_url']))): ?>
                                    <div class="cms-video-frame <?= $fScopeCls ?>">
                                        <iframe src="<?= esc($bData['embed_url'] ?? $bData['video_url']) ?>" title="Video" loading="lazy" allowfullscreen></iframe>
                                    </div>

                                <?php elseif ($bType === 'image_media' && ! empty($bData['image_url'])): ?>
                                    <div class="<?= $fScopeCls ?>">
                                        <img src="<?= esc($bData['image_url']) ?>" alt="<?= esc($bData['alt_text'] ?? '') ?>" style="max-width:<?= esc($bData['width'] ?? '160px') ?>; border-radius:8px;" loading="lazy">
                                        <?php if (! empty($bData['caption'])): ?>
                                            <div class="field-hint"><?= esc($bData['caption']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                <?php elseif ($bType === 'interactive_quiz'): ?>
                                    <div class="cms-quiz-card <?= $fScopeCls ?>" data-correct="<?= esc(strtoupper($bData['correct_index'] ?? 'A')) ?>" data-explanation="<?= esc($bData['explanation'] ?? 'Correct Answer!') ?>">
                                        <h4 class="cms-quiz-question"><?= esc($bData['question'] ?? '') ?></h4>
                                        <div class="cms-quiz-options">
                                            <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $letter =>$key): ?>
                                                <?php if (! empty($bData[$key])): ?>
                                                    <button type="button" class="cms-quiz-opt js-quiz-answer" data-choice="<?= $letter ?>">
                                                        <span class="step-index"><?= $letter ?></span>
                                                        <span><?= esc($bData[$key]) ?></span>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="alert-banner is-hidden js-quiz-feedback" style="margin-top: 0.5rem;"></div>
                                    </div>

                                <?php else: ?>
                                    <div class="<?= $fScopeCls ?>"><?= $bData['raw_code'] ?? ($bData['html'] ?? '') ?></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

            <div class="cms-footer-bottom">
                <span class="mono-note"><?= esc($activeFooter['copyright_text'] ?? ('© ' . date('Y') . ' ' . ($settings['site_title'] ?? 'Platform'))) ?></span>
                <span class="mono-note"><?= esc($settings['site_title'] ?? 'Portal') ?> // ULTRA-FAST ENGINE</span>
            </div>
        </div>
    </footer>

    <!-- Load Core app.js on Frontend (Prevents duplicate loading on Backend!) -->
    <?php
        $coreJsFile = FCPATH . 'assets/js/app.js';$coreJsVer  = is_file($coreJsFile) ? (string) @filemtime($coreJsFile) : '1.0';
    ?>
    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= esc($coreJsVer) ?>" defer></script>

    <?php if (! empty($settings['footer_scripts'])): ?>
        <?= $settings['footer_scripts'] ?>
    <?php endif; ?>
</body>
</html>
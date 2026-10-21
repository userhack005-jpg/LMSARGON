<?php
    $modelPath = WRITEPATH . 'addons/extracted/reviews/Models/Addons/Reviews/ReviewModel.php';
    if (! class_exists(\App\Models\Addons\Reviews\ReviewModel::class) && is_file($modelPath)) {
        require_once $modelPath;
    }

    $rd          = $data ?? [];
    $catFilter   = trim((string) ($rd['category'] ?? 'all'));
    if ($catFilter === '') {
        $catFilter = 'all';
    }
    $cols        = in_array((string) ($rd['columns'] ?? '3'), ['1', '2', '3'], true) ? (string) $rd['columns'] : '3';
    $autoScroll  = (string) ($rd['auto_scroll'] ?? '1') === '1';
    $animStyle   = in_array((string) ($rd['anim_style'] ?? 'smooth_scroll'), ['smooth_scroll', 'fade'], true)
        ? (string) $rd['anim_style']
        : 'smooth_scroll';
    $scrollDir   = in_array((string) ($rd['scroll_dir'] ?? 'rtl'), ['rtl', 'ltr'], true)
        ? (string) $rd['scroll_dir']
        : 'rtl';
    $scrollSpeed = max(4, min(120, (int) ($rd['scroll_speed'] ?? 28)));

    // Dual Theme Color Mode ('auto' uses global theme vars, 'dual' uses custom dark/light pickers)
    $colorMode   = ($rd['color_mode'] ?? 'auto') === 'dual' ? 'dual' : 'auto';
    $cardBgDark  = ! empty($rd['card_bg_dark']) ? $rd['card_bg_dark'] : ($rd['card_bg'] ?? '#171a26');
    $textDark    = ! empty($rd['text_dark']) ? $rd['text_dark'] : '#f8fafc';
    $starDark    = ! empty($rd['star_dark']) ? $rd['star_dark'] : ($rd['star_color'] ?? '#f59e0b');
    $cardBgLight = ! empty($rd['card_bg_light']) ? $rd['card_bg_light'] : '#ffffff';
    $textLight   = ! empty($rd['text_light']) ? $rd['text_light'] : '#0f172a';
    $starLight   = ! empty($rd['star_light']) ? $rd['star_light'] : '#d97706';

    $dbReviews = [];
    if (class_exists(\App\Models\Addons\Reviews\ReviewModel::class)) {
        $revModel  = new \App\Models\Addons\Reviews\ReviewModel();
        $dbReviews = $revModel->getByCategory($catFilter);
        if (empty($dbReviews) && strtolower($catFilter) !== 'all') {
            $dbReviews = $revModel->getByCategory('all');
        }
    }
?>
<section class="<?= esc($blockClass ?? 'cms-block') ?> cms-reviews-section is-revealed"
         data-color-mode="<?= esc($colorMode) ?>"
         data-card-bg-dark="<?= esc($cardBgDark) ?>"
         data-text-dark="<?= esc($textDark) ?>"
         data-star-dark="<?= esc($starDark) ?>"
         data-card-bg-light="<?= esc($cardBgLight) ?>"
         data-text-light="<?= esc($textLight) ?>"
         data-star-light="<?= esc($starLight) ?>"
         data-auto-scroll="<?= $autoScroll ? '1' : '0' ?>"
         data-anim-style="<?= esc($animStyle) ?>"
         data-scroll-dir="<?= esc($scrollDir) ?>"
         data-scroll-speed="<?= esc((string) $scrollSpeed) ?>">
    <div class="cms-inner-container">
        <div class="cms-section-head">
            <?php if (! empty($rd['badge'])): ?>
                <div class="status-pill"><span class="mono-label"><?= esc($rd['badge']) ?></span></div>
            <?php endif; ?>
            <h2><?= esc(! empty($rd['section_title']) ? $rd['section_title'] : 'What Parents & Students Say') ?></h2>
            <?php if (! empty($rd['subtitle'])): ?>
                <p class="muted-cell"><?= esc($rd['subtitle']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (empty($dbReviews)): ?>
            <div class="panel-card"><p class="muted-cell">No approved student reviews available yet.</p></div>
        <?php elseif ($autoScroll && $animStyle === 'smooth_scroll'): ?>
            <!-- MODE 1: Smooth Continuous Marquee Scroll (RTL or LTR) -->
            <div class="cms-reviews-marquee-viewport">
                <div class="cms-reviews-marquee-track dir-<?= esc($scrollDir) ?>">
                    <?php for ($loop = 0; $loop < 2; $loop++): ?>
                        <div class="cms-reviews-marquee-group" <?= $loop === 1 ? 'aria-hidden="true"' : '' ?>>
                            <?php foreach ($dbReviews as $rv): ?>
                                <?php
                                    $ratingInt = max(1, min(5, (int) ($rv['rating'] ?? 5)));
                                    $starsStr  = str_repeat('★', $ratingInt) . str_repeat('☆', 5 - $ratingInt);
                                    $parts     = explode(' ', trim((string) $rv['person_name']));
                                    $initials  = strtoupper(substr($parts[0] ?? 'S', 0, 1) . substr($parts[1] ?? '', 0, 1));
                                ?>
                                <article class="cms-review-card cms-review-marquee-card">
                                    <div class="cms-review-stars"><?= esc($starsStr) ?></div>
                                    <blockquote class="cms-review-quote">"<?= esc($rv['quote']) ?>"</blockquote>
                                    <div class="cms-review-author">
                                        <?php if (! empty($rv['avatar_url'])): ?>
                                            <img src="<?= esc($rv['avatar_url']) ?>" alt="<?= esc($rv['person_name']) ?>" class="cms-review-avatar" loading="lazy">
                                        <?php else: ?>
                                            <div class="cms-review-avatar-fallback"><?= esc($initials) ?></div>
                                        <?php endif; ?>
                                        <div class="cms-review-meta">
                                            <strong class="cms-review-name"><?= esc($rv['person_name']) ?></strong>
                                            <span class="cms-review-role"><?= esc($rv['person_role']) ?></span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

        <?php elseif ($autoScroll && $animStyle === 'fade'): ?>
            <!-- MODE 2: Fade-In / Fade-Out Animated Spotlight Carousel -->
            <div class="cms-reviews-fade-stage">
                <?php foreach ($dbReviews as $rIdx => $rv): ?>
                    <?php
                        $ratingInt = max(1, min(5, (int) ($rv['rating'] ?? 5)));
                        $starsStr  = str_repeat('★', $ratingInt) . str_repeat('☆', 5 - $ratingInt);
                        $parts     = explode(' ', trim((string) $rv['person_name']));
                        $initials  = strtoupper(substr($parts[0] ?? 'S', 0, 1) . substr($parts[1] ?? '', 0, 1));
                    ?>
                    <article class="cms-review-card cms-review-fade-slide <?= $rIdx === 0 ? 'is-active' : '' ?>" data-slide-idx="<?= esc((string) $rIdx) ?>">
                        <div class="cms-review-stars"><?= esc($starsStr) ?></div>
                        <blockquote class="cms-review-quote">"<?= esc($rv['quote']) ?>"</blockquote>
                        <div class="cms-review-author">
                            <?php if (! empty($rv['avatar_url'])): ?>
                                <img src="<?= esc($rv['avatar_url']) ?>" alt="<?= esc($rv['person_name']) ?>" class="cms-review-avatar" loading="lazy">
                            <?php else: ?>
                                <div class="cms-review-avatar-fallback"><?= esc($initials) ?></div>
                            <?php endif; ?>
                            <div class="cms-review-meta">
                                <strong class="cms-review-name"><?= esc($rv['person_name']) ?></strong>
                                <span class="cms-review-role"><?= esc($rv['person_role']) ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (count($dbReviews) > 1): ?>
                    <div class="cms-reviews-fade-dots">
                        <?php foreach ($dbReviews as $rIdx => $rv): ?>
                            <button type="button" class="cms-rev-dot js-rev-fade-dot <?= $rIdx === 0 ? 'is-active' : '' ?>" data-target="<?= esc((string) $rIdx) ?>" aria-label="Slide <?= esc((string) ($rIdx + 1)) ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- MODE 3: Static Responsive Multi-Column Grid (Auto-Scroll Disabled) -->
            <div class="cms-reviews-grid cols-<?= esc($cols) ?>">
                <?php foreach ($dbReviews as $rv): ?>
                    <?php
                        $ratingInt = max(1, min(5, (int) ($rv['rating'] ?? 5)));
                        $starsStr  = str_repeat('★', $ratingInt) . str_repeat('☆', 5 - $ratingInt);
                        $parts     = explode(' ', trim((string) $rv['person_name']));
                        $initials  = strtoupper(substr($parts[0] ?? 'S', 0, 1) . substr($parts[1] ?? '', 0, 1));
                    ?>
                    <article class="cms-review-card">
                        <div class="cms-review-stars"><?= esc($starsStr) ?></div>
                        <blockquote class="cms-review-quote">"<?= esc($rv['quote']) ?>"</blockquote>
                        <div class="cms-review-author">
                            <?php if (! empty($rv['avatar_url'])): ?>
                                <img src="<?= esc($rv['avatar_url']) ?>" alt="<?= esc($rv['person_name']) ?>" class="cms-review-avatar" loading="lazy">
                            <?php else: ?>
                                <div class="cms-review-avatar-fallback"><?= esc($initials) ?></div>
                            <?php endif; ?>
                            <div class="cms-review-meta">
                                <strong class="cms-review-name"><?= esc($rv['person_name']) ?></strong>
                                <span class="cms-review-role"><?= esc($rv['person_role']) ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

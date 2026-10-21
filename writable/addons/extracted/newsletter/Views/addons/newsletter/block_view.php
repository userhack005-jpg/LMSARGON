<?php
    $nd          = $data ?? [];
    $colorMode   = ! empty($nd['color_mode']) ? $nd['color_mode'] : 'auto';
    $bgCol       = ! empty($nd['bg_color']) ? $nd['bg_color'] : (! empty($nd['card_bg_dark']) ? $nd['card_bg_dark'] : '#0d1f1a');
    $bdrCol      = ! empty($nd['border_color']) ? $nd['border_color'] : '#10b981';
    $txtCol      = ! empty($nd['text_color']) ? $nd['text_color'] : (! empty($nd['text_dark']) ? $nd['text_dark'] : '#f3f4f6');
    $btnBg       = ! empty($nd['btn_bg']) ? $nd['btn_bg'] : (! empty($nd['accent_dark']) ? $nd['accent_dark'] : '#10b981');
    $btnTxt      = ! empty($nd['btn_text_col']) ? $nd['btn_text_col'] : '#04130d';
    $bgLight     = ! empty($nd['card_bg_light']) ? $nd['card_bg_light'] : '#ecfdf5';
    $txtLight    = ! empty($nd['text_light']) ? $nd['text_light'] : '#064e3b';

    $formLayout  = ! empty($nd['form_layout']) ? $nd['form_layout'] : 'name_phone_email';

    $showName = true;
    if ($formLayout === 'email_only' or (isset($nd['show_name_field']) && (string) $nd['show_name_field'] === '0')) {
        $showName = false;
    }

    $showPhone = true;
    if ($formLayout === 'email_only' or $formLayout === 'name_email' or (isset($nd['show_phone_field']) && (string) $nd['show_phone_field'] === '0')) {
        $showPhone = false;
    }

    $showTopic = ($formLayout === 'full_enquiry' or (isset($nd['show_topic_field']) && (string) $nd['show_topic_field'] === '1'));

    $rawTopics   = $nd['course_options'] ?? 'Abacus Level 1, Vedic Maths, Olympiad Prep, Free Trial Class';
    $topicList   = array_filter(array_map('trim', explode(',', (string) $rawTopics)));

    $isFloating  = ($nd['is_floating'] ?? '0') === '1';
    $leadTag     = ! empty($nd['lead_tag']) ? $nd['lead_tag'] : 'Website Lead Magnet';
    $privacyNote = $nd['privacy_note'] ?? '🔒 100% Privacy Guaranteed. Unsubscribe anytime.';
    $currentUri  = service('uri')->getPath() ?: 'home';
?>
<section class="<?= esc($blockClass ?? 'cms-block') ?> <?= esc($blockScopeClass ?? '') ?> cms-newsletter-box cms-newsletter-section <?= $isFloating ? 'is-floating-overlap' : '' ?> is-revealed"
         data-color-mode="<?= esc($colorMode) ?>"
         data-bg-color="<?= esc($bgCol) ?>"
         data-border-color="<?= esc($bdrCol) ?>"
         data-text-color="<?= esc($txtCol) ?>"
         data-btn-bg="<?= esc($btnBg) ?>"
         data-btn-text-col="<?= esc($btnTxt) ?>"
         data-card-bg-light="<?= esc($bgLight) ?>"
         data-text-light="<?= esc($txtLight) ?>">
    <div class="cms-inner-container cms-newsletter-grid">
        <div class="cms-nl-content-col">
            <?php if (! empty($nd['badge'])): ?>
                <span class="req-badge cms-nl-pill-badge"><?= esc($nd['badge']) ?></span>
            <?php endif; ?>
            <h2 class="cms-nl-title"><?= esc($nd['heading'] ?? 'Master Abacus & Mental Math Faster') ?></h2>
            <?php if (! empty($nd['subheading'])): ?>
                <p class="cms-nl-subtitle"><?= esc($nd['subheading']) ?></p>
            <?php endif; ?>
        </div>

        <form action="<?= base_url('addons/newsletter/subscribe') ?>" method="post" class="cms-lead-form js-cms-lead-form js-cms-newsletter-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="lead_tag" value="<?= esc($leadTag) ?>">
            <input type="hidden" name="source_page" value="<?= esc($currentUri) ?>">
            <input type="text" name="website_hp" value="" tabindex="-1" autocomplete="off" class="cms-nl-hp" aria-hidden="true">

            <?php if ($showName or $showPhone or $showTopic): ?>
                <div class="cms-nl-extra-fields">
                    <?php if ($showName): ?>
                        <input type="text"
                               name="full_name"
                               class="field-input cms-nl-field"
                               placeholder="<?= esc($nd['name_placeholder'] ?? 'Enter your full name...') ?>"
                               required>
                    <?php endif; ?>

                    <?php if ($showPhone): ?>
                        <input type="tel"
                               name="phone"
                               class="field-input cms-nl-field"
                               placeholder="<?= esc($nd['phone_placeholder'] ?? 'WhatsApp / Phone number...') ?>"
                               required>
                    <?php endif; ?>

                    <?php if ($showTopic && ! empty($topicList)): ?>
                        <select name="interest_topic" class="field-input cms-nl-field cms-nl-select">
                            <?php foreach ($topicList as $tOpt): ?>
                                <option value="<?= esc($tOpt) ?>"><?= esc($tOpt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="cms-lead-input-group">
                <input type="email"
                       name="email"
                       class="field-input cms-nl-field"
                       placeholder="<?= esc($nd['placeholder'] ?? 'Enter your email address...') ?>"
                       required>
                <button type="submit" class="btn-primary cms-nl-btn">
                    <span class="js-nl-btn-label"><?= esc($nd['btn_text'] ?? 'Get Free Access →') ?></span>
                </button>
            </div>

            <?php if ($privacyNote !== ''): ?>
                <div class="cms-nl-privacy-hint"><?= esc($privacyNote) ?></div>
            <?php endif; ?>

            <div class="js-lead-feedback field-hint cms-nl-feedback" role="status" aria-live="polite"></div>
        </form>
    </div>
</section>

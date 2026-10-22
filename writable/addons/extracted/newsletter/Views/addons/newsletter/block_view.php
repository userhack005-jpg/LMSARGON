<?php
    $modelPath = WRITEPATH . 'addons/extracted/newsletter/Models/Addons/Newsletter/NewsletterModel.php';
    if (! class_exists(\App\Models\Addons\Newsletter\NewsletterModel::class) && is_file($modelPath)) {
        require_once $modelPath;
    }

    $nd        = $data ?? [];
    $colorMode = ! empty($nd['color_mode']) ? $nd['color_mode'] : 'auto';
    $bgCol     = ! empty($nd['bg_color']) ? $nd['bg_color'] : '#0d1f1a';
    $bdrCol    = ! empty($nd['border_color']) ? $nd['border_color'] : '#10b981';
    $txtCol    = ! empty($nd['text_color']) ? $nd['text_color'] : '#f3f4f6';
    $btnBg     = ! empty($nd['btn_bg']) ? $nd['btn_bg'] : '#10b981';
    $btnTxt    = ! empty($nd['btn_text_col']) ? $nd['btn_text_col'] : '#04130d';
    $bgLight   = ! empty($nd['card_bg_light']) ? $nd['card_bg_light'] : '#ecfdf5';
    $txtLight  = ! empty($nd['text_light']) ? $nd['text_light'] : '#064e3b';

    $rawPh    = (string) ($nd['placeholder'] ?? '');
    $rawBadge = (string) ($nd['badge'] ?? 'FREE STUDY KIT & UPDATES');

    // Determine requested Form Template ID / Key from Page Builder block data
    $requestedKey = 'default';
    if (! empty($nd['template_id']) && strtolower((string) $nd['template_id']) !== 'default') {
        $requestedKey = (string) $nd['template_id'];
    } elseif (! empty($nd['form_id']) && (int) $nd['form_id'] > 0) {
        $requestedKey = (string) (int) $nd['form_id'];
    } elseif (! empty($nd['category']) && ! in_array(strtolower((string) $nd['category']), ['all', 'default'], true)) {
        $requestedKey = (string) $nd['category'];
    } elseif (preg_match('/#(?:form)?(\d+)/i', $rawPh, $m)) {
        $requestedKey = (string) $m[1];
    } elseif (preg_match('/#(?:form)?(\d+)/i', $rawBadge, $m)) {
        $requestedKey = (string) $m[1];
    }

    $rawPh    = trim((string) preg_replace('/#(?:form)?\d+/i', '', $rawPh));
    $rawBadge = trim((string) preg_replace('/#(?:form)?\d+/i', '', $rawBadge));

    $tpl = null;
    if (class_exists(\App\Models\Addons\Newsletter\NewsletterModel::class)) {
        $nlModel = new \App\Models\Addons\Newsletter\NewsletterModel();
        $tpl     = $nlModel->resolveTemplate($requestedKey);
    }

    // Fallback to static cache if DB model was unavailable
    if (! is_array($tpl)) {
        $cachePath = WRITEPATH . 'addons/newsletter_forms_cache.json';
        if (is_file($cachePath)) {
            $cachedMap = json_decode((string) file_get_contents($cachePath), true) ?: [];
            $defId     = (int) ($cachedMap['_default_id'] ?? 4);
            $lookupId  = (ctype_digit($requestedKey) && (int) $requestedKey > 0) ? (int) $requestedKey : $defId;
            $tpl       = $cachedMap[$lookupId] ?? ($cachedMap[(string) $lookupId] ?? ($cachedMap[$defId] ?? null));
        }
    }

    $chosenId  = (int) ($tpl['id'] ?? 4);
    $formName  = $tpl['form_name'] ?? ('Form #' . $chosenId);
    $showName  = is_array($tpl) ? ((int) ($tpl['show_name'] ?? 0) === 1) : true;
    $reqName   = is_array($tpl) ? ((int) ($tpl['req_name'] ?? 0) === 1) : true;
    $namePh    = ! empty($tpl['name_placeholder']) ? $tpl['name_placeholder'] : 'Enter your full name...';

    $showPhone = is_array($tpl) ? ((int) ($tpl['show_phone'] ?? 0) === 1) : true;
    $reqPhone  = is_array($tpl) ? ((int) ($tpl['req_phone'] ?? 0) === 1) : true;
    $phonePh   = ! empty($tpl['phone_placeholder']) ? $tpl['phone_placeholder'] : 'WhatsApp / Mobile number...';

    $showEmail = is_array($tpl) ? ((int) ($tpl['show_email'] ?? 0) === 1) : true;
    $reqEmail  = is_array($tpl) ? ((int) ($tpl['req_email'] ?? 0) === 1) : true;
    $emailPh   = ! empty($tpl['email_placeholder']) ? $tpl['email_placeholder'] : ($rawPh !== '' ? $rawPh : 'Enter your email address...');

    $btnLabel  = ! empty($nd['btn_text']) ? trim((string) $nd['btn_text']) : '';
    if (! empty($tpl['btn_text']) && ($btnLabel === '' || $btnLabel === 'Subscribe →' || $btnLabel === 'Subscribe')) {
        $btnLabel = $tpl['btn_text'];
    }
    if ($btnLabel === '') {
        $btnLabel = 'Subscribe →';
    }

    if (! $showName && ! $showPhone && ! $showEmail) {
        $showEmail = true;
        $reqEmail  = true;
    }

    $fieldCount  = ($showName ? 1 : 0) + ($showPhone ? 1 : 0) + ($showEmail ? 1 : 0);
    $isFloating  = ((string) ($nd['is_floating'] ?? '0') === '1');
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
            <?php if ($rawBadge !== ''): ?>
                <span class="req-badge cms-nl-pill-badge"><?= esc($rawBadge) ?></span>
            <?php endif; ?>
            <h2 class="cms-nl-title"><?= esc($nd['heading'] ?? 'Master Abacus & Mental Math Faster') ?></h2>
            <?php if (! empty($nd['subheading'])): ?>
                <p class="cms-nl-subtitle"><?= esc($nd['subheading']) ?></p>
            <?php endif; ?>
        </div>

        <form action="<?= base_url('addons/newsletter/subscribe') ?>" method="post" class="cms-lead-form js-cms-newsletter-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="form_id" value="<?= (int) $chosenId ?>">
            <input type="hidden" name="form_name" value="<?= esc($formName) ?>">
            <input type="hidden" name="source_page" value="<?= esc($currentUri) ?>">
            <input type="text" name="website_hp" value="" tabindex="-1" autocomplete="off" class="cms-nl-hp" aria-hidden="true">

            <?php if ($fieldCount === 1): ?>
                <div class="cms-lead-input-group">
                    <?php if ($showName): ?>
                        <input type="text" name="full_name" class="field-input cms-nl-field" placeholder="<?= esc($namePh) ?>" <?= $reqName ? 'required' : '' ?>>
                    <?php elseif ($showPhone): ?>
                        <input type="tel" name="phone" class="field-input cms-nl-field" placeholder="<?= esc($phonePh) ?>" <?= $reqPhone ? 'required' : '' ?>>
                    <?php else: ?>
                        <input type="email" name="email" class="field-input cms-nl-field" placeholder="<?= esc($emailPh) ?>" <?= $reqEmail ? 'required' : '' ?>>
                    <?php endif; ?>

                    <button type="submit" class="btn-primary cms-nl-btn">
                        <span class="js-nl-btn-label"><?= esc($btnLabel) ?></span>
                    </button>
                </div>
            <?php else: ?>
                <div class="cms-nl-extra-fields">
                    <?php if ($showName): ?>
                        <input type="text" name="full_name" class="field-input cms-nl-field" placeholder="<?= esc($namePh) ?>" <?= $reqName ? 'required' : '' ?>>
                    <?php endif; ?>

                    <?php if ($showPhone && $showEmail): ?>
                        <input type="tel" name="phone" class="field-input cms-nl-field" placeholder="<?= esc($phonePh) ?>" <?= $reqPhone ? 'required' : '' ?>>
                    <?php endif; ?>
                </div>

                <div class="cms-lead-input-group">
                    <?php if ($showEmail): ?>
                        <input type="email" name="email" class="field-input cms-nl-field" placeholder="<?= esc($emailPh) ?>" <?= $reqEmail ? 'required' : '' ?>>
                    <?php elseif ($showPhone): ?>
                        <input type="tel" name="phone" class="field-input cms-nl-field" placeholder="<?= esc($phonePh) ?>" <?= $reqPhone ? 'required' : '' ?>>
                    <?php endif; ?>

                    <button type="submit" class="btn-primary cms-nl-btn">
                        <span class="js-nl-btn-label"><?= esc($btnLabel) ?></span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($privacyNote !== ''): ?>
                <div class="cms-nl-privacy-hint"><?= esc($privacyNote) ?></div>
            <?php endif; ?>

            <div class="js-lead-feedback field-hint cms-nl-feedback" role="status" aria-live="polite"></div>
        </form>
    </div>
</section>

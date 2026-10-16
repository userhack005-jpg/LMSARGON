<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<!-- Notification Banner -->
<div id="settings-toast" class="alert-banner is-hidden" role="alert"></div>

<section class="panel-card">
    <form id="global-settings-form" action="<?= base_url('settings/update') ?>" method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>

        <!-- TOP HEADER WITH DIRECT SUBMIT BUTTON -->
        <div class="panel-header panel-header-wrap">
            <div>
                <span class="mono-label">SYSTEM CONFIGURATION ENGINE // AUTO-MIGRATED</span>
                <h3 class="panel-title">Global Site Settings, SEO & Code Injection</h3>
            </div>

            <div class="action-btn-group">
                <button type="button" id="btn-reinit-schema" data-action-url="<?= base_url('settings/reinitialize') ?>" class="btn-secondary btn-sm" title="Verify & sync missing database keys">
                    <span>↻ Sync DB Schema</span>
                </button>
                <button type="submit" class="btn-primary btn-sm">
                    <span>Save Changes</span>
                    <span class="btn-arrow">✓</span>
                </button>
            </div>
        </div>

        <!-- 4 INTERACTIVE TABS -->
        <div class="settings-tabs" role="tablist">
            <button type="button" class="settings-tab is-active" data-tab-target="tab-branding">
                <span class="step-index">01</span>
                <span>Brand & Identity</span>
            </button>
            <button type="button" class="settings-tab" data-tab-target="tab-seo">
                <span class="step-index">02</span>
                <span>SEO & OpenGraph</span>
            </button>
            <button type="button" class="settings-tab" data-tab-target="tab-analytics">
                <span class="step-index">03</span>
                <span>Analytics & Verification</span>
            </button>
            <button type="button" class="settings-tab" data-tab-target="tab-scripts">
                <span class="step-index">04</span>
                <span>Custom CSS & JS</span>
            </button>
        </div>

        <!-- ==========================================
             TAB 01: BRANDING, TITLE, LOGO & FAVICON
             ========================================== -->
        <div class="settings-pane is-visible" id="tab-branding">
            <div class="grid-2-col">
                <div class="field-group">
                    <label for="site_title" class="field-label">
                        <span>Website / Platform Title</span>
                        <span class="req-badge">REQUIRED</span>
                    </label>
                    <input type="text" id="site_title" name="site_title" class="field-input" value="<?= esc($settings['site_title'] ?? '') ?>" required>
                    <span class="field-hint">Primary name shown in the sidebar, login gateway, and browser title bar.</span>
                </div>

                <div class="field-group">
                    <label for="site_tagline" class="field-label">
                        <span>Brand Tagline / Subtitle</span>
                        <span class="opt-badge">OPTIONAL</span>
                    </label>
                    <input type="text" id="site_tagline" name="site_tagline" class="field-input" value="<?= esc($settings['site_tagline'] ?? '') ?>" placeholder="e.g., Next-Gen Learning Management Portal">
                    <span class="field-hint">Appended to default page titles if Meta Title is left blank.</span>
                </div>
            </div>

            <div class="field-group">
                <label for="contact_email" class="field-label">
                    <span>System Support / Contact Email</span>
                    <span class="opt-badge">OPTIONAL</span>
                </label>
                <input type="email" id="contact_email" name="contact_email" class="field-input mono-input" value="<?= esc($settings['contact_email'] ?? '') ?>" placeholder="support@yourdomain.com">
            </div>

            <div class="section-divider">
                <span class="mono-label">BRAND ASSETS (LOGO & FAVICON REPLACEMENT)</span>
            </div>

            <div class="grid-2-col">
                <!-- Primary Logo Box -->
                <div class="asset-box">
                    <div class="asset-preview-header">
                        <span class="mono-label">PRIMARY LOGO</span>
                        <?php if (! empty($settings['site_logo'])): ?>
                            <label class="remove-asset-check">
                                <input type="checkbox" name="remove_logo" value="1">
                                <span>Remove Current Logo</span>
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="asset-current-preview">
                        <?php if (! empty($settings['site_logo'])): ?>
                            <img src="<?= base_url(esc($settings['site_logo'])) ?>" alt="Current Logo" class="preview-img">
                        <?php else: ?>
                            <span class="mono-note">No Custom Logo Uploaded (Using Monogram)</span>
                        <?php endif; ?>
                    </div>

                    <div class="file-input-wrapper">
                        <input type="file" id="site_logo" name="site_logo" class="field-input" accept=".png,.jpg,.jpeg,.svg,.webp">
                        <span class="field-hint">Supported: PNG, SVG, JPG, WEBP</span>
                    </div>
                </div>

                <!-- Browser Favicon Box -->
                <div class="asset-box">
                    <div class="asset-preview-header">
                        <span class="mono-label">BROWSER FAVICON</span>
                        <?php if (! empty($settings['site_favicon'])): ?>
                            <label class="remove-asset-check">
                                <input type="checkbox" name="remove_favicon" value="1">
                                <span>Remove Current Favicon</span>
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="asset-current-preview">
                        <?php if (! empty($settings['site_favicon'])): ?>
                            <img src="<?= base_url(esc($settings['site_favicon'])) ?>" alt="Current Favicon" class="preview-img-sm">
                        <?php else: ?>
                            <span class="mono-note">No Favicon Configured</span>
                        <?php endif; ?>
                    </div>

                    <div class="file-input-wrapper">
                        <input type="file" id="site_favicon" name="site_favicon" class="field-input" accept=".ico,.png,.svg">
                        <span class="field-hint">Supported: ICO, PNG, SVG (32x32 or 64x64px)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 02: SEO & OPENGRAPH CONFIGURATION
             ========================================== -->
        <div class="settings-pane" id="tab-seo">
            <div class="grid-2-col">
                <div class="field-group">
                    <label for="meta_title" class="field-label">
                        <span>Global SEO Meta Title</span>
                        <span class="opt-badge">SEO</span>
                    </label>
                    <input type="text" id="meta_title" name="meta_title" class="field-input" value="<?= esc($settings['meta_title'] ?? '') ?>" placeholder="Recommended length: 50–60 characters">
                </div>

                <div class="field-group">
                    <label for="robots_txt" class="field-label">
                        <span>Search Engine Robots Directive</span>
                        <span class="req-badge">INDEXING</span>
                    </label>
                    <select id="robots_txt" name="robots_txt" class="field-select mono-input">
                        <?php $currRobots =$settings['robots_txt'] ?? 'index, follow'; ?>
                        <option value="index, follow" <?= $currRobots === 'index, follow' ? 'selected' : '' ?>>index, follow (Allow Full Indexing)</option>
                        <option value="noindex, nofollow" <?= $currRobots === 'noindex, nofollow' ? 'selected' : '' ?>>noindex, nofollow (Block Search Engines)</option>
                        <option value="index, nofollow" <?= $currRobots === 'index, nofollow' ? 'selected' : '' ?>>index, nofollow</option>
                        <option value="noindex, follow" <?= $currRobots === 'noindex, follow' ? 'selected' : '' ?>>noindex, follow</option>
                    </select>
                </div>
            </div>

            <div class="field-group">
                <label for="meta_description" class="field-label">
                    <span>Meta Description</span>
                    <span class="opt-badge">150-160 CHARS</span>
                </label>
                <textarea id="meta_description" name="meta_description" rows="3" class="field-input" placeholder="Write a compelling summary of your platform for search engine results..."><?= esc($settings['meta_description'] ?? '') ?></textarea>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label for="meta_keywords" class="field-label">
                        <span>Meta Keywords (Comma Separated)</span>
                    </label>
                    <input type="text" id="meta_keywords" name="meta_keywords" class="field-input" value="<?= esc($settings['meta_keywords'] ?? '') ?>" placeholder="abacus classes, online learning, portal, education">
                </div>

                <div class="field-group">
                    <label for="canonical_url" class="field-label">
                        <span>Primary Canonical Base URL</span>
                    </label>
                    <input type="url" id="canonical_url" name="canonical_url" class="field-input mono-input" value="<?= esc($settings['canonical_url'] ?? '') ?>" placeholder="https://yourdomain.com">
                </div>
            </div>

            <div class="field-group">
                <label for="og_image" class="field-label">
                    <span>Social Share OpenGraph Banner (`og:image` — 1200x630px)</span>
                    <?php if (! empty($settings['og_image'])): ?>
                        <span class="req-badge">ACTIVE: <?= esc($settings['og_image']) ?></span>
                    <?php endif; ?>
                </label>
                <input type="file" id="og_image" name="og_image" class="field-input" accept=".png,.jpg,.jpeg,.webp">
                <span class="field-hint">Displayed when your website link is shared on WhatsApp, LinkedIn, Facebook, or X.</span>
            </div>
        </div>

        <!-- ==========================================
             TAB 03: ANALYTICS & VERIFICATION TAGS
             ========================================== -->
        <div class="settings-pane" id="tab-analytics">
            <div class="section-divider">
                <span class="mono-label">01 // TRACKING & PIXEL IDENTIFIERS</span>
            </div>

            <div class="grid-3-col">
                <div class="field-group">
                    <label for="google_analytics_id" class="field-label">
                        <span>Google Analytics 4 (GA4) ID</span>
                    </label>
                    <input type="text" id="google_analytics_id" name="google_analytics_id" class="field-input mono-input" value="<?= esc($settings['google_analytics_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
                    <span class="field-hint">Auto-injects global `gtag.js` script in `<head>`.</span>
                </div>

                <div class="field-group">
                    <label for="google_tag_manager_id" class="field-label">
                        <span>Google Tag Manager ID</span>
                    </label>
                    <input type="text" id="google_tag_manager_id" name="google_tag_manager_id" class="field-input mono-input" value="<?= esc($settings['google_tag_manager_id'] ?? '') ?>" placeholder="GTM-XXXXXXX">
                    <span class="field-hint">Auto-injects GTM container in `<head>` & `<body>`.</span>
                </div>

                <div class="field-group">
                    <label for="meta_pixel_id" class="field-label">
                        <span>Meta / Facebook Pixel ID</span>
                    </label>
                    <input type="text" id="meta_pixel_id" name="meta_pixel_id" class="field-input mono-input" value="<?= esc($settings['meta_pixel_id'] ?? '') ?>" placeholder="123456789012345">
                    <span class="field-hint">Auto-injects Meta `fbq('init')` & `PageView` tracker.</span>
                </div>
            </div>

            <div class="section-divider">
                <span class="mono-label">02 // WEBMASTER & DOMAIN OWNERSHIP VERIFICATION</span>
            </div>

            <div class="grid-3-col">
                <div class="field-group">
                    <label for="google_site_verification" class="field-label">
                        <span>Google Search Console Code</span>
                    </label>
                    <input type="text" id="google_site_verification" name="google_site_verification" class="field-input mono-input" value="<?= esc($settings['google_site_verification'] ?? '') ?>" placeholder="Paste content token only">
                    <span class="field-hint">Renders `<meta name="google-site-verification" ...>`</span>
                </div>

                <div class="field-group">
                    <label for="meta_domain_verification" class="field-label">
                        <span>Meta Domain Verification</span>
                    </label>
                    <input type="text" id="meta_domain_verification" name="meta_domain_verification" class="field-input mono-input" value="<?= esc($settings['meta_domain_verification'] ?? '') ?>" placeholder="Paste content token only">
                    <span class="field-hint">Renders `<meta name="facebook-domain-verification" ...>`</span>
                </div>

                <div class="field-group">
                    <label for="bing_site_verification" class="field-label">
                        <span>Bing Webmaster Verification</span>
                    </label>
                    <input type="text" id="bing_site_verification" name="bing_site_verification" class="field-input mono-input" value="<?= esc($settings['bing_site_verification'] ?? '') ?>" placeholder="Paste msvalidate.01 token">
                    <span class="field-hint">Renders `<meta name="msvalidate.01" ...>`</span>
                </div>
            </div>
        </div>

      <!-- ==========================================
             TAB 04: CUSTOM CSS & JAVASCRIPT INJECTION
             ========================================== -->
        <div class="settings-pane" id="tab-scripts">
            <div class="field-group">
                <label for="custom_css" class="field-label">
                    <span>Custom Stylesheet Override (Injected inside &lt;style&gt; in &lt;head&gt;)</span>
                    <span class="opt-badge">PURE CSS (NO &lt;style&gt; TAG NEEDED)</span>
                </label>
                <textarea id="custom_css" name="custom_css" rows="6" class="field-input code-editor" placeholder="/* Write custom CSS overrides here */&#10;.topbar-title { letter-spacing: 0.02em; }"><?= esc($settings['custom_css'] ?? '') ?></textarea>
            </div>

            <div class="field-group">
                <label for="header_scripts" class="field-label">
                    <span>Header Scripts / Custom Meta Tags (Injected before closing &lt;/head&gt;)</span>
                    <span class="opt-badge">HTML / &lt;script&gt; ALLOWED</span>
                </label>
                <textarea id="header_scripts" name="header_scripts" rows="5" class="field-input code-editor" placeholder="<!-- e.g. Clarity, Hotjar, Custom Fonts or Third-Party SDKs -->"><?= esc($settings['header_scripts'] ?? '') ?></textarea>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label for="body_scripts" class="field-label">
                        <span>Body Start Scripts (After opening &lt;body&gt;)</span>
                        <span class="opt-badge">NOSCRIPT / TAGS</span>
                    </label>
                    <textarea id="body_scripts" name="body_scripts" rows="5" class="field-input code-editor" placeholder="<!-- Injected immediately after <body> -->"><?= esc($settings['body_scripts'] ?? '') ?></textarea>
                </div>

                <div class="field-group">
                    <label for="footer_scripts" class="field-label">
                        <span>Footer Scripts / Custom JS (Before closing &lt;/body&gt;)</span>
                        <span class="opt-badge">CHAT WIDGETS / JS</span>
                    </label>
                    <textarea id="footer_scripts" name="footer_scripts" rows="5" class="field-input code-editor" placeholder="&lt;script&gt;&#10;  console.log('Custom Footer JS Loaded');&#10;&lt;/script&gt;"><?= esc($settings['footer_scripts'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- BOTTOM FOOTER WITH MAIN SUBMIT BUTTON -->
        <div class="console-footer">
            <span class="mono-note">ALL CHANGES APPLY GLOBALLY ACROSS LAYOUTS</span>
            <button type="submit" id="btn-save-settings" class="btn-primary">
                <span id="save-settings-text">Save Global Configuration</span>
                <span class="btn-arrow">✓</span>
            </button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
<?= $this->extend('layouts/guest_layout') ?>

<?= $this->section('content') ?>
<main class="shell-container">
    <section class="console-card">
        <header class="console-header">
            <div class="status-pill">
                <span class="pulse-dot"></span>
                <span class="mono-label">BOOTLOADER // V4.0</span>
            </div>
            <h1 class="console-title">System Deployment Wizard</h1>
            <p class="console-subtitle">Configure workspace identity, database connection, and primary Super Admin credentials.</p>

            <div class="step-Architecture">
                <div class="step-node is-active" id="ind-step-1">
                    <span class="step-index">01</span>
                    <span class="step-text">Brand & Identity</span>
                </div>
                <div class="step-connector"></div>
                <div class="step-node" id="ind-step-2">
                    <span class="step-index">02</span>
                    <span class="step-text">Database & Super Admin</span>
                </div>
            </div>
        </header>

        <div id="installer-alert" class="alert-banner is-hidden" role="alert"></div>

        <form id="installer-form" action="<?= base_url('install/process') ?>" method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <!-- STEP 1: Website Identity & Optional Branding -->
            <div class="wizard-pane is-visible" id="pane-step-1">
                <div class="field-group">
                    <label for="site_title" class="field-label">
                        <span>Platform / Website Title</span>
                        <span class="req-badge">REQUIRED</span>
                    </label>
                    <input type="text" id="site_title" name="site_title" class="field-input" placeholder="e.g., Nexus Enterprise Portal" required autocomplete="off">
                    <span class="field-hint">Displayed across browser headers, login workspace, and sidebar navigation.</span>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label for="site_logo" class="field-label">
                            <span>Brand Logo</span>
                            <span class="opt-badge">OPTIONAL</span>
                        </label>
                        <label class="upload-tile" for="site_logo">
                            <input type="file" id="site_logo" name="site_logo" class="sr-file" accept=".png,.jpg,.jpeg,.svg,.webp" data-preview-target="logo-preview-label">
                            <span class="upload-icon">⊞</span>
                            <span class="upload-title" id="logo-preview-label">Select Logo Image</span>
                            <span class="upload-meta">PNG, SVG, JPG or WEBP (Can be skipped)</span>
                        </label>
                    </div>

                    <div class="field-group">
                        <label for="site_favicon" class="field-label">
                            <span>Browser Favicon</span>
                            <span class="opt-badge">OPTIONAL</span>
                        </label>
                        <label class="upload-tile" for="site_favicon">
                            <input type="file" id="site_favicon" name="site_favicon" class="sr-file" accept=".ico,.png,.svg" data-preview-target="fav-preview-label">
                            <span class="upload-icon">◈</span>
                            <span class="upload-title" id="fav-preview-label">Select Favicon</span>
                            <span class="upload-meta">ICO, PNG or SVG (Can be skipped)</span>
                        </label>
                    </div>
                </div>

                <div class="console-footer">
                    <span class="mono-note">STEP 01 OF 02 // ASSETS OPTIONAL</span>
                    <button type="button" id="btn-to-step-2" class="btn-primary">
                        <span>Continue to Database Setup</span>
                        <span class="btn-arrow">→</span>
                    </button>
                </div>
            </div>

            <!-- STEP 2: Database Provisioning & Super Admin -->
            <div class="wizard-pane" id="pane-step-2">
                <div class="section-divider">
                    <span class="mono-label">01 // MYSQL ENGINE CONNECTION</span>
                </div>

                <div class="grid-3-col">
                    <div class="field-group">
                        <label for="db_host" class="field-label"><span>Database Host</span></label>
                        <input type="text" id="db_host" name="db_host" class="field-input mono-input" value="localhost" required>
                    </div>
                    <div class="field-group">
                        <label for="db_port" class="field-label"><span>Port</span></label>
                        <input type="number" id="db_port" name="db_port" class="field-input mono-input" value="3306" required>
                    </div>
                    <div class="field-group">
                        <label for="db_name" class="field-label"><span>Database Name</span></label>
                        <input type="text" id="db_name" name="db_name" class="field-input mono-input" placeholder="ci4_platform_db" required>
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label for="db_user" class="field-label"><span>Database Username</span></label>
                        <input type="text" id="db_user" name="db_user" class="field-input mono-input" value="root" required>
                    </div>
                    <div class="field-group">
                        <label for="db_pass" class="field-label">
                            <span>Database Password</span>
                            <span class="opt-badge">IF ANY</span>
                        </label>
                        <input type="password" id="db_pass" name="db_pass" class="field-input mono-input" placeholder="Leave blank for local root">
                    </div>
                </div>

                <div class="section-divider">
                    <span class="mono-label">02 // SUPER ADMINISTRATOR ACCOUNT</span>
                </div>

                <div class="grid-3-col">
                    <div class="field-group">
                        <label for="admin_name" class="field-label"><span>Full Name</span></label>
                        <input type="text" id="admin_name" name="admin_name" class="field-input" value="Super Admin" required>
                    </div>
                    <div class="field-group">
                        <label for="admin_email" class="field-label"><span>Admin Email</span></label>
                        <input type="email" id="admin_email" name="admin_email" class="field-input" placeholder="admin@domain.com" required>
                    </div>
                    <div class="field-group">
                        <label for="admin_pass" class="field-label"><span>Admin Password</span></label>
                        <input type="password" id="admin_pass" name="admin_pass" class="field-input" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="console-footer">
                    <button type="button" id="btn-back-step-1" class="btn-secondary">
                        <span>← Back</span>
                    </button>
                    <button type="submit" id="btn-submit-install" class="btn-primary">
                        <span id="submit-btn-text">Deploy Schema & Lock Installer</span>
                        <span class="btn-arrow">⚡</span>
                    </button>
                </div>
            </div>
        </form>
    </section>
</main>
<?= $this->endSection() ?>
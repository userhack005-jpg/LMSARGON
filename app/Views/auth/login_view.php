<?= $this->extend('layouts/guest_layout') ?>

<?= $this->section('content') ?>
<main class="shell-container shell-narrow">
    <section class="console-card">
        <header class="console-header">
            <div class="brand-header">
                <?php if (! empty($settings['site_logo'])): ?>
                    <img src="<?= base_url(esc($settings['site_logo'])) ?>" alt="Brand Logo" class="brand-logo">
                <?php else: ?>
                    <div class="brand-monogram">
                        <?= esc(strtoupper(substr($settings['site_title'] ?? 'S', 0, 2))) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <span class="mono-label">AUTHENTICATION GATEWAY</span>
                    <h1 class="console-title"><?= esc($settings['site_title'] ?? 'System Portal') ?></h1>
                </div>
            </div>
        </header>

        <?php if (request()->getGet('installed') === '1'): ?>
            <div class="alert-banner alert-success">
                System provisioned, `.env` configured, and installer locked. Sign in with your Super Admin account.
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('auth_error')): ?>
            <div class="alert-banner alert-error">
                <?= esc(session()->getFlashdata('auth_error')) ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('login/auth') ?>" method="post" class="auth-form">
            <?= csrf_field() ?>
            <div class="field-group">
                <label for="email" class="field-label"><span>Email Address</span></label>
                <input type="email" id="email" name="email" class="field-input" placeholder="admin@domain.com" required autofocus>
            </div>

            <div class="field-group">
                <label for="password" class="field-label"><span>Master Password</span></label>
                <input type="password" id="password" name="password" class="field-input" placeholder="••••••••" required>
            </div>

            <div class="console-footer">
                <span class="mono-note">RBAC ENGINE // ACTIVE</span>
                <button type="submit" class="btn-primary">
                    <span>Access Control Plane</span>
                    <span class="btn-arrow">→</span>
                </button>
            </div>
        </form>
    </section>
</main>
<?= $this->endSection() ?>
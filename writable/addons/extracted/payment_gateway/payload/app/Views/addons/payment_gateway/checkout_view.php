<?= $this->extend('layouts/guest_layout') ?>

<?= $this->section('content') ?>
<main class="shell-container shell-narrow">
    <section class="console-card">
        <header class="console-header">
            <div class="status-pill">
                <span class="pulse-dot"></span>
                <span class="mono-label">FRONTEND ADD-ON // <?= esc(strtoupper($config['active_provider'] ?? 'RAZORPAY')) ?> (<?= esc(strtoupper($config['environment'] ?? 'SANDBOX')) ?>)</span>
            </div>
            <h1 class="console-title"><?= esc($settings['site_title'] ?? 'Portal') ?> Checkout</h1>
            <p class="console-subtitle">Powered dynamically by the installed Payment Gateway Add-On module.</p>
        </header>

        <?php if (request()->getGet('paid')): ?>
            <div class="alert-banner alert-success">
                Payment Confirmed! Reference ID: <strong><?= esc(request()->getGet('paid')) ?></strong>. Check the Backend Add-On Ledger to see this entry!
            </div>
        <?php endif; ?>

        <form action="<?= base_url('pay-checkout/process') ?>" method="post">
            <?= csrf_field() ?>
            <div class="field-group">
                <label class="field-label"><span>Customer Full Name</span></label>
                <input type="text" name="payer_name" class="field-input" placeholder="e.g., Imtiyaz Ali" required>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Customer Email</span></label>
                <input type="email" name="payer_email" class="field-input mono-input" placeholder="customer@domain.com" required>
            </div>

            <div class="field-group">
                <label class="field-label">
                    <span>Amount (<?= esc($config['currency'] ?? 'INR') ?>)</span>
                    <span class="req-badge"><?= esc(strtoupper($config['active_provider'] ?? 'RAZORPAY')) ?></span>
                </label>
                <input type="number" step="0.01" name="amount" class="field-input mono-input" value="1499.00" required>
            </div>

            <div class="console-footer">
                <a href="<?= base_url('addons/payment-gateway') ?>" class="btn-secondary btn-sm">← Back to Admin</a>
                <button type="submit" class="btn-primary">
                    <span>Pay Now via <?= esc(ucfirst($config['active_provider'] ?? 'Gateway')) ?></span>
                    <span class="btn-arrow">→</span>
                </button>
            </div>
        </form>
    </section>
</main>
<?= $this->endSection() ?>
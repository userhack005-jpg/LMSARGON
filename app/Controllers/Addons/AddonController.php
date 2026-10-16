<?php

namespace App\Controllers\Addons;

use App\Controllers\BaseController;
use App\Models\Addons\AddonModel;
use App\Models\Settings\SettingModel;
use ZipArchive;

class AddonController extends BaseController
{
    protected AddonModel $addonModel;
    protected SettingModel $settingModel;
    protected string $lockFile;

    public function __construct()
    {
        helper(['url', 'form', 'filesystem']);
        $this->addonModel   = new AddonModel();
        $this->settingModel = new SettingModel();$this->lockFile     = WRITEPATH . 'installed.lock';
    }

    private function canManageAddons(): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        return in_array((string) session()->get('role_slug'), ['super_admin', 'admin'], true);
    }

    /**
     * Main Add-On Manager Dashboard
     */
    public function index()
    {
        if (! is_file($this->lockFile)) {
            return redirect()->to(base_url('install'));
        }
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->canManageAddons()) {
            return redirect()->to(base_url('dashboard'));
        }

        // Auto-runs 1-time schema check for `addons` table
        $addons   =$this->addonModel->getAllAddons();
        $settings =$this->settingModel->getSystemSettings();

        return view('addons/index_view', [
            'activeMenu' => 'addons',
            'settings'   => $settings,
            'addons'     => $addons,
            'user'       => session()->get(),
            'pageTitle'  => ($settings['site_title'] ?? 'System') . ' — Modular Add-On Engine',
        ]);
    }

    /**
     * AJAX Endpoint: Upload & Unzip Add-On Package
     */
    public function uploadAndInstall()
    {
        if (! $this->canManageAddons()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized: Only Super Admin or Admin can install add-ons.',
            ]);
        }

        $zipFile =$this->request->getFile('addon_zip');
        if (!$zipFile and !$zipFile->isValid()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Please select a valid .zip add-on package file.',
            ]);
        }

        $ext = strtolower($zipFile->getClientExtension());
        if ($ext !== 'zip') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid file format. Only `.zip` packages are supported.',
            ]);
        }

        $pkgDir = WRITEPATH . 'addons/packages/';
        if (! is_dir($pkgDir)) {
            mkdir($pkgDir, 0775, true);
        }

        $savedName = 'pkg_' . time() . '_' . $zipFile->getRandomName();
        $zipFile->move($pkgDir, $savedName);$fullZipPath = $pkgDir .$savedName;

        $result = $this->addonModel->installFromZip($fullZipPath);

        return $this->response->setJSON($result);
    }

    /**
     * AJAX Endpoint: Enable / Disable Installed Add-On
     */
    public function toggleAddon()
    {
        if (! $this->canManageAddons()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized action.']);
        }

        $addonId = (int) $this->request->getPost('addon_id');$result  = $this->addonModel->toggleStatus($addonId);

        return $this->response->setJSON($result);
    }

    /**
     * 1-Click Starter ZIP Generator: Builds a real, working "Payment Gateway Suite" Add-On ZIP!
     * Contains:
     * - addon.json
     * - Routes.php (Backend config routes + Frontend `/pay-demo` checkout website route)
     * - payload/app/Controllers/Addons/PaymentGateway/PaymentController.php
     * - payload/app/Models/Addons/PaymentGateway/PaymentModel.php (with 1-time `payment_transactions` DB migration)
     * - payload/app/Views/addons/payment_gateway/config_view.php (Backend Configuration)
     * - payload/app/Views/addons/payment_gateway/checkout_view.php (Frontend Website Checkout Page)
     */
    public function downloadSampleGatewayZip()
    {
        if (! $this->canManageAddons()) {
            return redirect()->to(base_url('dashboard'));
        }

        $pkgDir = WRITEPATH . 'addons/packages/';
        if (! is_dir($pkgDir)) {
            mkdir($pkgDir, 0775, true);
        }

        $zipPath =$pkgDir . 'payment_gateway_suite_v1.zip';
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. addon.json Manifest
        $manifest = [
            'slug'           => 'payment_gateway',
            'name'           => 'OmniPay Gateway Suite (Razorpay / Stripe / PayPal)',
            'category'       => 'payment_gateway',
            'version'        => '1.0.0',
            'author'         => 'Core Architecture Team',
            'description'    => 'Complete multi-currency Payment Gateway add-on with Backend API Key Configuration, Live/Sandbox toggle, Frontend Website Checkout page, and automated `payment_transactions` ledger table.',
            'admin_route'    => 'addons/payment-gateway',
            'frontend_route' => 'pay-checkout',
            'init_model'     => 'App\\Models\\Addons\\PaymentGateway\\PaymentModel',
            'default_config' => [
                'active_provider' => 'razorpay',
                'environment'     => 'sandbox',
                'currency'        => 'INR',
                'razorpay_key_id' => 'rzp_test_YourKeyHere',
                'razorpay_secret' => '',
                'stripe_pub_key'  => 'pk_test_YourKeyHere',
                'stripe_sec_key'  => '',
                'paypal_client'   => '',
            ],
        ];
        $zip->addFromString('addon.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 2. Add-On Dynamic Routes.php
        $routesPhp = <<<'PHP'
<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

// Backend Admin Configuration Routes
$routes->group('addons/payment-gateway', ['namespace' => 'App\Controllers\Addons\PaymentGateway'], static function ($routes) {
    $routes->get('/', 'PaymentController::adminConfig');$routes->post('save', 'PaymentController::saveConfig');
});

// Frontend Website Checkout Routes
$routes->get('pay-checkout', 'App\Controllers\Addons\PaymentGateway\PaymentController::frontendCheckout');$routes->post('pay-checkout/process', 'App\Controllers\Addons\PaymentGateway\PaymentController::processDemoPayment');
PHP;
        $zip->addFromString('Routes.php',$routesPhp);

        // 3. Add-On Model (with One-Time `ensureSchemaInitialized`)
        $modelPhp = <<<'PHP'
<?php

namespace App\Models\Addons\PaymentGateway;

use CodeIgniter\Model;
use Config\Database;
use Throwable;

class PaymentModel extends Model
{
    protected string $lockFile;

    public function __construct()
    {
        parent::__construct();
        $this->lockFile = WRITEPATH . 'schema_addon_payment_v1.lock';
    }

    /**
     * ONE-TIME DATABASE INITIALIZER FOR PAYMENT GATEWAY ADD-ON
     */
    public function ensureSchemaInitialized(): void
    {
        if (is_file($this->lockFile)) {
            return;
        }

        try {
            $db = Database::connect();$sql = "
                CREATE TABLE IF NOT EXISTS `payment_transactions` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `txn_reference` VARCHAR(80) NOT NULL UNIQUE,
                    `provider` VARCHAR(40) NOT NULL,
                    `payer_name` VARCHAR(120) NOT NULL,
                    `payer_email` VARCHAR(150) NOT NULL,
                    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                    `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
                    `status` ENUM('success', 'pending', 'failed') DEFAULT 'success',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $db->query($sql);

            file_put_contents($this->lockFile, json_encode([
                'addon'       => 'payment_gateway',
                'migrated_at' => date('Y-m-d H:i:s'),
            ], JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
            // Ignore
        }
    }

    public function getTransactions(): array
    {
        $this->ensureSchemaInitialized();
        try {
            $db = Database::connect();
            return $db->table('payment_transactions')->orderBy('id', 'DESC')->limit(25)->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function recordTransaction(array $data): bool
    {
        $this->ensureSchemaInitialized();
        try {
            $db = Database::connect();
            return (bool) $db->table('payment_transactions')->insert($data);
        } catch (Throwable $e) {
            return false;
        }
    }
}
PHP;
        $zip->addFromString('payload/app/Models/Addons/PaymentGateway/PaymentModel.php',$modelPhp);

        // 4. Add-On Controller (Handles both Backend Config & Frontend Website Checkout)
        $controllerPhp = <<<'PHP'
<?php

namespace App\Controllers\Addons\PaymentGateway;

use App\Controllers\BaseController;
use App\Models\Addons\AddonModel;
use App\Models\Addons\PaymentGateway\PaymentModel;
use App\Models\Settings\SettingModel;

class PaymentController extends BaseController
{
    protected AddonModel $addonModel;
    protected PaymentModel $paymentModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->addonModel   = new AddonModel();
        $this->paymentModel = new PaymentModel();$this->settingModel = new SettingModel();
    }

    /**
     * Backend Admin Configuration Page
     */
    public function adminConfig()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $addon =$this->addonModel->getAddonBySlug('payment_gateway');
        if (! $addon) {
            return redirect()->to(base_url('addons'));
        }

        $config       = json_decode((string)$addon['config_json'], true) ?: [];
        $transactions =$this->paymentModel->getTransactions();
        $settings     =$this->settingModel->getSystemSettings();

        return view('addons/payment_gateway/config_view', [
            'activeMenu'   => 'addon_payment_gateway',
            'settings'     => $settings,
            'addon'        => $addon,
            'config'       => $config,
            'transactions' => $transactions,
            'user'         => session()->get(),
            'pageTitle'    => 'Payment Gateway Configuration — Add-On Module',
        ]);
    }

    /**
     * Save Backend Gateway API Keys & Settings
     */
    public function saveConfig()
    {
        if (! session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $newConfig = [
            'active_provider' => trim((string) $this->request->getPost('active_provider')),
            'environment'     => trim((string) $this->request->getPost('environment')),
            'currency'        => strtoupper(trim((string) $this->request->getPost('currency')) ?: 'INR'),
            'razorpay_key_id' => trim((string) $this->request->getPost('razorpay_key_id')),
            'razorpay_secret' => trim((string) $this->request->getPost('razorpay_secret')),
            'stripe_pub_key'  => trim((string) $this->request->getPost('stripe_pub_key')),
            'stripe_sec_key'  => trim((string) $this->request->getPost('stripe_sec_key')),
            'paypal_client'   => trim((string) $this->request->getPost('paypal_client')),
        ];

        $this->addonModel->updateAddonConfig('payment_gateway',$newConfig);

        return redirect()->to(base_url('addons/payment-gateway?saved=1'));
    }

    /**
     * Frontend Website Checkout View (`/pay-checkout`)
     */
    public function frontendCheckout()
    {
        $addon =$this->addonModel->getAddonBySlug('payment_gateway');
        if (! $addon and (int)$addon['is_active'] !== 1) {
            return redirect()->to(base_url('login'));
        }

        $config   = json_decode((string)$addon['config_json'], true) ?: [];
        $settings =$this->settingModel->getSystemSettings();

        return view('addons/payment_gateway/checkout_view', [
            'settings'  => $settings,
            'addon'     => $addon,
            'config'    => $config,
            'pageTitle' => ($settings['site_title'] ?? 'Portal') . ' — Secure Payment Checkout',
        ]);
    }

    /**
     * Process Frontend Checkout Simulation & Record in `payment_transactions`
     */
    public function processDemoPayment()
    {
        $addon  =$this->addonModel->getAddonBySlug('payment_gateway');
        $config = json_decode((string) ($addon['config_json'] ?? '{}'), true) ?: [];

        $payerName  = trim((string)$this->request->getPost('payer_name')) ?: 'Guest Customer';
        $payerEmail = trim((string)$this->request->getPost('payer_email')) ?: 'customer@example.com';
        $amount     = (float) ($this->request->getPost('amount') ?: 999.00);

        $txnRef = 'TXN-' . strtoupper(bin2hex(random_bytes(4)));

        $this->paymentModel->recordTransaction([
            'txn_reference' => $txnRef,
            'provider'      => $config['active_provider'] ?? 'razorpay',
            'payer_name'    => $payerName,
            'payer_email'   => $payerEmail,
            'amount'        => $amount,
            'currency'      => $config['currency'] ?? 'INR',
            'status'        => 'success',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url('pay-checkout?paid=' . $txnRef));
    }
}
PHP;
        $zip->addFromString('payload/app/Controllers/Addons/PaymentGateway/PaymentController.php',$controllerPhp);

        // 5. Backend Admin View (`config_view.php`)
        $configView = <<<'HTML'
<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php if (request()->getGet('saved') === '1'): ?>
    <div class="alert-banner alert-success">Payment Gateway configuration saved successfully!</div>
<?php endif; ?>

<div class="rbac-split-grid">
    <!-- Left: Gateway API Credentials Config -->
    <section class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">ADD-ON BACKEND // V<?= esc($addon['version']) ?></span>
                <h3 class="panel-title"><?= esc($addon['name']) ?></h3>
            </div>
            <a href="<?= base_url('pay-checkout') ?>" target="_blank" class="btn-secondary btn-sm">Open Frontend Checkout ↗</a>
        </div>

        <form action="<?= base_url('addons/payment-gateway/save') ?>" method="post">
            <?= csrf_field() ?>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Active Gateway</span></label>
                    <select name="active_provider" class="field-select">
                        <?php $prov =$config['active_provider'] ?? 'razorpay'; ?>
                        <option value="razorpay" <?= $prov === 'razorpay' ? 'selected' : '' ?>>Razorpay (India & Global)</option>
                        <option value="stripe" <?= $prov === 'stripe' ? 'selected' : '' ?>>Stripe (Cards & Apple Pay)</option>
                        <option value="paypal" <?= $prov === 'paypal' ? 'selected' : '' ?>>PayPal Express</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Environment Mode</span></label>
                    <select name="environment" class="field-select">
                        <?php $env =$config['environment'] ?? 'sandbox'; ?>
                        <option value="sandbox" <?= $env === 'sandbox' ? 'selected' : '' ?>>Sandbox / Test Mode</option>
                        <option value="live" <?= $env === 'live' ? 'selected' : '' ?>>Live Production Mode</option>
                    </select>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Default Currency ISO (e.g., INR, USD, AED, GBP)</span></label>
                <input type="text" name="currency" class="field-input mono-input" value="<?= esc($config['currency'] ?? 'INR') ?>">
            </div>

            <div class="section-divider"><span class="mono-label">RAZORPAY CREDENTIALS</span></div>
            <div class="field-group">
                <label class="field-label"><span>Razorpay Key ID</span></label>
                <input type="text" name="razorpay_key_id" class="field-input mono-input" value="<?= esc($config['razorpay_key_id'] ?? '') ?>">
            </div>
            <div class="field-group">
                <label class="field-label"><span>Razorpay Key Secret</span></label>
                <input type="password" name="razorpay_secret" class="field-input mono-input" value="<?= esc($config['razorpay_secret'] ?? '') ?>">
            </div>

            <div class="section-divider"><span class="mono-label">STRIPE CREDENTIALS</span></div>
            <div class="field-group">
                <label class="field-label"><span>Stripe Publishable Key</span></label>
                <input type="text" name="stripe_pub_key" class="field-input mono-input" value="<?= esc($config['stripe_pub_key'] ?? '') ?>">
            </div>
            <div class="field-group">
                <label class="field-label"><span>Stripe Secret Key</span></label>
                <input type="password" name="stripe_sec_key" class="field-input mono-input" value="<?= esc($config['stripe_sec_key'] ?? '') ?>">
            </div>

            <button type="submit" class="btn-primary btn-full">Save Gateway Configuration ✓</button>
        </form>
    </section>

    <!-- Right: Live Transactions Table (Auto-Created by Add-on Model) -->
    <section class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">ADD-ON DATABASE TABLE // `payment_transactions`</span>
                <h3 class="panel-title">Recent Gateway Transactions</h3>
            </div>
            <span class="req-badge">AUTO-MIGRATED</span>
        </div>

        <div class="role-table-wrapper">
            <table class="role-table">
                <thead>
                    <tr>
                        <th>TXN Reference</th>
                        <th>Provider</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="6" class="muted-cell">No transactions yet. Click "Open Frontend Checkout" to test a payment!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as$t): ?>
                        <tr>
                            <td class="mono-cell"><strong><?= esc($t['txn_reference']) ?></strong></td>
                            <td><span class="role-badge role-admin"><?= esc($t['provider']) ?></span></td>
                            <td>
                                <div><?= esc($t['payer_name']) ?></div>
                                <div class="identity-email"><?= esc($t['payer_email']) ?></div>
                            </td>
                            <td class="mono-cell"><strong><?= esc($t['currency']) ?> <?= esc(number_format((float)$t['amount'], 2)) ?></strong></td>
                            <td><span class="status-indicator status-active"><?= esc(strtoupper($t['status'])) ?></span></td>
                            <td class="mono-cell muted-cell"><?= esc($t['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
HTML;
        $zip->addFromString('payload/app/Views/addons/payment_gateway/config_view.php',$configView);

        // 6. Frontend Website Checkout View (`checkout_view.php`)
        $checkoutView = <<<'HTML'
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
HTML;
        $zip->addFromString('payload/app/Views/addons/payment_gateway/checkout_view.php',$checkoutView);

        $zip->close();

        return $this->response->download($zipPath, null)->setFileName('payment_gateway_suite_v1.zip');
    }
}
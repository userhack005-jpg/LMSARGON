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
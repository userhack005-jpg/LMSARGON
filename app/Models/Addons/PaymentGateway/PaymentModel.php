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
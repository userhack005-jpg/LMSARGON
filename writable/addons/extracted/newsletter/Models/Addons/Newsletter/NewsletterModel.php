<?php

namespace App\Models\Addons\Newsletter;

use CodeIgniter\Model;

class NewsletterModel extends Model
{
    protected $table            = 'cms_newsletter_leads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'full_name',
        'email',
        'phone',
        'interest_topic',
        'lead_tag',
        'source_page',
        'ip_address',
        'status',
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $lockFile = WRITEPATH . 'addons/schema_addon_newsletter_v9.lock';
        if (is_file($lockFile) && $this->db->tableExists($this->table)) {
            return;
        }

        try {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `cms_newsletter_leads` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `full_name` VARCHAR(120) NOT NULL DEFAULT '',
                    `email` VARCHAR(190) NOT NULL,
                    `phone` VARCHAR(45) NOT NULL DEFAULT '',
                    `interest_topic` VARCHAR(140) NOT NULL DEFAULT '',
                    `lead_tag` VARCHAR(90) NOT NULL DEFAULT 'Website Lead Magnet',
                    `source_page` VARCHAR(160) NOT NULL DEFAULT '',
                    `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
                    `status` VARCHAR(20) NOT NULL DEFAULT 'subscribed',
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    UNIQUE KEY `uniq_lead_email` (`email`),
                    KEY `idx_status_tag` (`status`, `lead_tag`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $fields = $this->db->getFieldNames($this->table);
            if (! in_array('interest_topic', $fields, true)) {$this->db->query("ALTER TABLE `cms_newsletter_leads` ADD COLUMN `interest_topic` VARCHAR(140) NOT NULL DEFAULT '' AFTER `phone`");
            }

            $count = (int) $this->db->table($this->table)->countAllResults();
            if ($count === 0) {$now = date('Y-m-d H:i:s');
                $this->db->table($this->table)->insertBatch([
                    [
                        'full_name'      => 'Aarav Sharma',
                        'email'          => 'aarav.parent@example.com',
                        'phone'          => '+971 50 123 4567',
                        'interest_topic' => 'Abacus Level 1',
                        'lead_tag'       => 'Free Study Kit',
                        'source_page'    => 'home',
                        'ip_address'     => '127.0.0.1',
                        'status'         => 'subscribed',
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ],
                    [
                        'full_name'      => 'Fatima Al-Mansoor',
                        'email'          => 'fatima.uae@example.com',
                        'phone'          => '+971 52 987 6543',
                        'interest_topic' => 'Free Trial Class',
                        'lead_tag'       => 'Website Lead Magnet',
                        'source_page'    => 'courses',
                        'ip_address'     => '127.0.0.1',
                        'status'         => 'subscribed',
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ],
                ]);
            }

            @is_dir(dirname($lockFile)) or @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, date('c'));
        } catch (\Throwable $e) {
            log_message('error', 'NewsletterModel schema error: ' . $e->getMessage());
        }
    }

    public function getStats(): array
    {
        $total  = (int) $this->db->table($this->table)->countAllResults();
        $active = (int)$this->db->table($this->table)->where('status', 'subscribed')->countAllResults();$unsub  = max(0, $total -$active);

        $tagsRows = $this->db->table($this->table)
            ->select('lead_tag, COUNT(id) as cnt')
            ->groupBy('lead_tag')
            ->orderBy('cnt', 'DESC')
            ->get()
            ->getResultArray();

        return [
            'total'      => $total,
            'subscribed' => $active,
            'unsub'      => $unsub,
            'tags'       => $tagsRows,
        ];
    }

    public function upsertLead(array $payload): array
    {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email === '' or ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Please enter a valid email address.'];
        }

        $now      = date('Y-m-d H:i:s');
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $phone    = trim((string) ($payload['phone'] ?? ''));
        $topic    = trim((string) ($payload['interest_topic'] ?? ''));
        $leadTag  = trim((string) ($payload['lead_tag'] ?? 'Website Lead Magnet')) ?: 'Website Lead Magnet';
        $srcPage  = trim((string) ($payload['source_page'] ?? ''));
        $ipAddr   = trim((string) ($payload['ip_address'] ?? ''));

        $existing =$this->db->table($this->table)->where('email',$email)->get()->getRowArray();

        if ($existing) {$updateData = [
                'status'     => 'subscribed',
                'updated_at' => $now,
            ];
            if ($fullName !== '') {
                $updateData['full_name'] =$fullName;
            }
            if ($phone !== '') {
                $updateData['phone'] =$phone;
            }
            if ($topic !== '') {
                $updateData['interest_topic'] =$topic;
            }
            if ($leadTag !== '') {
                $updateData['lead_tag'] =$leadTag;
            }
            $this->db->table($this->table)->where('id', (int) $existing['id'])->update($updateData);

            return [
                'ok'      => true,
                'is_new'  => false,
                'message' => 'Thank you! Your details have been updated in our records.',
            ];
        }

        $this->db->table($this->table)->insert([
            'full_name'      => $fullName,
            'email'          => $email,
            'phone'          => $phone,
            'interest_topic' => $topic,
            'lead_tag'       => $leadTag,
            'source_page'    => $srcPage,
            'ip_address'     => $ipAddr,
            'status'         => 'subscribed',
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        return [
            'ok'      => true,
            'is_new'  => true,
            'message' => 'Successfully subscribed! Your details have been saved.',
        ];
    }
}

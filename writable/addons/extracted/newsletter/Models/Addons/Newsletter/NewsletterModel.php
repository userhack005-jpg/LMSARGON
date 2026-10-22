<?php

namespace App\Models\Addons\Newsletter;

use CodeIgniter\Model;
use Config\Database;
use Throwable;

class NewsletterModel extends Model
{
    protected $table            = 'cms_newsletter_leads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'form_id',
        'form_name',
        'full_name',
        'email',
        'phone',
        'source_page',
        'ip_address',
        'status',
        'created_at',
        'updated_at',
    ];

    protected string $formsTable = 'cms_newsletter_forms';
    protected string $lockFile;
    protected string $cacheFile;

    public function __construct()
    {
        parent::__construct();
        $this->lockFile  = WRITEPATH . 'schema_addon_newsletter_v25.lock';
        $this->cacheFile = WRITEPATH . 'addons/newsletter_forms_cache.json';
        $this->ensureSchemaInitialized();
    }

    public function ensureSchema(): void
    {
        $this->ensureSchemaInitialized();
    }

    public function ensureSchemaInitialized(): void
    {
        try {
            $db = Database::connect();

            if (! is_file($this->lockFile)) {
                $db->query("
                    CREATE TABLE IF NOT EXISTS `cms_newsletter_forms` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `form_name` VARCHAR(120) NOT NULL,
                        `show_name` TINYINT(1) NOT NULL DEFAULT 0,
                        `req_name` TINYINT(1) NOT NULL DEFAULT 0,
                        `name_placeholder` VARCHAR(150) NOT NULL DEFAULT 'Enter your full name...',
                        `show_phone` TINYINT(1) NOT NULL DEFAULT 0,
                        `req_phone` TINYINT(1) NOT NULL DEFAULT 0,
                        `phone_placeholder` VARCHAR(150) NOT NULL DEFAULT 'WhatsApp / Mobile number...',
                        `show_email` TINYINT(1) NOT NULL DEFAULT 1,
                        `req_email` TINYINT(1) NOT NULL DEFAULT 1,
                        `email_placeholder` VARCHAR(150) NOT NULL DEFAULT 'Enter your email address...',
                        `btn_text` VARCHAR(90) NOT NULL DEFAULT 'Subscribe →',
                        `success_msg` VARCHAR(255) NOT NULL DEFAULT 'Thank you! Your details have been submitted.',
                        `is_default` TINYINT(1) NOT NULL DEFAULT 0,
                        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $fCols = $db->getFieldNames($this->formsTable);
                if (! in_array('is_default', $fCols, true)) {
                    $db->query("ALTER TABLE `cms_newsletter_forms` ADD COLUMN `is_default` TINYINT(1) NOT NULL DEFAULT 0 AFTER `success_msg`");
                }

                if ((int) $db->table($this->formsTable)->countAllResults() === 0) {
                    $now = date('Y-m-d H:i:s');
                    $db->table($this->formsTable)->insertBatch([
                        [
                            'id'                => 1,
                            'form_name'         => 'Email Newsletter Only',
                            'show_name'         => 0,
                            'req_name'          => 0,
                            'name_placeholder'  => 'Your full name...',
                            'show_phone'        => 0,
                            'req_phone'         => 0,
                            'phone_placeholder' => 'Mobile number...',
                            'show_email'        => 1,
                            'req_email'         => 1,
                            'email_placeholder' => 'Enter your email address...',
                            'btn_text'          => 'Subscribe →',
                            'success_msg'       => 'Thank you for subscribing to our newsletter!',
                            'is_default'        => 0,
                            'is_active'         => 1,
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ],
                        [
                            'id'                => 2,
                            'form_name'         => 'WhatsApp / Mobile Only',
                            'show_name'         => 0,
                            'req_name'          => 0,
                            'name_placeholder'  => 'Your full name...',
                            'show_phone'        => 1,
                            'req_phone'         => 1,
                            'phone_placeholder' => 'Enter your WhatsApp number...',
                            'show_email'        => 0,
                            'req_email'         => 0,
                            'email_placeholder' => 'Enter your email...',
                            'btn_text'          => 'Join on WhatsApp →',
                            'success_msg'       => 'Great! Your WhatsApp number has been registered.',
                            'is_default'        => 0,
                            'is_active'         => 1,
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ],
                        [
                            'id'                => 3,
                            'form_name'         => 'Name + Mobile Callback',
                            'show_name'         => 1,
                            'req_name'          => 1,
                            'name_placeholder'  => 'Enter your full name...',
                            'show_phone'        => 1,
                            'req_phone'         => 1,
                            'phone_placeholder' => 'Enter 10-digit mobile number...',
                            'show_email'        => 0,
                            'req_email'         => 0,
                            'email_placeholder' => 'Enter your email...',
                            'btn_text'          => 'Request Callback →',
                            'success_msg'       => 'Thank you! Our team will call you shortly.',
                            'is_default'        => 0,
                            'is_active'         => 1,
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ],
                        [
                            'id'                => 4,
                            'form_name'         => 'Full Lead Form (Name + WhatsApp + Email)',
                            'show_name'         => 1,
                            'req_name'          => 1,
                            'name_placeholder'  => 'Enter your full name...',
                            'show_phone'        => 1,
                            'req_phone'         => 1,
                            'phone_placeholder' => 'WhatsApp / Mobile number...',
                            'show_email'        => 1,
                            'req_email'         => 1,
                            'email_placeholder' => 'Enter your email address...',
                            'btn_text'          => 'Get Free Study Kit →',
                            'success_msg'       => 'Success! Your free study kit access is ready.',
                            'is_default'        => 1,
                            'is_active'         => 1,
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ],
                    ]);
                }

                $db->query("
                    CREATE TABLE IF NOT EXISTS `cms_newsletter_leads` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `form_id` INT UNSIGNED NOT NULL DEFAULT 1,
                        `form_name` VARCHAR(120) NOT NULL DEFAULT 'Email Newsletter Only',
                        `full_name` VARCHAR(120) NOT NULL DEFAULT '',
                        `email` VARCHAR(190) NOT NULL DEFAULT '',
                        `phone` VARCHAR(45) NOT NULL DEFAULT '',
                        `source_page` VARCHAR(160) NOT NULL DEFAULT '',
                        `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
                        `status` VARCHAR(20) NOT NULL DEFAULT 'subscribed',
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        KEY `idx_form_status` (`form_id`, `status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $leadFields = $db->getFieldNames($this->table);
                if (! in_array('form_id', $leadFields, true)) {
                    $db->query("ALTER TABLE `cms_newsletter_leads` ADD COLUMN `form_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`");
                }
                if (! in_array('form_name', $leadFields, true)) {
                    $db->query("ALTER TABLE `cms_newsletter_leads` ADD COLUMN `form_name` VARCHAR(120) NOT NULL DEFAULT 'Email Newsletter Only' AFTER `form_id`");
                }
                try {
                    $db->query("ALTER TABLE `cms_newsletter_leads` DROP INDEX `uniq_lead_email`");
                } catch (Throwable $ignore) {
                }

                @file_put_contents($this->lockFile, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
            }

            // Always ensure cms_components is registered with exact schema (matches AnnouncementModel standard)
            if ($db->tableExists('cms_components')) {
                $fields = $db->getFieldNames('cms_components');
                if (! in_array('addon_slug', $fields, true)) {
                    $db->query("ALTER TABLE `cms_components` ADD COLUMN `addon_slug` VARCHAR(60) NOT NULL DEFAULT 'page_builder' AFTER `category`");
                }

                $defaultSchema = json_encode([
                    'template_id'   => 'default',
                    'badge'         => 'FREE STUDY KIT & UPDATES',
                    'heading'       => 'Master Abacus & Mental Math Faster',
                    'subheading'    => 'Get free weekly practice worksheets, speed-math tricks, and scholarship alerts straight to your inbox.',
                    'btn_text'      => '',
                    'privacy_note'  => '🔒 100% Privacy Guaranteed. Unsubscribe anytime.',
                    'is_floating'   => '0',
                    'color_mode'    => 'auto',
                    'bg_color'      => '#0d1f1a',
                    'border_color'  => '#10b981',
                    'text_color'    => '#f3f4f6',
                    'btn_bg'        => '#10b981',
                    'btn_text_col'  => '#04130d',
                    'card_bg_light' => '#ecfdf5',
                    'text_light'    => '#064e3b',
                ], JSON_UNESCAPED_UNICODE);

                $sql = "INSERT INTO `cms_components` 
                    (`component_key`, `component_name`, `component_icon`, `category`, `addon_slug`, `default_schema_json`, `is_enabled`, `sort_order`)
                    VALUES ('newsletter_cta', 'Newsletter / Lead Magnet (Multi-Form)', '✉', 'header', 'newsletter', ?, 1, 40)
                    ON DUPLICATE KEY UPDATE 
                        `addon_slug` = 'newsletter',
                        `component_name` = 'Newsletter / Lead Magnet (Multi-Form)',
                        `is_enabled` = 1,
                        `default_schema_json` = VALUES(`default_schema_json`)";

                $db->query($sql, [$defaultSchema]);
            }

            if (! is_file($this->cacheFile)) {
                $this->rebuildFormsCache();
            }
        } catch (Throwable $e) {
        }
    }

    public function syncPageBuilderComponentRow(): void
    {
        $this->ensureSchemaInitialized();
    }

    public function rebuildFormsCache(): array
    {
        $map = [];
        try {
            $db   = Database::connect();
            $rows = $db->table($this->formsTable)->orderBy('id', 'ASC')->get()->getResultArray();
            $defaultId = 0;
            foreach ($rows as $r) {
                $id = (int) $r['id'];
                $map[$id] = $r;
                if ((int) ($r['is_default'] ?? 0) === 1) {
                    $defaultId = $id;
                }
            }
            if ($defaultId <= 0 && ! empty($rows)) {
                $defaultId = (int) $rows[0]['id'];
            }
            $map['_default_id'] = $defaultId ?: 1;
            @is_dir(dirname($this->cacheFile)) or @mkdir(dirname($this->cacheFile), 0775, true);
            @file_put_contents($this->cacheFile, json_encode($map, JSON_UNESCAPED_UNICODE));
        } catch (Throwable $e) {
        }
        return $map;
    }

    public function setDefaultTemplate(int $formId): void
    {
        try {
            $db = Database::connect();
            $db->table($this->formsTable)->update(['is_default' => 0]);
            $db->table($this->formsTable)->where('id', $formId)->update(['is_default' => 1]);
            $this->rebuildFormsCache();
        } catch (Throwable $e) {
        }
    }

    public function getAllFormTemplates(): array
    {
        $this->ensureSchemaInitialized();
        try {
            return Database::connect()->table($this->formsTable)->orderBy('id', 'ASC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getFormTemplateById(int $formId): ?array
    {
        $this->ensureSchemaInitialized();
        try {
            $db = Database::connect();
            if ($formId > 0) {
                $row = $db->table($this->formsTable)->where('id', $formId)->get()->getRowArray();
                if ($row) {
                    return $row;
                }
            }
            $def = $db->table($this->formsTable)->where('is_default', 1)->get()->getRowArray();
            if ($def) {
                return $def;
            }
            $first = $db->table($this->formsTable)->orderBy('id', 'ASC')->get()->getRowArray();
            return $first ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function resolveTemplate($rawTemplateKey = 'default'): ?array
    {
        $this->ensureSchemaInitialized();
        try {
            $db  = Database::connect();
            $key = trim((string) $rawTemplateKey);

            if ($key !== '' && strtolower($key) !== 'default' && strtolower($key) !== 'all') {
                if (ctype_digit($key) && (int) $key > 0) {
                    $byId = $db->table($this->formsTable)->where('id', (int) $key)->get()->getRowArray();
                    if ($byId) {
                        return $byId;
                    }
                }
                if (preg_match('/^#?(?:form)?(\d+)$/i', $key, $m)) {
                    $byId = $db->table($this->formsTable)->where('id', (int) $m[1])->get()->getRowArray();
                    if ($byId) {
                        return $byId;
                    }
                }
                $byName = $db->table($this->formsTable)->where('form_name', $key)->get()->getRowArray();
                if ($byName) {
                    return $byName;
                }
            }

            return $this->getFormTemplateById(0);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function saveFormTemplateData(array $data): int
    {
        $this->ensureSchemaInitialized();
        $id  = (int) ($data['id'] ?? 0);
        $now = date('Y-m-d H:i:s');

        $row = [
            'form_name'         => trim((string) ($data['form_name'] ?? 'Custom Form')) ?: 'Custom Form',
            'show_name'         => ! empty($data['show_name']) ? 1 : 0,
            'req_name'          => ! empty($data['req_name']) ? 1 : 0,
            'name_placeholder'  => trim((string) ($data['name_placeholder'] ?? 'Enter your full name...')) ?: 'Enter your full name...',
            'show_phone'        => ! empty($data['show_phone']) ? 1 : 0,
            'req_phone'         => ! empty($data['req_phone']) ? 1 : 0,
            'phone_placeholder' => trim((string) ($data['phone_placeholder'] ?? 'WhatsApp / Mobile number...')) ?: 'WhatsApp / Mobile number...',
            'show_email'        => ! empty($data['show_email']) ? 1 : 0,
            'req_email'         => ! empty($data['req_email']) ? 1 : 0,
            'email_placeholder' => trim((string) ($data['email_placeholder'] ?? 'Enter your email address...')) ?: 'Enter your email address...',
            'btn_text'          => trim((string) ($data['btn_text'] ?? 'Subscribe →')) ?: 'Subscribe →',
            'success_msg'       => trim((string) ($data['success_msg'] ?? 'Thank you! Your details have been submitted.')) ?: 'Thank you! Your details have been submitted.',
            'is_active'         => 1,
            'updated_at'        => $now,
        ];

        if ($row['show_name'] === 0 && $row['show_phone'] === 0 && $row['show_email'] === 0) {
            $row['show_email'] = 1;
            $row['req_email']  = 1;
        }

        try {
            $db = Database::connect();
            if ($id > 0) {
                $db->table($this->formsTable)->where('id', $id)->update($row);
            } else {
                $row['created_at'] = $now;
                $db->table($this->formsTable)->insert($row);
                $id = (int) $db->insertID();
            }

            if (! empty($data['is_default'])) {
                $this->setDefaultTemplate($id);
            } else {
                $this->rebuildFormsCache();
            }
        } catch (Throwable $e) {
        }

        return $id;
    }

    public function deleteFormTemplateById(int $id): bool
    {
        if ($id <= 1) {
            return false;
        }
        try {
            Database::connect()->table($this->formsTable)->where('id', $id)->delete();
            $this->rebuildFormsCache();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getStats(): array
    {
        $this->ensureSchemaInitialized();
        try {
            $db     = Database::connect();
            $total  = (int) $db->table($this->table)->countAllResults();
            $active = (int) $db->table($this->table)->where('status', 'subscribed')->countAllResults();
            $forms  = (int) $db->table($this->formsTable)->countAllResults();

            return [
                'total'      => $total,
                'subscribed' => $active,
                'forms'      => $forms,
            ];
        } catch (Throwable $e) {
            return ['total' => 0, 'subscribed' => 0, 'forms' => 0];
        }
    }

    public function upsertLead(array $payload): array
    {
        $this->ensureSchemaInitialized();
        try {
            $db       = Database::connect();
            $formId   = (int) ($payload['form_id'] ?? 0);
            $tpl      = $this->getFormTemplateById($formId);
            if ($tpl) {
                $formId = (int) $tpl['id'];
            }
            $formName = trim((string) ($payload['form_name'] ?? ($tpl['form_name'] ?? 'Website Form')));

            $fullName = trim((string) ($payload['full_name'] ?? ''));
            $email    = strtolower(trim((string) ($payload['email'] ?? '')));
            $phone    = trim((string) ($payload['phone'] ?? ''));
            $srcPage  = trim((string) ($payload['source_page'] ?? ''));
            $ipAddr   = trim((string) ($payload['ip_address'] ?? ''));

            if ($tpl) {
                if ((int) $tpl['show_name'] === 1 && (int) $tpl['req_name'] === 1 && $fullName === '') {
                    return ['ok' => false, 'message' => 'Please enter your name.'];
                }
                if ((int) $tpl['show_phone'] === 1 && (int) $tpl['req_phone'] === 1 && $phone === '') {
                    return ['ok' => false, 'message' => 'Please enter your mobile / WhatsApp number.'];
                }
                if ((int) $tpl['show_email'] === 1 && (int) $tpl['req_email'] === 1 && ($email === '' or ! filter_var($email, FILTER_VALIDATE_EMAIL))) {
                    return ['ok' => false, 'message' => 'Please enter a valid email address.'];
                }
            }

            if ($fullName === '' && $email === '' && $phone === '') {
                return ['ok' => false, 'message' => 'Please fill in the required fields.'];
            }
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'message' => 'Please enter a valid email address.'];
            }

            $now        = date('Y-m-d H:i:s');
            $successMsg = $tpl['success_msg'] ?? 'Thank you! Your details have been submitted.';

            $existing = null;
            if ($email !== '') {
                $existing = $db->table($this->table)->where('email', $email)->get()->getRowArray();
            } elseif ($phone !== '') {
                $existing = $db->table($this->table)->where('phone', $phone)->get()->getRowArray();
            }

            if ($existing) {
                $upd = [
                    'form_id'    => $formId,
                    'form_name'  => $formName,
                    'status'     => 'subscribed',
                    'updated_at' => $now,
                ];
                if ($fullName !== '') {
                    $upd['full_name'] = $fullName;
                }
                if ($email !== '') {
                    $upd['email'] = $email;
                }
                if ($phone !== '') {
                    $upd['phone'] = $phone;
                }
                $db->table($this->table)->where('id', (int) $existing['id'])->update($upd);

                return ['ok' => true, 'is_new' => false, 'message' => $successMsg];
            }

            $db->table($this->table)->insert([
                'form_id'     => $formId,
                'form_name'   => $formName,
                'full_name'   => $fullName,
                'email'       => $email,
                'phone'       => $phone,
                'source_page' => $srcPage,
                'ip_address'  => $ipAddr,
                'status'      => 'subscribed',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            return ['ok' => true, 'is_new' => true, 'message' => $successMsg];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Unable to process request right now.'];
        }
    }
}

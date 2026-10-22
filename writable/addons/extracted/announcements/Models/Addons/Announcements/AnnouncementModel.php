<?php

namespace App\Models\Addons\Announcements;

use CodeIgniter\Model;
use Config\Database;
use Throwable;

class AnnouncementModel extends Model
{
    protected string $lockFile;
    protected string $cacheFile;

    public function __construct()
    {
        parent::__construct();
        $this->lockFile  = WRITEPATH . 'schema_addon_announcements_v6.lock';$this->cacheFile = WRITEPATH . 'addons/announcements_cache.json';
    }

    public function ensureSchemaInitialized(): void
    {
        // FAST EXIT: यदि लॉक फ़ाइल और कैश फ़ाइल दोनों मौजूद हैं, तो 0 DB क्वेरी चलाएं!
        if (is_file($this->lockFile) && is_file($this->cacheFile)) {
            return;
        }

        try {
            $db = Database::connect();

            if (! is_file($this->lockFile)) {$db->query("
                    CREATE TABLE IF NOT EXISTS `cms_announcements` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `category` VARCHAR(80) NOT NULL DEFAULT 'General',
                        `message` VARCHAR(500) NOT NULL,
                        `link_text` VARCHAR(100) NULL,
                        `link_url` VARCHAR(255) NULL,
                        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        KEY `idx_active_cat` (`is_active`, `category`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                if ($db->table('cms_announcements')->countAllResults() === 0) {$db->table('cms_announcements')->insertBatch([
                        [
                            'category'  => 'Admissions',
                            'message'   => 'Admissions Open for 2026 International Batch — Get 30% Early Bird Scholarship!',
                            'link_text' => 'Claim Scholarship ->',
                            'link_url'  => '/login',
                            'is_active' => 1,
                        ],
                        [
                            'category'  => 'Updates',
                            'message'   => 'New Interactive Practice Portal 2.0 is now live for all enrolled students.',
                            'link_text' => '',
                            'link_url'  => '',
                            'is_active' => 1,
                        ],
                    ]);
                }

                if ($db->tableExists('cms_components')) {
                    $fields =$db->getFieldNames('cms_components');
                    if (! in_array('addon_slug', $fields, true)) {$db->query("ALTER TABLE `cms_components` ADD COLUMN `addon_slug` VARCHAR(60) NOT NULL DEFAULT 'page_builder' AFTER `category`");
                    }

                    $defaultSchema = json_encode([
                        'badge_text'      => 'NEW',
                        'filter_mode'     => 'category',
                        'category'        => 'all',
                        'announcement_id' => '',
                        'scroll_mode'     => 'rtl',
                        'scroll_speed'    => '22',
                        'bg_color'        => '#10b981',
                        'text_color'      => '#04130d',
                        'badge_bg'        => '#04130d',
                        'badge_color'     => '#10b981',
                        'dismissible'     => '1',
                    ]);

                    $sql = "INSERT INTO `cms_components` 
                        (`component_key`, `component_name`, `component_icon`, `category`, `addon_slug`, `default_schema_json`, `is_enabled`, `sort_order`)
                        VALUES ('announcement_bar', 'Announcement Top Bar (Ticker)', '!', 'header', 'announcements', ?, 1, 0)
                        ON DUPLICATE KEY UPDATE 
                            `addon_slug` = 'announcements',
                            `component_name` = 'Announcement Top Bar (Ticker)',
                            `default_schema_json` = VALUES(`default_schema_json`)";

                    $db->query($sql, [$defaultSchema]);
                }

                @is_dir(dirname($this->lockFile)) or @mkdir(dirname($this->lockFile), 0775, true);
                @file_put_contents($this->lockFile, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
            }

            if (! is_file($this->cacheFile)) {$this->rebuildCache();
            }
        } catch (Throwable $e) {
        }
    }

    public function rebuildCache(): array
    {
        try {
            $rows = Database::connect()
                ->table('cms_announcements')
                ->where('is_active', 1)
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            @is_dir(dirname($this->cacheFile)) or @mkdir(dirname($this->cacheFile), 0775, true);
            @file_put_contents($this->cacheFile, json_encode($rows, JSON_UNESCAPED_UNICODE));
            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getActiveFromCache(): array
    {
        if (is_file($this->cacheFile)) {
            $cached = json_decode((string) file_get_contents($this->cacheFile), true);
            if (is_array($cached)) {
                return $cached;
            }
        }
        $this->ensureSchemaInitialized();
        return $this->rebuildCache();
    }

    public function getAll(bool $onlyActive = false): array
    {
        if ($onlyActive) {
            return $this->getActiveFromCache();
        }
        $this->ensureSchemaInitialized();
        try {
            return Database::connect()
                ->table('cms_announcements')
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getFiltered(string $filterMode = 'category', string $category = 'all', int$singleId = 0): array
    {
        $activeRows =$this->getActiveFromCache();
        if (empty($activeRows)) {
            return [];
        }

        $cleanCat = trim($category);

        if ($filterMode === 'single_id') {
            $targetId =$singleId > 0 ? $singleId : (int) preg_replace('/[^0-9]/', '',$cleanCat);
            if ($targetId > 0) {
                return array_values(array_filter($activeRows, static fn($r) => (int) ($r['id'] ?? 0) ===$targetId));
            }
            return $activeRows;
        }

        if ($cleanCat !== '' && strtolower($cleanCat) !== 'all') {
            $targetCat = strtolower($cleanCat);
            return array_values(array_filter($activeRows, static fn($r) => strtolower(trim((string) ($r['category'] ?? ''))) ===$targetCat));
        }

        return $activeRows;
    }

    public function getCategories(): array
    {
        $this->ensureSchemaInitialized();
        try {
            $rows = Database::connect()
                ->table('cms_announcements')
                ->select('DISTINCT(category) as category')
                ->orderBy('category', 'ASC')
                ->get()
                ->getResultArray();
            return array_column($rows, 'category') ?: ['General'];
        } catch (Throwable $e) {
            return ['General'];
        }
    }

    public function saveRecord(array $data, int$id = 0): bool
    {
        $this->ensureSchemaInitialized();
        try {
            $db = Database::connect();
            if ($id > 0) {
                unset($data['is_active']);
                $ok = (bool)$db->table('cms_announcements')->where('id', $id)->update($data);
            } else {
                if (! isset($data['is_active'])) {
                    $data['is_active'] = 1; }$ok = (bool) $db->table('cms_announcements')->insert($data);
            }
            $this->rebuildCache();
            return $ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function toggleStatus(int $id): bool
    {
        try {
            $db  = Database::connect();$row = $db->table('cms_announcements')->where('id',$id)->get()->getRowArray();
            if (! $row) {
                return false;
            }
            $next = ((int)$row['is_active'] === 1) ? 0 : 1;
            $ok   = (bool) $db->table('cms_announcements')->where('id',$id)->update(['is_active' => $next]);$this->rebuildCache();
            return $ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function deleteRecord(int $id): bool
    {
        try {
            $ok = (bool) Database::connect()->table('cms_announcements')->where('id', $id)->delete();$this->rebuildCache();
            return $ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}
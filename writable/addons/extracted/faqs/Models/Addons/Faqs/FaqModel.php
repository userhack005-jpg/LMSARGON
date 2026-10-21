<?php

namespace App\Models\Addons\Faqs;

use CodeIgniter\Model;
use Config\Database;
use Throwable;

class FaqModel extends Model
{
    protected string $lockFile;

    public function __construct()
    {
        parent::__construct();
        $this->lockFile = WRITEPATH . 'schema_addon_faqs_v6.lock';
    }

    public function ensureSchemaInitialized(): void
    {
        if (is_file($this->lockFile)) {
            return;
        }

        try {
            $db = Database::connect();

            $db->query("
                CREATE TABLE IF NOT EXISTS `cms_faqs` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `category` VARCHAR(80) NOT NULL DEFAULT 'General',
                    `question` VARCHAR(255) NOT NULL,
                    `answer` TEXT NOT NULL,
                    `sort_order` INT NOT NULL DEFAULT 1,
                    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            if ($db->table('cms_faqs')->countAllResults() === 0) {
                $db->table('cms_faqs')->insertBatch([
                    [
                        'category'     => 'Admissions',
                        'question'     => 'How do I enroll in a live interactive batch?',
                        'answer'       => 'Click the Get Started button, create your student account, and choose your preferred time slot.',
                        'sort_order'   => 1,
                        'is_published' => 1,
                    ],
                    [
                        'category'     => 'Platform',
                        'question'     => 'Can I access the portal on tablets and mobile phones?',
                        'answer'       => 'Yes! The entire platform is 100% responsive and optimized with native lazy-loading for instant mobile performance.',
                        'sort_order'   => 2,
                        'is_published' => 1,
                    ],
                ]);
            }

            if ($db->tableExists('cms_components')) {
                $fields = $db->getFieldNames('cms_components');
                if (! in_array('addon_slug', $fields, true)) {
                    $db->query("ALTER TABLE `cms_components` ADD COLUMN `addon_slug` VARCHAR(60) NOT NULL DEFAULT 'page_builder' AFTER `category`");
                }

                $defaultSchema = json_encode([
                    'badge'         => 'SUPPORT & CLARITY',
                    'section_title' => 'Frequently Asked Questions',
                    'subtitle'      => 'Fetched live from the database. Click any question to expand.',
                    'category'      => 'all',
                    'box_position'  => 'center',
                    'text_align'    => 'left',
                    'expand_first'  => '1',
                    'border_color'  => '#232738',
                    'accent_color'  => '#10b981',
                ]);

                $sql = "INSERT INTO `cms_components` 
                    (`component_key`, `component_name`, `component_icon`, `category`, `addon_slug`, `default_schema_json`, `is_enabled`, `sort_order`)
                    VALUES ('faq_accordion', 'Dynamic FAQ Accordion', '?', 'interactive', 'faqs', ?, 1, 10)
                    ON DUPLICATE KEY UPDATE 
                        `addon_slug` = 'faqs',
                        `component_name` = 'Dynamic FAQ Accordion',
                        `default_schema_json` = VALUES(`default_schema_json`)";

                $db->query($sql, [$defaultSchema]);
            }

            file_put_contents($this->lockFile, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
        } catch (Throwable $e) {
            log_message('error', '[FaqModel::ensureSchemaInitialized] ' . $e->getMessage());
        }
    }

    public function getAll(bool $onlyPublished = false): array
    {
        $this->ensureSchemaInitialized();
        try {
            $b = Database::connect()->table('cms_faqs');
            if ($onlyPublished) {
                $b->where('is_published', 1);
            }
            return $b->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        } catch (Throwable $e) {
            log_message('error', '[FaqModel::getAll] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Properly Escaped CI4 Query Builder Category Filter
     * Relies on utf8mb4_unicode_ci collation for safe case-insensitive matching
     */
    public function getByCategory(string $category = 'all'): array
    {
        $this->ensureSchemaInitialized();
        try {
            $cleanCat = trim($category);
            $b = Database::connect()->table('cms_faqs')->where('is_published', 1);

            if ($cleanCat !== '' && strtolower($cleanCat) !== 'all') {
                $b->where('category', $cleanCat);
            }

            return $b->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        } catch (Throwable $e) {
            log_message('error', '[FaqModel::getByCategory] ' . $e->getMessage());
            return [];
        }
    }

    public function getCategories(): array
    {
        $this->ensureSchemaInitialized();
        try {
            $rows = Database::connect()->table('cms_faqs')
                ->select('DISTINCT(category) as category')
                ->orderBy('category', 'ASC')
                ->get()
                ->getResultArray();
            return array_column($rows, 'category') ?: ['General'];
        } catch (Throwable $e) {
            log_message('error', '[FaqModel::getCategories] ' . $e->getMessage());
            return ['General'];
        }
    }

    /**
     * Preserves `is_published` status when updating an existing FAQ ($id > 0)
     */
    public function saveRecord(array $data, int $id = 0): bool
    {
        $this->ensureSchemaInitialized();
        try {
            $db = Database::connect();
            if ($id > 0) {
                unset($data['is_published']);
                return (bool) $db->table('cms_faqs')->where('id', $id)->update($data);
            }
            if (! isset($data['is_published'])) {
                $data['is_published'] = 1;
            }
            return (bool) $db->table('cms_faqs')->insert($data);
        } catch (Throwable $e) {
            log_message('error', '[FaqModel::saveRecord] ' . $e->getMessage());
            return false;
        }
    }

    public function toggleStatus(int $id): bool
    {
        $this->ensureSchemaInitialized();
        try {
            $db  = Database::connect();
            $row = $db->table('cms_faqs')->where('id', $id)->get()->getRowArray();
            if (! $row) {
                return false;
            }
            $next = ((int) $row['is_published'] === 1) ? 0 : 1;
            return (bool) $db->table('cms_faqs')->where('id', $id)->update(['is_published' => $next]);
        } catch (Throwable $e) {
            log_message('error', '[FaqModel::toggleStatus] ' . $e->getMessage());
            return false;
        }
    }

    public function deleteRecord(int $id): bool
    {
        $this->ensureSchemaInitialized();
        try {
            return (bool) Database::connect()->table('cms_faqs')->where('id', $id)->delete();
        } catch (Throwable $e) {
            log_message('error', '[FaqModel::deleteRecord] ' . $e->getMessage());
            return false;
        }
    }
}
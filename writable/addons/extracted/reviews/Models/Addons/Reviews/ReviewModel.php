<?php

namespace App\Models\Addons\Reviews;

use CodeIgniter\Model;
use Config\Database;
use Throwable;

class ReviewModel extends Model
{
    protected string $lockFile;

    public function __construct()
    {
        parent::__construct();
        $this->lockFile = WRITEPATH . 'schema_addon_reviews_v2.lock';
    }

    public function ensureSchemaInitialized(): void
    {
        try {
            $db = Database::connect();

            if (!is_file($this->lockFile)) {
                $db->query("
                    CREATE TABLE IF NOT EXISTS `cms_testimonials` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `category` VARCHAR(80) NOT NULL DEFAULT 'General',
                        `person_name` VARCHAR(120) NOT NULL,
                        `person_role` VARCHAR(120) NOT NULL DEFAULT 'Verified Student',
                        `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
                        `quote` TEXT NOT NULL,
                        `avatar_url` VARCHAR(255) NULL,
                        `sort_order` INT NOT NULL DEFAULT 1,
                        `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $fields = $db->getFieldNames('cms_testimonials');
                if (! in_array('category', $fields, true)) {
                    $db->query("ALTER TABLE `cms_testimonials` ADD COLUMN `category` VARCHAR(80) NOT NULL DEFAULT 'General' AFTER `id`");
                }
                if (! in_array('sort_order', $fields, true)) {
                    $db->query("ALTER TABLE `cms_testimonials` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 1 AFTER `avatar_url`");
                }

                if ($db->table('cms_testimonials')->countAllResults() === 0) {
                    $db->table('cms_testimonials')->insertBatch([
                        [
                            'category'    => 'Parents',
                            'person_name' => 'Sarah Jenkins',
                            'person_role' => 'Parent of Grade 5 Student (UK)',
                            'rating'      => 5,
                            'quote'       => 'My son improved his mental math speed in just 6 weeks! The live portal and instant feedback are world-class.',
                            'sort_order'  => 1,
                            'is_approved' => 1,
                        ],
                        [
                            'category'    => 'Students',
                            'person_name' => 'Aarav Mehta',
                            'person_role' => 'Level 4 Champion (UAE)',
                            'rating'      => 5,
                            'quote'       => 'The interactive practice challenges make learning super fun. I love earning my digital badges!',
                            'sort_order'  => 2,
                            'is_approved' => 1,
                        ],
                        [
                            'category'    => 'Parents',
                            'person_name' => 'David Okafor',
                            'person_role' => 'Parent of Twins (Nigeria)',
                            'rating'      => 5,
                            'quote'       => 'Highly structured curriculum and very supportive teachers. Both my kids look forward to every session.',
                            'sort_order'  => 3,
                            'is_approved' => 1,
                        ],
                    ]);
                }

                file_put_contents($this->lockFile, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
            }

            if ($db->tableExists('cms_components')) {
                $cFields = $db->getFieldNames('cms_components');
                if (! in_array('addon_slug', $cFields, true)) {
                    $db->query("ALTER TABLE `cms_components` ADD COLUMN `addon_slug` VARCHAR(60) NOT NULL DEFAULT 'page_builder' AFTER `category`");
                }

                $defaultSchema = json_encode([
                    'badge'         => 'WALL OF LOVE',
                    'section_title' => 'What Parents & Students Say',
                    'subtitle'      => 'Real verified stories from our global learning community.',
                    'category'      => 'all',
                    'columns'       => '3',
                    'auto_scroll'   => '1',
                    'anim_style'    => 'smooth_scroll',
                    'scroll_dir'    => 'rtl',
                    'scroll_speed'  => '28',
                    'color_mode'    => 'auto',
                    'card_bg_dark'  => '#171a26',
                    'text_dark'     => '#f8fafc',
                    'star_dark'     => '#f59e0b',
                    'card_bg_light' => '#ffffff',
                    'text_light'    => '#0f172a',
                    'star_light'    => '#d97706',
                ]);

                $sql = "INSERT INTO `cms_components` 
                    (`component_key`, `component_name`, `component_icon`, `category`, `addon_slug`, `default_schema_json`, `is_enabled`, `sort_order`)
                    VALUES ('testimonial_slider', 'Student Reviews & Star Slider', '★', 'social_proof', 'reviews', ?, 1, 9)
                    ON DUPLICATE KEY UPDATE 
                        `addon_slug` = 'reviews',
                        `component_name` = 'Student Reviews & Star Slider',
                        `default_schema_json` = VALUES(`default_schema_json`)";

                $db->query($sql, [$defaultSchema]);
            }
        } catch (Throwable $e) {
        }
    }

    public function getAll(bool $onlyApproved = false): array
    {
        $this->ensureSchemaInitialized();
        try {
            $b = Database::connect()->table('cms_testimonials');
            if ($onlyApproved) {
                $b->where('is_approved', 1);
            }
            return $b->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getByCategory(string $category = 'all'): array
    {
        $this->ensureSchemaInitialized();
        try {
            $cleanCat = trim($category);
            $b = Database::connect()->table('cms_testimonials')->where('is_approved', 1);

            if ($cleanCat !== '' && strtolower($cleanCat) !== 'all') {
                $b->where('category', $cleanCat);
            }

            return $b->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getCategories(): array
    {
        $this->ensureSchemaInitialized();
        try {
            $rows = Database::connect()->table('cms_testimonials')->select('DISTINCT(category) as category')->orderBy('category', 'ASC')->get()->getResultArray();
            return array_column($rows, 'category') ?: ['General'];
        } catch (Throwable $e) {
            return ['General'];
        }
    }

    public function saveRecord(array $data, int $id = 0): bool
    {
        $this->ensureSchemaInitialized();
        try {
            $db = Database::connect();
            if ($id > 0) {
                unset($data['is_approved']);
                return (bool) $db->table('cms_testimonials')->where('id', $id)->update($data);
            }
            if (! isset($data['is_approved'])) {
                $data['is_approved'] = 1;
            }
            return (bool) $db->table('cms_testimonials')->insert($data);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function toggleStatus(int $id): bool
    {
        try {
            $db  = Database::connect();
            $row = $db->table('cms_testimonials')->where('id', $id)->get()->getRowArray();
            if (! $row) {
                return false;
            }
            $next = ((int) $row['is_approved'] === 1) ? 0 : 1;
            return (bool) $db->table('cms_testimonials')->where('id', $id)->update(['is_approved' => $next]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function deleteRecord(int $id): bool
    {
        try {
            return (bool) Database::connect()->table('cms_testimonials')->where('id', $id)->delete();
        } catch (Throwable $e) {
            return false;
        }
    }
}

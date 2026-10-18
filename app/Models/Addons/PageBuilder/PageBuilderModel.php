<?php

namespace App\Models\Addons\PageBuilder;

use CodeIgniter\Model;
use Config\Database;
use Throwable;

class PageBuilderModel extends Model
{
    protected string $lockFile;

    public function __construct()
    {
        parent::__construct();
        $this->lockFile = WRITEPATH . 'schema_addon_pagebuilder_v1.lock';
    }

    public function ensureSchemaInitialized(): void
    {
        if (! is_file($this->lockFile)) {
            try {
                $db = Database::connect();

                $db->query("
                    CREATE TABLE IF NOT EXISTS `cms_components` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `component_key` VARCHAR(60) NOT NULL UNIQUE,
                        `component_name` VARCHAR(100) NOT NULL,
                        `component_icon` VARCHAR(30) NOT NULL DEFAULT '⊞',
                        `category` VARCHAR(50) NOT NULL DEFAULT 'content',
                        `default_schema_json` LONGTEXT NULL,
                        `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
                        `sort_order` INT NOT NULL DEFAULT 0,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $db->query("
                    CREATE TABLE IF NOT EXISTS `cms_pages` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `title` VARCHAR(180) NOT NULL,
                        `slug` VARCHAR(180) NOT NULL UNIQUE,
                        `blocks_json` LONGTEXT NULL,
                        `meta_title` VARCHAR(180) NULL,
                        `meta_description` TEXT NULL,
                        `status` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
                        `is_homepage` TINYINT(1) NOT NULL DEFAULT 0,
                        `show_in_nav` TINYINT(1) NOT NULL DEFAULT 1,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                file_put_contents($this->lockFile, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
            } catch (Throwable $e) {
            }
        }

        $this->ensureFooterSchema();
        $this->ensureExtendedWidgetsSchema();$this->ensureAnnouncementsSchema();
    }

    public function ensureFooterSchema(): void
    {
        $footerLock = WRITEPATH . 'schema_addon_pb_footers_v3.lock';
        if (is_file($footerLock)) {
            return;
        }

        try {
            $db = Database::connect();$db->query("
                CREATE TABLE IF NOT EXISTS `cms_footers` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(120) NOT NULL,
                    `style_key` VARCHAR(50) NOT NULL,
                    `columns_count` TINYINT(1) NOT NULL DEFAULT 3,
                    `sections_json` LONGTEXT NULL,
                    `copyright_text` VARCHAR(255) NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            if ($db->table('cms_footers')->countAllResults() === 0) {$db->table('cms_footers')->insertBatch([
                    [
                        'name'           => 'Style 01 // Single Section (Full-Width)',
                        'style_key'      => 'style_1col',
                        'columns_count'  => 1,
                        'copyright_text' => '© ' . date('Y') . ' All Rights Reserved.',
                        'is_active'      => 0,
                        'sections_json'  => json_encode([
                            [['id' => 'f1', 'type' => 'rich_text', 'data' => ['html' => '<h3>Enterprise Web Platform</h3><p>Single full-width footer section.</p>']]],
                        ]),
                    ],
                    [
                        'name'           => 'Style 02 // 2 Sections (50/50 Split)',
                        'style_key'      => 'style_2col',
                        'columns_count'  => 2,
                        'copyright_text' => '© ' . date('Y') . ' All Rights Reserved.',
                        'is_active'      => 0,
                        'sections_json'  => json_encode([
                            [['id' => 'f2', 'type' => 'rich_text', 'data' => ['html' => '<h3>About Platform</h3><p>Modern modular web platform.</p>']]],
                            [['id' => 'f3', 'type' => 'cta_buttons', 'data' => ['title' => 'Join Us', 'primary_text' => 'Sign In', 'primary_url' => '/login', 'secondary_text' => 'About', 'secondary_url' => '/about-us']]],
                        ]),
                    ],
                    [
                        'name'           => 'Style 03 // 3 Sections (Balanced Grid)',
                        'style_key'      => 'style_3col',
                        'columns_count'  => 3,
                        'copyright_text' => '© ' . date('Y') . ' Enterprise Portal.',
                        'is_active'      => 1,
                        'sections_json'  => json_encode([
                            [['id' => 'f4', 'type' => 'rich_text', 'data' => ['html' => '<h3>Brand Identity</h3><p>Empowering learners worldwide.</p>']]],
                            [['id' => 'f5', 'type' => 'rich_text', 'data' => ['html' => '<h3>Quick Links</h3><ul><li><a href="/about-us">About Us</a></li><li><a href="/login">Login</a></li></ul>']]],
                            [['id' => 'f6', 'type' => 'rich_text', 'data' => ['html' => '<h3>Contact Desk</h3><p>Email: support@domain.com</p>']]],
                        ]),
                    ],
                    [
                        'name'           => 'Style 04 // 4 Sections (Enterprise Matrix)',
                        'style_key'      => 'style_4col',
                        'columns_count'  => 4,
                        'copyright_text' => '© ' . date('Y') . ' Global Operations.',
                        'is_active'      => 0,
                        'sections_json'  => json_encode([
                            [['id' => 'f7', 'type' => 'rich_text', 'data' => ['html' => '<h4>Overview</h4><p>Next-gen modular architecture.</p>']]],
                            [['id' => 'f8', 'type' => 'rich_text', 'data' => ['html' => '<h4>Modules</h4><p>CMS, RBAC & Gateways.</p>']]],
                            [['id' => 'f9', 'type' => 'rich_text', 'data' => ['html' => '<h4>Resources</h4><p>Docs & FAQs.</p>']]],
                            [['id' => 'f10', 'type' => 'cta_buttons', 'data' => ['title' => 'Get Started', 'primary_text' => 'Launch', 'primary_url' => '/login', 'secondary_text' => '', 'secondary_url' => '']]],
                        ]),
                    ],
                ]);
            }

            file_put_contents($footerLock, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
        } catch (Throwable $e) {
        }
    }

    public function ensureExtendedWidgetsSchema(): void
    {
        $lockV4 = WRITEPATH . 'schema_addon_pb_v4.lock';
        if (is_file($lockV4)) {
            return;
        }

        try {
            $db = Database::connect();

            $db->query("
                CREATE TABLE IF NOT EXISTS `cms_reviews` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `reviewer_name` VARCHAR(120) NOT NULL,
                    `designation` VARCHAR(140) NOT NULL DEFAULT 'Verified Student',
                    `avatar_url` TEXT NULL,
                    `rating` TINYINT(1) NOT NULL DEFAULT 5,
                    `review_text` TEXT NOT NULL,
                    `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $db->query("
                CREATE TABLE IF NOT EXISTS `cms_faqs` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `category` VARCHAR(80) NOT NULL DEFAULT 'General',
                    `question` VARCHAR(255) NOT NULL,
                    `answer` TEXT NOT NULL,
                    `sort_order` INT NOT NULL DEFAULT 0,
                    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $db->query("
                CREATE TABLE IF NOT EXISTS `cms_leads` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `email` VARCHAR(160) NOT NULL UNIQUE,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            file_put_contents($lockV4, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
        } catch (Throwable $e) {
        }
    }

    public function ensureAnnouncementsSchema(): void
    {
        $lockAnn = WRITEPATH . 'schema_addon_pb_announcements_v1.lock';
        if (is_file($lockAnn)) {
            return;
        }

        try {
            $db = Database::connect();$db->query("
                CREATE TABLE IF NOT EXISTS `cms_announcements` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `category` VARCHAR(80) NOT NULL DEFAULT 'General',
                    `message` VARCHAR(500) NOT NULL,
                    `link_text` VARCHAR(100) NULL,
                    `link_url` VARCHAR(255) NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            if ($db->table('cms_announcements')->countAllResults() === 0) {$db->table('cms_announcements')->insertBatch([
                    [
                        'category'  => 'Admissions',
                        'message'   => '🚀 Admissions Open for 2026 International Abacus Batch — Get 30% Early Bird Scholarship!',
                        'link_text' => 'Claim Scholarship →',
                        'link_url'  => '/login',
                        'is_active' => 1,
                    ],
                    [
                        'category'  => 'Admissions',
                        'message'   => '🏆 Global Mental Math Olympiad Registration closes this Sunday!',
                        'link_text' => 'Register Now →',
                        'link_url'  => '/about-us',
                        'is_active' => 1,
                    ],
                ]);
            }

            file_put_contents($lockAnn, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
        } catch (Throwable $e) {
        }
    }

    public function getAllPages(): array
    {
        $this->ensureSchemaInitialized();
        try {
            return Database::connect()->table('cms_pages')->orderBy('is_homepage', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getPublishedNavPages(): array
    {
        $this->ensureSchemaInitialized();
        try {
            return Database::connect()->table('cms_pages')->where('status', 'published')->where('show_in_nav', 1)->orderBy('is_homepage', 'DESC')->orderBy('title', 'ASC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getPageById(int $id): ?array
    {
        $this->ensureSchemaInitialized();
        try {
            return Database::connect()->table('cms_pages')->where('id', $id)->get()->getRowArray() ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getPageBySlug(string $slug): ?array
    {
        $this->ensureSchemaInitialized();
        try {
            return Database::connect()->table('cms_pages')->where('slug', trim($slug, '/'))->where('status', 'published')->get()->getRowArray() ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getHomepage(): ?array
    {
        $this->ensureSchemaInitialized();
        try {
            return Database::connect()->table('cms_pages')->where('is_homepage', 1)->where('status', 'published')->get()->getRowArray() ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getAllComponents(bool $onlyEnabled = false): array
    {
        $this->ensureSchemaInitialized();
        try {
            $b = Database::connect()->table('cms_components');
            if ($onlyEnabled) {$b->where('is_enabled', 1);
            }
            return $b->orderBy('sort_order', 'ASC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function savePageData(array $data): array
    {
        $this->ensureSchemaInitialized();
        try {
            $db     = Database::connect();
            $pageId = (int) ($data['id'] ?? 0);
            $slug   = trim(preg_replace('/[^a-z0-9-\/]/', '-', strtolower($data['slug'])), '-/');
            if ($slug === '') {$slug = 'page-' . time();
            }

            $reserved = ['install', 'login', 'logout', 'dashboard', 'users', 'settings', 'addons', 'p'];
            $firstSeg = explode('/',$slug)[0];
            if (in_array($firstSeg,$reserved, true)) {
                return ['status' => 'error', 'message' => 'Slug "' . $firstSeg . '" is a reserved system route.'];
            }

            $dupCheck = $db->table('cms_pages')->where('slug',$slug);
            if ($pageId > 0) {
                $dupCheck->where('id !=',$pageId);
            }
            if ($dupCheck->countAllResults() > 0) {
                return ['status' => 'error', 'message' => 'Another page is already using the URL slug "/' . $slug . '".'];
            }

            $isHome = (int) ($data['is_homepage'] ?? 0);
            if ($isHome === 1) {$db->table('cms_pages')->update(['is_homepage' => 0]);
            }

            $payload = [
                'title'            => $data['title'],
                'slug'             => $slug,
                'blocks_json'      => $data['blocks_json'],
                'meta_title'       => $data['meta_title'],
                'meta_description' => $data['meta_description'],
                'status'           => $data['status'],
                'is_homepage'      => $isHome,
                'show_in_nav'      => (int) ($data['show_in_nav'] ?? 1),
            ];

            if ($pageId > 0) {$db->table('cms_pages')->where('id', $pageId)->update($payload);
            } else {
                $db->table('cms_pages')->insert($payload);
                $pageId = (int)$db->insertID();
            }

            return ['status' => 'success', 'page_id' => $pageId, 'slug' => $slug, 'message' => 'Page saved & published at /' .$slug];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()];
        }
    }

    public function deletePageById(int $id): bool
    {
        try {
            $db = Database::connect();
            $db->table('cms_pages')->where('id',$id)->delete();
            return $db->affectedRows() > 0; } catch (Throwable$e) {
            return false;
        }
    }

    public function flipComponentState(int $compId): array
    {
        try {
            $db   = Database::connect();$comp = $db->table('cms_components')->where('id',$compId)->get()->getRowArray();
            if (! $comp) {
                return ['status' => 'error', 'message' => 'Component not found.'];
            }
            $newState = ((int)$comp['is_enabled'] === 1) ? 0 : 1;
            $db->table('cms_components')->where('id', $compId)->update(['is_enabled' =>$newState]);
            return ['status' => 'success', 'is_enabled' => $newState, 'message' => 'Component updated.'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Failed to toggle component.'];
        }
    }

    public function getAllFooters(): array
    {
        $this->ensureFooterSchema();
        try {
            return Database::connect()->table('cms_footers')->orderBy('columns_count', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getActiveFooter(): ?array
    {
        $this->ensureFooterSchema();
        try {
            $db  = Database::connect();
            $row =$db->table('cms_footers')->where('is_active', 1)->get()->getRowArray();
            if (! $row) {
                $row =$db->table('cms_footers')->orderBy('updated_at', 'DESC')->get()->getRowArray();
            }
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function saveFooterTemplate(array $data): bool
    {
        $this->ensureFooterSchema();
        try {
            $db       = Database::connect();$styleKey = $data['style_key'] ?? 'style_3col';$existing = $db->table('cms_footers')->where('style_key',$styleKey)->get()->getRowArray();

            if (! empty($data['set_active'])) {$db->table('cms_footers')->update(['is_active' => 0]);
                $activeState = 1; } else {$activeState = $existing ? (int) $existing['is_active'] : 0;
            }

            $payload = [
                'name'           => 'Footer // ' . strtoupper($styleKey),
                'style_key'      => $styleKey,
                'columns_count'  => (int) $data['columns_count'],
                'sections_json'  => $data['sections_json'],
                'copyright_text' => $data['copyright_text'],
                'is_active'      => $activeState,
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                return (bool) $db->table('cms_footers')->where('id', $existing['id'])->update($payload);
            }
            return (bool) $db->table('cms_footers')->insert($payload);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getDynamicAnnouncements(string $filterMode = 'category', string $category = 'all', int$singleId = 0): array
    {
        $this->ensureAnnouncementsSchema();
        try {
            $b = Database::connect()->table('cms_announcements')->where('is_active', 1);
            if ($filterMode === 'single_id' &&$singleId > 0) {
                $b->where('id',$singleId);
            } elseif ($category !== '' && strtolower($category) !== 'all') {
                $b->where('category',$category);
            }
            return $b->orderBy('id', 'DESC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function saveAnnouncementRecord(array $data, int$id = 0): bool
    {
        $this->ensureAnnouncementsSchema();
        try {
            $db = Database::connect();
            if ($id > 0) {
                return (bool) $db->table('cms_announcements')->where('id', $id)->update($data);
            }
            return (bool) $db->table('cms_announcements')->insert($data);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function deleteAnnouncementRecord(int $id): bool
    {
        try {
            return (bool) Database::connect()->table('cms_announcements')->where('id', $id)->delete();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getDynamicReviews(int $limit = 10): array
    {
        $this->ensureExtendedWidgetsSchema();
        try {
            return Database::connect()->table('cms_reviews')->where('is_approved', 1)->orderBy('id', 'DESC')->limit($limit)->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function addReviewRecord(array $data, int$id = 0): bool
    {
        $this->ensureExtendedWidgetsSchema();
        try {
            $db = Database::connect();
            if ($id > 0) {
                return (bool) $db->table('cms_reviews')->where('id', $id)->update($data);
            }
            return (bool) $db->table('cms_reviews')->insert($data);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function deleteReviewRecord(int $id): bool
    {
        try {
            return (bool) Database::connect()->table('cms_reviews')->where('id', $id)->delete();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getDynamicFaqs(string $category = 'all'): array
    {
        $this->ensureExtendedWidgetsSchema();
        try {
            $b = Database::connect()->table('cms_faqs')->where('is_published', 1);
            if ($category !== '' && strtolower($category) !== 'all') {
                $b->where('category',$category);
            }
            return $b->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function addFaqRecord(array $data, int$id = 0): bool
    {
        $this->ensureExtendedWidgetsSchema();
        try {
            $db = Database::connect();
            if ($id > 0) {
                return (bool) $db->table('cms_faqs')->where('id', $id)->update($data);
            }
            return (bool) $db->table('cms_faqs')->insert($data);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function deleteFaqRecord(int $id): bool
    {
        try {
            return (bool) Database::connect()->table('cms_faqs')->where('id', $id)->delete();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function saveLeadEmail(string $email): array
    {
        $this->ensureExtendedWidgetsSchema();
        try {
            $db = Database::connect();
            if ($db->table('cms_leads')->where('email',$email)->countAllResults() > 0) {
                return ['status' => 'success', 'message' => 'You are already subscribed! Thank you.'];
            }
            $db->table('cms_leads')->insert(['email' =>$email, 'created_at' => date('Y-m-d H:i:s')]);
            return ['status' => 'success', 'message' => 'Subscribed successfully!'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Could not subscribe at this moment.'];
        }
    }
}
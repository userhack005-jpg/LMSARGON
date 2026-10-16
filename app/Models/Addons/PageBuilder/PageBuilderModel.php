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

    /**
     * ONE-TIME AUTO-INITIALIZER: Creates `cms_pages` and `cms_components` tables & seeds default blocks
     */
    public function ensureSchemaInitialized(): void
    {
        if (is_file($this->lockFile)) {
            return;
        }

        try {
            $db = Database::connect();

            // 1. Create `cms_components` table (Database-Driven Drag & Drop Blocks)
            $componentsSql = "
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
            ";

            // 2. Create `cms_pages` table (Dynamic Pages & Custom URL Slugs)
            $pagesSql = "
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
            ";

            $db->query($componentsSql);
            $db->query($pagesSql);

            // 3. Seed Database-Driven Modular Components
            $defaultComponents = [
                [
                    'hero_banner',
                    'Hero Banner Section',
                    '★',
                    'layout',
                    json_encode([
                        'badge'       => 'WELCOME TO OUR PLATFORM',
                        'heading'     => 'Build High-Impact Digital Experiences',
                        'subheading'  => 'Fully customizable dynamic page section powered by the modular CI4 engine.',
                        'btn_text'    => 'Explore Now',
                        'btn_url'     => '#features',
                        'btn_style'   => 'primary',
                        'align'       => 'center',
                    ]),
                    1,
                    1,
                ],
                [
                    'rich_text',
                    'Advanced Rich Text Editor',
                    '¶',
                    'content',
                    json_encode([
                        'html' => '<h2>About Our Vision</h2><p>Write unrestricted formatted content here using the visual WYSIWYG toolbar, custom headings, lists, highlights, and code blocks.</p>',
                    ]),
                    1,
                    2,
                ],
                [
                    'image_media',
                    'Image & Media Showcase',
                    '⊞',
                    'media',
                    json_encode([
                        'image_url' => '',
                        'caption'   => 'Featured Visual Showcase',
                        'alt_text'  => 'Platform Image',
                        'width'     => '100%',
                    ]),
                    1,
                    3,
                ],
                [
                    'cta_buttons',
                    'Action Button Group',
                    '◈',
                    'interactive',
                    json_encode([
                        'title'          => 'Ready to get started with us?',
                        'primary_text'   => 'Get Started Free',
                        'primary_url'    => '/login',
                        'secondary_text' => 'Contact Support',
                        'secondary_url'  => '#contact',
                    ]),
                    1,
                    4,
                ],
                [
                    'interactive_quiz',
                    'Interactive Quiz / Assessment',
                    '?',
                    'interactive',
                    json_encode([
                        'question'      => 'Which mental math tool uses beads sliding on rods for rapid calculation?',
                        'option_a'      => 'Slide Rule',
                        'option_b'      => 'Abacus (Soroban)',
                        'option_c'      => 'Pascaline',
                        'option_d'      => 'Astrolabe',
                        'correct_index' => 'B',
                        'explanation'   => 'Correct! The Abacus (Soroban) is used globally for rapid mental arithmetic.',
                    ]),
                    1,
                    5,
                ],
                [
                    'feature_grid',
                    '3-Column Feature Cards',
                    '❖',
                    'layout',
                    json_encode([
                        'section_title' => 'Why Choose Our Platform',
                        'card1_title'   => 'Modular Architecture',
                        'card1_desc'    => 'Plug-and-play zip add-ons with one-time auto-database initialization.',
                        'card2_title'   => 'Dynamic SEO Slugs',
                        'card2_desc'    => 'Define custom clean URLs like /about-us or /pricing effortlessly.',
                        'card3_title'   => 'Interactive Widgets',
                        'card3_desc'    => 'Embed quizzes, rich media, and action buttons from the DB registry.',
                    ]),
                    1,
                    6,
                ],
                [
                    'custom_html',
                    'Raw HTML / Embed Block',
                    '<>',
                    'advanced',
                    json_encode([
                        'raw_code' => '<div class="custom-embed-box">Custom HTML, YouTube iframe, or Widget Script</div>',
                    ]),
                    1,
                    7,
                ],
            ];

            $compSql = "INSERT INTO `cms_components` 
                (`component_key`, `component_name`, `component_icon`, `category`, `default_schema_json`, `is_enabled`, `sort_order`)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE `component_name` = VALUES(`component_name`)";

            foreach ($defaultComponents as $c) {
                $db->query($compSql, $c);
            }

            // 4. Seed Default Starter Page ("About Us" -> `/about-us`)
            $starterBlocks = [
                [
                    'id'   => 'blk_1',
                    'type' => 'hero_banner',
                    'data' => [
                        'badge'      => 'DYNAMIC CMS // LIVE',
                        'heading'    => 'About Our Organization',
                        'subheading' => 'This page is rendered dynamically from the `cms_pages` table using the `/about-us` slug.',
                        'btn_text'   => 'Sign In to Portal',
                        'btn_url'    => '/login',
                        'btn_style'  => 'primary',
                        'align'      => 'center',
                    ],
                ],
                [
                    'id'   => 'blk_2',
                    'type' => 'rich_text',
                    'data' => [
                        'html' => '<h2>Unrestricted Drag & Drop Page Builder</h2><p>You can edit this page, add new blocks, upload images, embed interactive quizzes, or create unlimited new pages with custom URLs from the <strong>Visual Page Builder</strong> inside your Admin Sidebar.</p>',
                    ],
                ],
                [
                    'id'   => 'blk_3',
                    'type' => 'interactive_quiz',
                    'data' => [
                        'question'      => 'Can you add new database-driven components to this Page Builder anytime?',
                        'option_a'      => 'Yes, 100% Database-Driven',
                        'option_b'      => 'No, it is hardcoded',
                        'option_c'      => 'Only via FTP',
                        'option_d'      => 'None of the above',
                        'correct_index' => 'A',
                        'explanation'   => 'Spot on! All components are registered in the `cms_components` table and can be toggled or extended anytime.',
                    ],
                ],
            ];

            $pageCount = $db->table('cms_pages')->countAllResults();
            if ($pageCount === 0) {
                $db->table('cms_pages')->insert([
                    'title'            => 'About Us',
                    'slug'             => 'about-us',
                    'blocks_json'      => json_encode($starterBlocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    'meta_title'       => 'About Us — Dynamic Page',
                    'meta_description' => 'Learn more about our platform.',
                    'status'           => 'published',
                    'is_homepage'      => 0,
                    'show_in_nav'      => 1,
                ]);
            }

            file_put_contents($this->lockFile, json_encode([
                'addon'       => 'page_builder_v1',
                'migrated_at' => date('Y-m-d H:i:s'),
            ], JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
            // Ignore if DB not ready
        }
    }

    public function getAllPages(): array
    {
        $this->ensureSchemaInitialized();
        try {
            $db = Database::connect();
            return $db->table('cms_pages')->orderBy('is_homepage', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getPublishedNavPages(): array
    {
        $this->ensureSchemaInitialized();
        try {
            $db = Database::connect();
            return $db->table('cms_pages')
                ->where('status', 'published')
                ->where('show_in_nav', 1)
                ->orderBy('is_homepage', 'DESC')
                ->orderBy('title', 'ASC')
                ->get()
                ->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getPageById(int $id): ?array
    {
        $this->ensureSchemaInitialized();
        try {
            $db  = Database::connect();
            $row = $db->table('cms_pages')->where('id', $id)->get()->getRowArray();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getPageBySlug(string $slug): ?array
    {
        $this->ensureSchemaInitialized();
        try {
            $db  = Database::connect();
            $row = $db->table('cms_pages')
                ->where('slug', trim($slug, '/'))
                ->where('status', 'published')
                ->get()
                ->getRowArray();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getHomepage(): ?array
    {
        $this->ensureSchemaInitialized();
        try {
            $db  = Database::connect();
            $row = $db->table('cms_pages')
                ->where('is_homepage', 1)
                ->where('status', 'published')
                ->get()
                ->getRowArray();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getAllComponents(bool $onlyEnabled = false): array
    {
        $this->ensureSchemaInitialized();
        try {
            $db      = Database::connect();
            $builder = $db->table('cms_components');
            if ($onlyEnabled) {
                $builder->where('is_enabled', 1);
            }
            return $builder->orderBy('sort_order', 'ASC')->get()->getResultArray();
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

            if ($slug === '') {
                $slug = 'page-' . time();
            }

            // Reserved system routes protection
            $reserved = ['install', 'login', 'logout', 'dashboard', 'users', 'settings', 'addons', 'p'];
            $firstSeg = explode('/', $slug)[0];
            if (in_array($firstSeg, $reserved, true)) {
                return [
                    'status'  => 'error',
                    'message' => 'Slug "' . $firstSeg . '" is a reserved system route. Please choose another URL slug.',
                ];
            }

            // Check duplicate slug
            $dupCheck = $db->table('cms_pages')->where('slug', $slug);
            if ($pageId > 0) {
                $dupCheck->where('id !=', $pageId);
            }
            if ($dupCheck->countAllResults() > 0) {
                return [
                    'status'  => 'error',
                    'message' => 'Another page is already using the URL slug "/' . $slug . '".',
                ];
            }

            // If marked as homepage, unset previous homepage
            $isHome = (int) ($data['is_homepage'] ?? 0);
            if ($isHome === 1) {
                $db->table('cms_pages')->update(['is_homepage' => 0]);
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

            if ($pageId > 0) {
                $db->table('cms_pages')->where('id', $pageId)->update($payload);
            } else {
                $db->table('cms_pages')->insert($payload);
                $pageId = (int) $db->insertID();
            }

            return [
                'status'  => 'success',
                'page_id' => $pageId,
                'slug'    => $slug,
                'message' => 'Page saved & published at /' . $slug,
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'error',
                'message' => 'Database Error: ' . $e->getMessage(),
            ];
        }
    }

    public function deletePageById(int $id): bool
    {
        try {
            $db = Database::connect();
            return (bool) $db->table('cms_pages')->where('id', $id)->delete();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function flipComponentState(int $compId): array
    {
        try {
            $db   = Database::connect();
            $comp = $db->table('cms_components')->where('id', $compId)->get()->getRowArray();
            if (! $comp) {
                return ['status' => 'error', 'message' => 'Component not found.'];
            }
            $newState = ((int) $comp['is_enabled'] === 1) ? 0 : 1;
            $db->table('cms_components')->where('id', $compId)->update(['is_enabled' => $newState]);

            return [
                'status'     => 'success',
                'is_enabled' => $newState,
                'message'    => 'Component "' . $comp['component_name'] . '" ' . ($newState ? 'ENABLED' : 'DISABLED') . '.',
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Failed to toggle component.'];
        }
    }
}
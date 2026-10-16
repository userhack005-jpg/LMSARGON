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
	
	
	/**
     * ONE-TIME AUTO-INITIALIZER FOR FOOTER TEMPLATES (Runs only once)
     */
    public function ensureFooterSchema(): void
    {
          $footerLockV3 = WRITEPATH . 'schema_addon_pb_footers_v3.lock';
        if (is_file($footerLockV3)) {
            return;
        }
		
		

        try {
            $db = \Config\Database::connect();$db->query("
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

            if ($db->table('cms_footers')->countAllResults() === 0) {$defaultTemplates = [
                    [
                        'name'           => 'Style 01 // Dual Split Footer (2 Sections)',
                        'style_key'      => 'style_2col',
                        'columns_count'  => 2,
                        'copyright_text' => '© ' . date('Y') . ' All Rights Reserved.',
                        'is_active'      => 0,
                        'sections_json'  => json_encode([
                            [['id' => 'f1', 'type' => 'rich_text', 'data' => ['html' => '<h3>About Platform</h3><p>Modern modular web platform built for speed and scalability.</p>']]],
                            [['id' => 'f2', 'type' => 'cta_buttons', 'data' => ['title' => 'Join Our Community', 'primary_text' => 'Sign In', 'primary_url' => '/login', 'secondary_text' => 'About Us', 'secondary_url' => '/about-us']]],
                        ]),
                    ],
                    [
                        'name'           => 'Style 02 // Balanced Grid Footer (3 Sections)',
                        'style_key'      => 'style_3col',
                        'columns_count'  => 3,
                        'copyright_text' => '© ' . date('Y') . ' Enterprise Portal. Powered by CI4.',
                        'is_active'      => 1,
                        'sections_json'  => json_encode([
                            [['id' => 'f3', 'type' => 'rich_text', 'data' => ['html' => '<h3>Brand Identity</h3><p>Empowering learners and organizations worldwide.</p>']]],
                            [['id' => 'f4', 'type' => 'rich_text', 'data' => ['html' => '<h3>Quick Links</h3><ul><li><a href="/about-us">About Us</a></li><li><a href="/login">Portal Login</a></li></ul>']]],
                            [['id' => 'f5', 'type' => 'rich_text', 'data' => ['html' => '<h3>Contact Desk</h3><p>Email: support@domain.com<br>Hours: Mon - Sat (9AM - 7PM)</p>']]],
                        ]),
                    ],
                    [
                        'name'           => 'Style 03 // Enterprise Matrix Footer (4 Sections)',
                        'style_key'      => 'style_4col',
                        'columns_count'  => 4,
                        'copyright_text' => '© ' . date('Y') . ' Global Operations.',
                        'is_active'      => 0,
                        'sections_json'  => json_encode([
                            [['id' => 'f6', 'type' => 'rich_text', 'data' => ['html' => '<h4>Overview</h4><p>Next-gen modular architecture.</p>']]],
                            [['id' => 'f7', 'type' => 'rich_text', 'data' => ['html' => '<h4>Modules</h4><p>CMS, RBAC, Analytics & Gateways.</p>']]],
                            [['id' => 'f8', 'type' => 'rich_text', 'data' => ['html' => '<h4>Resources</h4><p>Documentation, Help Center & FAQs.</p>']]],
                            [['id' => 'f9', 'type' => 'cta_buttons', 'data' => ['title' => 'Get Started', 'primary_text' => 'Launch App', 'primary_url' => '/login', 'secondary_text' => '', 'secondary_url' => '']]],
                        ]),
                    ],
                    [
                        'name'           => 'Style 04 // Highlight Bar + 3 Sections',
                        'style_key'      => 'style_highlight_3col',
                        'columns_count'  => 3,
                        'copyright_text' => '© ' . date('Y') . ' Crafted with precision.',
                        'is_active'      => 0,
                        'sections_json'  => json_encode([
                            [['id' => 'f10', 'type' => 'rich_text', 'data' => ['html' => '<h4>Our Mission</h4><p>Delivering high-performance digital tools.</p>']]],
                            [['id' => 'f11', 'type' => 'rich_text', 'data' => ['html' => '<h4>Navigation</h4><p><a href="/">Home</a> | <a href="/about-us">About</a></p>']]],
                            [['id' => 'f12', 'type' => 'custom_html', 'data' => ['raw_code' => '<div class="status-pill"><span class="pulse-dot"></span><span class="mono-label">SYSTEMS ONLINE</span></div>']]],
                        ]),
                    ],
                ];
                $db->table('cms_footers')->insertBatch($defaultTemplates);
            }

            file_put_contents($footerLock, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
        } catch (\Throwable $e) {
        }
		
		try {
            $db = \Config\Database::connect();$db->query("
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

            // Check if Single Column (1-Section) template exists; if not, seed it once!
            $hasSingle =$db->table('cms_footers')->where('style_key', 'style_1col')->countAllResults();
            if ($hasSingle === 0) {$db->table('cms_footers')->insert([
                    'name'           => 'Style 05 // Single Full-Width Banner (1 Section)',
                    'style_key'      => 'style_1col',
                    'columns_count'  => 1,
                    'copyright_text' => '© ' . date('Y') . ' All Rights Reserved.',
                    'is_active'      => 0,
                    'sections_json'  => json_encode([
                        [
                            [
                                'id'   => 'f_single_1',
                                'type' => 'cta_buttons',
                                'data' => [
                                    'title'          => 'Ready to Transform Your Learning Experience?',
                                    'primary_text'   => 'Get Started Now',
                                    'primary_url'    => '/login',
                                    'secondary_text' => 'Learn More About Us',
                                    'secondary_url'  => '/about-us',
                                ],
                            ],
                        ],
                    ]),
                ]);
            }

            file_put_contents($footerLockV2, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
        } catch (\Throwable $e) {
        }
		
		
		// New v3 lock ensures the 1-Section Tab is added & ordered at #1 once!
     

        try {
            $db = \Config\Database::connect();$db->query("
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

            // Ensure "1 Section (Single Full-Width)" tab exists in `cms_footers`
            $singleRow =$db->table('cms_footers')->where('style_key', 'style_1col')->get()->getRowArray();
            if (! $singleRow) {$db->table('cms_footers')->insert([
                    'name'           => '1 Section // Full-Width Single Footer',
                    'style_key'      => 'style_1col',
                    'columns_count'  => 1,
                    'copyright_text' => '© ' . date('Y') . ' All Rights Reserved.',
                    'is_active'      => 0,
                    'sections_json'  => json_encode([
                        [
                            [
                                'id'   => 'f_single_1',
                                'type' => 'hero_banner',
                                'data' => [
                                    'badge'      => 'SINGLE SECTION FOOTER',
                                    'heading'    => 'Ready to Transform Your Learning Experience?',
                                    'subheading' => 'Full-width single column footer section powered by the Visual Builder.',
                                    'btn_text'   => 'Get Started Now',
                                    'btn_url'    => '/login',
                                    'btn_style'  => 'primary',
                                    'align'      => 'center',
                                ],
                            ],
                        ],
                    ]),
                ]);
            } else {
                $db->table('cms_footers')->where('style_key', 'style_1col')->update([
                    'name'          => '1 Section // Full-Width Single Footer',
                    'columns_count' => 1,
                ]);
            }

            // Rename existing tabs cleanly so top tabs show: 1 Section, 2 Sections, 3 Sections, 4 Sections
            $db->table('cms_footers')->where('style_key', 'style_2col')->update(['name' => '2 Sections // Split Footer']);$db->table('cms_footers')->where('style_key', 'style_3col')->update(['name' => '3 Sections // Balanced Grid']);
            $db->table('cms_footers')->where('style_key', 'style_4col')->update(['name' => '4 Sections // Enterprise Matrix']);$db->table('cms_footers')->where('style_key', 'style_highlight_3col')->update(['name' => 'Highlight Bar // 3 Sections']);

            file_put_contents($footerLockV3, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
        } catch (\Throwable $e) {
        }
		
		
		
		
    }

    public function getAllFooters(): array
    {
      $this->ensureFooterSchema();
        try {
            // Order by `columns_count ASC` so "1 Section" tab ALWAYS appears FIRST in the top tabs!
            return \Config\Database::connect()
                ->table('cms_footers')
                ->orderBy('columns_count', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
public function getActiveFooter(): ?array
    {
        $this->ensureFooterSchema();
        try {
            $db  = \Config\Database::connect();
            $row = $db->table('cms_footers')->where('is_active', 1)->get()->getRowArray();

            // Fallback: Agar koi bhi active=1 nahi mila, toh latest updated footer dikhao (Never Blank!)
            if (! $row) {
                $row = $db->table('cms_footers')->orderBy('updated_at', 'DESC')->get()->getRowArray();
            }
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function saveFooterTemplate(array $data): bool
    {
        $this->ensureFooterSchema();
        try {
            $db = \Config\Database::connect();
            $id = (int) $data['footer_id'];

            // Agar user ne "Activate" tick kiya hai, toh baaki sab ko 0 karke isko 1 karo
            if (! empty($data['set_active'])) {
                $db->table('cms_footers')->update(['is_active' => 0]);
                $activeState = 1;
            } else {
                $activeState = (int) $data['current_active'];
            }

            return (bool) $db->table('cms_footers')->where('id', $id)->update([
                'columns_count'  => (int) $data['columns_count'],
                'sections_json'  => $data['sections_json'],
                'copyright_text' => $data['copyright_text'],
                'is_active'      => $activeState,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
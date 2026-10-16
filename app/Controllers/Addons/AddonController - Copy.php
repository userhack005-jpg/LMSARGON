<?php

namespace App\Controllers\Addons;

use App\Controllers\BaseController;
use App\Models\Addons\AddonModel;
use App\Models\Settings\SettingModel;
use ZipArchive;

class AddonController extends BaseController
{
    protected AddonModel $addonModel;
    protected SettingModel $settingModel;
    protected string $lockFile;

    public function __construct()
    {
        helper(['url', 'form', 'filesystem']);
        $this->addonModel   = new AddonModel();
        $this->settingModel = new SettingModel();
        $this->lockFile     = WRITEPATH . 'installed.lock';
    }

    private function canManageAddons(): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        return in_array((string) session()->get('role_slug'), ['super_admin', 'admin'], true);
    }

    public function index()
    {
        if (! is_file($this->lockFile)) {
            return redirect()->to(base_url('install'));
        }
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->canManageAddons()) {
            return redirect()->to(base_url('dashboard'));
        }

        $addons   = $this->addonModel->getAllAddons();
        $settings = $this->settingModel->getSystemSettings();

        return view('addons/index_view', [
            'activeMenu' => 'addons',
            'settings'   => $settings,
            'addons'     => $addons,
            'user'       => session()->get(),
            'pageTitle'  => ($settings['site_title'] ?? 'System') . ' — Modular Add-On Engine',
        ]);
    }

    public function uploadAndInstall()
    {
        if (! $this->canManageAddons()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized: Only Super Admin or Admin can install add-ons.',
            ]);
        }

        $zipFile = $this->request->getFile('addon_zip');
        if (! $zipFile || ! $zipFile->isValid()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Please select a valid .zip add-on package file.',
            ]);
        }

        if (strtolower($zipFile->getClientExtension()) !== 'zip') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid file format. Only `.zip` packages are supported.',
            ]);
        }

        $pkgDir = WRITEPATH . 'addons/packages/';
        if (! is_dir($pkgDir)) {
            mkdir($pkgDir, 0775, true);
        }

        $savedName = 'pkg_' . time() . '_' . $zipFile->getRandomName();
        $zipFile->move($pkgDir, $savedName);

        $result = $this->addonModel->installFromZip($pkgDir . $savedName);

        return $this->response->setJSON($result);
    }

    public function toggleAddon()
    {
        if (! $this->canManageAddons()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized action.']);
        }

        $addonId = (int) $this->request->getPost('addon_id');
        return $this->response->setJSON($this->addonModel->toggleStatus($addonId));
    }

    /**
     * 1-Click Generator: Complete Drag & Drop Page Builder & Dynamic CMS Add-On (.zip)
     */
    public function downloadPageBuilderZip()
    {
        if (! $this->canManageAddons()) {
            return redirect()->to(base_url('dashboard'));
        }

        if (! class_exists('ZipArchive')) {
            return $this->response->setBody('PHP ZipArchive extension is required. Enable extension=zip in php.ini.');
        }

        $pkgDir = WRITEPATH . 'addons/packages/';
        if (! is_dir($pkgDir)) {
            mkdir($pkgDir, 0775, true);
        }

        $zipPath = $pkgDir . 'visual_page_builder_cms_v1.zip';
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // ----------------------------------------------------------
        // 1. addon.json Manifest
        // ----------------------------------------------------------
        $manifest = [
            'slug'           => 'page_builder',
            'name'           => 'Visual Page Builder & Dynamic CMS',
            'category'       => 'frontend_cms',
            'version'        => '1.0.0',
            'author'         => 'Core Architecture Team',
            'description'    => 'Database-driven Drag & Drop Visual Page Builder with Rich Text Editor, Direct Image Upload, Interactive Quiz Blocks, CTA Buttons, Custom URL Slugs (e.g. /about-us), and Dynamic Homepage routing.',
            'admin_route'    => 'addons/page-builder',
            'frontend_route' => 'about-us',
            'init_model'     => 'App\\Models\\Addons\\PageBuilder\\PageBuilderModel',
            'default_config' => [
                'enable_root_homepage' => '1',
                'show_top_navbar'      => '1',
                'container_width'      => '1180px',
            ],
        ];
        $zip->addFromString('addon.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // ----------------------------------------------------------
        // 2. Dynamic Add-On Routes.php (Admin + Unrestricted Frontend Slugs)
        // ----------------------------------------------------------
        $routesPhp = <<<'PHP'
<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

// A. Backend Page Builder Admin Routes
$routes->group('addons/page-builder', ['namespace' => 'App\Controllers\Addons\PageBuilder'], static function ($routes) {
    $routes->get('/', 'PageBuilderController::index');
    $routes->get('editor', 'PageBuilderController::editor');
    $routes->get('editor/(:num)', 'PageBuilderController::editor/$1');
    $routes->post('save-page', 'PageBuilderController::savePage');
    $routes->post('delete-page', 'PageBuilderController::deletePage');
    $routes->post('toggle-component', 'PageBuilderController::toggleComponent');
    $routes->post('upload-media', 'PageBuilderController::uploadMedia');
});

// B. Explicit /p/(:any) Frontend Preview Route
$routes->get('p/(:any)', 'App\Controllers\Addons\PageBuilder\PageBuilderController::renderFrontend/$1');

// C. Dynamic Custom Slug Route (Matches any slug that isn't a reserved system controller)
$routes->get('(:segment)', 'App\Controllers\Addons\PageBuilder\PageBuilderController::renderFrontend/$1', ['priority' => 100]);
PHP;
        $zip->addFromString('Routes.php', $routesPhp);

        // ----------------------------------------------------------
        // 3. PageBuilderModel.php (1-Time DB Migration for Pages & Components)
        // ----------------------------------------------------------
        $modelPhp = <<<'PHP'
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
PHP;
        $zip->addFromString('payload/app/Models/Addons/PageBuilder/PageBuilderModel.php', $modelPhp);

        // ----------------------------------------------------------
        // 4. PageBuilderController.php (Admin Manager, Visual Editor, Media Upload & Frontend Renderer)
        // ----------------------------------------------------------
        $controllerPhp = <<<'PHP'
<?php

namespace App\Controllers\Addons\PageBuilder;

use App\Controllers\BaseController;
use App\Models\Addons\PageBuilder\PageBuilderModel;
use App\Models\Settings\SettingModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PageBuilderController extends BaseController
{
    protected PageBuilderModel $pbModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form', 'filesystem']);
        $this->pbModel      = new PageBuilderModel();
        $this->settingModel = new SettingModel();
    }

    private function canManagePages(): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        return in_array((string) session()->get('role_slug'), ['super_admin', 'admin'], true);
    }

    /**
     * Admin Dashboard: List All Dynamic Pages & Toggle DB-Driven Components
     */
    public function index()
    {
        if (! $this->canManagePages()) {
            return redirect()->to(base_url('login'));
        }

        $pages      = $this->pbModel->getAllPages();
        $components = $this->pbModel->getAllComponents(false);
        $settings   = $this->settingModel->getSystemSettings();

        return view('addons/page_builder/pages_list_view', [
            'activeMenu' => 'addon_page_builder',
            'settings'   => $settings,
            'pages'      => $pages,
            'components' => $components,
            'user'       => session()->get(),
            'pageTitle'  => 'Visual Page Builder & Component Registry',
        ]);
    }

    /**
     * Visual Drag & Drop Page Editor Workspace
     */
    public function editor(?int $pageId = null)
    {
        if (! $this->canManagePages()) {
            return redirect()->to(base_url('login'));
        }

        $page = $pageId ? $this->pbModel->getPageById($pageId) : null;
        if (! $page) {
            $page = [
                'id'               => 0,
                'title'            => '',
                'slug'             => '',
                'blocks_json'      => '[]',
                'meta_title'       => '',
                'meta_description' => '',
                'status'           => 'published',
                'is_homepage'      => 0,
                'show_in_nav'      => 1,
            ];
        }

        $enabledComponents = $this->pbModel->getAllComponents(true);
        $settings          = $this->settingModel->getSystemSettings();

        return view('addons/page_builder/editor_view', [
            'activeMenu' => 'addon_page_builder',
            'settings'   => $settings,
            'page'       => $page,
            'components' => $enabledComponents,
            'user'       => session()->get(),
            'pageTitle'  => ($page['id'] > 0 ? 'Edit Page: ' . $page['title'] : 'Create New Dynamic Page'),
        ]);
    }

    /**
     * AJAX Endpoint: Save Page & Blocks JSON
     */
    public function savePage()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $title = trim((string) $this->request->getPost('title'));
        $slug  = trim((string) $this->request->getPost('slug'));

        if ($title === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Page Title is required.']);
        }

        if ($slug === '') {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
        }

        $result = $this->pbModel->savePageData([
            'id'               => (int) $this->request->getPost('page_id'),
            'title'            => $title,
            'slug'             => $slug,
            'blocks_json'      => (string) $this->request->getPost('blocks_json'),
            'meta_title'       => trim((string) $this->request->getPost('meta_title')),
            'meta_description' => trim((string) $this->request->getPost('meta_description')),
            'status'           => (string) ($this->request->getPost('status') ?: 'published'),
            'is_homepage'      => (int) ($this->request->getPost('is_homepage') ? 1 : 0),
            'show_in_nav'      => (int) ($this->request->getPost('show_in_nav') ? 1 : 0),
        ]);

        return $this->response->setJSON($result);
    }

    /**
     * AJAX Endpoint: Delete Dynamic Page
     */
    public function deletePage()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $id = (int) $this->request->getPost('page_id');
        $ok = $this->pbModel->deletePageById($id);

        return $this->response->setJSON([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Page deleted permanently.' : 'Unable to delete page.',
        ]);
    }

    /**
     * AJAX Endpoint: Enable / Disable a Builder Component in Database
     */
    public function toggleComponent()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $compId = (int) $this->request->getPost('component_id');
        return $this->response->setJSON($this->pbModel->flipComponentState($compId));
    }

    /**
     * AJAX Endpoint: Upload Image Directly from Page Builder
     */
    public function uploadMedia()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $imgFile = $this->request->getFile('media_file');
        if (! $imgFile || ! $imgFile->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No valid image selected.']);
        }

        $ext = strtolower($imgFile->getClientExtension());
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Only PNG, JPG, WEBP, GIF, and SVG images are allowed.']);
        }

        $uploadDir = FCPATH . 'uploads/cms/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $newName = 'cms_' . time() . '_' . $imgFile->getRandomName();
        $imgFile->move($uploadDir, $newName);

        return $this->response->setJSON([
            'status'    => 'success',
            'image_url' => base_url('uploads/cms/' . $newName),
            'message'   => 'Image uploaded!',
        ]);
    }

    /**
     * Frontend Dynamic Page Renderer (Matches `/about-us`, `/p/about-us`, or Homepage)
     */
    public function renderFrontend(string $slug = '')
    {
        $page = $this->pbModel->getPageBySlug($slug);
        if (! $page) {
            throw PageNotFoundException::forPageNotFound('The requested page "/' . esc($slug) . '" was not found.');
        }

        $settings = $this->settingModel->getSystemSettings();
        $navPages = $this->pbModel->getPublishedNavPages();
        $blocks   = json_decode((string) $page['blocks_json'], true) ?: [];

        // Override SEO Meta if defined on the page
        if (! empty($page['meta_title'])) {
            $settings['meta_title'] = $page['meta_title'];
        }
        if (! empty($page['meta_description'])) {
            $settings['meta_description'] = $page['meta_description'];
        }

        return view('addons/page_builder/frontend_page_view', [
            'settings'  => $settings,
            'page'      => $page,
            'blocks'    => $blocks,
            'navPages'  => $navPages,
            'pageTitle' => $page['meta_title'] ?: ($page['title'] . ' — ' . ($settings['site_title'] ?? 'Website')),
        ]);
    }
}
PHP;
        $zip->addFromString('payload/app/Controllers/Addons/PageBuilder/PageBuilderController.php', $controllerPhp);

        // ----------------------------------------------------------
        // 5. View 1: Admin Pages List & DB Component Toggle (`pages_list_view.php`)
        // ----------------------------------------------------------
        $listView = <<<'HTML'
<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div id="pb-toast" class="alert-banner is-hidden" role="alert"></div>

<div class="rbac-split-grid">

    <!-- LEFT: Database-Driven Component Registry (Enable/Disable Widgets) -->
    <aside class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">DB COMPONENT REGISTRY</span>
                <h3 class="panel-title">Builder Widgets (`cms_components`)</h3>
            </div>
            <span class="req-badge">MODULAR</span>
        </div>

        <p class="field-hint" style="margin-bottom: 1rem;">
            Enable or disable components available inside the Drag & Drop Page Editor. Any future module can register its own widget in this table!
        </p>

        <div class="telemetry-list">
            <?php foreach ($components as $c): ?>
            <?php $enabled = ((int) $c['is_enabled'] === 1); ?>
            <div class="telemetry-item">
                <div>
                    <div class="identity-name">
                        <span class="text-emerald"><?= esc($c['component_icon']) ?></span>
                        <span><?= esc($c['component_name']) ?></span>
                    </div>
                    <div class="identity-email">key: <?= esc($c['component_key']) ?> // <?= esc($c['category']) ?></div>
                </div>
                <button type="button"
                        class="btn-action js-pb-toggle-comp"
                        data-comp-id="<?= esc((string) $c['id']) ?>"
                        data-action-url="<?= base_url('addons/page-builder/toggle-component') ?>">
                    <?= $enabled ? '✓ Enabled' : '✕ Disabled' ?>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- RIGHT: Dynamic Pages Directory -->
    <section class="panel-card">
        <div class="panel-header panel-header-wrap">
            <div>
                <span class="mono-label">DYNAMIC FRONTEND ROUTING</span>
                <h3 class="panel-title">Website Pages (`cms_pages`)</h3>
            </div>
            <a href="<?= base_url('addons/page-builder/editor') ?>" class="btn-primary btn-sm">
                <span>+ Create New Dynamic Page</span>
            </a>
        </div>

        <div class="role-table-wrapper">
            <table class="role-table">
                <thead>
                    <tr>
                        <th>Page Title</th>
                        <th>Custom URL Slug</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pages)): ?>
                        <tr>
                            <td colspan="5" class="muted-cell">No dynamic pages created yet. Click "+ Create New Dynamic Page" to launch the Visual Builder!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pages as $p): ?>
                        <tr>
                            <td>
                                <div class="identity-name">
                                    <?= esc($p['title']) ?>
                                    <?php if ((int) $p['is_homepage'] === 1): ?>
                                        <span class="self-tag">HOMEPAGE (/)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="identity-email"><?= esc($p['meta_title'] ?: 'Default SEO Title') ?></div>
                            </td>
                            <td class="mono-cell">
                                <a href="<?= base_url(esc($p['slug'])) ?>" target="_blank" class="text-emerald">
                                    /<?= esc($p['slug']) ?> ↗
                                </a>
                            </td>
                            <td>
                                <span class="status-indicator <?= $p['status'] === 'published' ? 'status-active' : 'status-suspended' ?>">
                                    <?= esc(strtoupper($p['status'])) ?>
                                </span>
                            </td>
                            <td class="mono-cell muted-cell"><?= esc(date('d M Y, H:i', strtotime($p['updated_at']))) ?></td>
                            <td class="text-right">
                                <div class="action-btn-group">
                                    <a href="<?= base_url(esc($p['slug'])) ?>" target="_blank" class="btn-action">↗ View Live</a>
                                    <a href="<?= base_url('addons/page-builder/editor/' . $p['id']) ?>" class="btn-action">✎ Visual Builder</a>
                                    <button type="button"
                                            class="btn-action btn-action-danger js-pb-delete-page"
                                            data-page-id="<?= esc((string) $p['id']) ?>"
                                            data-page-title="<?= esc($p['title']) ?>"
                                            data-action-url="<?= base_url('addons/page-builder/delete-page') ?>">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>
<?= $this->endSection() ?>
HTML;
        $zip->addFromString('payload/app/Views/addons/page_builder/pages_list_view.php', $listView);

        // ----------------------------------------------------------
        // 6. View 2: Visual Drag & Drop Page Editor (`editor_view.php`)
        // ----------------------------------------------------------
        $editorView = <<<'HTML'
<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div id="pb-editor-toast" class="alert-banner is-hidden" role="alert"></div>

<form id="pb-save-form"
      action="<?= base_url('addons/page-builder/save-page') ?>"
      method="post"
      data-upload-url="<?= base_url('addons/page-builder/upload-media') ?>"
      novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="page_id" id="pb_page_id" value="<?= esc((string) $page['id']) ?>">
    <textarea name="blocks_json" id="pb_blocks_json" class="is-hidden"><?= esc($page['blocks_json'] ?: '[]') ?></textarea>

    <!-- TOP ACTION BAR -->
    <section class="panel-card" style="margin-bottom: 1.25rem;">
        <div class="panel-header panel-header-wrap" style="margin-bottom: 1rem;">
            <div>
                <span class="mono-label">VISUAL DRAG & DROP PAGE BUILDER</span>
                <h3 class="panel-title"><?= $page['id'] > 0 ? 'Editing: ' . esc($page['title']) : 'New Unrestricted Dynamic Page' ?></h3>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('addons/page-builder') ?>" class="btn-secondary btn-sm">← All Pages</a>
                <?php if (! empty($page['slug'])): ?>
                    <a href="<?= base_url(esc($page['slug'])) ?>" target="_blank" id="pb-live-link" class="btn-secondary btn-sm">↗ View /<?= esc($page['slug']) ?></a>
                <?php endif; ?>
                <button type="submit" id="btn-pb-save" class="btn-primary btn-sm">
                    <span id="btn-pb-save-text">Save & Publish Page ✓</span>
                </button>
            </div>
        </div>

        <!-- Page Title, Custom Slug & Options -->
        <div class="grid-3-col">
            <div class="field-group">
                <label class="field-label"><span>Page Title</span><span class="req-badge">REQUIRED</span></label>
                <input type="text" name="title" id="pb_title" class="field-input" value="<?= esc($page['title']) ?>" placeholder="e.g., About Us" required>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Custom URL Slug (Dynamic URL)</span><span class="req-badge">CUSTOMIZABLE</span></label>
                <input type="text" name="slug" id="pb_slug" class="field-input mono-input" value="<?= esc($page['slug']) ?>" placeholder="about-us">
                <span class="field-hint">Live URL: <strong><?= base_url() ?><span id="slug-preview-text"><?= esc($page['slug'] ?: 'about-us') ?></span></strong></span>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Publish Status</span></label>
                <select name="status" class="field-select">
                    <option value="published" <?= ($page['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                    <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                </select>
            </div>
        </div>

        <div class="grid-3-col">
            <div class="field-group">
                <label class="field-label"><span>SEO Meta Title (Optional)</span></label>
                <input type="text" name="meta_title" class="field-input" value="<?= esc($page['meta_title'] ?? '') ?>" placeholder="Custom browser title">
            </div>
            <div class="field-group">
                <label class="field-label"><span>SEO Meta Description (Optional)</span></label>
                <input type="text" name="meta_description" class="field-input" value="<?= esc($page['meta_description'] ?? '') ?>" placeholder="Summary for Google search">
            </div>
            <div class="field-group" style="display:flex; align-items:center; gap:1.25rem; padding-top:1.4rem;">
                <label style="display:inline-flex; align-items:center; gap:0.45rem; cursor:pointer; font-size:0.84rem;">
                    <input type="checkbox" name="is_homepage" value="1" <?= ((int) ($page['is_homepage'] ?? 0) === 1) ? 'checked' : '' ?>>
                    <span>Set as Root Homepage (`/`)</span>
                </label>
                <label style="display:inline-flex; align-items:center; gap:0.45rem; cursor:pointer; font-size:0.84rem;">
                    <input type="checkbox" name="show_in_nav" value="1" <?= ((int) ($page['show_in_nav'] ?? 1) === 1) ? 'checked' : '' ?>>
                    <span>Show in Top Navbar</span>
                </label>
            </div>
        </div>
    </section>

    <!-- BUILDER SPLIT WORKSPACE: Left Palette (DB Components) + Right Drag & Drop Canvas -->
    <div class="rbac-split-grid">

        <!-- LEFT PALETTE: Click or Drag to Add Component -->
        <aside class="panel-card">
            <div class="panel-header">
                <div>
                    <span class="mono-label">COMPONENT PALETTE</span>
                    <h3 class="panel-title">Add Content Blocks</h3>
                </div>
                <span class="req-badge"><?= count($components) ?> ACTIVE</span>
            </div>

            <p class="field-hint" style="margin-bottom: 0.9rem;">
                Click any component below (or drag it) to append it to the visual page canvas:
            </p>

            <div class="pb-palette-list">
                <?php foreach ($components as$comp): ?>
                <button type="button"
                        class="pb-palette-item js-pb-add-block"
                        draggable="true"
                        data-comp-key="<?= esc($comp['component_key']) ?>"
                        data-comp-name="<?= esc($comp['component_name']) ?>"
                        data-comp-schema="<?= esc($comp['default_schema_json']) ?>">
                    <span class="pb-palette-icon"><?= esc($comp['component_icon']) ?></span>
                    <div class="pb-palette-meta">
                        <strong><?= esc($comp['component_name']) ?></strong>
                        <span><?= esc(strtoupper($comp['category'])) ?></span>
                    </div>
                    <span class="pb-palette-plus">+ Add</span>
                </button>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- RIGHT CANVAS: Live Reorderable Block Stack -->
        <section class="panel-card">
            <div class="panel-header">
                <div>
                    <span class="mono-label">LIVE PAGE CANVAS (DRAG ↑↓ TO REORDER)</span>
                    <h3 class="panel-title">Page Structure & Rich Content Blocks</h3>
                </div>
                <button type="button" id="btn-pb-clear-all" class="btn-secondary btn-sm">Clear Canvas</button>
            </div>

            <!-- Dynamic Blocks Render Here via JS -->
            <div id="pb-canvas-dropzone" class="pb-canvas-container"></div>
        </section>

    </div>
</form>
<?= $this->endSection() ?>
HTML;
        $zip->addFromString('payload/app/Views/addons/page_builder/editor_view.php',$editorView);

        // ----------------------------------------------------------
        // 7. View 3: Public Frontend Dynamic Website Renderer (`frontend_page_view.php`)
        // ----------------------------------------------------------
        $frontendView = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $this->include('layouts/partials/head_meta') ?>
</head>
<body class="theme-obsidian" data-base-url="<?= base_url() ?>">

    <?php if (! empty($settings['body_scripts'])): ?>
        <?= $settings['body_scripts'] ?>
    <?php endif; ?>

    <div class="ambient-grid"></div>

    <!-- Frontend Website Public Navigation Bar -->
    <header class="cms-public-navbar">
        <div class="cms-nav-inner">
            <a href="<?= base_url() ?>" class="cms-brand-link">
                <?php if (! empty($settings['site_logo'])): ?>
                    <img src="<?= base_url(esc($settings['site_logo'])) ?>" alt="Logo" class="brand-logo-sm">
                <?php else: ?>
                    <div class="brand-monogram-sm"><?= esc(strtoupper(substr($settings['site_title'] ?? 'S', 0, 2))) ?></div>
                <?php endif; ?>
                <span class="cms-brand-title"><?= esc($settings['site_title'] ?? 'Portal') ?></span>
            </a>

            <nav class="cms-nav-links">
                <?php foreach ($navPages as$np): ?>
                    <a href="<?= base_url(esc($np['slug'])) ?>" class="cms-nav-item <?= ($np['id'] ===$page['id']) ? 'is-active' : '' ?>">
                        <?= esc($np['title']) ?>
                    </a>
                <?php endforeach; ?>

                <?php if (session()->get('isLoggedIn')): ?>
                    <a href="<?= base_url('addons/page-builder/editor/' . $page['id']) ?>" class="btn-secondary btn-sm">✎ Edit This Page</a>
                    <a href="<?= base_url('dashboard') ?>" class="btn-primary btn-sm">Dashboard →</a>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="btn-primary btn-sm">Sign In →</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Dynamic Blocks Renderer -->
    <main class="cms-public-main">
        <?php if (empty($blocks)): ?>
            <section class="console-card">
                <h2><?= esc($page['title']) ?></h2>
                <p class="muted-cell">This page has no blocks yet. Open the Visual Page Builder to add sections.</p>
            </section>
        <?php else: ?>
            <?php foreach ($blocks as $idx =>$blk): ?>
                <?php
                    $type =$blk['type'] ?? 'rich_text';
                    $data =$blk['data'] ?? [];
                ?>

                <?php if ($type === 'hero_banner'): ?>
                    <section class="cms-block cms-hero align-<?= esc($data['align'] ?? 'center') ?>">
                        <?php if (! empty($data['badge'])): ?>
                            <div class="status-pill"><span class="pulse-dot"></span><span class="mono-label"><?= esc($data['badge']) ?></span></div>
                        <?php endif; ?>
                        <h1 class="cms-hero-title"><?= esc($data['heading'] ?? '') ?></h1>
                        <?php if (! empty($data['subheading'])): ?>
                            <p class="cms-hero-sub"><?= esc($data['subheading']) ?></p>
                        <?php endif; ?>
                        <?php if (! empty($data['btn_text'])): ?>
                            <div style="margin-top: 1.5rem;">
                                <a href="<?= esc($data['btn_url'] ?? '#') ?>" class="<?= ($data['btn_style'] ?? 'primary') === 'secondary' ? 'btn-secondary' : 'btn-primary' ?>">
                                    <span><?= esc($data['btn_text']) ?></span>
                                    <span class="btn-arrow">→</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($type === 'rich_text'): ?>
                    <section class="cms-block cms-richtext-box">
                        <div class="cms-prose">
                            <?= $data['html'] ?? '' ?>
                        </div>
                    </section>

                <?php elseif ($type === 'image_media'): ?>
                    <section class="cms-block cms-media-box">
                        <?php if (! empty($data['image_url'])): ?>
                            <figure class="cms-figure">
                                <img src="<?= esc($data['image_url']) ?>" alt="<?= esc($data['alt_text'] ?? 'Image') ?>" style="max-width: <?= esc($data['width'] ?? '100%') ?>;">
                                <?php if (! empty($data['caption'])): ?>
                                    <figcaption class="field-hint"><?= esc($data['caption']) ?></figcaption>
                                <?php endif; ?>
                            </figure>
                        <?php endif; ?>
                    </section>

                <?php elseif ($type === 'cta_buttons'): ?>
                    <section class="cms-block cms-cta-strip">
                        <h3><?= esc($data['title'] ?? 'Take the next step') ?></h3>
                        <div class="action-btn-group">
                            <?php if (! empty($data['primary_text'])): ?>
                                <a href="<?= esc($data['primary_url'] ?? '#') ?>" class="btn-primary"><?= esc($data['primary_text']) ?></a>
                            <?php endif; ?>
                            <?php if (! empty($data['secondary_text'])): ?>
                                <a href="<?= esc($data['secondary_url'] ?? '#') ?>" class="btn-secondary"><?= esc($data['secondary_text']) ?></a>
                            <?php endif; ?>
                        </div>
                    </section>

                <?php elseif ($type === 'interactive_quiz'): ?>
                    <section class="cms-block cms-quiz-card" data-correct="<?= esc(strtoupper($data['correct_index'] ?? 'A')) ?>" data-explanation="<?= esc($data['explanation'] ?? 'Correct Answer!') ?>">
                        <div class="status-pill"><span class="mono-label">INTERACTIVE KNOWLEDGE CHECK</span></div>
                        <h3 class="cms-quiz-question"><?= esc($data['question'] ?? '') ?></h3>
                        <div class="cms-quiz-options">
                            <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $letter =>$key): ?>
                                <?php if (! empty($data[$key])): ?>
                                    <button type="button" class="cms-quiz-opt js-quiz-answer" data-choice="<?= $letter ?>">
                                        <span class="step-index"><?= $letter ?></span>
                                        <span><?= esc($data[$key]) ?></span>
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="alert-banner is-hidden js-quiz-feedback" style="margin-top: 1rem;"></div>
                    </section>

                <?php elseif ($type === 'feature_grid'): ?>
                    <section class="cms-block">
                        <?php if (! empty($data['section_title'])): ?>
                            <h2 style="margin-bottom: 1.25rem; text-align: center;"><?= esc($data['section_title']) ?></h2>
                        <?php endif; ?>
                        <div class="grid-3-col">
                            <div class="panel-card">
                                <h4 style="color: var(--accent-emerald); margin-bottom: 0.4rem;"><?= esc($data['card1_title'] ?? '') ?></h4>
                                <p class="muted-cell"><?= esc($data['card1_desc'] ?? '') ?></p>
                            </div>
                            <div class="panel-card">
                                <h4 style="color: var(--accent-cyan); margin-bottom: 0.4rem;"><?= esc($data['card2_title'] ?? '') ?></h4>
                                <p class="muted-cell"><?= esc($data['card2_desc'] ?? '') ?></p>
                            </div>
                            <div class="panel-card">
                                <h4 style="color: var(--accent-amber); margin-bottom: 0.4rem;"><?= esc($data['card3_title'] ?? '') ?></h4>
                                <p class="muted-cell"><?= esc($data['card3_desc'] ?? '') ?></p>
                            </div>
                        </div>
                    </section>

                <?php elseif ($type === 'custom_html'): ?>
                    <section class="cms-block">
                        <?= $data['raw_code'] ?? '' ?>
                    </section>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <footer class="app-footer" style="max-width: 1180px; margin: 3rem auto 0; border-radius: 12px 12px 0 0;">
        <span class="mono-note">© <?= date('Y') ?> <?= esc($settings['site_title'] ?? 'Platform') ?></span>
        <span class="mono-note">POWERED BY MODULAR PAGE BUILDER</span>
    </footer>

    <?php if (! empty($settings['footer_scripts'])): ?>
        <?= $settings['footer_scripts'] ?>
    <?php endif; ?>
</body>
</html>
HTML;
        $zip->addFromString('payload/app/Views/addons/page_builder/frontend_page_view.php',$frontendView);

        $zip->close();

        return $this->response->download($zipPath, null)->setFileName('visual_page_builder_cms_v1.zip');
    }

    /**
     * Existing Sample Payment Gateway ZIP Generator (Kept intact)
     */
    public function downloadSampleGatewayZip()
    {
        if (! $this->canManageAddons()) { return redirect()->to(base_url('dashboard')); }$pkgDir  = WRITEPATH . 'addons/packages/';
        $zipPath =$pkgDir . 'payment_gateway_suite_v1.zip';
        if (is_file($zipPath)) {
            return $this->response->download($zipPath, null)->setFileName('payment_gateway_suite_v1.zip');
        }
        return redirect()->to(base_url('addons'));
    }
}
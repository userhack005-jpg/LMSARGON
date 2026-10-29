<?php

namespace App\Models\Addons\Rbac;

use App\Models\Addons\AddonModel;
use CodeIgniter\Model;
use Throwable;

class RbacModel extends Model
{
    protected $table            = 'cms_roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'role_slug',
        'role_name',
        'portal_type',
        'description',
        'permissions_json',
        'workspace_json',
        'is_system',
        'is_default_front',
        'status',
        'created_at',
        'updated_at',
    ];

    protected string $featuresTable = 'cms_rbac_features';
    protected string $cacheFile     = WRITEPATH . 'addons/rbac_permissions_cache.json';
    protected static ?array $globalSettingsCache = null;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchemaInitialized();
    }

    public function ensureSchema(): void
    {
        $this->ensureSchemaInitialized();
    }

    public function ensureSchemaInitialized(): void
    {
        try {
            if (! $this->db->tableExists('cms_roles')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `cms_roles` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `role_slug` VARCHAR(64) NOT NULL,
                        `role_name` VARCHAR(120) NOT NULL,
                        `portal_type` VARCHAR(32) NOT NULL DEFAULT 'backend',
                        `description` VARCHAR(255) NOT NULL DEFAULT '',
                        `permissions_json` LONGTEXT NULL,
                        `workspace_json` LONGTEXT NULL,
                        `is_system` TINYINT(1) NOT NULL DEFAULT 0,
                        `is_default_front` TINYINT(1) NOT NULL DEFAULT 0,
                        `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        UNIQUE KEY `uniq_role_slug` (`role_slug`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } else {
                $rCols = $this->db->getFieldNames('cms_roles');
                if (! in_array('workspace_json', $rCols, true)) {
                    $this->db->query("ALTER TABLE `cms_roles` ADD COLUMN `workspace_json` LONGTEXT NULL AFTER `permissions_json`");
                }
            }

            $this->db->query("
                CREATE TABLE IF NOT EXISTS `cms_rbac_features` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `perm_key` VARCHAR(120) NOT NULL,
                    `perm_label` VARCHAR(190) NOT NULL,
                    `module_group` VARCHAR(90) NOT NULL DEFAULT 'Core System',
                    `source_type` VARCHAR(32) NOT NULL DEFAULT 'core',
                    `created_at` DATETIME NULL,
                    UNIQUE KEY `uniq_perm_key` (`perm_key`),
                    KEY `idx_module_group` (`module_group`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $lockFile = WRITEPATH . 'addons/schema_addon_rbac_v70.lock';
            if (is_file($lockFile) && is_file($this->cacheFile)) {
                return;
            }

            if ($this->db->tableExists('addons')) {
                $this->db->table('addons')
                    ->where('slug', 'rbac')
                    ->update(['name' => 'Roles, Permissions & Sidebar Architect']);
            }

            $this->syncCoreAndAddonPermissions();

            if ((int) $this->db->table($this->table)->countAllResults() === 0) {
                $now = date('Y-m-d H:i:s');
                $this->db->table($this->table)->insertBatch([
                    [
                        'role_slug'        => 'super_admin',
                        'role_name'        => 'Super Administrator',
                        'portal_type'      => 'backend',
                        'description'      => 'Unrestricted root access to all system modules, add-ons, and security settings.',
                        'permissions_json' => json_encode(['*']),
                        'workspace_json'   => json_encode(['show_child_switcher' => 0]),
                        'is_system'        => 1,
                        'is_default_front' => 0,
                        'status'           => 'active',
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ],
                    [
                        'role_slug'        => 'admin',
                        'role_name'        => 'Administrator',
                        'portal_type'      => 'backend',
                        'description'      => 'Administrative staff role — manages courses, users, pages, exams & moderation.',
                        'permissions_json' => json_encode(['dashboard.view', 'users.view', 'users.create', 'users.edit', 'settings.view', 'pages.view', 'pages.create', 'pages.edit']),
                        'workspace_json'   => json_encode(['show_child_switcher' => 0]),
                        'is_system'        => 1,
                        'is_default_front' => 0,
                        'status'           => 'active',
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ],
                    [
                        'role_slug'        => 'teacher',
                        'role_name'        => 'Teacher / Instructor',
                        'portal_type'      => 'backend',
                        'description'      => 'Instructor role — manages assigned batches, live classes, question banks & 1-to-1 student chat.',
                        'permissions_json' => json_encode(['dashboard.view']),
                        'workspace_json'   => json_encode(['show_child_switcher' => 0]),
                        'is_system'        => 1,
                        'is_default_front' => 0,
                        'status'           => 'active',
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ],
                    [
                        'role_slug'        => 'parent',
                        'role_name'        => 'Parent / Guardian',
                        'portal_type'      => 'frontend',
                        'description'      => 'Family account managing linked children, monthly fee subscriptions & progress reports.',
                        'permissions_json' => json_encode(['dashboard.view']),
                        'workspace_json'   => json_encode(['show_child_switcher' => 1]),
                        'is_system'        => 1,
                        'is_default_front' => 0,
                        'status'           => 'active',
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ],
                    [
                        'role_slug'        => 'student',
                        'role_name'        => 'Student (Learner)',
                        'portal_type'      => 'frontend',
                        'description'      => 'Learner role (Also active when Parent enters Child Learning Hub).',
                        'permissions_json' => json_encode(['dashboard.view']),
                        'workspace_json'   => json_encode(['show_child_switcher' => 0]),
                        'is_system'        => 1,
                        'is_default_front' => 1,
                        'status'           => 'active',
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ],
                ]);
            }

            // Apply Friendly Role Presets on upgrade so Student/Parent/Teacher menus immediately look clean!
            $this->applySmartRolePresets();
            $this->mirrorAssets(true);

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
        } catch (Throwable $e) {
            log_message('error', 'RbacModel schema error: ' . $e->getMessage());
        }
    }

    public function mirrorAssets(bool $forceTouch = false): void
    {
        $srcDir = WRITEPATH . 'addons/extracted/rbac/assets';
        $dstDir = FCPATH . 'assets/addons/rbac';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['rbac.css', 'rbac.js'] as $f) {
                $sFile = $srcDir . '/' . $f;
                $dFile = $dstDir . '/' . $f;
                if (is_file($sFile)) {
                    if ($forceTouch || ! is_file($dFile) || md5_file($sFile) !== md5_file($dFile)) {
                        @copy($sFile, $dFile);
                        @touch($dFile, time());
                    }
                }
            }
        }
    }

    public function getGlobalSiteSettings(): array
    {
        if (self::$globalSettingsCache !== null) {
            return self::$globalSettingsCache;
        }
        self::$globalSettingsCache = ['site_title' => 'Academy Portal', 'active_theme_mode' => 'dark', 'system_environment_mode' => 'testing'];
        try {
            if ($this->db->tableExists('settings')) {
                foreach ($this->db->table('settings')->get()->getResultArray() as $r) {
                    self::$globalSettingsCache[$r['setting_key']] = $r['setting_value'];
                }
            }
        } catch (Throwable $e) {
        }
        return self::$globalSettingsCache;
    }

    /**
     * Discovers all sidebar modules & sub-views from Core + ACTIVE Add-Ons
     * Synced with our latest v7.0 / v8.0 / v9.0 dedicated single-purpose routes!
     */
    public function discoverActiveNavigationTree(): array
    {
        $tree = [
            'core_dashboard' => [
                'slug'          => 'core_dashboard',
                'default_title' => 'Dashboard Overview',
                'icon'          => '⊞',
                'route'         => 'dashboard',
                'section'       => 'MAIN WORKSPACE',
                'permissions'   => ['dashboard.view'],
                'sub_views'     => [],
            ],
            'core_users' => [
                'slug'          => 'core_users',
                'default_title' => 'Users & Role Directory',
                'icon'          => '👥',
                'route'         => 'users',
                'section'       => 'ACCESS & SECURITY',
                'permissions'   => ['users.view', 'users.create', 'users.edit', 'users.delete'],
                'sub_views'     => [],
            ],
            'core_settings' => [
                'slug'          => 'core_settings',
                'default_title' => 'Site & SEO Settings',
                'icon'          => '⚙',
                'route'         => 'settings',
                'section'       => 'SYSTEM CONFIGURATION',
                'permissions'   => ['settings.view', 'settings.edit'],
                'sub_views'     => [],
            ],
            'core_addons' => [
                'slug'          => 'core_addons',
                'default_title' => 'Add-On Manager',
                'icon'          => '❖',
                'route'         => 'addons',
                'section'       => 'SYSTEM CONFIGURATION',
                'permissions'   => ['addons.manage'],
                'sub_views'     => [],
            ],
        ];

        $activeAddons = class_exists(AddonModel::class) ? (new AddonModel())->getActiveAddons() : [];

        $builtinSubViews = [
            'rbac' => [
                ['key' => 'rbac.overview',  'label' => 'Roles & Live Sidebar Preview', 'route' => 'addons/rbac',           'icon' => '📊', 'permission' => 'rbac.view'],
                ['key' => 'rbac.matrix',    'label' => 'Permission Switch Matrix',     'route' => 'addons/rbac/matrix',    'icon' => '🛡', 'permission' => 'rbac.view'],
                ['key' => 'rbac.workspace', 'label' => 'Menu Renamer & Widgets',       'route' => 'addons/rbac/workspace', 'icon' => '🎨', 'permission' => 'rbac.edit'],
            ],
            'page_builder' => [
                ['key' => 'pb.pages',  'label' => 'All Pages & Visual Builder', 'route' => 'addons/page-builder',        'icon' => '📄', 'permission' => 'pages.view'],
                ['key' => 'pb.create', 'label' => 'Create New Page',            'route' => 'addons/page-builder/editor', 'icon' => '➕', 'permission' => 'pages.create'],
                ['key' => 'pb.footer', 'label' => 'Footer Architect',           'route' => 'addons/page-builder/footer', 'icon' => '⊞', 'permission' => 'pages.edit'],
            ],
            'core_lms_auth' => [
                ['key' => 'cla.family',      'label' => 'My Children & Learning Hub', 'route' => 'portal/family',                    'icon' => '👨‍👩‍👧‍👦', 'permission' => 'core_lms_auth.view'],
                ['key' => 'cla.add_child',   'label' => 'Add Child Profile',          'route' => 'portal/family/add-child',          'icon' => '➕', 'permission' => 'core_lms_auth.view'],
                ['key' => 'cla.profile',     'label' => 'My Profile & Password',      'route' => 'portal/family/profile',            'icon' => '⚙',  'permission' => 'core_lms_auth.view'],
                ['key' => 'cla.directory',   'label' => 'Family Directory [Admin]',   'route' => 'addons/core-lms-auth/directory',   'icon' => '📋', 'permission' => 'core_lms_auth.edit'],
                ['key' => 'cla.linker',      'label' => 'Assign Child & Course',      'route' => 'addons/core-lms-auth/linker',      'icon' => '🔗', 'permission' => 'core_lms_auth.create'],
                ['key' => 'cla.role_mapper', 'label' => 'Role Behavior Mapper',       'route' => 'addons/core-lms-auth/role-mapper', 'icon' => '🛡', 'permission' => 'core_lms_auth.edit'],
                ['key' => 'cla.countries',   'label' => 'Servicing Countries',        'route' => 'addons/core-lms-auth/countries',   'icon' => '🌍', 'permission' => 'core_lms_auth.edit'],
            ],
            'lms_hierarchy' => [
                ['key' => 'lhr.tree',        'label' => 'All Courses & Levels', 'route' => 'addons/lms-hierarchy',             'icon' => '📚', 'permission' => 'lms_hierarchy.create'],
                ['key' => 'lhr.builder',     'label' => 'Add / Edit Course',    'route' => 'addons/lms-hierarchy/builder',     'icon' => '➕', 'permission' => 'lms_hierarchy.create'],
                ['key' => 'lhr.pricing',     'label' => 'Course Fee Plans',     'route' => 'addons/lms-hierarchy/pricing',     'icon' => '💳', 'permission' => 'lms_hierarchy.pricing'],
                ['key' => 'lhr.enrollments', 'label' => 'Student Enrollments',  'route' => 'addons/lms-hierarchy/enrollments', 'icon' => '🎓', 'permission' => 'lms_hierarchy.edit'],
                ['key' => 'lhr.catalog',     'label' => 'My Courses',           'route' => 'courses/catalog',                  'icon' => '📖', 'permission' => 'lms_hierarchy.view'],
            ],
            'lms_assessments' => [
                ['key' => 'ass.portal_exams', 'label' => 'Practice & Timed Exams',     'route' => 'assessments/portal',                   'icon' => '🎯', 'permission' => 'lms_assessments.take_exam'],
                ['key' => 'ass.portal_ws',    'label' => 'My PDF Worksheets',          'route' => 'assessments/portal/worksheets',        'icon' => '📄', 'permission' => 'lms_assessments.take_exam'],
                ['key' => 'ass.portal_hist',  'label' => 'My Scores & Certificates',   'route' => 'assessments/portal/history',           'icon' => '🏆', 'permission' => 'lms_assessments.take_exam'],
                ['key' => 'ass.qbank',        'label' => 'Question Bank [Admin/Tchr]', 'route' => 'addons/lms-assessments/question-bank', 'icon' => '🗃', 'permission' => 'lms_assessments.create'],
                ['key' => 'ass.exams',        'label' => 'Exam Builder [Admin/Tchr]',  'route' => 'addons/lms-assessments/exams',         'icon' => '📝', 'permission' => 'lms_assessments.create'],
                ['key' => 'ass.worksheets',   'label' => 'Upload PDF Worksheets',      'route' => 'addons/lms-assessments/worksheets',    'icon' => '📤', 'permission' => 'lms_assessments.create'],
                ['key' => 'ass.analytics',    'label' => 'Student Scores & Certs',     'route' => 'addons/lms-assessments/analytics',     'icon' => '📊', 'permission' => 'lms_assessments.view'],
            ],
            'lms_communication' => [
                ['key' => 'comm.chat',     'label' => '1-to-1 Classroom Chat',       'route' => 'chat/portal',                          'icon' => '💬', 'permission' => 'lms_communication.chat'],
                ['key' => 'comm.feedback', 'label' => 'Teacher Feedback Cards',      'route' => 'chat/feedback',                        'icon' => '⭐', 'permission' => 'lms_communication.feedback'],
                ['key' => 'comm.moderate', 'label' => 'Chat Moderation [Admin]',     'route' => 'addons/lms-communication/moderation',  'icon' => '🛡', 'permission' => 'lms_communication.moderate'],
                ['key' => 'comm.filters',  'label' => 'Safety Filter Rules [Admin]', 'route' => 'addons/lms-communication/dlp-filters', 'icon' => '🔒', 'permission' => 'lms_communication.filters'],
                ['key' => 'comm.alerts',   'label' => 'Email & WhatsApp Logs',       'route' => 'addons/lms-communication/alerts',      'icon' => '🔔', 'permission' => 'lms_communication.alerts'],
            ],
            'lms_timetable' => [
                ['key' => 'tt.overview',    'label' => 'Timetable Overview [Admin]', 'route' => 'addons/lms-timetable',             'icon' => '📊', 'permission' => 'lms_timetable.create'],
                ['key' => 'tt.cohorts',     'label' => 'Live Classroom Batches',     'route' => 'addons/lms-timetable/cohorts',     'icon' => '🏫', 'permission' => 'lms_timetable.create'],
                ['key' => 'tt.builder',     'label' => 'Master Schedule & Makeup',   'route' => 'addons/lms-timetable/sessions',    'icon' => '📅', 'permission' => 'lms_timetable.create'],
                ['key' => 'tt.substitutes', 'label' => 'Substitute Inbox [Admin]',   'route' => 'addons/lms-timetable/substitutes', 'icon' => '⇄',  'permission' => 'lms_timetable.edit'],
                ['key' => 'tt.portal',      'label' => 'My Live Classes',            'route' => 'timetable/portal',                 'icon' => '🎥', 'permission' => 'lms_timetable.view'],
            ],
            'lms_finance' => [
                ['key' => 'fin.portal',         'label' => 'My Fee & Receipts',           'route' => 'finance/portal',                    'icon' => '💳', 'permission' => 'lms_finance.view'],
                ['key' => 'fin.teacher_slips',  'label' => 'My Salary Slips',             'route' => 'finance/my-payouts',                'icon' => '💵', 'permission' => 'lms_finance.teacher_view'],
                ['key' => 'fin.overview',       'label' => 'Finance Overview [Admin]',    'route' => 'addons/lms-finance',                'icon' => '📊', 'permission' => 'lms_finance.collect'],
                ['key' => 'fin.invoices',       'label' => 'Subscriptions & Approvals',   'route' => 'addons/lms-finance/invoices',       'icon' => '✅', 'permission' => 'lms_finance.collect'],
                ['key' => 'fin.custom_billing', 'label' => 'Custom Parent Billing',       'route' => 'addons/lms-finance/custom-billing', 'icon' => '🧾', 'permission' => 'lms_finance.collect'],
                ['key' => 'fin.payroll',        'label' => 'Teacher Salary Entry',        'route' => 'addons/lms-finance/payroll',        'icon' => '💸', 'permission' => 'lms_finance.payroll'],
                ['key' => 'fin.expenses',       'label' => 'Operational Expenses',        'route' => 'addons/lms-finance/expenses',       'icon' => '📉', 'permission' => 'lms_finance.expenses'],
                ['key' => 'fin.gateways',       'label' => 'Razorpay & Receipt Designer', 'route' => 'addons/lms-finance/gateways',       'icon' => '⚙',  'permission' => 'lms_finance.expenses'],
            ],
            'abacus_pro_suite' => [
                ['key' => 'aps.arena',     'label' => '3D Soroban Abacus Lab',     'route' => 'abacus/arena',                       'icon' => '🧮', 'permission' => 'abacus_pro_suite.practice'],
                ['key' => 'aps.anzan',     'label' => 'Flash Anzan & Audio Drill', 'route' => 'abacus/anzan',                       'icon' => '⚡', 'permission' => 'abacus_pro_suite.practice'],
                ['key' => 'aps.progress',  'label' => 'My Speed & Badges',         'route' => 'abacus/my-progress',                 'icon' => '🏆', 'permission' => 'abacus_pro_suite.practice'],
                ['key' => 'aps.analytics', 'label' => 'Student Practice Analytics','route' => 'addons/abacus-pro-suite/analytics',  'icon' => '📊', 'permission' => 'abacus_pro_suite.view'],
                ['key' => 'aps.settings',  'label' => 'Abacus Theme & Settings',   'route' => 'addons/abacus-pro-suite/settings',   'icon' => '⚙',  'permission' => 'abacus_pro_suite.edit'],
            ],
            'lms_onboarding' => [
                ['key' => 'onb.pipeline',  'label' => 'Demo Leads & Conversion', 'route' => 'addons/lms-onboarding',           'icon' => '🎯', 'permission' => 'lms_onboarding.view'],
                ['key' => 'onb.scheduler', 'label' => 'Demo Class Scheduler',    'route' => 'addons/lms-onboarding/scheduler', 'icon' => '📅', 'permission' => 'demos.manage'],
                ['key' => 'onb.my_demos',  'label' => 'My Assigned Demo Classes','route' => 'addons/lms-onboarding/my-demos',  'icon' => '👩‍🏫', 'permission' => 'lms_onboarding.teacher_demos'],
            ],
        ];

        foreach ($activeAddons as $act) {
            $slug  = trim((string) ($act['slug'] ?? ''));
            $route = trim((string) ($act['admin_route'] ?? ($act['frontend_route'] ?? '')));
            if ($slug === '' || $route === '') {
                continue;
            }

            $manifestPath = WRITEPATH . 'addons/extracted/' . $slug . '/addon.json';
            $manifest     = is_file($manifestPath)
                ? (json_decode(ltrim((string) file_get_contents($manifestPath), "\xEF\xBB\xBF"), true) ?: [])
                : [];

            $permKeys = [];
            if ($slug === 'page_builder') {
                $permKeys = ['pages.view', 'pages.create', 'pages.edit', 'pages.delete'];
            } else {
                foreach (($manifest['permissions'] ?? []) as $p) {
                    if (! empty($p['key'])) {
                        $permKeys[] = trim((string) $p['key']);
                    }
                }
            }
            if (empty($permKeys)) {
                $permKeys = ['addons.manage'];
            }

            $subViews = $manifest['sub_views'] ?? ($manifest['sidebar_links'] ?? ($builtinSubViews[$slug] ?? []));

            $tree['addon_' . $slug] = [
                'slug'          => $slug,
                'default_title' => $manifest['name'] ?? ($act['name'] ?? ucfirst($slug)),
                'icon'          => $manifest['icon'] ?? '⚡',
                'route'         => $route,
                'section'       => 'LEARNING & ACADEMY MODULES',
                'permissions'   => $permKeys,
                'sub_views'     => is_array($subViews) ? $subViews : [],
            ];
        }

        return $tree;
    }

    public function syncCoreAndAddonPermissions(): void
    {
        $now = date('Y-m-d H:i:s');

        $validPermissions = [
            ['perm_key' => 'dashboard.view', 'perm_label' => 'Access Dashboard Overview [All Roles]',                 'module_group' => 'Core Website & Settings', 'source_type' => 'core'],
            ['perm_key' => 'settings.view',  'perm_label' => 'View Global Website Settings [Super Admin + Admin]',    'module_group' => 'Core Website & Settings', 'source_type' => 'core'],
            ['perm_key' => 'settings.edit',  'perm_label' => 'Modify Global Website Settings [Super Admin + Admin]',  'module_group' => 'Core Website & Settings', 'source_type' => 'core'],
            ['perm_key' => 'addons.manage',  'perm_label' => 'Install, Enable & Delete Add-ons [Super Admin Only]',   'module_group' => 'Core Website & Settings', 'source_type' => 'core'],

            ['perm_key' => 'users.view',     'perm_label' => 'View Users Directory [Super Admin + Admin]',            'module_group' => 'Users & Academic Roles',  'source_type' => 'core'],
            ['perm_key' => 'users.create',   'perm_label' => 'Create New User Accounts [Super Admin + Admin]',        'module_group' => 'Users & Academic Roles',  'source_type' => 'core'],
            ['perm_key' => 'users.edit',     'perm_label' => 'Edit Users & Assign Roles [Super Admin + Admin]',       'module_group' => 'Users & Academic Roles',  'source_type' => 'core'],
            ['perm_key' => 'users.delete',   'perm_label' => 'Delete User Accounts (Cascade) [Super Admin + Admin]',  'module_group' => 'Users & Academic Roles',  'source_type' => 'core'],
        ];

        $activeAddons = class_exists(AddonModel::class) ? (new AddonModel())->getActiveAddons() : [];

        foreach ($activeAddons as $actAddon) {
            $slug = trim((string) ($actAddon['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            if ($slug === 'page_builder') {
                $validPermissions[] = ['perm_key' => 'pages.view',   'perm_label' => 'View CMS Pages & Visual Builder [Super Admin + Admin]',  'module_group' => 'Add-on: Visual Page Builder & CMS', 'source_type' => 'addon'];
                $validPermissions[] = ['perm_key' => 'pages.create', 'perm_label' => 'Create New Website Pages [Super Admin + Admin]',         'module_group' => 'Add-on: Visual Page Builder & CMS', 'source_type' => 'addon'];
                $validPermissions[] = ['perm_key' => 'pages.edit',   'perm_label' => 'Edit Page Blocks & Footer Layout [Super Admin + Admin]', 'module_group' => 'Add-on: Visual Page Builder & CMS', 'source_type' => 'addon'];
                $validPermissions[] = ['perm_key' => 'pages.delete', 'perm_label' => 'Delete Website Pages [Super Admin + Admin]',             'module_group' => 'Add-on: Visual Page Builder & CMS', 'source_type' => 'addon'];
                continue;
            }

            $manifestPath = WRITEPATH . 'addons/extracted/' . $slug . '/addon.json';
            if (is_file($manifestPath)) {
                $manifest = json_decode(ltrim((string) file_get_contents($manifestPath), "\xEF\xBB\xBF"), true);
                if (is_array($manifest) && ! empty($manifest['permissions']) && is_array($manifest['permissions'])) {
                    $groupName = 'Add-on: ' . ($manifest['name'] ?? ($actAddon['name'] ?? ucfirst($slug)));
                    foreach ($manifest['permissions'] as $pItem) {
                        if (! empty($pItem['key'])) {
                            $validPermissions[] = [
                                'perm_key'     => trim((string) $pItem['key']),
                                'perm_label'   => trim((string) ($pItem['label'] ?? $pItem['key'])),
                                'module_group' => $groupName,
                                'source_type'  => 'addon',
                            ];
                        }
                    }
                }
            }
        }

        $validKeysList = [];
        foreach ($validPermissions as $vp) {
            $validKeysList[] = $vp['perm_key'];
            $this->db->query("
                INSERT INTO `cms_rbac_features` (`perm_key`, `perm_label`, `module_group`, `source_type`, `created_at`)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `perm_label`   = VALUES(`perm_label`),
                    `module_group` = VALUES(`module_group`),
                    `source_type`  = VALUES(`source_type`)
            ", [$vp['perm_key'], $vp['perm_label'], $vp['module_group'], $vp['source_type'], $now]);
        }

        if (! empty($validKeysList)) {
            $this->db->table($this->featuresTable)
                ->whereIn('source_type', ['core', 'addon'])
                ->whereNotIn('perm_key', array_unique($validKeysList))
                ->delete();
        }
    }

    /**
     * 1-Click Friendly Role Presets:
     * Configures Permissions + Child-Friendly / Parent-Friendly / Teacher-Friendly Sidebar Labels!
     */
    public function applySmartRolePresets(): array
    {
        $now = date('Y-m-d H:i:s');
        $presets = [
            'admin' => [
                'perms' => [
                    'dashboard.view', 'users.view', 'users.create', 'users.edit', 'settings.view', 'settings.edit',
                    'pages.view', 'pages.create', 'pages.edit', 'pages.delete',
                    'core_lms_auth.view', 'core_lms_auth.create', 'core_lms_auth.edit', 'core_lms_auth.delete',
                    'lms_hierarchy.view', 'lms_hierarchy.create', 'lms_hierarchy.pricing', 'lms_hierarchy.edit',
                    'lms_assessments.view', 'lms_assessments.create', 'lms_assessments.edit', 'lms_assessments.take_exam',
                    'lms_communication.chat', 'lms_communication.feedback', 'lms_communication.moderate', 'lms_communication.filters', 'lms_communication.alerts',
                    'lms_timetable.view', 'lms_timetable.create', 'lms_timetable.edit',
                    'lms_finance.view', 'lms_finance.collect', 'lms_finance.payroll', 'lms_finance.expenses',
                    'abacus_pro_suite.view', 'abacus_pro_suite.edit', 'abacus_pro_suite.practice',
                    'lms_onboarding.view', 'demos.manage',
                ],
                'ws' => [
                    'show_child_switcher' => 0,
                    'landing_route'       => 'dashboard',
                    'custom_labels'       => [
                        'addon_core_lms_auth'     => 'Family & Role Setup',
                        'addon_lms_hierarchy'     => 'Courses & Fee Plans',
                        'addon_lms_timetable'     => 'Live Batches & Timetable',
                        'addon_lms_assessments'   => 'Exams & Question Bank',
                        'addon_lms_finance'       => 'Finance, Invoices & Payroll',
                        'addon_lms_communication' => 'Chat Moderation & Feedback',
                        'addon_abacus_pro_suite'  => '3D Abacus & Analytics',
                        'addon_lms_onboarding'    => 'Demo Leads & Admissions',
                    ],
                    'dashboard_widgets' => ['kpi_overview', 'quick_modules', 'mod_flagged_chat'],
                ],
            ],
            'teacher' => [
                'perms' => [
                    'dashboard.view',
                    'lms_hierarchy.view',
                    'lms_assessments.view', 'lms_assessments.create',
                    'lms_communication.chat', 'lms_communication.feedback',
                    'lms_timetable.view', 'lms_timetable.teacher_panel',
                    'lms_finance.teacher_view',
                    'abacus_pro_suite.view', 'abacus_pro_suite.practice',
                    'lms_onboarding.teacher_demos',
                ],
                'ws' => [
                    'show_child_switcher' => 0,
                    'landing_route'       => 'timetable/portal',
                    'custom_labels'       => [
                        'addon_lms_timetable'     => 'My Live Classes & Batches',
                        'addon_lms_hierarchy'     => 'My Assigned Courses',
                        'addon_lms_assessments'   => 'Question Bank & Scores',
                        'addon_lms_communication' => 'Student Chat & Feedback',
                        'addon_lms_finance'       => 'My Salary Slips',
                        'addon_abacus_pro_suite'  => '3D Abacus & Batch Progress',
                        'addon_lms_onboarding'    => 'My Demo Classes',
                    ],
                    'dashboard_widgets' => ['teacher_schedule', 'quick_modules'],
                ],
            ],
            'parent' => [
                'perms' => [
                    'dashboard.view',
                    'core_lms_auth.view',
                    'lms_hierarchy.view',
                    'lms_assessments.take_exam',
                    'lms_communication.chat', 'lms_communication.feedback',
                    'lms_timetable.view',
                    'lms_finance.view',
                ],
                'ws' => [
                    'show_child_switcher' => 1,
                    'landing_route'       => 'portal/family',
                    'custom_labels'       => [
                        'addon_core_lms_auth'     => 'My Children & Profile',
                        'addon_lms_hierarchy'     => 'Enrolled Courses',
                        'addon_lms_timetable'     => 'Kids Class Schedule',
                        'addon_lms_assessments'   => 'Worksheets & Exam Reports',
                        'addon_lms_finance'       => 'Fee Payment & Receipts',
                        'addon_lms_communication' => 'Teacher Chat & Feedback',
                    ],
                    'hidden_items' => [
                        'ass.portal_exams' => 1,
                    ],
                    'dashboard_widgets' => ['parent_children', 'quick_modules'],
                ],
            ],
            'student' => [
                'perms' => [
                    'dashboard.view',
                    'core_lms_auth.view',
                    'lms_hierarchy.view',
                    'lms_assessments.take_exam',
                    'lms_communication.chat', 'lms_communication.feedback',
                    'lms_timetable.view',
                    'abacus_pro_suite.practice',
                ],
                'ws' => [
                    'show_child_switcher' => 0,
                    'landing_route'       => 'dashboard',
                    'custom_labels'       => [
                        'addon_core_lms_auth'     => 'My Learning Hub',
                        'addon_lms_timetable'     => 'My Live Classes',
                        'addon_abacus_pro_suite'  => '3D Abacus & Speed Drills',
                        'addon_lms_assessments'   => 'Practice Exams & Worksheets',
                        'addon_lms_hierarchy'     => 'My Courses',
                        'addon_lms_communication' => 'Ask My Teacher & Feedback',
                    ],
                    'hidden_items' => [
                        'cla.add_child' => 1,
                    ],
                    'dashboard_widgets' => ['student_arena', 'quick_modules'],
                ],
            ],
        ];

        $updatedCount = 0;
        foreach ($presets as $rSlug => $cfg) {
            $roleRow = $this->db->table($this->table)->where('role_slug', $rSlug)->get()->getRowArray();
            if (! $roleRow) {
                continue;
            }
            $currWs   = json_decode((string) ($roleRow['workspace_json'] ?? '{}'), true) ?: [];
            $mergedWs = array_merge($currWs, $cfg['ws']);

            $this->db->table($this->table)->where('id', $roleRow['id'])->update([
                'permissions_json' => json_encode($cfg['perms']),
                'workspace_json'   => json_encode($mergedWs, JSON_UNESCAPED_UNICODE),
                'updated_at'       => $now,
            ]);
            $updatedCount++;
        }

        $this->rebuildStaticCache();
        return [
            'ok'      => true,
            'message' => 'Applied Child/Parent/Teacher-Friendly Menu Names, Permissions & Dashboard Widgets across ' . $updatedCount . ' roles!',
        ];
    }

    public function rebuildStaticCache(): array
    {
        $this->syncCoreAndAddonPermissions();

        $activePermKeys = array_flip(array_column($this->getAllFeatures(), 'perm_key'));
        $navCatalog     = $this->discoverActiveNavigationTree();

        $cachePayload = [
            'updated_at'   => time(),
            'default_role' => 'student',
            'nav_catalog'  => $navCatalog,
            'roles'        => [],
        ];

        try {
            $roles = $this->db->table($this->table)->orderBy('id', 'ASC')->get()->getResultArray();
            foreach ($roles as $r) {
                $slug  = (string) $r['role_slug'];
                $perms = json_decode((string) ($r['permissions_json'] ?? '[]'), true);
                if (! is_array($perms)) {
                    $perms = [];
                }

                $wsConfig = json_decode((string) ($r['workspace_json'] ?? '{}'), true);
                if (! is_array($wsConfig)) {
                    $wsConfig = [];
                }

                $isSuper = ($slug === 'super_admin' || in_array('*', $perms, true));
                if ($isSuper) {
                    $effectivePerms = ['*'];
                } else {
                    $effectivePerms = [];
                    foreach (array_unique($perms) as $pk) {
                        if (isset($activePermKeys[$pk])) {
                            $effectivePerms[] = $pk;
                        }
                    }
                }

                $customLabels = $wsConfig['custom_labels'] ?? [];
                $hiddenItems  = $wsConfig['hidden_items'] ?? [];
                $compiledNav  = [];

                foreach ($navCatalog as $itemKey => $navItem) {
                    if (! empty($hiddenItems[$itemKey])) {
                        continue;
                    }

                    $hasModuleAccess = $isSuper;
                    if (! $hasModuleAccess) {
                        foreach (($navItem['permissions'] ?? []) as $reqP) {
                            if (in_array($reqP, $effectivePerms, true)) {
                                $hasModuleAccess = true;
                                break;
                            }
                        }
                    }
                    if (! $hasModuleAccess) {
                        continue;
                    }

                    $visibleSubViews = [];
                    foreach (($navItem['sub_views'] ?? []) as $sv) {
                        $svKey  = $sv['key'] ?? '';
                        $svPerm = $sv['permission'] ?? '';
                        if ($svKey !== '' && ! empty($hiddenItems[$svKey])) {
                            continue;
                        }
                        if ($isSuper || $svPerm === '' || in_array($svPerm, $effectivePerms, true)) {
                            $sv['display_label'] = ! empty($customLabels[$svKey]) ? $customLabels[$svKey] : $sv['label'];
                            $visibleSubViews[]   = $sv;
                        }
                    }

                    $primaryRoute = $navItem['route'];
                    if (! empty($visibleSubViews) && ! $isSuper) {
                        $primaryRoute = $visibleSubViews[0]['route'];
                    }

                    $compiledNav[$itemKey] = [
                        'key'           => $itemKey,
                        'slug'          => $navItem['slug'],
                        'display_label' => ! empty($customLabels[$itemKey]) ? $customLabels[$itemKey] : $navItem['default_title'],
                        'icon'          => $navItem['icon'],
                        'route'         => $primaryRoute,
                        'section'       => $navItem['section'],
                        'sub_views'     => $visibleSubViews,
                    ];
                }

                if ((int) ($r['is_default_front'] ?? 0) === 1) {
                    $cachePayload['default_role'] = $slug;
                }

                $showSwitcher = isset($wsConfig['show_child_switcher'])
                    ? (int) $wsConfig['show_child_switcher']
                    : ($slug === 'parent' ? 1 : 0);

                $cachePayload['roles'][$slug] = [
                    'id'               => (int) $r['id'],
                    'role_slug'        => $slug,
                    'role_name'        => $r['role_name'],
                    'portal_type'      => $r['portal_type'] ?? 'frontend',
                    'is_default_front' => (int) ($r['is_default_front'] ?? 0),
                    'status'           => $r['status'] ?? 'active',
                    'permissions'      => array_values($effectivePerms),
                    'workspace'        => [
                        'show_child_switcher' => $showSwitcher,
                        'landing_route'       => $wsConfig['landing_route'] ?? 'dashboard',
                        'custom_labels'       => $customLabels,
                        'hidden_items'        => $hiddenItems,
                        'dashboard_widgets'   => $wsConfig['dashboard_widgets'] ?? ['kpi_overview', 'quick_modules'],
                    ],
                    'compiled_sidebar' => $compiledNav,
                ];
            }

            @is_dir(dirname($this->cacheFile)) || @mkdir(dirname($this->cacheFile), 0775, true);
            @file_put_contents($this->cacheFile, json_encode($cachePayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
        }

        return $cachePayload;
    }

    public function getAllRoles(): array
    {
        $userCounts = [];
        if ($this->db->tableExists('users')) {
            $cntRows = $this->db->table('users')
                ->select('role_slug, COUNT(*) AS cnt')
                ->groupBy('role_slug')
                ->get()
                ->getResultArray();
            foreach ($cntRows as $cr) {
                $s = strtolower(trim((string) ($cr['role_slug'] ?? 'student')));
                $userCounts[$s] = (int) $cr['cnt'];
            }
        }

        $cache = is_file($this->cacheFile) ? (json_decode((string) @file_get_contents($this->cacheFile), true) ?: []) : [];
        $rows  = $this->db->table($this->table)->orderBy('id', 'ASC')->get()->getResultArray();

        foreach ($rows as &$r) {
            $slug    = (string) $r['role_slug'];
            $decoded = json_decode((string) ($r['permissions_json'] ?? '[]'), true);
            $r['permissions_array'] = is_array($decoded) ? $decoded : [];
            $ws = json_decode((string) ($r['workspace_json'] ?? '{}'), true);
            $r['workspace_array']   = is_array($ws) ? $ws : [];
            $r['users_count']       = $userCounts[$slug] ?? 0;
            $r['compiled_sidebar']  = $cache['roles'][$slug]['compiled_sidebar'] ?? [];
        }
        return $rows;
    }

    public function getAllFeatures(): array
    {
        return $this->db->table($this->featuresTable)
            ->orderBy('module_group', 'ASC')
            ->orderBy('perm_key', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getGroupedFeatures(): array
    {
        $this->syncCoreAndAddonPermissions();
        $rows   = $this->getAllFeatures();
        $groups = [];
        foreach ($rows as $r) {
            $g = $r['module_group'] ?: 'General';
            $groups[$g][] = $r;
        }
        return $groups;
    }

    public function saveRoleData(array $post): array
    {
        $id          = (int) ($post['id'] ?? 0);
        $roleName    = trim((string) ($post['role_name'] ?? ''));
        $rawSlug     = strtolower(trim((string) ($post['role_slug'] ?? '')));
        $portalType  = in_array($post['portal_type'] ?? '', ['backend', 'frontend'], true) ? $post['portal_type'] : 'frontend';
        $description = trim((string) ($post['description'] ?? ''));
        $isDefFront  = ! empty($post['is_default_front']) ? 1 : 0;

        if ($roleName === '') {
            return ['ok' => false, 'message' => 'Role Name is required.'];
        }

        if ($rawSlug === '') {
            $rawSlug = preg_replace('/[^a-z0-9_]+/', '_', strtolower($roleName));
        } else {
            $rawSlug = preg_replace('/[^a-z0-9_]+/', '_', $rawSlug);
        }
        $rawSlug = trim((string) $rawSlug, '_');

        $now = date('Y-m-d H:i:s');

        if ($isDefFront === 1) {
            $this->db->table($this->table)->update(['is_default_front' => 0]);
        }

        if ($id > 0) {
            $existing = $this->find($id);
            if (! $existing) {
                return ['ok' => false, 'message' => 'Role not found.'];
            }
            $upd = [
                'role_name'        => $roleName,
                'portal_type'      => $portalType,
                'description'      => $description,
                'is_default_front' => $isDefFront,
                'updated_at'       => $now,
            ];
            if ((int) ($existing['is_system'] ?? 0) === 0) {
                $upd['role_slug'] = $rawSlug;
            }
            $this->db->table($this->table)->where('id', $id)->update($upd);
            $this->syncRoleIntoCoreRolesTable($rawSlug, $roleName, $description);
            $this->rebuildStaticCache();
            return ['ok' => true, 'role_slug' => $rawSlug, 'message' => 'Role "' . $roleName . '" updated successfully.'];
        }

        $dup = $this->db->table($this->table)->where('role_slug', $rawSlug)->get()->getRowArray();
        if ($dup) {
            return ['ok' => false, 'message' => 'Role slug "' . $rawSlug . '" already exists.'];
        }

        $defaultPerms = ['dashboard.view'];
        $this->db->table($this->table)->insert([
            'role_slug'        => $rawSlug,
            'role_name'        => $roleName,
            'portal_type'      => $portalType,
            'description'      => $description,
            'permissions_json' => json_encode($defaultPerms),
            'workspace_json'   => json_encode(['show_child_switcher' => 0]),
            'is_system'        => 0,
            'is_default_front' => $isDefFront,
            'status'           => 'active',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $this->syncRoleIntoCoreRolesTable($rawSlug, $roleName, $description);
        $this->rebuildStaticCache();
        return ['ok' => true, 'role_slug' => $rawSlug, 'message' => 'Custom Role "' . $roleName . '" created!'];
    }

    private function syncRoleIntoCoreRolesTable(string $slug, string $name, string $desc): void
    {
        try {
            if (! $this->db->tableExists('roles')) {
                return;
            }
            $ex = $this->db->table('roles')->where('role_slug', $slug)->get()->getRowArray();
            if ($ex) {
                $this->db->table('roles')->where('id', $ex['id'])->update([
                    'role_name'   => $name,
                    'description' => $desc,
                ]);
            } else {
                $this->db->table('roles')->insert([
                    'role_slug'   => $slug,
                    'role_name'   => $name,
                    'description' => $desc,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (Throwable $e) {
        }
    }

    public function updateRolePermissions(int $roleId, array $selectedPermissions): array
    {
        $role = $this->find($roleId);
        if (! $role) {
            return ['ok' => false, 'message' => 'Role not found.'];
        }

        if ($role['role_slug'] === 'super_admin') {
            $cleanPerms = ['*'];
        } else {
            $cleanPerms = array_values(array_unique(array_filter(array_map('trim', $selectedPermissions))));
        }

        $this->db->table($this->table)->where('id', $roleId)->update([
            'permissions_json' => json_encode($cleanPerms),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->rebuildStaticCache();
        return ['ok' => true, 'message' => 'Permissions updated for "' . $role['role_name'] . '" (0ms Cache Rebuilt).'];
    }

    public function updateRoleWorkspaceConfig(int $roleId, array $post): array
    {
        $role = $this->find($roleId);
        if (! $role) {
            return ['ok' => false, 'message' => 'Role not found.'];
        }

        $customLabels = [];
        if (! empty($post['custom_labels']) && is_array($post['custom_labels'])) {
            foreach ($post['custom_labels'] as $k => $v) {
                $val = trim((string) $v);
                if ($val !== '') {
                    $customLabels[trim((string) $k)] = $val;
                }
            }
        }

        $hiddenItems = [];
        if (! empty($post['hidden_items']) && is_array($post['hidden_items'])) {
            foreach ($post['hidden_items'] as $hk) {
                $hiddenItems[trim((string) $hk)] = 1;
            }
        }

        $widgets = $post['dashboard_widgets'] ?? [];
        if (! is_array($widgets)) {
            $widgets = [];
        }

        $wsPayload = [
            'show_child_switcher' => ! empty($post['show_child_switcher']) ? 1 : 0,
            'landing_route'       => trim((string) ($post['landing_route'] ?? 'dashboard')) ?: 'dashboard',
            'custom_labels'       => $customLabels,
            'hidden_items'        => $hiddenItems,
            'dashboard_widgets'   => array_values($widgets),
        ];

        $this->db->table($this->table)->where('id', $roleId)->update([
            'workspace_json' => json_encode($wsPayload, JSON_UNESCAPED_UNICODE),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->rebuildStaticCache();
        return ['ok' => true, 'message' => 'Sidebar Menu Names, Sub-Menus & Widgets saved for "' . $role['role_name'] . '"!'];
    }

    public function cloneRoleConfiguration(int $targetRoleId, string $sourceRoleSlug): array
    {
        $target = $this->find($targetRoleId);
        $source = $this->db->table($this->table)->where('role_slug', $sourceRoleSlug)->get()->getRowArray();

        if (! $target || ! $source) {
            return ['ok' => false, 'message' => 'Source or target role not found.'];
        }
        if ($target['role_slug'] === 'super_admin') {
            return ['ok' => false, 'message' => 'Super Admin cannot be overwritten.'];
        }

        $this->db->table($this->table)->where('id', $targetRoleId)->update([
            'permissions_json' => $source['permissions_json'],
            'workspace_json'   => $source['workspace_json'],
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->rebuildStaticCache();
        return ['ok' => true, 'message' => 'Cloned permissions & sidebar layout from "' . $source['role_name'] . '" into "' . $target['role_name'] . '"!'];
    }

    public function deleteCustomRole(int $id): array
    {
        $role = $this->find($id);
        if (! $role) {
            return ['ok' => false, 'message' => 'Role not found.'];
        }
        if ((int) ($role['is_system'] ?? 0) === 1) {
            return ['ok' => false, 'message' => 'System roles cannot be deleted.'];
        }

        $this->db->table($this->table)->where('id', $id)->delete();
        $this->rebuildStaticCache();
        return ['ok' => true, 'message' => 'Custom role deleted.'];
    }

    public function addCustomFeature(string $key, string $label, string $group): array
    {
        $cleanKey = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9._-]+/', '', $key)));
        $label    = trim($label);
        $group    = trim($group) ?: 'Custom Website Features';

        if ($cleanKey === '' || $label === '') {
            return ['ok' => false, 'message' => 'Both Permission Key and Label are required.'];
        }

        $dup = $this->db->table($this->featuresTable)->where('perm_key', $cleanKey)->get()->getRowArray();
        if ($dup) {
            $this->db->table($this->featuresTable)->where('id', $dup['id'])->update([
                'perm_label'   => $label,
                'module_group' => $group,
            ]);
            return ['ok' => true, 'message' => 'Permission key "' . $cleanKey . '" updated.'];
        }

        $this->db->table($this->featuresTable)->insert([
            'perm_key'     => $cleanKey,
            'perm_label'   => $label,
            'module_group' => $group,
            'source_type'  => 'custom',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->rebuildStaticCache();
        return ['ok' => true, 'message' => 'Custom permission "' . $cleanKey . '" added.'];
    }

    public function deleteCustomFeature(int $id): bool
    {
        $row = $this->db->table($this->featuresTable)->where('id', $id)->get()->getRowArray();
        if (! $row || ($row['source_type'] ?? '') === 'core') {
            return false;
        }
        $this->db->table($this->featuresTable)->where('id', $id)->delete();
        $this->rebuildStaticCache();
        return true;
    }
}

<?php

namespace App\Models\Addons\CoreLmsAuth;

use CodeIgniter\Model;
use DateTime;
use DateTimeZone;
use Throwable;

class CoreLmsAuthModel extends Model
{
    protected $table            = 'cms_user_meta';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id',
        'leaf_node_id',
        'child_age',
        'grade_level',
        'country_code',
        'currency_code',
        'timezone',
        'avatar_color',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected string $cacheFile = WRITEPATH . 'addons/core_lms_auth_cache.json';
    protected static ?array $globalSettingsCache = null;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchemaInitialized(): void
    {
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        try {
            if (! $this->db->tableExists('cms_user_meta')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `cms_user_meta` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `user_id` INT UNSIGNED NOT NULL,
                        `leaf_node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `child_age` TINYINT UNSIGNED NOT NULL DEFAULT 8,
                        `grade_level` VARCHAR(160) NOT NULL DEFAULT 'Foundation Level 1',
                        `country_code` VARCHAR(10) NOT NULL DEFAULT 'IN',
                        `currency_code` VARCHAR(10) NOT NULL DEFAULT 'INR',
                        `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Kolkata',
                        `avatar_color` VARCHAR(20) NOT NULL DEFAULT '#10b981',
                        `notes` VARCHAR(255) NOT NULL DEFAULT '',
                        `created_at` DATETIME NULL,
                        `updated_at` DATETIME NULL,
                        UNIQUE KEY `uniq_user_meta_uid` (`user_id`),
                        KEY `idx_country_tz` (`country_code`, `timezone`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } else {
                $mCols = $this->db->getFieldNames('cms_user_meta');
                if (! in_array('leaf_node_id', $mCols, true)) {
                    $this->db->query("ALTER TABLE `cms_user_meta` ADD COLUMN `leaf_node_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `user_id`");
                }
            }

            if ($this->db->tableExists('users')) {
                $uCols = $this->db->getFieldNames('users');
                if (! in_array('role_slug', $uCols, true)) {
                    $this->db->query("ALTER TABLE `users` ADD COLUMN `role_slug` VARCHAR(64) NOT NULL DEFAULT 'student'");
                }
                if (! in_array('parent_user_id', $uCols, true)) {
                    $this->db->query("ALTER TABLE `users` ADD COLUMN `parent_user_id` INT UNSIGNED NULL DEFAULT NULL");
                }
            }

            $lockFile = WRITEPATH . 'addons/schema_addon_core_lms_auth_v70.lock';
            if (is_file($lockFile) && is_file($this->cacheFile)) {
                return;
            }

            $existingMap = $this->getSettingsByGroup('lms_role_map');
            if (empty($existingMap)) {
                $this->saveSettingRow('lms_role_map', 'super_admin', json_encode(['super_admin']));
                $this->saveSettingRow('lms_role_map', 'admin', json_encode(['admin']));
                $this->saveSettingRow('lms_role_map', 'teacher', json_encode(['teacher']));
                $this->saveSettingRow('lms_role_map', 'parent', json_encode(['parent']));
                $this->saveSettingRow('lms_role_map', 'student', json_encode(['student', 'website_user']));
            }

            $existingCountries = $this->getSettingsByGroup('lms_countries');
            if (empty($existingCountries)) {
                $defaultCountries = $this->getDefaultCountriesCatalog();
                $this->saveSettingRow('lms_countries', 'catalog_json', json_encode($defaultCountries, JSON_UNESCAPED_UNICODE));
                $this->saveSettingRow('lms_countries', 'system_storage_tz', 'UTC');
            }

            // Update addon display name in `addons` table so Sidebar shows friendly name
            if ($this->db->tableExists('addons')) {
                $this->db->table('addons')
                    ->where('slug', 'core_lms_auth')
                    ->update(['name' => 'Family Hub & Account Settings']);
            }

            $this->syncRolesAndForeignKeys();
            $this->syncRbacPermissionsAndAutoEnableRoles();
            $this->mirrorAssets(true);
            $this->rebuildStaticCache();

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, date('c'));
        } catch (Throwable $e) {
            log_message('error', 'CoreLmsAuthModel schema error: ' . $e->getMessage());
        }
    }

    public function syncRbacPermissionsAndAutoEnableRoles(): void
    {
        try {
            $now = date('Y-m-d H:i:s');
            if ($this->db->tableExists('cms_rbac_features')) {
                $perms = [
                    ['core_lms_auth.view',   'Access Family Hub, Add Child & Profile Settings [Parent + Student]'],
                    ['core_lms_auth.create', 'Link Students to Parents & Courses [Super Admin + Admin]'],
                    ['core_lms_auth.edit',   'Manage Family Directory, Role Mapper & Servicing Countries [Super Admin + Admin]'],
                    ['core_lms_auth.delete', 'Unlink or Remove Family Links [Super Admin + Admin]'],
                ];
                foreach ($perms as $p) {
                    $this->db->query("
                        INSERT INTO `cms_rbac_features` (`perm_key`, `perm_label`, `module_group`, `source_type`, `created_at`)
                        VALUES (?, ?, 'Add-on: Family Hub & Account Settings', 'addon', ?)
                        ON DUPLICATE KEY UPDATE `perm_label` = VALUES(`perm_label`), `module_group` = VALUES(`module_group`)
                    ", [$p[0], $p[1], $now]);
                }
            }

            if ($this->db->tableExists('cms_roles')) {
                $roleAutoGrants = [
                    'parent'  => ['core_lms_auth.view'],
                    'student' => ['core_lms_auth.view'],
                    'admin'   => ['core_lms_auth.view', 'core_lms_auth.create', 'core_lms_auth.edit', 'core_lms_auth.delete'],
                ];

                $roles = $this->db->table('cms_roles')->get()->getResultArray();
                foreach ($roles as $r) {
                    $rSlug = strtolower(trim((string) ($r['role_slug'] ?? '')));
                    if (! isset($roleAutoGrants[$rSlug])) {
                        continue;
                    }
                    $currPerms = json_decode((string) ($r['permissions_json'] ?? '[]'), true);
                    if (! is_array($currPerms)) {
                        $currPerms = [];
                    }
                    $changed = false;
                    foreach ($roleAutoGrants[$rSlug] as $grantKey) {
                        if (! in_array($grantKey, $currPerms, true) && ! in_array('*', $currPerms, true)) {
                            $currPerms[] = $grantKey;
                            $changed     = true;
                        }
                    }

                    $wsConfig = json_decode((string) ($r['workspace_json'] ?? '{}'), true);
                    if (! is_array($wsConfig)) {
                        $wsConfig = [];
                    }
                    if ($rSlug === 'parent' && ! isset($wsConfig['show_child_switcher'])) {
                        $wsConfig['show_child_switcher'] = 1;
                        $changed = true;
                    }

                    if ($changed) {
                        $this->db->table('cms_roles')->where('id', $r['id'])->update([
                            'permissions_json' => json_encode(array_values(array_unique($currPerms))),
                            'workspace_json'   => json_encode($wsConfig, JSON_UNESCAPED_UNICODE),
                            'updated_at'       => $now,
                        ]);
                    }
                }
            }

            $rbacModelFile = WRITEPATH . 'addons/extracted/rbac/Models/Addons/Rbac/RbacModel.php';
            if (is_file($rbacModelFile)) {
                require_once $rbacModelFile;
                if (class_exists('\App\Models\Addons\Rbac\RbacModel')) {
                    (new \App\Models\Addons\Rbac\RbacModel())->rebuildStaticCache();
                }
            }
        } catch (Throwable $e) {
        }
    }

    public function mirrorAssets(bool $forceTouch = false): void
    {
        $srcDir = WRITEPATH . 'addons/extracted/core_lms_auth/assets';
        $dstDir = FCPATH . 'assets/addons/core_lms_auth';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['core_lms_auth.css', 'core_lms_auth.js'] as $f) {
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
        self::$globalSettingsCache = [
            'site_title'              => 'Portal',
            'active_theme_mode'       => 'dark',
            'system_environment_mode' => 'testing',
        ];
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

    public function isProductionMode(): bool
    {
        if (function_exists('cms_is_sandbox_mode')) {
            return ! cms_is_sandbox_mode();
        }
        $s = $this->getGlobalSiteSettings();
        return strtolower(trim((string) ($s['system_environment_mode'] ?? 'testing'))) === 'production';
    }

    public function getCompanionAddonsStatus(): array
    {
        $active = function_exists('cms_get_active_addon_slugs') ? cms_get_active_addon_slugs() : [];
        if (empty($active) && class_exists('\App\Models\Addons\AddonModel')) {
            foreach ((new \App\Models\Addons\AddonModel())->getActiveAddons() as $r) {
                $active[trim((string) ($r['slug'] ?? ''))] = true;
            }
        }
        return [
            'lms_hierarchy'     => isset($active['lms_hierarchy']),
            'lms_finance'       => isset($active['lms_finance']),
            'lms_assessments'   => isset($active['lms_assessments']),
            'lms_timetable'     => isset($active['lms_timetable']),
            'lms_communication' => isset($active['lms_communication']),
            'abacus_pro_suite'  => isset($active['abacus_pro_suite']),
            'rbac'              => isset($active['rbac']),
        ];
    }

    public function getDynamicLeafBatchesMap(): array
    {
        $batches = [];
        if (! $this->db->tableExists('lms_hierarchy_nodes')) {
            return $batches;
        }

        try {
            $allNodes = [];
            foreach ($this->db->table('lms_hierarchy_nodes')->where('status', 'active')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray() as $n) {
                $allNodes[(int) $n['id']] = $n;
            }

            foreach ($allNodes as $nid => $nRow) {
                if ((int) ($nRow['is_leaf_node'] ?? 0) !== 1) {
                    continue;
                }
                $pathParts = [$nRow['title']];
                $currPid   = (int) ($nRow['parent_id'] ?? 0);
                $guard     = 0;
                while ($currPid > 0 && isset($allNodes[$currPid]) && $guard < 12) {
                    array_unshift($pathParts, $allNodes[$currPid]['title']);
                    $currPid = (int) ($allNodes[$currPid]['parent_id'] ?? 0);
                    $guard++;
                }
                $batches[$nid] = [
                    'id'              => $nid,
                    'title'           => $nRow['title'],
                    'breadcrumb_path' => implode(' › ', $pathParts),
                ];
            }
        } catch (Throwable $e) {
        }

        return $batches;
    }

    public function getDefaultCountriesCatalog(): array
    {
        return [
            'IN' => ['code' => 'IN', 'name' => 'India', 'flag' => '🇮🇳', 'currency_code' => 'INR', 'currency_sym' => '₹', 'dial_code' => '+91', 'default_tz' => 'Asia/Kolkata', 'timezones' => ['Asia/Kolkata'], 'is_enabled' => 1],
            'AE' => ['code' => 'AE', 'name' => 'United Arab Emirates (UAE)', 'flag' => '🇦🇪', 'currency_code' => 'AED', 'currency_sym' => 'AED ', 'dial_code' => '+971', 'default_tz' => 'Asia/Dubai', 'timezones' => ['Asia/Dubai'], 'is_enabled' => 1],
            'GB' => ['code' => 'GB', 'name' => 'United Kingdom (UK)', 'flag' => '🇬🇧', 'currency_code' => 'GBP', 'currency_sym' => '£', 'dial_code' => '+44', 'default_tz' => 'Europe/London', 'timezones' => ['Europe/London'], 'is_enabled' => 1],
            'US' => ['code' => 'US', 'name' => 'United States (USA)', 'flag' => '🇺🇸', 'currency_code' => 'USD', 'currency_sym' => '$', 'dial_code' => '+1', 'default_tz' => 'America/New_York', 'timezones' => ['America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles'], 'is_enabled' => 1],
            'NG' => ['code' => 'NG', 'name' => 'Nigeria', 'flag' => '🇳🇬', 'currency_code' => 'NGN', 'currency_sym' => '₦', 'dial_code' => '+234', 'default_tz' => 'Africa/Lagos', 'timezones' => ['Africa/Lagos'], 'is_enabled' => 1],
            'CA' => ['code' => 'CA', 'name' => 'Canada', 'flag' => '🇨🇦', 'currency_code' => 'CAD', 'currency_sym' => 'CA$', 'dial_code' => '+1', 'default_tz' => 'America/Toronto', 'timezones' => ['America/Toronto', 'America/Vancouver'], 'is_enabled' => 0],
            'AU' => ['code' => 'AU', 'name' => 'Australia', 'flag' => '🇦🇺', 'currency_code' => 'AUD', 'currency_sym' => 'A$', 'dial_code' => '+61', 'default_tz' => 'Australia/Sydney', 'timezones' => ['Australia/Sydney', 'Australia/Perth'], 'is_enabled' => 0],
            'SA' => ['code' => 'SA', 'name' => 'Saudi Arabia', 'flag' => '🇸🇦', 'currency_code' => 'SAR', 'currency_sym' => 'SAR ', 'dial_code' => '+966', 'default_tz' => 'Asia/Riyadh', 'timezones' => ['Asia/Riyadh'], 'is_enabled' => 0],
        ];
    }

    public function getSettingsByGroup(string $group): array
    {
        if (! $this->db->tableExists('settings')) {
            return [];
        }
        $rows   = $this->db->table('settings')->where('setting_group', $group)->get()->getResultArray();
        $prefix = $group . '__';
        $map    = [];
        foreach ($rows as $r) {
            $k = (string) ($r['setting_key'] ?? '');
            if ($k !== '') {
                $cleanKey       = (strpos($k, $prefix) === 0) ? substr($k, strlen($prefix)) : $k;
                $map[$cleanKey] = $r['setting_value'] ?? '';
                $map[$k]        = $r['setting_value'] ?? '';
            }
        }
        return $map;
    }

    public function saveSettingRow(string $group, string $key, string $value): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }
        $scopedKey = (strpos($key, $group . '__') === 0) ? $key : ($group . '__' . $key);
        $sql = "INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `setting_group` = VALUES(`setting_group`),
                    `setting_value` = VALUES(`setting_value`)";
        $this->db->query($sql, [$scopedKey, $group, $value]);
    }

    public function getAllAvailableRoles(): array
    {
        if ($this->db->tableExists('cms_roles')) {
            $rows = $this->db->table('cms_roles')->orderBy('id', 'ASC')->get()->getResultArray();
            if (! empty($rows)) {
                return $rows;
            }
        }
        if ($this->db->tableExists('roles')) {
            $rCols    = $this->db->getFieldNames('roles');
            $slugCol  = in_array('role_slug', $rCols, true) ? 'role_slug' : 'name';
            $nameCol  = in_array('role_name', $rCols, true) ? 'role_name' : $slugCol;
            $rawRoles = $this->db->table('roles')->orderBy('id', 'ASC')->get()->getResultArray();
            $out      = [];
            foreach ($rawRoles as $r) {
                $slug = strtolower(trim((string) ($r[$slugCol] ?? 'student')));
                $out[] = [
                    'id'          => (int) $r['id'],
                    'role_slug'   => $slug,
                    'role_name'   => $r[$nameCol] ?? ucfirst($slug),
                    'portal_type' => in_array($slug, ['super_admin', 'admin', 'teacher'], true) ? 'backend' : 'frontend',
                ];
            }
            return $out;
        }
        return [];
    }

    public function getBehaviorRoleMap(): array
    {
        $raw       = $this->getSettingsByGroup('lms_role_map');
        $behaviors = ['super_admin', 'admin', 'teacher', 'parent', 'student'];
        $map       = [];
        foreach ($behaviors as $bKey) {
            $decoded    = isset($raw[$bKey]) ? json_decode((string) $raw[$bKey], true) : null;
            $map[$bKey] = is_array($decoded) && ! empty($decoded) ? array_values(array_unique($decoded)) : [$bKey];
        }
        return $map;
    }

    public function saveBehaviorRoleMap(array $post): void
    {
        $behaviors = ['super_admin', 'admin', 'teacher', 'parent', 'student'];
        foreach ($behaviors as $bKey) {
            $selected = $post['behavior_' . $bKey] ?? [$bKey];
            if (! is_array($selected)) {
                $selected = [$selected];
            }
            $clean = array_values(array_unique(array_filter(array_map('trim', $selected))));
            if (empty($clean)) {
                $clean = [$bKey];
            }
            $this->saveSettingRow('lms_role_map', $bKey, json_encode($clean));
        }

        $this->syncRolesAndForeignKeys();
        $this->rebuildStaticCache();
    }

    public function syncRolesAndForeignKeys(): array
    {
        $syncedRoles = 0;
        $syncedUsers = 0;

        try {
            if (! $this->db->tableExists('roles')) {
                return ['roles' => 0, 'users' => 0];
            }

            $rCols   = $this->db->getFieldNames('roles');
            $slugCol = in_array('role_slug', $rCols, true) ? 'role_slug' : (in_array('slug', $rCols, true) ? 'slug' : '');
            $nameCol = in_array('role_name', $rCols, true) ? 'role_name' : (in_array('name', $rCols, true) ? 'name' : '');

            $cmsRoles = $this->getAllAvailableRoles();
            $now      = date('Y-m-d H:i:s');

            foreach ($cmsRoles as $cr) {
                $slug = trim((string) ($cr['role_slug'] ?? ''));
                $name = trim((string) ($cr['role_name'] ?? ucfirst($slug)));
                if ($slug === '' || $slugCol === '') {
                    continue;
                }
                $existing = $this->db->table('roles')->where($slugCol, $slug)->get()->getRowArray();
                if (! $existing) {
                    $insertData = [$slugCol => $slug];
                    if ($nameCol !== '') $insertData[$nameCol] = $name;
                    if (in_array('description', $rCols, true)) $insertData['description'] = $cr['description'] ?? $name;
                    if (in_array('created_at', $rCols, true)) $insertData['created_at'] = $now;
                    $this->db->table('roles')->insert($insertData);
                    $syncedRoles++;
                }
            }

            $coreRoles = $this->db->table('roles')->get()->getResultArray();
            $idToSlug  = [];
            $slugToId  = [];
            foreach ($coreRoles as $row) {
                $rid = (int) $row['id'];
                if ($slugCol !== '' && ! empty($row[$slugCol])) {
                    $sKey            = strtolower(trim((string) $row[$slugCol]));
                    $idToSlug[$rid]  = $sKey;
                    $slugToId[$sKey] = $rid;
                }
            }

            if ($this->db->tableExists('users')) {
                $uCols = $this->db->getFieldNames('users');
                if (in_array('role_id', $uCols, true) && in_array('role_slug', $uCols, true)) {
                    $users = $this->db->table('users')->select('id, role_id, role_slug')->get()->getResultArray();
                    foreach ($users as $u) {
                        $uId   = (int) $u['id'];
                        $uRid  = (int) ($u['role_id'] ?? 0);
                        $uSlug = strtolower(trim((string) ($u['role_slug'] ?? '')));

                        if ($uId === 1) {
                            if ($uRid !== 1 || $uSlug !== 'super_admin') {
                                $this->db->table('users')->where('id', 1)->update(['role_id' => 1, 'role_slug' => 'super_admin']);
                                $syncedUsers++;
                            }
                            continue;
                        }

                        if (isset($idToSlug[$uRid])) {
                            $authSlug = $idToSlug[$uRid];
                            if ($uSlug === '' || ($uSlug === 'student' && $authSlug !== 'student')) {
                                $this->db->table('users')->where('id', $uId)->update(['role_slug' => $authSlug]);
                                $syncedUsers++;
                            }
                        } elseif (isset($slugToId[$uSlug])) {
                            $this->db->table('users')->where('id', $uId)->update(['role_id' => $slugToId[$uSlug]]);
                            $syncedUsers++;
                        }
                    }
                }
            }
        } catch (Throwable $e) {
        }

        return ['roles' => $syncedRoles, 'users' => $syncedUsers];
    }

    public function getCountriesCatalog(): array
    {
        $raw      = $this->getSettingsByGroup('lms_countries');
        $defaults = $this->getDefaultCountriesCatalog();
        $saved    = ! empty($raw['catalog_json']) ? json_decode((string) $raw['catalog_json'], true) : [];

        if (is_array($saved) && ! empty($saved)) {
            foreach ($defaults as $code => $defInfo) {
                if (isset($saved[$code])) {
                    $defaults[$code] = array_merge($defInfo, $saved[$code]);
                }
            }
        }

        $nowUtc = new DateTime('now', new DateTimeZone('UTC'));
        foreach ($defaults as $code => &$cInfo) {
            $tzName = $cInfo['default_tz'] ?? 'UTC';
            try {
                $tzObj   = new DateTimeZone($tzName);
                $locTime = clone $nowUtc;
                $locTime->setTimezone($tzObj);
                $cInfo['live_local_time'] = $locTime->format('d M Y, h:i A');
                $cInfo['utc_offset']      = 'UTC' . $locTime->format('P');
            } catch (Throwable $e) {
                $cInfo['live_local_time'] = gmdate('d M Y, h:i A');
                $cInfo['utc_offset']      = 'UTC+00:00';
            }
        }
        return $defaults;
    }

    public function getEnabledCountriesCatalog(): array
    {
        $all     = $this->getCountriesCatalog();
        $enabled = [];
        foreach ($all as $code => $cInfo) {
            if ((int) ($cInfo['is_enabled'] ?? 0) === 1) {
                $enabled[$code] = $cInfo;
            }
        }
        return ! empty($enabled) ? $enabled : ['IN' => $all['IN']];
    }

    public function saveCountriesSettings(array $post): void
    {
        $catalog      = $this->getDefaultCountriesCatalog();
        $enabledCodes = $post['enabled_countries'] ?? [];
        if (! is_array($enabledCodes)) {
            $enabledCodes = [];
        }

        foreach ($catalog as $code => &$info) {
            $info['is_enabled'] = in_array($code, $enabledCodes, true) ? 1 : 0;
            if (! empty($post['tz_' . $code])) {
                $info['default_tz'] = trim((string) $post['tz_' . $code]);
            }
            if (! empty($post['curr_' . $code])) {
                $info['currency_code'] = strtoupper(trim((string) $post['curr_' . $code]));
            }
        }

        $anyEnabled = false;
        foreach ($catalog as $c) {
            if ((int) $c['is_enabled'] === 1) {
                $anyEnabled = true;
                break;
            }
        }
        if (! $anyEnabled) {
            $catalog['IN']['is_enabled'] = 1;
        }

        $this->saveSettingRow('lms_countries', 'catalog_json', json_encode($catalog, JSON_UNESCAPED_UNICODE));
        $this->saveSettingRow('lms_countries', 'system_storage_tz', 'UTC');
        $this->rebuildStaticCache();
    }

    public function getFamilyDirectory(): array
    {
        if (! $this->db->tableExists('users')) {
            return [
                'all_users'          => [],
                'eligible_learners'  => [],
                'eligible_parents'   => [],
                'parents'            => [],
                'students'           => [],
                'children_by_parent' => [],
            ];
        }

        $roleMap     = $this->getBehaviorRoleMap();
        $staffSlugs  = array_unique(array_map('strtolower', array_merge(
            ['super_admin', 'admin', 'teacher'],
            $roleMap['super_admin'] ?? [],
            $roleMap['admin'] ?? [],
            $roleMap['teacher'] ?? []
        )));
        $parentSlugs = array_unique(array_map('strtolower', array_merge(
            ['parent'],
            $roleMap['parent'] ?? []
        )));

        $users     = $this->db->table('users')->orderBy('id', 'ASC')->get()->getResultArray();
        $metas     = $this->db->table($this->table)->get()->getResultArray();
        $metaByUid = [];
        foreach ($metas as $m) {
            $metaByUid[(int) $m['user_id']] = $m;
        }

        $batchesMap      = $this->getDynamicLeafBatchesMap();
        $enrollByStudent = [];
        if ($this->db->tableExists('lms_hierarchy_enrollments')) {
            foreach ($this->db->table('lms_hierarchy_enrollments')->where('status', 'active')->orderBy('id', 'DESC')->get()->getResultArray() as $enr) {
                $sid = (int) $enr['student_user_id'];
                if (! isset($enrollByStudent[$sid])) {
                    $enrollByStudent[$sid] = $enr;
                }
            }
        }

        $allById          = [];
        $eligibleLearners = [];
        $eligibleParents  = [];
        $parents          = [];
        $students         = [];
        $childrenByParent = [];

        foreach ($users as $u) {
            $uid      = (int) $u['id'];
            $roleId   = (int) ($u['role_id'] ?? 4);
            $rSlug    = strtolower(trim((string) ($u['role_slug'] ?? 'student')));
            $parentId = (int) ($u['parent_user_id'] ?? 0);

            $isStaffAccount  = in_array($roleId, [1, 2, 3], true) || in_array($rSlug, $staffSlugs, true);
            $isParentAccount = ! $isStaffAccount && in_array($rSlug, $parentSlugs, true);

            $uMeta   = $metaByUid[$uid] ?? [];
            $enrInfo = $enrollByStudent[$uid] ?? null;
            $leafId  = (int) ($enrInfo['leaf_node_id'] ?? ($uMeta['leaf_node_id'] ?? 0));

            $resolvedBatchTitle = $uMeta['grade_level'] ?? 'Enrolled Course';
            if ($leafId > 0 && isset($batchesMap[$leafId])) {
                $resolvedBatchTitle = $batchesMap[$leafId]['breadcrumb_path'];
            }

            $u['meta'] = [
                'leaf_node_id'       => $leafId,
                'child_age'          => (int) ($uMeta['child_age'] ?? 8),
                'grade_level'        => $resolvedBatchTitle,
                'country_code'       => $enrInfo['country_code'] ?? ($uMeta['country_code'] ?? 'IN'),
                'currency_code'      => $enrInfo['currency_code'] ?? ($uMeta['currency_code'] ?? 'INR'),
                'timezone'           => $uMeta['timezone'] ?? 'Asia/Kolkata',
                'avatar_color'       => $uMeta['avatar_color'] ?? '#10b981',
                'attendance_pct'     => (float) ($enrInfo['attendance_pct'] ?? 100.0),
                'test_score_avg'     => (float) ($enrInfo['test_score_avg'] ?? 0.0),
                'certificates_count' => (int) ($enrInfo['certificates_count'] ?? 0),
                'monthly_fee'        => (float) ($enrInfo['monthly_fee'] ?? 0.0),
                'next_due_date'      => $enrInfo['next_due_date'] ?? null,
            ];

            $allById[$uid] = $u;
            if ($isStaffAccount) {
                continue;
            }

            if ($isParentAccount) {
                $eligibleParents[$uid] = $u;
                $parents[$uid]         = $u;
            } else {
                $eligibleLearners[$uid] = $u;
                $students[$uid]         = $u;
                if ($parentId > 0) {
                    $childrenByParent[$parentId][] = $u;
                }
            }
        }

        return [
            'all_users'          => $allById,
            'eligible_learners'  => $eligibleLearners,
            'eligible_parents'   => $eligibleParents,
            'parents'            => $parents,
            'students'           => $students,
            'children_by_parent' => $childrenByParent,
        ];
    }

    public function saveChildLinkAndMeta(array $post): array
    {
        if (! $this->db->tableExists('users')) {
            return ['ok' => false, 'message' => 'Users table not found.'];
        }

        $studentId   = (int) ($post['student_user_id'] ?? 0);
        $parentId    = (int) ($post['parent_user_id'] ?? 0);
        $leafNodeId  = (int) ($post['leaf_node_id'] ?? 0);
        $childAge    = max(3, min(25, (int) ($post['child_age'] ?? 8)));
        $gradeLevel  = trim((string) ($post['grade_level'] ?? ''));
        $countryCode = strtoupper(trim((string) ($post['country_code'] ?? 'IN'))) ?: 'IN';
        $timezone    = trim((string) ($post['timezone'] ?? 'Asia/Kolkata')) ?: 'Asia/Kolkata';
        $avatarColor = trim((string) ($post['avatar_color'] ?? '#10b981')) ?: '#10b981';

        if ($studentId <= 0) {
            return ['ok' => false, 'message' => 'Please select a valid Student account.'];
        }
        if ($studentId === $parentId) {
            return ['ok' => false, 'message' => 'Student and Parent cannot be the same account.'];
        }

        $batchesMap = $this->getDynamicLeafBatchesMap();
        if ($leafNodeId > 0 && isset($batchesMap[$leafNodeId])) {
            $gradeLevel = $batchesMap[$leafNodeId]['title'];
        } elseif ($gradeLevel === '') {
            $gradeLevel = 'Foundation Level 1';
        }

        $countries = $this->getEnabledCountriesCatalog();
        if (! isset($countries[$countryCode])) {
            $firstC      = reset($countries);
            $countryCode = $firstC['code'] ?? 'IN';
        }
        $currencyCode = $countries[$countryCode]['currency_code'] ?? 'INR';
        if (empty($post['timezone']) && isset($countries[$countryCode]['default_tz'])) {
            $timezone = $countries[$countryCode]['default_tz'];
        }

        $this->db->table('users')->where('id', $studentId)->update([
            'parent_user_id' => $parentId > 0 ? $parentId : null,
        ]);

        $now      = gmdate('Y-m-d H:i:s');
        $existing = $this->db->table($this->table)->where('user_id', $studentId)->get()->getRowArray();
        $metaRow  = [
            'user_id'       => $studentId,
            'leaf_node_id'  => $leafNodeId,
            'child_age'     => $childAge,
            'grade_level'   => $gradeLevel,
            'country_code'  => $countryCode,
            'currency_code' => $currencyCode,
            'timezone'      => $timezone,
            'avatar_color'  => $avatarColor,
            'updated_at'    => $now,
        ];

        if ($existing) {
            $this->db->table($this->table)->where('id', $existing['id'])->update($metaRow);
        } else {
            $metaRow['created_at'] = $now;
            $this->db->table($this->table)->insert($metaRow);
        }

        if ($leafNodeId > 0 && $this->db->tableExists('lms_hierarchy_enrollments')) {
            $monthlyFee = 1499.00;
            if ($this->db->tableExists('lms_hierarchy_pricing')) {
                $pRow = $this->db->table('lms_hierarchy_pricing')
                    ->where('node_id', $leafNodeId)
                    ->where('country_code', $countryCode)
                    ->get()
                    ->getRowArray();
                if ($pRow && (float) $pRow['monthly_fee'] > 0) {
                    $monthlyFee   = (float) $pRow['monthly_fee'];
                    $currencyCode = $pRow['currency_code'] ?: $currencyCode;
                }
            }

            $exEnr = $this->db->table('lms_hierarchy_enrollments')
                ->where('student_user_id', $studentId)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            $nextDue = date('Y-m-d', strtotime('+30 days'));
            if ($exEnr) {
                $this->db->table('lms_hierarchy_enrollments')->where('id', $exEnr['id'])->update([
                    'parent_user_id' => $parentId,
                    'leaf_node_id'   => $leafNodeId,
                    'country_code'   => $countryCode,
                    'currency_code'  => $currencyCode,
                    'monthly_fee'    => $monthlyFee,
                    'status'         => 'active',
                    'updated_at'     => $now,
                ]);
            } else {
                $this->db->table('lms_hierarchy_enrollments')->insert([
                    'student_user_id'    => $studentId,
                    'parent_user_id'     => $parentId,
                    'leaf_node_id'       => $leafNodeId,
                    'country_code'       => $countryCode,
                    'currency_code'      => $currencyCode,
                    'monthly_fee'        => $monthlyFee,
                    'billing_cycle'      => 'monthly',
                    'next_due_date'      => $nextDue,
                    'attendance_pct'     => 100.00,
                    'test_score_avg'     => 0.00,
                    'certificates_count' => 0,
                    'history_json'       => json_encode([]),
                    'status'             => 'active',
                    'enrolled_at'        => $now,
                    'updated_at'         => $now,
                ]);
            }
        }

        return [
            'ok'      => true,
            'message' => 'Child account linked & enrolled in "' . $gradeLevel . '" successfully!',
        ];
    }

    /**
     * Allows a Logged-In Parent to Create a New Child Profile Directly from the Family Hub!
     */
    public function createNewChildUnderParent(int $parentUserId, array $post): array
    {
        if (! $this->db->tableExists('users') || $parentUserId <= 0) {
            return ['ok' => false, 'message' => 'Invalid parent account.'];
        }

        $childName   = trim((string) ($post['full_name'] ?? ''));
        $childEmail  = strtolower(trim((string) ($post['email'] ?? '')));
        $rawPassword = (string) ($post['password'] ?? '');
        $childAge    = max(3, min(25, (int) ($post['child_age'] ?? 8)));
        $leafNodeId  = (int) ($post['leaf_node_id'] ?? 0);
        $avatarColor = trim((string) ($post['avatar_color'] ?? '#10b981')) ?: '#10b981';

        if ($childName === '') {
            return ['ok' => false, 'message' => 'Please enter your child\'s full name.'];
        }

        // If parent didn't enter an email for the child, auto-generate a unique child login username/email
        if ($childEmail === '') {
            $slugName   = preg_replace('/[^a-z0-9]+/', '', strtolower($childName)) ?: 'learner';
            $childEmail = $slugName . '.' . $parentUserId . '.' . rand(100, 999) . '@student.portal';
        }

        $dup = $this->db->table('users')->where('email', $childEmail)->get()->getRowArray();
        if ($dup) {
            return ['ok' => false, 'message' => 'An account with email/username "' . $childEmail . '" already exists.'];
        }

        if (strlen($rawPassword) < 4) {
            $rawPassword = 'Kid@' . rand(1000, 9999);
        }

        // Inherit parent's country & timezone if available
        $parentMeta  = $this->db->table($this->table)->where('user_id', $parentUserId)->get()->getRowArray();
        $countryCode = $parentMeta['country_code'] ?? (session()->get('user_country') ?: 'IN');
        $timezone    = $parentMeta['timezone'] ?? (session()->get('user_timezone') ?: 'Asia/Kolkata');

        // Resolve student `role_id`
        $studentRoleId = 4;
        if ($this->db->tableExists('roles')) {
            $rRow = $this->db->table('roles')->where('role_slug', 'student')->get()->getRowArray();
            if ($rRow) {
                $studentRoleId = (int) $rRow['id'];
            }
        }

        $now   = gmdate('Y-m-d H:i:s');
        $uCols = array_flip($this->db->getFieldNames('users'));

        $newUserRow = [
            'role_id'        => $studentRoleId,
            'role_slug'      => 'student',
            'parent_user_id' => $parentUserId,
            'full_name'      => $childName,
            'name'           => $childName,
            'username'       => explode('@', $childEmail)[0],
            'email'          => $childEmail,
            'password_hash'  => password_hash($rawPassword, PASSWORD_DEFAULT),
            'status'         => 'active',
            'created_at'     => $now,
            'updated_at'     => $now,
        ];

        $filteredUserRow = array_intersect_key($newUserRow, $uCols);
        $this->db->table('users')->insert($filteredUserRow);
        $newStudentId = (int) $this->db->insertID();

        if ($newStudentId <= 0) {
            return ['ok' => false, 'message' => 'Could not create child account.'];
        }

        $this->saveChildLinkAndMeta([
            'student_user_id' => $newStudentId,
            'parent_user_id'  => $parentUserId,
            'leaf_node_id'    => $leafNodeId,
            'child_age'       => $childAge,
            'country_code'    => $countryCode,
            'timezone'        => $timezone,
            'avatar_color'    => $avatarColor,
        ]);

        return [
            'ok'         => true,
            'student_id' => $newStudentId,
            'message'    => 'Child profile "' . $childName . '" added to your family account (Login ID: ' . $childEmail . ')!',
        ];
    }

    /**
     * Updates Logged-In User's Profile (Full Name, Email, Country/Timezone, and Optional Password Change)
     */
    public function updateUserProfileAndSettings(int $userId, array $post): array
    {
        if (! $this->db->tableExists('users') || $userId <= 0) {
            return ['ok' => false, 'message' => 'Invalid user account.'];
        }

        $fullName    = trim((string) ($post['full_name'] ?? ''));
        $email       = strtolower(trim((string) ($post['email'] ?? '')));
        $newPassword = trim((string) ($post['new_password'] ?? ''));
        $countryCode = strtoupper(trim((string) ($post['country_code'] ?? 'IN'))) ?: 'IN';
        $timezone    = trim((string) ($post['timezone'] ?? 'Asia/Kolkata')) ?: 'Asia/Kolkata';

        if ($fullName === '' || $email === '') {
            return ['ok' => false, 'message' => 'Full Name and Email are required.'];
        }

        $dup = $this->db->table('users')->where('email', $email)->where('id !=', $userId)->get()->getRowArray();
        if ($dup) {
            return ['ok' => false, 'message' => 'That email address is already in use by another account.'];
        }

        $uCols = array_flip($this->db->getFieldNames('users'));
        $upd   = [];
        if (isset($uCols['full_name'])) $upd['full_name'] = $fullName;
        if (isset($uCols['name']))      $upd['name']      = $fullName;
        if (isset($uCols['email']))     $upd['email']     = $email;
        if ($newPassword !== '' && strlen($newPassword) >= 6) {
            if (isset($uCols['password_hash'])) {
                $upd['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            } elseif (isset($uCols['password'])) {
                $upd['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
        }
        if (isset($uCols['updated_at'])) {
            $upd['updated_at'] = gmdate('Y-m-d H:i:s');
        }

        if (! empty($upd)) {
            $this->db->table('users')->where('id', $userId)->update($upd);
        }

        // Update country & timezone in `cms_user_meta`
        $countries    = $this->getEnabledCountriesCatalog();
        $currencyCode = $countries[$countryCode]['currency_code'] ?? 'INR';
        $now          = gmdate('Y-m-d H:i:s');

        $exMeta = $this->db->table($this->table)->where('user_id', $userId)->get()->getRowArray();
        if ($exMeta) {
            $this->db->table($this->table)->where('id', $exMeta['id'])->update([
                'country_code'  => $countryCode,
                'currency_code' => $currencyCode,
                'timezone'      => $timezone,
                'updated_at'    => $now,
            ]);
        } else {
            $this->db->table($this->table)->insert([
                'user_id'       => $userId,
                'country_code'  => $countryCode,
                'currency_code' => $currencyCode,
                'timezone'      => $timezone,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        session()->set([
            'full_name'     => $fullName,
            'name'          => $fullName,
            'email'         => $email,
            'user_country'  => $countryCode,
            'user_currency' => $currencyCode,
            'user_timezone' => $timezone,
        ]);

        return [
            'ok'      => true,
            'message' => 'Your profile settings' . ($newPassword !== '' ? ' and password' : '') . ' have been updated!',
        ];
    }

    public function unlinkChildFromParent(int $studentId): void
    {
        if ($studentId > 0 && $this->db->tableExists('users')) {
            $this->db->table('users')->where('id', $studentId)->update(['parent_user_id' => null]);
        }
    }

    public function getUnifiedPortalContext(int $loggedInUserId, int $simulatedUserId = 0): array
    {
        $family       = $this->getFamilyDirectory();
        $allUsers     = $family['all_users'] ?? [];
        $targetUserId = ($simulatedUserId > 0 && isset($allUsers[$simulatedUserId])) ? $simulatedUserId : $loggedInUserId;
        $currentUser  = $allUsers[$targetUserId] ?? null;
        $siblings     = [];

        if ($currentUser) {
            if (! empty($family['children_by_parent'][$targetUserId])) {
                $siblings = $family['children_by_parent'][$targetUserId];
            } elseif (isset($family['students'][$targetUserId])) {
                $siblings = [$currentUser];
            }
        }

        $activeStudentId = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode  = (string) (session()->get('portal_view_mode') ?: 'parent');
        $activeStudent   = null;

        if ($activeStudentId > 0) {
            foreach ($siblings as $sib) {
                if ((int) $sib['id'] === $activeStudentId) {
                    $activeStudent = $sib;
                    break;
                }
            }
            if (! $activeStudent && isset($family['students'][$activeStudentId])) {
                $activeStudent = $family['students'][$activeStudentId];
            }
        }

        $targetRoleSlug = strtolower((string) ($currentUser['role_slug'] ?? session()->get('role_slug') ?? 'student'));
        if ($targetRoleSlug === 'student' && ! $activeStudent && isset($family['students'][$targetUserId])) {
            $activeStudent   = $family['students'][$targetUserId];
            $activeStudentId = $targetUserId;
            $portalViewMode  = 'student';
        }

        $activeTz   = $activeStudent['meta']['timezone'] ?? ($currentUser['meta']['timezone'] ?? (session()->get('user_timezone') ?: 'Asia/Kolkata'));
        $activeCurr = $activeStudent['meta']['currency_code'] ?? ($currentUser['meta']['currency_code'] ?? (session()->get('user_currency') ?: 'INR'));

        return [
            'current_user'      => $currentUser,
            'target_user_id'    => $targetUserId,
            'siblings'          => $siblings,
            'active_student'    => $activeStudent,
            'active_student_id' => $activeStudentId,
            'portal_view_mode'  => $portalViewMode,
            'active_timezone'   => $activeTz,
            'active_currency'   => $activeCurr,
        ];
    }

    public function rebuildStaticCache(): array
    {
        $payload = [
            'updated_at'        => time(),
            'storage_timezone'  => 'UTC',
            'role_map'          => $this->getBehaviorRoleMap(),
            'enabled_countries' => $this->getEnabledCountriesCatalog(),
        ];
        try {
            @is_dir(dirname($this->cacheFile)) || @mkdir(dirname($this->cacheFile), 0775, true);
            @file_put_contents($this->cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
        }
        return $payload;
    }
}

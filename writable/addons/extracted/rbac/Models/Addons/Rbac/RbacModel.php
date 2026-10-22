<?php

namespace App\Models\Addons\Rbac;

use CodeIgniter\Model;

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
        'is_system',
        'is_default_front',
        'status',
        'created_at',
        'updated_at',
    ];

    protected string $featuresTable = 'cms_rbac_features';
    protected string $cacheFile     = WRITEPATH . 'addons/rbac_permissions_cache.json';

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $lockFile = WRITEPATH . 'addons/schema_addon_rbac_v1.lock';
        if (is_file($lockFile) && is_file($this->cacheFile)) {
            return;
        }

        try {
            // 1. Roles Table
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `cms_roles` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `role_slug` VARCHAR(64) NOT NULL,
                    `role_name` VARCHAR(120) NOT NULL,
                    `portal_type` VARCHAR(32) NOT NULL DEFAULT 'backend',
                    `description` VARCHAR(255) NOT NULL DEFAULT '',
                    `permissions_json` LONGTEXT NULL,
                    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
                    `is_default_front` TINYINT(1) NOT NULL DEFAULT 0,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    UNIQUE KEY `uniq_role_slug` (`role_slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. Custom & Auto-Discovered Features / Permissions Table
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `cms_rbac_features` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `perm_key` VARCHAR(120) NOT NULL,
                    `perm_label` VARCHAR(160) NOT NULL,
                    `module_group` VARCHAR(80) NOT NULL DEFAULT 'Core System',
                    `source_type` VARCHAR(32) NOT NULL DEFAULT 'core',
                    `created_at` DATETIME NULL,
                    UNIQUE KEY `uniq_perm_key` (`perm_key`),
                    KEY `idx_module_group` (`module_group`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 3. Ensure `users` table has `role_slug` and `parent_user_id` if `users` table exists
            if ($this->db->tableExists('users')) {
                $uCols =$this->db->getFieldNames('users');
                if (! in_array('role_slug', $uCols, true)) {$this->db->query("ALTER TABLE `users` ADD COLUMN `role_slug` VARCHAR(64) NOT NULL DEFAULT 'student'");
                }
                if (! in_array('parent_user_id', $uCols, true)) {$this->db->query("ALTER TABLE `users` ADD COLUMN `parent_user_id` INT UNSIGNED NULL DEFAULT NULL");
                }
            }

            // 4. Seed Default Core Features & Scan Add-on Manifests
            $this->syncCoreAndAddonPermissions();

            // 5. Seed Default Roles (Super Admin, Admin, Teacher, Student, Parent, Website User)
            if ((int) $this->db->table($this->table)->countAllResults() === 0) {
                $now = date('Y-m-d H:i:s');
                $allPermKeys = array_column($this->getAllFeatures(), 'perm_key');

                $teacherPerms = [
                    'dashboard.view',
                    'courses.view', 'courses.create', 'courses.edit',
                    'students.view', 'students.progress',
                    'announcements.view', 'faqs.view', 'reviews.view',
                ];

                $studentPerms = [
                    'portal.student', 'courses.enroll', 'courses.learn', 'profile.edit',
                ];

                $parentPerms = [
                    'portal.parent', 'students.child_progress', 'profile.edit',
                ];

                $webUserPerms = [
                    'portal.website', 'profile.edit',
                ];

                $this->db->table($this->table)->insertBatch([
                    [
                        'role_slug'        => 'super_admin',
                        'role_name'        => 'Super Administrator',
                        'portal_type'      => 'backend',
                        'description'      => 'Unrestricted root access to all system modules, add-ons, and security settings.',
                        'permissions_json' => json_encode(['*']),
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
                        'description'      => 'Full administrative access to website pages, add-ons, courses, and users.',
                        'permissions_json' => json_encode($allPermKeys),
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
                        'description'      => 'Manages classes, abacus batches, student progress, and learning materials.',
                        'permissions_json' => json_encode($teacherPerms),
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
                        'description'      => 'Default learner role with access to enrolled courses, practice sheets, and exams.',
                        'permissions_json' => json_encode($studentPerms),
                        'is_system'        => 1,
                        'is_default_front' => 1,
                        'status'           => 'active',
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ],
                    [
                        'role_slug'        => 'parent',
                        'role_name'        => 'Parent / Guardian',
                        'portal_type'      => 'frontend',
                        'description'      => 'Parent account linked to student profiles to track attendance, fees, and reports.',
                        'permissions_json' => json_encode($parentPerms),
                        'is_system'        => 1,
                        'is_default_front' => 0,
                        'status'           => 'active',
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ],
                    [
                        'role_slug'        => 'website_user',
                        'role_name'        => 'Website User (Default Visitor)',
                        'portal_type'      => 'frontend',
                        'description'      => 'Standard registered website member before course enrollment.',
                        'permissions_json' => json_encode($webUserPerms),
                        'is_system'        => 1,
                        'is_default_front' => 0,
                        'status'           => 'active',
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ],
                ]);
            }

            $this->rebuildStaticCache();

            @is_dir(dirname($lockFile)) or @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, date('c'));
        } catch (\Throwable $e) {
            log_message('error', 'RbacModel schema error: ' . $e->getMessage());
        }
    }

    public function syncCoreAndAddonPermissions(): void
    {
        $now = date('Y-m-d H:i:s');

        $corePermissions = [
            ['perm_key' => 'dashboard.view',     'perm_label' => 'Access Admin Dashboard Overview',   'module_group' => 'Core Website & Settings', 'source_type' => 'core'],
            ['perm_key' => 'settings.view',      'perm_label' => 'View Global Website Settings',      'module_group' => 'Core Website & Settings', 'source_type' => 'core'],
            ['perm_key' => 'settings.edit',      'perm_label' => 'Modify Global Website Settings',    'module_group' => 'Core Website & Settings', 'source_type' => 'core'],
            ['perm_key' => 'addons.manage',      'perm_label' => 'Install, Enable & Delete Add-ons',  'module_group' => 'Core Website & Settings', 'source_type' => 'core'],

            ['perm_key' => 'pages.view',         'perm_label' => 'View CMS Pages & Visual Builder',   'module_group' => 'Page Builder & CMS',      'source_type' => 'core'],
            ['perm_key' => 'pages.create',       'perm_label' => 'Create New Website Pages',          'module_group' => 'Page Builder & CMS',      'source_type' => 'core'],
            ['perm_key' => 'pages.edit',         'perm_label' => 'Edit Page Blocks & Footer Layout',  'module_group' => 'Page Builder & CMS',      'source_type' => 'core'],
            ['perm_key' => 'pages.delete',       'perm_label' => 'Delete Website Pages',              'module_group' => 'Page Builder & CMS',      'source_type' => 'core'],

            ['perm_key' => 'users.view',         'perm_label' => 'View Users Directory',              'module_group' => 'Users & Academic Roles',  'source_type' => 'core'],
            ['perm_key' => 'users.create',       'perm_label' => 'Create Users (Teachers/Students)',  'module_group' => 'Users & Academic Roles',  'source_type' => 'core'],
            ['perm_key' => 'users.edit',         'perm_label' => 'Edit Users & Link Parent-Student',  'module_group' => 'Users & Academic Roles',  'source_type' => 'core'],
            ['perm_key' => 'users.delete',       'perm_label' => 'Delete User Accounts',              'module_group' => 'Users & Academic Roles',  'source_type' => 'core'],

            ['perm_key' => 'courses.view',       'perm_label' => 'View Courses & Batches (Admin)',    'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
            ['perm_key' => 'courses.create',     'perm_label' => 'Create Courses & Worksheets',       'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
            ['perm_key' => 'courses.edit',       'perm_label' => 'Edit Courses & Assessments',        'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
            ['perm_key' => 'students.view',      'perm_label' => 'View Assigned Students List',       'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
            ['perm_key' => 'students.progress',  'perm_label' => 'Grade & Update Student Progress',   'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
            ['perm_key' => 'portal.student',     'perm_label' => 'Student Portal Access (Frontend)',  'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
            ['perm_key' => 'courses.enroll',     'perm_label' => 'Enroll in Courses (Student)',       'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
            ['perm_key' => 'courses.learn',      'perm_label' => 'Access Abacus Practice & Exams',    'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
            ['perm_key' => 'portal.parent',      'perm_label' => 'Parent Portal Access (Frontend)',   'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
            ['perm_key' => 'students.child_progress', 'perm_label' => 'View Linked Child Progress Report', 'module_group' => 'LMS & Frontend Portals', 'source_type' => 'core'],
            ['perm_key' => 'portal.website',     'perm_label' => 'Default Website Member Access',     'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
            ['perm_key' => 'profile.edit',       'perm_label' => 'Update Own Profile & Password',     'module_group' => 'LMS & Frontend Portals',  'source_type' => 'core'],
        ];

        $extractedDir = WRITEPATH . 'addons/extracted/';
        if (is_dir($extractedDir)) {
            foreach (scandir($extractedDir) as $folder) {
                if ($folder === '.' || $folder === '..') {
                    continue;
                }
                $manifestPath = $extractedDir . $folder . '/addon.json';
                if (is_file($manifestPath)) {
                    $manifest = json_decode((string) file_get_contents($manifestPath), true);
                    if (is_array($manifest) && ! empty($manifest['permissions']) && is_array($manifest['permissions'])) {
                        $groupName = 'Add-on: ' . ($manifest['name'] ?? ucfirst($folder));
                        foreach ($manifest['permissions'] as $pItem) {
                            if (! empty($pItem['key'])) {
                                $corePermissions[] = [
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
        }

        $existingRows = $this->db->table($this->featuresTable)->select('perm_key')->get()->getResultArray();
        $existingKeys = array_flip(array_column($existingRows, 'perm_key'));

        $toInsert = [];
        foreach ($corePermissions as $perm) {
            if (! isset($existingKeys[$perm['perm_key']])) {
                $perm['created_at'] = $now;
                $toInsert[] = $perm;
                $existingKeys[$perm['perm_key']] = true;
            }
        }

        if (! empty($toInsert)) {
            $this->db->table($this->featuresTable)->insertBatch($toInsert);
        }
    }

    public function rebuildStaticCache(): array
    {
        $cachePayload = [
            'updated_at'   => time(),
            'default_role' => 'student',
            'roles'        => [],
        ];
		
		// RbacModel.php -> rebuildStaticCache() ke andar `foreach ($roles as $r)` loop ke baad yeh add karein:
			if ($this->db->tableExists('roles')) {
				$rCols = $this->db->getFieldNames('roles');
				foreach ($roles as $r) {
					$slug = (string) $r['role_slug'];
					$slugCol = in_array('role_slug', $rCols, true) ? 'role_slug' : (in_array('slug', $rCols, true) ? 'slug' : '');
					$nameCol = in_array('role_name', $rCols, true) ? 'role_name' : (in_array('name', $rCols, true) ? 'name' : '');
					if ($slugCol !== '' && $nameCol !== '') {
						$exists = $this->db->table('roles')->where($slugCol, $slug)->countAllResults();
						if ((int) $exists === 0) {
							$this->db->table('roles')->insert([
								$slugCol => $slug,
								$nameCol => $r['role_name'],
							]);
						}
					}
				}
			}

        try {
            $roles = $this->db->table($this->table)->orderBy('id', 'ASC')->get()->getResultArray();
            foreach ($roles as $r) {
                $slug  = (string) $r['role_slug'];
                $perms = json_decode((string) ($r['permissions_json'] ?? '[]'), true);
                if (! is_array($perms)) {
                    $perms = [];
                }
                if ((int) ($r['is_default_front'] ?? 0) === 1) {
                    $cachePayload['default_role'] = $slug;
                }
                $cachePayload['roles'][$slug] = [
                    'id'               => (int) $r['id'],
                    'role_slug'        => $slug,
                    'role_name'        => $r['role_name'],
                    'portal_type'      => $r['portal_type'] ?? 'frontend',
                    'is_default_front' => (int) ($r['is_default_front'] ?? 0),
                    'status'           => $r['status'] ?? 'active',
                    'permissions'      => array_values(array_unique($perms)),
                ];
            }

            @is_dir(dirname($this->cacheFile)) or @mkdir(dirname($this->cacheFile), 0775, true);
            @file_put_contents($this->cacheFile, json_encode($cachePayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
        }

        return $cachePayload;
    }

    public function getAllRoles(): array
    {
        $rows = $this->db->table($this->table)->orderBy('id', 'ASC')->get()->getResultArray();
        foreach ($rows as &$r) {
            $decoded = json_decode((string) ($r['permissions_json'] ?? '[]'), true);
            $r['permissions_array'] = is_array($decoded) ? $decoded : [];
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
            $this->rebuildStaticCache();
            return ['ok' => true, 'message' => 'Role "' . $roleName . '" updated successfully.'];
        }

        $dup = $this->db->table($this->table)->where('role_slug', $rawSlug)->get()->getRowArray();
        if ($dup) {
            return ['ok' => false, 'message' => 'Role slug "' . $rawSlug . '" already exists.'];
        }

        $defaultPerms = $portalType === 'backend' ? ['dashboard.view'] : ['portal.website', 'profile.edit'];
        $this->db->table($this->table)->insert([
            'role_slug'        => $rawSlug,
            'role_name'        => $roleName,
            'portal_type'      => $portalType,
            'description'      => $description,
            'permissions_json' => json_encode($defaultPerms),
            'is_system'        => 0,
            'is_default_front' => $isDefFront,
            'status'           => 'active',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $this->rebuildStaticCache();
        return ['ok' => true, 'message' => 'Custom Role "' . $roleName . '" created!'];
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

    public function deleteCustomRole(int $id): array
    {
        $role = $this->find($id);
        if (! $role) {
            return ['ok' => false, 'message' => 'Role not found.'];
        }
        if ((int) ($role['is_system'] ?? 0) === 1) {
            return ['ok' => false, 'message' => 'System roles (Super Admin, Admin, Teacher, Student, Parent) cannot be deleted.'];
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
            return ['ok' => false, 'message' => 'Both Permission Key (e.g. blog.edit) and Label are required.'];
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

        return ['ok' => true, 'message' => 'Custom feature permission "' . $cleanKey . '" added to matrix!'];
    }

    public function deleteCustomFeature(int $id): bool
    {
        $row = $this->db->table($this->featuresTable)->where('id', $id)->get()->getRowArray();
        if (! $row || ($row['source_type'] ?? '') === 'core') {
            return false;
        }
        $this->db->table($this->featuresTable)->where('id', $id)->delete();
        return true;
    }

    public function getUsersWithRoles(): array
    {
        if (! $this->db->tableExists('users')) {
            return [];
        }
        return $this->db->table('users')
            ->orderBy('id', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();
    }

    public function assignRoleToUser(int $userId, string $roleSlug, ?int $parentUserId = null): array
    {
        if (! $this->db->tableExists('users') || $userId <= 0) {
            return ['ok' => false, 'message' => 'Invalid user account.'];
        }

        $role = $this->db->table($this->table)->where('role_slug', $roleSlug)->get()->getRowArray();
        if (! $role) {
            return ['ok' => false, 'message' => 'Selected role does not exist.'];
        }

        $uCols = $this->db->getFieldNames('users');
        $upd   = [];
        if (in_array('role_slug', $uCols, true)) {
            $upd['role_slug'] = $roleSlug;
        }
        if (in_array('role', $uCols, true)) {
            $upd['role'] = $roleSlug;
        }
        if (in_array('role_id', $uCols, true)) {
            $upd['role_id'] = (int) $role['id'];
        }
        if (in_array('parent_user_id', $uCols, true)) {
            $upd['parent_user_id'] = ($parentUserId !== null && $parentUserId > 0) ? $parentUserId : null;
        }

        if (! empty($upd)) {
            $this->db->table('users')->where('id', $userId)->update($upd);
        }

        if ((int) session()->get('user_id') === $userId || (int) session()->get('id') === $userId) {
            $perms = json_decode((string) ($role['permissions_json'] ?? '[]'), true) ?: [];
            session()->set([
                'role_slug'        => $roleSlug,
                'permissions'      => $perms,
                'role_permissions' => $perms,
            ]);
        }

        return ['ok' => true, 'message' => 'User #' . $userId . ' assigned to role "' . $role['role_name'] . '".'];
    }
}

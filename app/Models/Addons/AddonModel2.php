<?php

namespace App\Models\Addons;

use CodeIgniter\Model;
use Config\Database;
use Throwable;
use ZipArchive;

class AddonModel extends Model
{
    protected string $schemaLockFile;

    public function __construct()
    {
        parent::__construct();
        // Lock file jo ensure karegi ki `addons` table migration sirf EK BAAR chale
        $this->schemaLockFile = WRITEPATH . 'schema_addons_v1.lock';
    }

    /**
     * ONE-TIME AUTO-INITIALIZER (Runs only once on first visit)
     */
    public function ensureSchemaInitialized(): void
    {
        if (is_file($this->schemaLockFile)) {
            return;
        }

        try {
            $db = Database::connect();

            $sql = "
                CREATE TABLE IF NOT EXISTS `addons` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `slug` VARCHAR(80) NOT NULL UNIQUE,
                    `name` VARCHAR(140) NOT NULL,
                    `category` VARCHAR(60) NOT NULL DEFAULT 'general',
                    `version` VARCHAR(30) NOT NULL DEFAULT '1.0.0',
                    `author` VARCHAR(100) NULL,
                    `description` TEXT NULL,
                    `admin_route` VARCHAR(150) NULL,
                    `frontend_route` VARCHAR(150) NULL,
                    `config_json` LONGTEXT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `installed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";

            $db->query($sql);

            file_put_contents($this->schemaLockFile, json_encode([
                'module'      => 'addons_core_v1',
                'migrated_at' => date('Y-m-d H:i:s'),
            ], JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
            // Ignore if DB not ready
        }
    }

    /**
     * Force Re-Initialize Schema
     */
    public function forceReinitializeSchema(): bool
    {
        if (is_file($this->schemaLockFile)) {
            @unlink($this->schemaLockFile);
        }
        $this->ensureSchemaInitialized();
        return is_file($this->schemaLockFile);
    }

    /**
     * Fetch all installed add-ons
     */
    public function getAllAddons(): array
    {
        $this->ensureSchemaInitialized();

        try {
            $db = Database::connect();
            return $db->table('addons')->orderBy('id', 'DESC')->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Fetch only ACTIVE add-ons (Used by Sidebar & Frontend dynamically)
     */
    public function getActiveAddons(): array
    {
        $this->ensureSchemaInitialized();

        try {
            $db = Database::connect();
            return $db->table('addons')
                ->where('is_active', 1)
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Fetch single add-on by slug
     */
    public function getAddonBySlug(string $slug): ?array
    {
        $this->ensureSchemaInitialized();

        try {
            $db  = Database::connect();
            $row = $db->table('addons')->where('slug', $slug)->get()->getRowArray();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Toggle Add-On Active / Disabled status
     */
    public function toggleStatus(int $id): array
    {
        $this->ensureSchemaInitialized();

        try {
            $db    = Database::connect();
            $addon = $db->table('addons')->where('id', $id)->get()->getRowArray();
            if (! $addon) {
                return ['status' => 'error', 'message' => 'Add-on not found.'];
            }

            $newState = ((int) $addon['is_active'] === 1) ? 0 : 1;
            $db->table('addons')->where('id', $id)->update(['is_active' => $newState]);

            return [
                'status'    => 'success',
                'is_active' => $newState,
                'message'   => 'Add-on "' . $addon['name'] . '" is now ' . ($newState ? 'ACTIVE' : 'DISABLED') . '.',
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Database error while toggling add-on.'];
        }
    }

    /**
     * Update Add-On JSON Configuration (e.g., Payment Gateway Keys, Frontend Settings)
     */
    public function updateAddonConfig(string $slug, array $newConfig): bool
    {
        $this->ensureSchemaInitialized();

        try {
            $db = Database::connect();
            return (bool) $db->table('addons')
                ->where('slug', $slug)
                ->update([
                    'config_json' => json_encode($newConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Core ZIP Installer: Extracts ZIP, copies Controller/Model/View/Assets, runs 1-time DB init
     */
    public function installFromZip(string $zipFilePath): array
    {
        $this->ensureSchemaInitialized();

        if (! class_exists('ZipArchive')) {
            return [
                'status'  => 'error',
                'message' => 'PHP ZipArchive extension is not enabled on this server.',
            ];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            return [
                'status'  => 'error',
                'message' => 'Unable to open the uploaded .zip archive.',
            ];
        }

        // Read addon.json from root of ZIP
        $manifestRaw = $zip->getFromName('addon.json');
        if ($manifestRaw === false) {
            $zip->close();
            return [
                'status'  => 'error',
                'message' => 'Invalid Add-on Package: Missing `addon.json` manifest at root of ZIP.',
            ];
        }

        $manifest = json_decode($manifestRaw, true);
        if (! is_array($manifest) || empty($manifest['slug']) || empty($manifest['name'])) {
            $zip->close();
            return [
                'status'  => 'error',
                'message' => 'Corrupt `addon.json`: `slug` and `name` keys are required.',
            ];
        }

        $slug        = preg_replace('/[^a-z0-9_-]/', '', strtolower($manifest['slug']));
        $extractDir  = WRITEPATH . 'addons/extracted/' . $slug . '/';

        if (! is_dir($extractDir)) {
            mkdir($extractDir, 0775, true);
        }

        // 1. Extract full package into writable/addons/extracted/<slug>/
        $zip->extractTo($extractDir);
        $zip->close();

        // 2. Copy payload files (app/Controllers/..., app/Models/..., app/Views/..., public/...) to ROOTPATH
        $payloadDir = $extractDir . 'payload/';
        if (is_dir($payloadDir)) {
            $this->recursiveCopy($payloadDir, ROOTPATH);
        }

        // 3. Run One-Time Initializer if the Add-on specifies an init_model in addon.json
        if (! empty($manifest['init_model']) && class_exists($manifest['init_model'])) {
            try {
                $addonModelInstance = new $manifest['init_model']();
                if (method_exists($addonModelInstance, 'ensureSchemaInitialized')) {
                    $addonModelInstance->ensureSchemaInitialized();
                }
            } catch (Throwable $e) {
                return [
                    'status'  => 'error',
                    'message' => 'Add-on DB Migration Error: ' . $e->getMessage(),
                ];
            }
        }

        // 4. Register Add-on in `addons` table (Preserves existing config if upgrading)
        try {
            $db       = Database::connect();
            $existing = $this->getAddonBySlug($slug);

            $defaultConfig = $manifest['default_config'] ?? [];
            $configToSave  = ($existing && ! empty($existing['config_json']))
                ? $existing['config_json']
                : json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $sql = "
                INSERT INTO `addons` 
                    (`slug`, `name`, `category`, `version`, `author`, `description`, `admin_route`, `frontend_route`, `config_json`, `is_active`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE
                    `name` = VALUES(`name`),
                    `category` = VALUES(`category`),
                    `version` = VALUES(`version`),
                    `author` = VALUES(`author`),
                    `description` = VALUES(`description`),
                    `admin_route` = VALUES(`admin_route`),
                    `frontend_route` = VALUES(`frontend_route`)
            ";

            $db->query($sql, [
                $slug,
                $manifest['name'],
                $manifest['category'] ?? 'general',
                $manifest['version'] ?? '1.0.0',
                $manifest['author'] ?? 'System Architect',
                $manifest['description'] ?? '',
                $manifest['admin_route'] ?? null,
                $manifest['frontend_route'] ?? null,
                $configToSave,
            ]);

            return [
                'status'  => 'success',
                'message' => 'Add-On "' . $manifest['name'] . '" (v' . ($manifest['version'] ?? '1.0.0') . ') unzipped, deployed & database initialized!',
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'error',
                'message' => 'Failed to register add-on in database: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Recursive Directory Copy Helper
     */
    private function recursiveCopy(string $src, string $dst): void
    {
        $dir = opendir($src);
        if (! is_dir($dst)) {
            mkdir($dst, 0775, true);
        }
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $srcPath = $src . '/' .$file;
            $dstPath = $dst . '/' .$file;
            if (is_dir($srcPath)) {$this->recursiveCopy($srcPath,$dstPath);
            } else {
                copy($srcPath,$dstPath);
            }
        }
        closedir($dir);
    }
}
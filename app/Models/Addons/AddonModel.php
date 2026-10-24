<?php

namespace App\Models\Addons;

use CodeIgniter\Model;
use Config\Database;
use Throwable;
use ZipArchive;

class AddonModel extends Model
{
    protected string $schemaLockFile;
    protected static ?array $allAddonsCache    = null;
    protected static ?array $activeAddonsCache = null;

    public function __construct()
    {
        parent::__construct();
        $this->schemaLockFile = WRITEPATH . 'schema_addons_v1.lock';
    }

    public static function clearMemoryCache(): void
    {
        self::$allAddonsCache    = null;
        self::$activeAddonsCache = null;
    }

    public function ensureSchemaInitialized(): void
    {
        if (is_file($this->schemaLockFile)) {
            return;
        }

        try {
            $db = Database::connect();
            $db->query("
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
            ");

            file_put_contents($this->schemaLockFile, json_encode(['migrated_at' => date('Y-m-d H:i:s')]));
        } catch (Throwable $e) {
        }
    }

    public function getAllAddons(): array
    {
        if (self::$allAddonsCache !== null) {
            return self::$allAddonsCache;
        }

        $this->ensureSchemaInitialized();
        try {
            self::$allAddonsCache = Database::connect()
                ->table('addons')
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();
            return self::$allAddonsCache;
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getActiveAddons(): array
    {
        if (self::$activeAddonsCache !== null) {
            return self::$activeAddonsCache;
        }

        $this->ensureSchemaInitialized();
        try {
            $db     = Database::connect();
            $fields = $db->getFieldNames('addons');
            if (! in_array('sort_order', $fields, true)) {
                $db->query("ALTER TABLE `addons` ADD COLUMN `sort_order` INT(11) NOT NULL DEFAULT 0 AFTER `is_active`");
            }

            // 1, 2, 3... come first in exact ascending order; all 0s come at the bottom!
            $rows = $db->query("
                SELECT * FROM `addons`
                WHERE `is_active` = 1
                ORDER BY (`sort_order` = 0) ASC, `sort_order` ASC, `id` ASC
            ")->getResultArray();

            $filtered = [];
            foreach ($rows as $r) {
                $slug = trim((string) ($r['slug'] ?? ''));
                if ($slug === '') {
                    continue;
                }
                if (isset($r['is_enabled']) && (int) $r['is_enabled'] === 0) {
                    continue;
                }
                if (isset($r['status']) && strtolower((string) $r['status']) !== 'active') {
                    continue;
                }
                if (is_dir(WRITEPATH . 'addons/extracted/' . $slug)) {
                    $filtered[] = $r;
                }
            }

            self::$activeAddonsCache = $filtered;
            return self::$activeAddonsCache;
        } catch (Throwable $e) {
            return [];
        }
    }

    public function isAddonActive(string $slug): bool
    {
        $slug = trim($slug);
        if ($slug === '' || $slug === 'page_builder') {
            return true;
        }
        foreach ($this->getActiveAddons() as $addon) {
            if (($addon['slug'] ?? '') === $slug) {
                return true;
            }
        }
        return false;
    }

    public function getAddonBySlug(string $slug): ?array
    {
        $this->ensureSchemaInitialized();
        try {
            return Database::connect()->table('addons')->where('slug', $slug)->get()->getRowArray() ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function updateAddonConfig(string $slug, array $newConfig): bool
    {
        try {
            $ok = (bool) Database::connect()->table('addons')
                ->where('slug', $slug)
                ->update(['config_json' => json_encode($newConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)]);
            self::clearMemoryCache();
            return $ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function toggleStatus(int $id): array
    {
        try {
            $db    = Database::connect();
            $addon = $db->table('addons')->where('id', $id)->get()->getRowArray();
            if (! $addon) {
                return ['status' => 'error', 'message' => 'Add-on not found.'];
            }

            $newState = ((int) $addon['is_active'] === 1) ? 0 : 1;
            $db->table('addons')->where('id', $id)->update(['is_active' => $newState]);
            self::clearMemoryCache();

            return [
                'status'    => 'success',
                'is_active' => $newState,
                'message'   => $addon['name'] . ' is now ' . ($newState ? 'ENABLED' : 'DISABLED') . '.',
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Failed to toggle status.'];
        }
    }

    /**
     * Complete Uninstall: Deletes deployed files, extracted folder & DB record
     */
    public function uninstallAddon(int $id): array
    {
        try {
            $db    = Database::connect();
            $addon = $db->table('addons')->where('id', $id)->get()->getRowArray();
            if (! $addon) {
                return ['status' => 'error', 'message' => 'Add-on not found.'];
            }

            $slug       = $addon['slug'];
            $extractDir = WRITEPATH . 'addons/extracted/' . $slug . '/';
            $payloadDir = $extractDir . 'payload/';

            // 1. Remove deployed files from app/ and public/ that came from payload/
            if (is_dir($payloadDir)) {
                $this->removeDeployedFiles($payloadDir, ROOTPATH);
            }

            // 2. Delete writable/addons/extracted/<slug>/ folder
            if (is_dir($extractDir)) {
                $this->deleteDirectory($extractDir);
            }

            // 3. Delete database record
            $db->table('addons')->where('id', $id)->delete();
            self::clearMemoryCache();

            return [
                'status'  => 'success',
                'message' => 'Add-on "' . $addon['name'] . '" and its files have been completely removed.',
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Uninstall error: ' . $e->getMessage()];
        }
    }

    /**
     * Unzips, deploys files, runs 1-time DB init, and ALWAYS deletes the .zip file afterwards
     */
    public function installFromZip(string $zipFilePath): array
    {
        $this->ensureSchemaInitialized();

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipFilePath) !== true) {
                return ['status' => 'error', 'message' => 'Could not open .zip file.'];
            }

            $manifestRaw = $zip->getFromName('addon.json');
            if ($manifestRaw === false) {
                $zip->close();
                return ['status' => 'error', 'message' => 'Missing `addon.json` inside root of ZIP.'];
            }

            $manifest = json_decode(ltrim($manifestRaw, "\xEF\xBB\xBF"), true);
            if (! is_array($manifest) || empty($manifest['slug']) || empty($manifest['name'])) {
                $zip->close();
                return ['status' => 'error', 'message' => 'Invalid `addon.json` manifest.'];
            }

            $slug       = preg_replace('/[^a-z0-9_-]/', '', strtolower($manifest['slug']));
            $extractDir = WRITEPATH . 'addons/extracted/' . $slug . '/';
            if (! is_dir($extractDir)) {
                mkdir($extractDir, 0775, true);
            }

            $zip->extractTo($extractDir);
            $zip->close();

            // Copy payload files to project root
            if (is_dir($extractDir . 'payload/')) {
                $this->recursiveCopy($extractDir . 'payload/', ROOTPATH);
            }

            // Run 1-Time DB Initializer if defined (Auto-requires model file from extracted folder first!)
            if (! empty($manifest['init_model'])) {
                $initClass = ltrim((string) $manifest['init_model'], '\\');
                if (! class_exists($initClass)) {
                    $relModelPath = str_replace(['App\\', '\\'], ['', '/'], $initClass) . '.php';
                    if (is_file($extractDir . $relModelPath)) {
                        require_once $extractDir . $relModelPath;
                    } elseif (is_file(APPPATH . $relModelPath)) {
                        require_once APPPATH . $relModelPath;
                    }
                }
                if (class_exists($initClass)) {
                    $modelObj = new $initClass();
                    if (method_exists($modelObj, 'ensureSchemaInitialized')) {
                        $modelObj->ensureSchemaInitialized();
                    } elseif (method_exists($modelObj, 'ensureSchema')) {
                        $modelObj->ensureSchema();
                    }
                }
            }

            $db       = Database::connect();
            $existing = $this->getAddonBySlug($slug);
            $cfgJson  = ($existing && ! empty($existing['config_json']))
                ? $existing['config_json']
                : json_encode($manifest['default_config'] ?? [], JSON_PRETTY_PRINT);

            $db->query("
                INSERT INTO `addons` (`slug`, `name`, `category`, `version`, `author`, `description`, `admin_route`, `frontend_route`, `config_json`, `is_active`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE
                    `name` = VALUES(`name`), `version` = VALUES(`version`),
                    `admin_route` = VALUES(`admin_route`), `frontend_route` = VALUES(`frontend_route`), `is_active` = 1
            ", [
                $slug,
                $manifest['name'],
                $manifest['category'] ?? 'general',
                $manifest['version'] ?? '1.0.0',
                $manifest['author'] ?? 'System',
                $manifest['description'] ?? '',
                $manifest['admin_route'] ?? null,
                $manifest['frontend_route'] ?? null,
                $cfgJson,
            ]);

            self::clearMemoryCache();

            return [
                'status'  => 'success',
                'message' => 'Add-On "' . $manifest['name'] . '" installed & temporary ZIP deleted!',
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } finally {
            // ALWAYS DELETE THE UPLOADED ZIP FILE AFTER INSTALLATION
            if (is_file($zipFilePath)) {
                @unlink($zipFilePath);
            }
            foreach (glob(WRITEPATH . 'addons/packages/*.zip') ?: [] as $oldZip) {
                @unlink($oldZip);
            }
        }
    }

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
            $s = $src . '/' . $file;
            $d = $dst . '/' . $file;
            is_dir($s) ? $this->recursiveCopy($s, $d) : copy($s, $d);
        }
        closedir($dir);
    }

    private function removeDeployedFiles(string $payloadPath, string $targetRoot): void
    {
        $dir = opendir($payloadPath);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $pFile = $payloadPath . '/' . $file;
            $tFile = $targetRoot . '/' . $file;
            if (is_dir($pFile)) {
                $this->removeDeployedFiles($pFile, $tFile);
                @rmdir($tFile);
            } elseif (is_file($tFile)) {
                @unlink($tFile);
            }
        }
        closedir($dir);
    }

    private function deleteDirectory(string $dirPath): void
    {
        if (! is_dir($dirPath)) {
            return;
        }
        foreach (scandir($dirPath) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dirPath . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dirPath);
    }
}
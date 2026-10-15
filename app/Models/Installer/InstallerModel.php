<?php

namespace App\Models\Installer;

use CodeIgniter\Model;
use mysqli;

class InstallerModel extends Model
{
    /**
     * Connects to MySQL host, creates Database if missing, creates RBAC + Settings tables, and seeds data.
     */
    public function initializeSystemDatabase(array $dbConfig, array$payload): array
    {
        mysqli_report(MYSQLI_REPORT_OFF);

        $mysqli = @new mysqli($dbConfig['host'],
            $dbConfig['user'],$dbConfig['pass'],
            '',
            (int) $dbConfig['port']
        );

        if ($mysqli->connect_errno) {
            return [
                'success' => false,
                'message' => 'Database Host Connection Failed: ' . $mysqli->connect_error,
            ];
        }

        $cleanDbName = preg_replace('/[^a-zA-Z0-9_]/', '',$dbConfig['name']);
        if (! $cleanDbName) {
            return [
                'success' => false,
                'message' => 'Invalid database name provided.',
            ];
        }

        $createDbSql = "CREATE DATABASE IF NOT EXISTS `{$cleanDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        if (! $mysqli->query($createDbSql)) {
            return [
                'success' => false,
                'message' => 'Unable to create database: ' . $mysqli->error,
            ];
        }

        $mysqli->select_db($cleanDbName);

        // 1. Settings Table
        $settingsTableSql = "
            CREATE TABLE IF NOT EXISTS `settings` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                `setting_value` TEXT NULL,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        // 2. Roles Table
        $rolesTableSql = "
            CREATE TABLE IF NOT EXISTS `roles` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `role_slug` VARCHAR(50) NOT NULL UNIQUE,
                `role_name` VARCHAR(100) NOT NULL,
                `description` VARCHAR(255) NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        // 3. Users Table
        $usersTableSql = "
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `role_id` INT UNSIGNED NOT NULL,
                `full_name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `password_hash` VARCHAR(255) NOT NULL,
                `status` ENUM('active', 'suspended') DEFAULT 'active',
                `last_login` DATETIME NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        if (! $mysqli->query($settingsTableSql) || ! $mysqli->query($rolesTableSql) || ! $mysqli->query($usersTableSql)) {
            return [
                'success' => false,
                'message' => 'Schema Migration Error: ' . $mysqli->error,
            ];
        }

        $defaultRoles = [
            ['super_admin', 'Super Administrator', 'Full unrestricted system & architecture access'],
            ['admin', 'Administrator', 'Operations, user management, and portal configuration'],
            ['teacher', 'Teacher / Instructor', 'Course, batch, and student evaluation management'],
            ['student', 'Student / Member', 'Learning portal and interactive classroom access'],
        ];

        $roleStmt =$mysqli->prepare("INSERT INTO `roles` (`role_slug`, `role_name`, `description`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `role_name` = VALUES(`role_name`)");
        foreach ($defaultRoles as $r) {$roleStmt->bind_param('sss', $r[0],$r[1], $r[2]);$roleStmt->execute();
        }
        $roleStmt->close();

        $roleRes      =$mysqli->query("SELECT `id` FROM `roles` WHERE `role_slug` = 'super_admin' LIMIT 1");
        $superRoleRow =$roleRes->fetch_assoc();
        $superRoleId  = (int) ($superRoleRow['id'] ?? 1);

        $settingsData = [
            'site_title'   => $payload['site_title'],
            'site_logo'    => $payload['site_logo'],
            'site_favicon' => $payload['site_favicon'],
        ];

        $setStmt =$mysqli->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
        foreach ($settingsData as $k =>$v) {
            $setStmt->bind_param('ss',$k, $v);$setStmt->execute();
        }
        $setStmt->close();

        $hashedPass = password_hash($payload['admin_pass'], PASSWORD_BCRYPT);
        $userStmt   =$mysqli->prepare("
            INSERT INTO `users` (`role_id`, `full_name`, `email`, `password_hash`, `status`)
            VALUES (?, ?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE
                `role_id` = VALUES(`role_id`),
                `full_name` = VALUES(`full_name`),
                `password_hash` = VALUES(`password_hash`)
        ");
        $userStmt->bind_param('isss', $superRoleId,$payload['admin_name'], $payload['admin_email'],$hashedPass);

        if (! $userStmt->execute()) {
            return [
                'success' => false,
                'message' => 'Failed to seed Super Admin account: ' . $userStmt->error,
            ];
        }
        $userStmt->close();$mysqli->close();

        return ['success' => true];
    }

    /**
     * Automatically updates CodeIgniter's .env file
     */
    public function writeEnvironmentConfig(array $config): bool
    {
        $envPath    = ROOTPATH . '.env';$samplePath = ROOTPATH . 'env';

        if (! is_file($envPath) && is_file($samplePath)) {
            copy($samplePath,$envPath);
        }

        $content = is_file($envPath) ? file_get_contents($envPath) : '';

        $replacements = [
            'CI_ENVIRONMENT'             => 'production',
            'app.baseURL'                => "'" . rtrim($config['baseURL'], '/') . "/'",
            'database.default.hostname'  => "'" . addslashes($config['hostname']) . "'",
            'database.default.database'  => "'" . addslashes($config['database']) . "'",
            'database.default.username'  => "'" . addslashes($config['username']) . "'",
            'database.default.password'  => "'" . addslashes($config['password']) . "'",
            'database.default.DBDriver'  => "'MySQLi'",
            'database.default.port'      => (string) (int) $config['port'],
        ];

        foreach ($replacements as$key => $val) {$pattern = '/^[\s#]*' . preg_quote($key, '/') . '\s*=.*$/m';
            $line    = "{$key} = {$val}";

            if (preg_match($pattern,$content)) {
                $content = preg_replace($pattern, $line,$content);
            } else {
                $content .= PHP_EOL .$line;
            }
        }

        return file_put_contents($envPath, trim($content) . PHP_EOL) !== false;
    }
}
<?php

namespace App\Models\Settings;

use CodeIgniter\Model;
use Config\Database;
use Throwable;

class SettingModel extends Model
{
    protected string $schemaLockFile;

    public function __construct()
    {
        parent::__construct();
        // Lock file jo ensure karegi ki DB migration sirf EK BAAR chale
        $this->schemaLockFile = WRITEPATH . 'schema_settings_v3.lock';
    }

    /**
     * ONE-TIME AUTO-INITIALIZER (Re-initialize / Migrate without touching existing data)
     * Yeh check karta hai ki Settings v2 schema update hua hai ya nahi.
     * Agar nahi hua, toh table alter karke naye SEO/Analytics/Scripts keys seed kar deta hai.
     */
    public function ensureSchemaInitialized(): void
    {
        if (is_file($this->schemaLockFile)) {
            return; // Already initialized, do nothing!
        }

        try {
            $db = Database::connect();

            // 1. Check if `setting_group` column exists in `settings` table, if not add it
            $fields =$db->getFieldNames('settings');
            if (! in_array('setting_group', $fields, true)) {$db->query("ALTER TABLE `settings` ADD COLUMN `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general' AFTER `setting_key`");
            }

            // 2. Default keys for Branding, SEO, Analytics, Verification & Custom Code
            $defaultSettings = [
                // General & Branding
                ['site_title', 'general', 'Core Platform'],
                ['site_tagline', 'general', 'Enterprise Modular Portal'],
                ['site_logo', 'general', null],
                ['site_favicon', 'general', null],
                ['contact_email', 'general', ''],

                // SEO & OpenGraph Configuration
                ['meta_title', 'seo', ''],
                ['meta_description', 'seo', ''],
                ['meta_keywords', 'seo', ''],
                ['canonical_url', 'seo', ''],
                ['robots_txt', 'seo', 'index, follow'],
                ['og_image', 'seo', null],

                // Analytics & Search Engine Verification
                ['google_analytics_id', 'analytics', ''],
                ['google_tag_manager_id', 'analytics', ''],
                ['meta_pixel_id', 'analytics', ''],
                ['google_site_verification', 'analytics', ''],
                ['bing_site_verification', 'analytics', ''],
                ['meta_domain_verification', 'analytics', ''],

                // Custom Code & Script Injection
                ['custom_css', 'scripts', ''],
                ['header_scripts', 'scripts', ''],
                ['body_scripts', 'scripts', ''],
                ['footer_scripts', 'scripts', ''],
            ];

            // Insert only if the key doesn't already exist (Preserves existing Title/Logo/Favicon!)
            $sql = "INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE `setting_group` = VALUES(`setting_group`)";

            foreach ($defaultSettings as $item) {$db->query($sql, [$item[0], $item[1],$item[2]]);
            }

            // 3. Create Lock File so this migration never runs again
            file_put_contents($this->schemaLockFile, json_encode([
                'module'      => 'settings_v2_seo_analytics_scripts',
                'migrated_at' => date('Y-m-d H:i:s'),
            ], JSON_PRETTY_PRINT));

        } catch (Throwable $e) {
            // Silently ignore if DB is not yet installed
        }
		
		
		try {
            $db     = Database::connect();
            $fields =$db->getFieldNames('settings');
            if (! in_array('setting_group', $fields, true)) {$db->query("ALTER TABLE `settings` ADD COLUMN `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general' AFTER `setting_key`");
            }

            $defaultSettings = [
                // General & Branding
                ['site_title', 'general', 'Core Platform'],
                ['site_tagline', 'general', 'Enterprise Modular Portal'],
                ['site_logo', 'general', null],
                ['site_favicon', 'general', null],
                ['contact_email', 'general', ''],

                // SEO & OpenGraph
                ['meta_title', 'seo', ''],
                ['meta_description', 'seo', ''],
                ['meta_keywords', 'seo', ''],
                ['canonical_url', 'seo', ''],
                ['robots_txt', 'seo', 'index, follow'],
                ['og_image', 'seo', null],

                // Analytics & Verification
                ['google_analytics_id', 'analytics', ''],
                ['google_tag_manager_id', 'analytics', ''],
                ['meta_pixel_id', 'analytics', ''],
                ['google_site_verification', 'analytics', ''],
                ['bing_site_verification', 'analytics', ''],
                ['meta_domain_verification', 'analytics', ''],

                // Custom Code
                ['custom_css', 'scripts', ''],
                ['header_scripts', 'scripts', ''],
                ['body_scripts', 'scripts', ''],
                ['footer_scripts', 'scripts', ''],

                // NEW: Appearance, Theme (Dark/Light), Colors, Fonts & Global Layout
                ['active_theme_mode', 'appearance', 'dark'],          // 'dark' or 'light' (Admin controlled)
                ['site_layout_width', 'appearance', 'boxed'],         // 'boxed' (Centered) or 'full' (100% Full-Width)
                ['default_block_surface', 'appearance', 'card'],      // 'card' or 'seamless'
                ['font_family_sans', 'appearance', 'Inter'],          // Inter, Poppins, Plus Jakarta Sans, Outfit, Space Grotesk
                ['font_family_mono', 'appearance', 'JetBrains Mono'], // JetBrains Mono, Fira Code
                ['primary_accent_color', 'appearance', '#10b981'],    // Main Brand Accent
                ['secondary_accent_color', 'appearance', '#06b6d4'],  // Secondary Brand Accent

                // Dark Theme Palette
                ['dark_bg_canvas', 'appearance', '#090a0f'],
                ['dark_bg_surface', 'appearance', '#11131c'],
                ['dark_text_primary', 'appearance', '#f3f4f6'],
                ['dark_border_color', 'appearance', '#232738'],

                // Light Theme Palette
                ['light_bg_canvas', 'appearance', '#f8fafc'],
                ['light_bg_surface', 'appearance', '#ffffff'],
                ['light_text_primary', 'appearance', '#0f172a'],
                ['light_border_color', 'appearance', '#e2e8f0'],
            ];

            $sql = "INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE `setting_group` = VALUES(`setting_group`)";

            foreach ($defaultSettings as $item) {$db->query($sql, [$item[0], $item[1],$item[2]]);
            }

            file_put_contents($this->schemaLockFile, json_encode([
                'module'      => 'settings_v3_theme_fonts_layout',
                'migrated_at' => date('Y-m-d H:i:s'),
            ], JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
        }
    }

    /**
     * Force Re-Initialize (Agar kabhi future mein naye columns/keys force sync karne hon)
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
     * Fetch All Key-Value Settings
     */
    public function getSystemSettings(): array
    {
        $this->ensureSchemaInitialized();

       $defaults = [
            'site_title'               => 'Core Platform',
            'site_tagline'             => '',
            'site_logo'                => null,
            'site_favicon'             => null,
            'contact_email'            => '',
            'meta_title'               => '',
            'meta_description'         => '',
            'meta_keywords'            => '',
            'canonical_url'            => '',
            'robots_txt'               => 'index, follow',
            'og_image'                 => null,
            'google_analytics_id'      => '',
            'google_tag_manager_id'    => '',
            'meta_pixel_id'            => '',
            'google_site_verification' => '',
            'bing_site_verification'   => '',
            'meta_domain_verification' => '',
            'custom_css'               => '',
            'header_scripts'           => '',
            'body_scripts'             => '',
            'footer_scripts'           => '',
            'active_theme_mode'        => 'dark',
            'site_layout_width'        => 'boxed',
            'default_block_surface'    => 'card',
            'font_family_sans'         => 'Inter',
            'font_family_mono'         => 'JetBrains Mono',
            'primary_accent_color'     => '#10b981',
            'secondary_accent_color'   => '#06b6d4',
            'dark_bg_canvas'           => '#090a0f',
            'dark_bg_surface'          => '#11131c',
            'dark_text_primary'        => '#f3f4f6',
            'dark_border_color'        => '#232738',
            'light_bg_canvas'          => '#f8fafc',
            'light_bg_surface'         => '#ffffff',
            'light_text_primary'       => '#0f172a',
            'light_border_color'       => '#e2e8f0',
        ];

        try {
            $db   = Database::connect();
            $rows =$db->table('settings')->get()->getResultArray();
            foreach ($rows as $row) {$defaults[$row['setting_key']] =$row['setting_value'];
            }
            return $defaults;
        } catch (Throwable $e) {
            return $defaults;
        }
    }

    /**
     * Bulk Update Settings in Database
     */
    public function updateSettingsBatch(array $data): bool
    {
        $this->ensureSchemaInitialized();

        try {
          $db  = Database::connect();$sql = "INSERT INTO `settings` (`setting_key`, `setting_value`) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)";
            $db->transStart();
            foreach ($data as $key =>$value) {
                $db->query($sql, [$key,$value]);
            }
            $db->transComplete();

            return $db->transStatus();
        } catch (Throwable $e) {
            return false;
        }
    }
}
<?php

namespace App\Models\Settings;

use CodeIgniter\Model;
use Config\Database;
use Throwable;

class SettingModel extends Model
{
    /**
     * Fetch Key-Value Settings (Website Title, Logo, Favicon)
     */
    public function getSystemSettings(): array
    {
        try {
            $db   = Database::connect();
            $rows = $db->table('settings')->get()->getResultArray();
            $out  = [
                'site_title'   => 'Core Platform',
                'site_logo'    => null,
                'site_favicon' => null,
            ];
            foreach ($rows as $row) {
                $out[$row['setting_key']] = $row['setting_value'];
            }
            return $out;
        } catch (Throwable $e) {
            return [
                'site_title'   => 'Core Platform',
                'site_logo'    => null,
                'site_favicon' => null,
            ];
        }
    }
}
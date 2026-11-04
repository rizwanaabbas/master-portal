<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $module = Module::where('name', 'FireblocksTransactionsMonitoring')->first();
        if (!$module) {
            return;
        }

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$configRecord || !$configRecord->configurations) {
            return;
        }

        $existing = unserialize($configRecord->configurations) ?: [];
        $key = 'low_balance_alert_recipient_roles';
        if (!isset($existing[$key]) || !is_array($existing[$key])) {
            return;
        }

        $raw = $existing[$key]['value'] ?? '';
        if (is_array($raw)) {
            $raw = implode(',', $raw);
        }

        $existing[$key]['value'] = self::toRoleIdList((string) $raw);
        $existing[$key]['hint'] = 'Users with any selected role receive email when Aquanow liquidity is too low (client balance OK) or Fireblocks custody vault balance rejects a withdrawal (client balance OK). Values are Spatie role IDs. Leave empty to use the finance role.';
        $existing[$key]['sample'] = '';

        $configRecord->configurations = serialize($existing);
        $configRecord->save();
    }

    public function down(): void
    {
        // Role IDs are left in place. Names with spaces cannot be restored safely.
    }

    protected static function toRoleIdList(string $raw): string
    {
        $tokens = array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($token) => $token !== ''));
        if ($tokens === []) {
            $tokens = ['finance'];
        }

        $roles = Role::query()->get(['id', 'name']);
        $ids = [];
        foreach ($tokens as $token) {
            if (ctype_digit($token)) {
                $ids[] = (int) $token;
                continue;
            }

            $match = $roles->first(fn ($role) => strtolower((string) $role->name) === strtolower($token));
            if ($match) {
                $ids[] = (int) $match->id;
            }
        }

        return implode(',', array_values(array_unique($ids)));
    }
};

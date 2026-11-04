<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class MonitoringAlertRecipientService
{
    /** @var array<int, int>|null */
    private static ?array $cachedRoleIds = null;

    /**
     * Spatie role IDs configured for liquidity (Aquanow) and custody (Fireblocks) low-balance alerts.
     *
     * @return array<int, int>
     */
    public static function recipientRoleIds(?array $config = null): array
    {
        if ($config !== null) {
            return self::normalizeRoleIds($config['low_balance_alert_recipient_roles'] ?? []);
        }

        if (self::$cachedRoleIds !== null) {
            return self::$cachedRoleIds;
        }

        $resolved = MonitoringConfigService::tryGet();
        self::$cachedRoleIds = self::normalizeRoleIds(
            is_array($resolved) ? ($resolved['low_balance_alert_recipient_roles'] ?? []) : []
        );

        return self::$cachedRoleIds;
    }

    public static function usersForLowBalanceAlerts(?array $config = null): Collection
    {
        $roleIds = self::recipientRoleIds($config);
        if ($roleIds === []) {
            return collect();
        }

        $rolesTable = config('permission.table_names.roles', 'roles');

        return User::whereHas('roles', function ($q) use ($roleIds, $rolesTable) {
            $q->whereIn($rolesTable . '.id', $roleIds);
        })->get()->unique('email');
    }

    public static function flush(): void
    {
        self::$cachedRoleIds = null;
    }

    /**
     * Accept stored role IDs, or legacy comma-separated role names (spaces kept inside a name).
     * An empty value falls back to the role named finance.
     *
     * @param  array<int, int|string>|string|null  $roles
     * @return array<int, int>
     */
    public static function normalizeRoleIds(array|string|null $roles): array
    {
        $tokens = self::roleTokens($roles);
        $ids = self::resolveRoleIds($tokens);

        if ($ids === [] && $tokens === []) {
            $ids = self::resolveRoleIds(['finance']);
        }

        return $ids;
    }

    /**
     * @param  array<int, int|string>|string|null  $roles
     * @return array<int, string>
     */
    protected static function roleTokens(array|string|null $roles): array
    {
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }
        if (!is_array($roles)) {
            return [];
        }

        $tokens = [];
        foreach ($roles as $role) {
            $token = trim((string) $role);
            if ($token !== '' && strtolower($token) !== 'array') {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, int>
     */
    protected static function resolveRoleIds(array $tokens): array
    {
        if ($tokens === []) {
            return [];
        }

        $wantedIds = [];
        $wantedNames = [];
        foreach ($tokens as $token) {
            if (ctype_digit($token)) {
                $wantedIds[] = (int) $token;
            } else {
                $wantedNames[] = strtolower($token);
            }
        }

        if ($wantedNames !== []) {
            foreach (Role::query()->get(['id', 'name']) as $role) {
                if (in_array(strtolower((string) $role->name), $wantedNames, true)) {
                    $wantedIds[] = (int) $role->id;
                }
            }
        }

        return array_values(array_unique(array_filter($wantedIds)));
    }
}

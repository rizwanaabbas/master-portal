<?php

namespace App\Services;

use App\Models\Module;
use App\Models\PermissionModuleGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionModuleGroupOrganizer
{
    public static function syncGroupsFromModules(): Collection
    {
        $general = PermissionModuleGroup::firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General', 'sort_order' => 0]
        );

        $sort = 10;
        foreach (Module::query()->orderBy('name')->get() as $module) {
            $name = trim((string) $module->name);
            if ($name === '') {
                continue;
            }
            PermissionModuleGroup::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $sort]
            );
            $sort += 10;
        }

        return PermissionModuleGroup::query()->orderBy('sort_order')->orderBy('name')->get();
    }

    public static function assignUngroupedPermissions(): void
    {
        $groups = PermissionModuleGroup::query()->get();
        if ($groups->isEmpty()) {
            return;
        }
        $generalId = optional($groups->firstWhere('slug', 'general'))->id;

        foreach (Permission::query()->whereNull('module_group_id')->get() as $permission) {
            $permission->module_group_id = self::guessGroupId((string) $permission->name, $groups) ?: $generalId;
            $permission->save();
        }
    }

    public static function guessGroupId(string $permissionName, ?Collection $groups = null): ?int
    {
        $groups = $groups ?: PermissionModuleGroup::query()->get();
        $haystack = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $permissionName));
        $bestId = null;
        $bestScore = 0;

        foreach ($groups as $group) {
            if ($group->slug === 'general') {
                continue;
            }
            $label = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', (string) $group->name));
            $score = 0;
            if ($label !== '' && str_contains($haystack, $label)) {
                $score = 20 + strlen($label);
            } else {
                foreach (preg_split('/\s+/', $label, -1, PREG_SPLIT_NO_EMPTY) as $token) {
                    if (strlen($token) < 3) {
                        continue;
                    }
                    if (preg_match('/\b' . preg_quote($token, '/') . '\b/', $haystack)) {
                        $score += strlen($token);
                    }
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = (int) $group->id;
            }
        }

        return $bestId;
    }
}

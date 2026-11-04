<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Permission;

class PermissionModuleGroup extends Model
{
    protected $table = 'permission_module_groups';

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'module_group_id');
    }

    public static function general(): ?self
    {
        return static::query()->where('slug', 'general')->first();
    }
}

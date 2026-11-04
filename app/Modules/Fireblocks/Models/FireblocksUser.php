<?php

namespace App\Modules\Fireblocks\Models;

use Illuminate\Database\Eloquent\Model;

class FireblocksUser extends Model
{
    protected $table = 'fireblocks_users';

    protected $fillable = [
        'fireblocks_user_id',
        'first_name',
        'last_name',
        'email',
        'role',
        'badge_color',
        'enabled',
        'raw_response',
        'last_synced_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'raw_response' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function displayName(): string
    {
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));

        return $name !== '' ? $name : (string) $this->fireblocks_user_id;
    }

    public function resolvedBadgeColor(): string
    {
        $color = strtoupper(trim((string) ($this->badge_color ?? '')));
        if (preg_match('/^#[0-9A-F]{6}$/', $color)) {
            return $color;
        }

        return '#6C757D';
    }

    public function badgeHtml(?string $label = null): string
    {
        $label = $label !== null && $label !== '' ? $label : $this->displayName();
        $bg = $this->resolvedBadgeColor();
        $fg = self::contrastColor($bg);

        return '<span class="badge" style="background-color:' . e($bg) . ';color:' . e($fg) . ';" title="'
            . e($this->fireblocks_user_id) . '">' . e($label) . '</span>';
    }

    public static function unmappedBadgeHtml(string $userId): string
    {
        return '<span class="badge badge-secondary" title="No directory name">' . e($userId) . '</span>';
    }

    public static function contrastColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#ffffff';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luma = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);

        return $luma > 160 ? '#212529' : '#ffffff';
    }
}

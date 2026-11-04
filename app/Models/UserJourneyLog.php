<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserJourneyLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'session_id',
        'route_path',
        'method',
        'user_agent',
        'ip_address',
        'ip_v4',
        'ip_v6',
        'ip_vpn',
        'ip_forwarded',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'device_type',
        'device_name',
        'browser',
        'os',
        'referrer',
        'page_title',
        'channel',
        'event_name',
        'screen_name',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

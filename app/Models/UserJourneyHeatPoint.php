<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserJourneyHeatPoint extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'session_id',
        'route_path',
        'x',
        'y',
        'channel',
        'created_at',
    ];
}

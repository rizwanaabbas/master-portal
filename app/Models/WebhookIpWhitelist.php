<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;

class WebhookIpWhitelist extends Model
{
    use HasFactory;

    protected $table = "webhook_ip_whitelists";
    protected $casts = [
        'service' => CleanHtml::class,
        'ip_address' => CleanHtml::class,
        'whitelisted_for' => CleanHtml::class,
        'description' => CleanHtml::class,
    ];
}

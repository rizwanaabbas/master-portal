<?php

namespace App\Modules\Fireblocks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FireblocksVaultAccount extends Model
{
    use HasFactory;

    protected $table = 'fireblocks_vault_accounts';

    protected $fillable = [
        'client_id',
        'vault_title',
        'vault_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

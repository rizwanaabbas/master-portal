<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'show_on_front',
        'status',
    ];

    // Optional constants or enums
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $casts = [
        'show_on_front' => 'boolean',
    ];

    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function accountTransactionRequests()
    {
        return $this->hasMany(TRRequest::class);
    }
    
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    protected static function booted()
    {
        static::addGlobalScope('activeModule', function ($query) {
            $query->whereNull('module_id')
                ->orWhereHas('module', function ($q) {
                    $q->where('is_enabled', '1');
                });
        });
    }
}


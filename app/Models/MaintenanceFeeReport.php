<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceFeeReport extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope('not_deleted', function (Builder $builder) {
            $builder->where($builder->getModel()->qualifyColumn('is_deleted'), false);
        });
    }
    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }
}

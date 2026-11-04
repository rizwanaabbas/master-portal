<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TRRequestDoc extends Model
{
    use HasFactory;

    protected $table = "accoun_transaction_request_docs";

    protected static function booted()
    {
        static::addGlobalScope('not_deleted', function (Builder $builder) {
            $builder->where($builder->getModel()->qualifyColumn('is_deleted'), false);
        });
    }
}

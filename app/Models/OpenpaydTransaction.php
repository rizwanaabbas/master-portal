<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenpaydTransaction extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = "openpayd_transactions";

    public function getTRrequest()
    {
        return $this->hasOne(TRRequest::class, 'id', 'trn_id');
    }

    public function getAccountTransaction() 
    {
        return $this->hasOne(AccountTransaction::class, 'id', 'parent_transaction_id');
    }
}

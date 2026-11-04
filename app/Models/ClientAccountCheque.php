<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientAccountCheque extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = true;
    protected $table = "client_account_cheque_details";
}

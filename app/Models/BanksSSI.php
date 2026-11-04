<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BanksSSI extends Model
{
    use HasFactory;
    protected $table = "banks_ssi";

    protected $fillable = ['bank_id', 'currency_id', 'ssi_details', 'created_by', 'updated_by'];


    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id', 'id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

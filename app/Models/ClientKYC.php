<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientKYC extends Model
{
    use HasFactory;

    protected $table = "client_kyc_records";

    protected $fillable = [
        'client_id',
        'sumsub_kyc_status',
        'sumsub_kyc_answer',
        'sumsub_kyc_reject_reason',
        'sumsub_applicant_id',
        'director_id'
    ];

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
}

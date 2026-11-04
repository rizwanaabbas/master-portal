<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientDirector extends Model
{
    use HasFactory;

    protected $table = 'client_directors';

    protected $fillable = [
        'name',
        'email',
        'passport_number',
        'passport_expiry',
        'dob',
        'gender',
        'nationality_id',
        'residence_country_id',
        'client_id',
        'user_id',
        'type',
        'is_deleted',
        'created_by',
        'created_at',
        'updated_at',
        'updated_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    public function kyc_requests()
    {
        return $this->hasOne(KycRequest::class, 'director_id')->latest();
    }
    public function kyc()
    {
        return $this->hasOne(ClientKyc::class, 'director_id')->latest();
       
    }
    public function sanction_report()
    {
        return $this->hasOne(SanctionsReport::class, 'director_id')->whereNotNull('director_id')->latest();
        
       
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function findKyc()
    {
        // Try to find KYC using director_id
        $kyc = $this->kyc()->first();
        
        // If no KYC found, try to find using client_id
        if (!$kyc) {
            $kyc = ClientKyc::where('client_id', $this->client_id)->latest()->first();
        }
        
        return $kyc;
    }
}

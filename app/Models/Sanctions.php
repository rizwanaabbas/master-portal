<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sanctions extends Model
{
    protected $fillable = ['user_id', 'name', 'entity_type', 'screen_by', 'nationality_iso2', 'email', 'citizenship_iso2', 'place_of_birth_iso2', 'country_residence_iso2', 'dob', 'passport_number', 'passport_expiry_date', 'is_suspicious', 'result', 's_response', 'w_response', 'w_passport_response', 'worldCheck', 'sanctions_io', 'case_id', 'resolved', 'resolved_by', 'resolved_at', 'blocked', 'blocked_by', 'blocked_at', 'gender', 'passport_issuing_state', 'report_id', 'passport_case_id', 'report_url'];

    use HasFactory;

    public function accountTransactionRequest()
    {
        return $this->belongsTo(AccountTransaction::class, 'case_id');
    }

    public function report()
    {
        return $this->hasOne(SanctionsReport::class, 'report_id');
    }
}

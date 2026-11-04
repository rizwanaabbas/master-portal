<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRiskRating extends Model
{
    use HasFactory;

    protected $table = 'client_risk_rating';

    protected $fillable = [
        'client_id',
        'scoring_data',
        'risk_score',
        'remarks',
        'created_by',
        'updated_by',
        'status',
    ];

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
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

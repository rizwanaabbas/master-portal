<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientSignatory extends Model
{
    use HasFactory;

    protected $table = 'client_signatories';

    protected $fillable = [
        'name',
        'email',
        'passport_number',
        'passport_expiry',
        'dob',
        'gender',
        'nationality_id',
        'residence_country_id',
        'user_id',
        'client_id',
        'created_by',
        'created_at',
        'updated_at',
        'updated_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'id','client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycRequest extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kyc_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       
        'hash',
        'created_by',
        'created_at',
        'updated_at',
        'expiry',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'expiry',
    ];
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function director() {
        return $this->hasOne(ClientDirector::class, 'id','director_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FingateClient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'fingate_clients';

    protected $fillable = [
        'customer_id',
        'client_id',
    ];
   
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
}

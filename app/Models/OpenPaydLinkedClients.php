<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenPaydLinkedClients extends Model
{
    use HasFactory;
    protected $table = 'openpayd_linked_clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'linked_client_id',
        'client_id',
        // Add other table columns here if needed
    ];

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
    
    public function account()
    {
        return $this->belongsTo(Account::class, 'client_id', 'id');
    }
    
    public function getAllAccounts()
    {
        return $this->hasMany(Account::class, 'client_id', 'client_id');
    }

    public function sepaAccount()
    {
        return $this->hasMany(Account::class, 'client_id', 'id')->whereNotNull('openpayd_account_id');
    }
}

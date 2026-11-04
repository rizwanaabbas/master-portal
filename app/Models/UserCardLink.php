<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCardLink extends Model
{
    use HasFactory;

    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function owner()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function cardDetails()
    {
        return $this->hasOne(CardDetail::class, 'id', 'detail_id');
    }

    public function linkedCard()
    {
        return $this->hasOne(AssignedCard::class, 'id', 'assigned_card_id');
    }
}

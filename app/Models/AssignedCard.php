<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignedCard extends Model
{
    use HasFactory;

    public function link()
    {
        return $this->hasOne(UserCardLink::class, 'assigned_card_id', 'id');
    }
    
    public function cardDetails()
    {
        return $this->hasOne(CardDetail::class, 'mpin', 'mpin');
        // hasOne(RelatedModel::class, foreign_key_on_related, local_key_on_this_model)
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingFeeRecords extends Model
{
    protected $fillable = ['client_id', 'order_id',  'transaction_status', 'transaction_amount', 'currency_code', 'reference_number',  'payment_link_url', 'payment_link_token', 'transaction_date', 'transaction_error_description', 'error_description', 'is_transaction_succeeded'];

    use HasFactory;

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
}

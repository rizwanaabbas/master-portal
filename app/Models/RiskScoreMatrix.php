<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskScoreMatrix extends Model
{
    use HasFactory;

    // Table name (optional if the table name follows Laravel's naming convention)
    protected $table = 'risk_score_matrix'; // Adjust the table name if necessary

    // The attributes that are mass assignable
    protected $fillable = [
        'country', 'score', 'risk_level', 'additional_field_1', 'additional_field_2', // Add fields as per your CSV structure
    ];

    // If using timestamps (created_at, updated_at), set to true, if not, set to false
    public $timestamps = false;
}

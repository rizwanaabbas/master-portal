<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanctionsReport extends Model
{
    protected $fillable = ['client_id', 'case_system_id', 'case_report_id', 'status', 'url', 'requested'];

    use HasFactory;
}

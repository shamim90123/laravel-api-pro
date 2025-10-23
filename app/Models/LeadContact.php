<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadContact extends Model
{
    use HasFactory;

    // protected $fillable = ['name', 'email', 'phone', 'job_title', 'department', 'primary_status', 'lead_id'];
    protected $fillable = [
    'lead_id', 'first_name', 'last_name', 'name', 'email', 'phone', 'job_title', 'department',
    'primary_status', // legacy
    'is_primary',
];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}

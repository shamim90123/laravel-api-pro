<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadContact extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'job_title', 'department', 'primary_status'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}

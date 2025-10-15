<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    protected $fillable = ['lead_name', 'destination_id', 'city'];

    public function contacts()
    {
        return $this->hasMany(LeadContact::class); // Define the relationship to LeadContact
    }
}

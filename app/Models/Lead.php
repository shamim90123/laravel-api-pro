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

    public function comments()
    {
        return $this->hasMany(\App\Models\LeadComment::class);
    }

    public function destination()
    {
        return $this->belongsTo(\App\Models\Country::class, 'destination_id');
    }

    // public function leadStage()
    // {
    //     return $this->belongsTo(\App\Models\LeadStage::class, 'lead_stage_id');
    // }

    public function leadProducts()
    {
        return $this->hasMany(LeadProduct::class);
    }


    public function products()
    {
        return $this->belongsToMany(Product::class, 'lead_products')
                    ->withTimestamps();
    }
}

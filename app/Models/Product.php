<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];


    // public function leads()
    // {
    //     return $this->belongsToMany(Lead::class, 'lead_products')
    //                 ->withTimestamps();
    // }

    public function leads()
{
    return $this->belongsToMany(Lead::class, 'lead_products')
        ->withPivot(['stage_id', 'account_manager_id'])
        ->withTimestamps();
}
}

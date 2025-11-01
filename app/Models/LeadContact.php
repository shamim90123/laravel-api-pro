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
        'is_primary', 'item_id', 'link'
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function products()
    {
        // Example if pivot table is lead_contact_product or contact_product
        return $this->belongsToMany(Product::class, 'lead_products', 'contact_id', 'product_id');
    }
}

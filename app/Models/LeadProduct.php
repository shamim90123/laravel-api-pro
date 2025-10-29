<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadProduct extends Model
{
    use HasFactory;

    protected $table = 'lead_products'; // optional, if your table name is different

    // Allow mass assignment for these fields
    protected $fillable = [
        'lead_id',
        'product_id',
        'stage_id',
        'account_manager_id',
        'notes',
        'contact_id',

    ];

    // (optional) If you prefer to protect none:
    // protected $guarded = [];

    // Define relationships if needed
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function saleStage()
    {
        return $this->belongsTo(SaleStage::class, 'stage_id');
    }
    
    public function contact()
    {
        return $this->belongsTo(LeadContact::class, 'contact_id');
    }
}

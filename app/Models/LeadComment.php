<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'user_id',     // points to users.id OR lead_contacts.id (see user_type)
        'comment',
        'user_type',   // 1 = User, 2 = LeadContact
    ];

    protected $casts = [
        'user_type' => 'int',
    ];

    // auto-append a unified author object to JSON
    protected $appends = ['author'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    // Staff user relation
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Lead contact relation (shares the same user_id column)
    public function contact()
    {
        return $this->belongsTo(LeadContact::class, 'user_id');
    }

    /**
     * Unified author accessor.
     * Returns: ['type' => 'user'|'lead_contact', 'id' => int|null, 'name' => string]
     *
     * NOTE: To avoid N+1, make sure controllers eager-load:
     *   ->with(['user:id,name', 'contact:id,name'])
     */
    public function getAuthorAttribute(): array
    {
        if ($this->user_type == 1 && $this->relationLoaded('user') && $this->user) {
            return [
                'type' => 'user',
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ];
        }

        if ($this->user_type == 2 && $this->relationLoaded('contact') && $this->contact) {
            return [
                'type' => 'lead_contact',
                'id'   => $this->contact->id,
                'name' => $this->contact->name,
            ];
        }

        // Fallback when relations aren’t loaded (keeps response shape stable)
        return [
            'type' => $this->user_type === 2 ? 'lead_contact' : 'user',
            'id'   => $this->user_id,
            'name' => 'Unknown',
        ];
    }
}

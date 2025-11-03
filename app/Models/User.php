<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    // Spatie guard to match your seeder/config
    protected $guard_name = 'web';

    /**
     * Mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image_url',
        'image_path',
    ];

    /**
     * Hide sensitive fields from JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // If you want to hide full roles relation; we expose computed lists instead
        // 'roles',
        // 'permissions',
    ];

    /**
     * Append computed accessors to JSON.
     *
     * @var list<string>
     */
    protected $appends = [
        'primary_role',
        'roles_list',
        'permissions_list',
    ];

    /**
     * Casts.
     *
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ===== Accessors for your frontend =====

    public function getPrimaryRoleAttribute(): ?string
    {
        // first role name or null
        return $this->roles->pluck('name')->first();
    }

    public function getRolesListAttribute(): array
    {
        // ["admin","manager",...]
        return $this->getRoleNames()->values()->all();
    }

    public function getPermissionsListAttribute(): array
    {
        // ["leads.view","products.update",...]
        return $this->getAllPermissions()->pluck('name')->values()->all();
    }

    // ===== Notifications =====

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // Add image accessor
    public function getImageAttribute()
    {
        return $this->image_url ?: null;
    }
}

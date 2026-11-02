<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_verified',
        'password_reset_required', 'client_organization_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'               => 'hashed',
            'is_verified'            => 'boolean',
            'password_reset_required'=> 'boolean',
        ];
    }

    public function clientOrganization()
    {
        return $this->belongsTo(ClientOrganization::class);
    }

    public function cases()
    {
        return $this->hasMany(Cases::class, 'created_by');
    }

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isClient(): bool   { return in_array($this->role, ['organization', 'individual']); }
}

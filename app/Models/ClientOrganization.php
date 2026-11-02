<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientOrganization extends Model
{
    protected $fillable = [
        'name', 'gstin', 'contact_person', 'contact_email', 'contact_phone', 'address', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function cases()
    {
        return $this->hasMany(Cases::class);
    }
}

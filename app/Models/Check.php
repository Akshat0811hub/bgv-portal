<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Check extends Model
{
    protected $fillable = ['name', 'slug', 'category', 'description', 'schema', 'active'];

    protected $casts = ['schema' => 'array', 'active' => 'boolean'];
}

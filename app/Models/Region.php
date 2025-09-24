<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function counties(): HasMany
    {
        return $this->hasMany(County::class);
    }

    public function communities(): HasMany
    {
        return $this->hasMany(Community::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

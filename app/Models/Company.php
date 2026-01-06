<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['name' , 'rating'];

    protected $attributes = [
        'name' => '',
        'rating' => 0,
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}

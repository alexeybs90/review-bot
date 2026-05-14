<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['name' , 'rating', 'email', 'phone',
        'address', 'website', 'description', 'is_dealer', 'inn', 'company_type'];

    protected $attributes = [
        'name' => '',
        'rating' => 0,
        'email' => '',
        'phone' => '',
        'address' => '',
        'website' => '',
        'description' => '',
        'is_dealer' => 0,
        'inn' => '',
        'company_type' => '',
    ];

    protected $casts = [
        'is_dealer' => 'boolean',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CompanyImage::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyImage extends Model
{
    protected $fillable = ['path', 'company_id', 'original_name'];
    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

}

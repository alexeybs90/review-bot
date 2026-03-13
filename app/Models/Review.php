<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = ['chat_id', 'company_id', 'grade', 'comment'];

    protected $attributes = [
        'grade' => 0, 'comment' => '',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewFiles(): HasMany
    {
        return $this->hasMany(ReviewFile::class);
    }
}

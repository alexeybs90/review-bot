<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewFile extends Model
{
    protected $fillable = ['review_id', 'file_id', 'file_unique_id', 'path'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}

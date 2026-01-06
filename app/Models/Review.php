<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}

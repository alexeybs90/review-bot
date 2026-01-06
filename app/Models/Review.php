<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['chat_id', 'company_id', 'grade', 'comment'];

    protected $attributes = [
        'grade' => 0, 'comment' => '',
    ];
}

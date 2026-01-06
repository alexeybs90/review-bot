<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Context extends Model
{
    protected $fillable = ['chat', 'status', 'company_id', 'grade'];

    protected $attributes = [
        'chat' => '', 'status' => '', 'company_id' => 0, 'grade' => 0
    ];
}

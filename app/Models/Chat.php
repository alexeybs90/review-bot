<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = ['chat', 'name' , 'phone'];

    protected $attributes = [
        'chat' => '',
        'name' => '',
        'phone' => '',
    ];
}

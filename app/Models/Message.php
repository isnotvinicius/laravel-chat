<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillale = ['chat_id', 'user_id', 'content'];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

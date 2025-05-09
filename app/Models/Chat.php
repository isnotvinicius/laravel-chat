<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'chats';

    protected $fillable = ['name'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_user')->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}

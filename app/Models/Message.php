<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'chat_messages';
    
    protected $fillable = [
        'conversation_id',
        'emisor_id',
        'content',
        'read_at'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function emisor()
    {
        return $this->belongsTo(User::class, 'emisor_id');
    }
}
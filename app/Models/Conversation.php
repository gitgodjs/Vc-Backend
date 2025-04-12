<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $table = 'chat_conversations';
    
    protected $fillable = [
        'emisor_id',
        'receptor_id'
    ];

    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    // Relación para obtener el último mensaje
    public function latestMessage()
    {
        return $this->hasOne(Message::class, 'conversation_id')->latest();
    }

    // Relación para obtener mensajes no leídos
    public function unreadMessages()
    {
        return $this->hasMany(Message::class, 'conversation_id')
            ->where('read_at', null)
            ->where('emisor_id', '!=', auth()->id());
    }

    public function emisor()
    {
        return $this->belongsTo(User::class, 'emisor_id');
    }

    public function receptor()
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    public function scopeBetweenUsers($query, $userId1, $userId2)
    {
        return $query->where(function($q) use ($userId1, $userId2) {
            $q->where('emisor_id', $userId1)
              ->where('receptor_id', $userId2);
        })->orWhere(function($q) use ($userId1, $userId2) {
            $q->where('emisor_id', $userId2)
              ->where('receptor_id', $userId1);
        });
    }
}
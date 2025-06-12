<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatConversacion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'chat_conversations';

    protected $fillable = [
        'emisor_id',
        'receptor_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 🔗 Emisor de la conversación
    public function emisor()
    {
        return $this->belongsTo(User::class, 'emisor_id');
    }

    // 🔗 Receptor de la conversación
    public function receptor()
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    // 🔗 Mensajes de la conversación
    public function mensajes()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;

use App\Events\NewMessage;
use App\Events\NewMessageNotification;

class ChatController extends Controller
{

    public function obtenerChats() {
        $user = auth()->user();
        if(!$user) {
            return response()->json([
                'mensaje' => "No se pudo encontrar al usuario",
            ], 404);
        };
    
        // Cargamos las conversaciones con las relaciones necesarias
        $conversations = $user->conversations()
            ->with([
                'emisor', 
                'receptor',
                'latestMessage' => function($query) {
                    $query->latest(); // Obtener el mensaje más reciente
                },
                'unreadMessages' => function($query) use ($user) {
                    $query->where('read_at', null)
                          ->where('emisor_id', '!=', $user->id);
                }
            ])
            ->get();
    
        foreach ($conversations as $conversation) {
            // Contar mensajes no leídos
            $conversation->unread_count = $conversation->unreadMessages->count();
            
            // Obtener el último mensaje
            $conversation->last_message = $conversation->latestMessage;
            
            $conversation->emisor->foto_perfil_url = $conversation->emisor->getFotoPerfilUrl();
            $conversation->receptor->foto_perfil_url = $conversation->receptor->getFotoPerfilUrl();
            
            // Eliminar relaciones que no necesitamos en la respuesta
            unset($conversation->unreadMessages);
            unset($conversation->latestMessage);
        }
    
        return response()->json([
            "conversations" => $conversations,
            'mensaje' => "Chat existente encontrado",
        ], 200);
    }

    public function obtenerConversation(Request $request, $conversation_id) {
        $user = auth()->user();
        if(!$user) {
            return response()->json([
                'mensaje' => "No se pudo encontrar al usuario",
            ], 404);
        };
        $conversation = Conversation::find($conversation_id);

        return response()->json([
            "conversation" => $conversation,
            'mensaje' => "Chat existente encontrado",
        ], 200);
    }
    
    public function ofertar(Request $request)
    {
        $emisor = auth()->user();
        $receptor = User::findOrFail($request->publicacion['creador']['id']);

        // Buscar o crear conversación
        $conversation = Conversation::firstOrCreate(
            [
                'emisor_id' => $emisor->id,
                'receptor_id' => $receptor->id
            ],
            ['created_at' => now()]
        );

        // Crear mensaje
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'emisor_id' => $emisor->id,
            'content' => $request->mensaje
        ]);

        return response()->json([
            'conversation' => $conversation,
            'message' => $message,
            'status' => $conversation->wasRecentlyCreated ? 'created' : 'existing'
        ], $conversation->wasRecentlyCreated ? 201 : 200);
    }

    // Enviar mensaje
    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:chat_conversations,id',
            'content' => 'required|string|max:1000'
        ]);

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'emisor_id' => Auth::id(),
            'content' => $request->content
        ]);

        // Disparar evento
        broadcast(new NewMessage($message))->toOthers();

        return response()->json($message->load('sender'), 201);
    }

    // Marcar mensajes como leídos
    public function markAsRead($conversationId)
    {
        Message::where('conversation_id', $conversationId)
               ->where('emisor_id', '!=', Auth::id())
               ->whereNull('read_at')
               ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
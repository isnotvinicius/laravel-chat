<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;

class ChatController extends Controller
{
    public function index()
    {
        $chats = Chat::all();
        $userChats = Auth::user()->chats;
        return view('chat', compact('chats', 'userChats'));
    }

    public function join(Chat $chat)
    {
        Auth::user()->chats()->attach($chat->id);
        return redirect()->route('chat.index');
    }

    public function leave(Chat $chat)
    {
        Auth::user()->chats()->detach($chat->id);
        return redirect()->route('chat.index');
    }

    public function sendMessage(Request $request, Chat $chat)
    {
        $request->validate(['content' => 'required|string|max:1000']);

        $message = $chat->messages()->create([
            'user_id' => Auth::user()->id,
            'content' => $request->content
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['message' => 'Message Sent']);
    }
}

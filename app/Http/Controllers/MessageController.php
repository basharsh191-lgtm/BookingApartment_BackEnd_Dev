<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function sender(Request $request)
    {
        $request->validate([
            'receiver_id'=>'required|exists:users,id',
            'message'=>'required|string'
        ]);
        $message=Message::with('receiver:id,FirstName,LastName,mobile')->create([
            'sender_id'=>Auth::id(),
            'receiver_id'=>$request->receiver_id,
            'message'=>$request->message
        ]);
        $message->load('receiver:id,FirstName,LastName,mobile');
        return response()->json([
            'succsess'=>'true',
            'data'=>$message,
        ], 200);
    }
        public function conversation($receiverId)
    {
        $userId = Auth::id();

        $messages = Message::with('sender:id,FirstName,LastName,mobile')->where(function ($q) use ($userId, $receiverId)
            {
                $q->where('sender_id', $userId)
                ->where('receiver_id', $receiverId);
            })
            ->orWhere(function ($q) use ($userId, $receiverId)
            {
                $q->where('sender_id', $receiverId)
                ->where('receiver_id', $userId);
            })
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data'=>$messages,
        ]);
    }

}

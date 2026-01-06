<?php

namespace App\Repositories;

use App\Models\Chat;

class ChatRepository
{
    public function find(int $id)
    {
        return Chat::find($id);
    }

    public function findByChat(string $chat)
    {
        return Chat::where('chat', $chat)->first();
    }

    public function create(string $chat, string $name)
    {
        return Chat::create(['chat' => $chat, 'name' => $name]);
    }

    public function save(Chat $chat): bool
    {
        return $chat->save();
    }

    public function findOrCreateUser(string $chat, string $name): Chat
    {
        $user = $this->findByChat($chat);
//        Log::debug('user = ' . $user->id . ', ' . $user->chat . ', ' . $chat);
        if ($user && $user->id && $user->chat === $chat) return $user;
        return $this->create($chat, $name);
    }
}

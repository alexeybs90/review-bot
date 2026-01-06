<?php

namespace App\Repositories;

use App\Models\Context;

class ContextRepository
{
    public function findByChat($chat)
    {
        return Context::where('chat', $chat)->first();
    }

    public function delete(Context $context): ?bool
    {
        return $context->delete();
    }

    public function save(Context $context): bool
    {
        return $context->save();
    }

    public function reset($chat): ?bool
    {
        $context = $this->findByChat($chat);
        return $context?->delete();
    }
}

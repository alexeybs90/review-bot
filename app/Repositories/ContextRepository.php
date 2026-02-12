<?php

namespace App\Repositories;

use App\Models\Context;
use Illuminate\Support\Facades\Log;

class ContextRepository
{
    public function findByChat($chat): ?Context
    {
        $context = Context::where('chat', $chat)->first();
        Log::debug('read session context:' . ($context ?
            ' id=' . $context->id
            . ', chat=' . $context->chat
            . ', company_id=' . $context->company_id
            . ', grade=' . $context->grade
            . ', status=' . $context->status
            . ', comment=' . $context->comment
            . ', files=' . $context->files
            : '')
        );
        return $context;
    }

    public function delete(Context $context): ?bool
    {
        return $context->delete();
    }

    public function save(Context $context): bool
    {
        $saved = $context->save();
        Log::debug('set session context: id=' . $context->id
            . ', chat=' . $context->chat
            . ', company_id=' . $context->company_id
            . ', grade=' . $context->grade
            . ', status=' . $context->status
            . ', comment=' . $context->comment
            . ', files=' . $context->files);
        return $saved;
    }

    public function reset($chat): ?bool
    {
        $context = $this->findByChat($chat);
        return $context?->delete();
    }
}

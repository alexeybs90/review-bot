<?php

namespace App\DTO;

class TelegramUpdate
{
    public function __construct(
        public string $chatId,
        public array $message,
        public ?array $callbackQuery,
        public string $text,
        public string|int $messageId,
        public string $name,
        public string $phone,
    )
    {}

    public static function fromArray(array $data): self
    {
        $message = $data['message'] ?? null;
        $callbackQuery = $data['callback_query'] ?? null;

        if (!$message && $callbackQuery) $message = $callbackQuery['message'];

        return new self(
            chatId: (string)$message['chat']['id'],
            message: $message,
            callbackQuery: $callbackQuery,
            text: $message['text'] ?? '',
            messageId: $message['message_id'] ?? '',
            name: $message['from']['first_name'] ?? '',
            phone: $message['contact']['phone_number'] ?? '',
        );
    }
}

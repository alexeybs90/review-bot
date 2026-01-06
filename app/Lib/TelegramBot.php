<?php

namespace App\Lib;

use Illuminate\Support\Facades\Http;

class TelegramBot
{
    protected string $api_key = '';
    protected string $api_webhook_url = '';

    public function __construct(string $api_key, string $api_webhook_url)
    {
        $this->api_key = $api_key;
        $this->api_webhook_url = $api_webhook_url;
    }

    public function apiUrl(): string
    {
        return 'https://api.telegram.org/bot' . $this->api_key . '/';
    }

    public function info()
    {
        return Http::get($this->apiUrl() . 'getWebhookInfo')->json();
    }

    public function sendMessage(string $chat, string $text, array $reply_markup = null)
    {
        $data = [
            'chat_id' => $chat,
            'text' => $text,
        ];
        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }
        return Http::get($this->apiUrl() . 'sendMessage', $data)->json();
    }

    public function setWebhook()
    {
        return Http::get($this->apiUrl() . 'setWebhook', [
            'url' => $this->api_webhook_url,
        ])->json();
    }
}

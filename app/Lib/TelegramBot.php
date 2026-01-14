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
        return Http::post($this->apiUrl() . 'sendMessage', $data)->json();
    }

    public function sendPhotos(string $chat, $files)
    {
        $media = [];
        foreach ($files as $file) {
            $media[] = ['type' => 'photo', 'media' => $file->file_id];
        }
        $data = [
            'chat_id' => $chat,
            'media' => $media,
        ];
        return Http::post($this->apiUrl() . 'sendMediaGroup', $data)->json();
    }

    public function editMessageText(string $chat, int $message_id, string $text, array $reply_markup = null)
    {
        $data = [
            'chat_id' => $chat,
            'message_id' => $message_id,
            'text' => $text,
            'reply_markup' => $reply_markup ? json_encode($reply_markup) : null
        ];
        return Http::post($this->apiUrl() . 'editMessageText', $data)->json();
    }

    public function setWebhook()
    {
        return Http::get($this->apiUrl() . 'setWebhook', [
            'url' => $this->api_webhook_url,
        ])->json();
    }

    public function getFile(string $file_id)
    {
        $data = [
            'file_id' => $file_id,
        ];
        return Http::post($this->apiUrl() . 'getFile', $data)->json();
    }

    public function fileUrl($file_path)
    {
        return 'https://api.telegram.org/file/bot' . $this->api_key . '/' . $file_path;
    }
}

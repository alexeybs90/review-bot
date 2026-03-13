<?php

namespace App\Lib;

use Illuminate\Support\Facades\Http;

class TelegramBot
{
    protected string $apiKey = '';
    protected string $apiWebhookUrl = '';

    public function __construct(string $apiKey, string $apiWebhookUrl)
    {
        $this->apiKey = $apiKey;
        $this->apiWebhookUrl = $apiWebhookUrl;
    }

    public function apiUrl(): string
    {
        return 'https://api.telegram.org/bot' . $this->apiKey . '/';
    }

    public function info()
    {
        return Http::get($this->apiUrl() . 'getWebhookInfo')->json();
    }

    public function sendMessage(string $chat, string $text, array $replyMarkup = null)
    {
        $data = [
            'chat_id' => $chat,
            'text' => $text,
        ];
        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
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

    public function editMessageText(string $chat, int $messageId, string $text, array $replyMarkup = null)
    {
        $data = [
            'chat_id' => $chat,
            'message_id' => $messageId,
            'text' => $text,
            'reply_markup' => $replyMarkup ? json_encode($replyMarkup) : null
        ];
        return Http::post($this->apiUrl() . 'editMessageText', $data)->json();
    }

    public function setWebhook()
    {
        return Http::get($this->apiUrl() . 'setWebhook', [
            'url' => $this->apiWebhookUrl,
        ])->json();
    }

    public function getFile(string $fileId)
    {
        $data = [
            'file_id' => $fileId,
        ];
        return Http::post($this->apiUrl() . 'getFile', $data)->json();
    }

    public function fileUrl($filePath): string
    {
        return 'https://api.telegram.org/file/bot' . $this->apiKey . '/' . $filePath;
    }
}

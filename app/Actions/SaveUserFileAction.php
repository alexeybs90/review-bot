<?php

namespace App\Actions;

use App\Lib\TelegramBot;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SaveUserFileAction
{
    public function __construct(protected TelegramBot $bot)
    {}

    public function execute(string $tgFileId): ?string
    {
        $fileData = $this->bot->getFile($tgFileId);
        $fileData = $fileData['result'] ?? [];
        if (!$fileData) return null;

        $fileUrl = $this->bot->fileUrl($fileData['file_path'] ?? '');
        if (!$fileUrl) return null;

        $response = Http::get($fileUrl);
        if (!$response->successful()) return null;

        $fileContent = $response->body();
        $originalName = basename($fileUrl);
        $mimeType = $response->header('Content-Type');

        $tempFilePath = tempnam(sys_get_temp_dir(), 'laravel_download');
        file_put_contents($tempFilePath, $fileContent);

        $file = new UploadedFile($tempFilePath, $originalName, $mimeType, null, true);
        $filePath = 'review_files/' . $file->hashName();
        Storage::disk('public')->put($filePath, $response->body());
        unlink($tempFilePath);

        return $filePath;
    }
}

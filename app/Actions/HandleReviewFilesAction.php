<?php

namespace App\Actions;

use App\Models\Context;
use App\Repositories\ContextRepository;
use App\Services\BotResponseService;

class HandleReviewFilesAction
{
    public function __construct(
        protected ContextRepository $contextRepository,
        protected BotResponseService $botResponseService
    )
    {}

    public function execute(string $chat, array $photo, Context $context): void
    {
        $files = $context->files ? json_decode($context->files) : [];
        //телеграм отправляет каждое фото из сообщения в отдельном запросе в разных разрешениях,
        // самое большое 1280х... это последнее в массиве photo
        $photoItem = $photo[count($photo) - 1] ?? null;
        if ($photoItem) {
            $files[] = ['file_id' => $photoItem['file_id'], 'file_unique_id' => $photoItem['file_unique_id']];
        }
        $context->files = json_encode($files);
        $this->contextRepository->save($context);

        $this->botResponseService->sendRequestForSaveReview($chat, 'Загружено фото (' . count($files) . ')');
    }
}

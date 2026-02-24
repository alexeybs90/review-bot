<?php

namespace App\Actions;

use App\Models\Chat;
use App\Models\Review;
use App\Models\ReviewFile;
use App\Repositories\ContextRepository;
use App\Repositories\ReviewFileRepository;
use App\Repositories\ReviewRepository;
use App\Services\BotResponseService;
use Illuminate\Support\Facades\Log;

class SaveReviewAction
{
    public function __construct(
        protected ContextRepository $contextRepository,
        protected ReviewRepository $reviewRepository,
        protected SaveUserFileAction $saveUserFileAction,
        protected BotResponseService $botResponseService
    )
    {}

    public function execute(Chat $user): void
    {
        $context = $this->contextRepository->findByChat($user->chat);
        if (!$context) return;
        $review = $this->reviewRepository->findByChatIdAndCompanyId($user->id, $context->company_id);
        if (!$review) $review = new Review();
        $review->chat_id = $user->id;
        $review->company_id = $context->company_id;
        $review->grade = $context->grade;
        $review->comment = $context->comment;
        $this->reviewRepository->save($review);

        $files = json_decode($context->files, true);
        if ($files) {
            //если загружают новые файлы, то удаляем старые
            foreach ($review->reviewFiles as $reviewFile) {
                unlink(storage_path('app/public/' . $reviewFile->path));
                $reviewFile->delete();
            }
            for ($i = 0; $i < 3; $i ++) {
                $fileData = $files[$i] ?? null;
                if (!$fileData) continue;
                $filePath = $this->saveUserFileAction->execute($fileData['file_id']);
                if (!$filePath) continue;
                $file = new ReviewFile();
                $file->review_id = $review->id;
                $file->file_id = $fileData['file_id'];
                $file->file_unique_id = $fileData['file_unique_id'];
                $file->path = $filePath;
                (new ReviewFileRepository())->save($file);
            }
        }

        Log::debug('review saved: id=' . $review?->id . ', grade=' . $review?->grade
            . ', company_id=' . $review?->company_id . ', comment=' . $review?->comment
            . ', files=' . json_encode($files)
        );
        $this->contextRepository->delete($context);
        $this->botResponseService->sendSaveSuccess($user->chat);
    }
}

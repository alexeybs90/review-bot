<?php

namespace App\Services;

use App\Actions\SaveUserFileAction;
use App\Actions\StartReviewAction;
use App\DTO\TelegramUpdate;
use App\Helpers\ReviewBotHelper;
use App\Models\Chat;
use App\Models\Company;
use App\Models\Context;
use App\Models\Review;
use App\Models\ReviewFile;
use App\Repositories\ChatRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ContextRepository;
use App\Repositories\ReviewFileRepository;
use App\Repositories\ReviewRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReviewBotService
{
    public function __construct(
        protected CompanyRepository $companyRepository,
        protected ContextRepository $contextRepository,
        protected ReviewRepository $reviewRepository,
        protected ChatRepository $chatRepository,
        protected BotResponseService $botResponseService,
        protected SaveUserFileAction $saveUserFileAction,
    ) {}

    public function handleUpdate(TelegramUpdate $update): void
    {
        $user = $this->chatRepository->findOrCreateUser($update->chatId, $update->name);

        if (!$update->phone && !$user->phone) {
            $this->botResponseService->sendPhoneButton($update->chatId);
            return;
        } elseif ($update->phone && !$user->phone) {
            $user->phone = $update->phone;
            $this->chatRepository->save($user);
            $this->botResponseService->sendHello($update->chatId);
            return;
        }

        if ($update->callbackQuery) {
            $this->handleCallbackQueryRequest($update, $user);
            return;
        }
        $this->handleTextRequest($update, $user);
    }

    public function handleTextRequest(TelegramUpdate $update, Chat $user): void
    {
        if ($update->text === '/company_list' || $update->text === 'Все компании') {
            $this->contextRepository->reset($user->chat);
            $this->searchCompanies($user->chat, 0, $update->text);
            return;
        } elseif ($update->text === '/search_company' || $update->text === 'Поиск компании') {
            $this->setWaitCompanySearch($user->chat);
            return;
        }

        $context = $this->contextRepository->findByChat($user->chat);
        if (!$context || $context->chat !== $user->chat) return;

        match ($context->status) {
            ReviewBotHelper::CONTEXT_STATUS_WAIT_REVIEW_COMMENT =>
                $this->handleReviewComment($user->chat, $update->text, $context),
            ReviewBotHelper::CONTEXT_STATUS_WAIT_REVIEW_FILES =>
                $this->handleReviewFiles($user->chat, $update->message['photo'] ?? [], $context),
            ReviewBotHelper::CONTEXT_STATUS_WAIT_SEARCH_COMPANY =>
                $this->searchCompanies($user->chat, 0, $update->text, $context),
            default => $this->botResponseService->sendHello($user->chat),
        };
    }

    public function handleCallbackQueryRequest(TelegramUpdate $update, Chat $user): void
    {
        $callbackQueryDataStr = $update->callbackQuery ? $update->callbackQuery['data'] : '';
        $callbackQueryData = explode(':', $callbackQueryDataStr);
        $callbackQueryDataAction = $callbackQueryData ? $callbackQueryData[0] : '';
        if (!$callbackQueryData || !$callbackQueryDataAction) {
            return;
        }
        switch ($callbackQueryDataAction) {
            case ReviewBotHelper::CALLBACK_ACTION_START_REVIEW:
                $companyId = $callbackQueryData[1];
                app(StartReviewAction::class)->handle($user->chat, $companyId, $user->id);
                break;
            case ReviewBotHelper::CALLBACK_ACTION_SET_GRADE:
                $companyId = $callbackQueryData[1];
                $grade = $callbackQueryData[2];
                $this->setCompanyGrade($user->chat, $companyId, $grade);
                break;
            case ReviewBotHelper::CALLBACK_ACTION_SAVE_REVIEW:
                $this->saveReviewFromContext($user);
                break;
            case ReviewBotHelper::CALLBACK_ACTION_SHOW_REVIEW:
                $companyId = $callbackQueryData[1];
                $page = $callbackQueryData[2] ?? 0;
                $this->sendCompanyReview($user->chat, $companyId, $page);
                break;
            case ReviewBotHelper::CALLBACK_ACTION_COMPANY_LIST:
                $page = $callbackQueryData[1] ?? 0;
                $text = $callbackQueryData[2] ?? '';
                $context = $this->contextRepository->findByChat($user->chat);
                $this->searchCompanies($user->chat, $page, $text, $context);
                break;
        }
    }

    public function searchCompanies(string $chat, int $page, $text = '', Context $context = null): void
    {
        if ($context && $context->status === ReviewBotHelper::CONTEXT_STATUS_WAIT_SEARCH_COMPANY && $text) {
            $companies = $this->companyRepository->getByName($text, $page);
            $count = $this->companyRepository->countByName($text);
        } else {
            $companies = $this->companyRepository->get($page);
            $count = $this->companyRepository->count();
        }
        $this->botResponseService->searchCompanies($chat, $page, $text, $companies, $count);
    }

    public function sendCompanyReview(string $chat, int $companyId, int $page = 0): void
    {
        $review = $this->reviewRepository->findOneByCompanyId($companyId, $page);
        $count = $this->reviewRepository->countByCompanyId($companyId);
        if (!$review) {
            $this->botResponseService->sendNotFound($chat);
            return;
        }
        $company = $this->companyRepository->find($companyId);
        $user = $this->chatRepository->find($review->chat_id);
        $this->contextRepository->reset($chat);
        $this->botResponseService->sendCompanyReview($chat, $review, $company, $user, $page, $count);
    }

    public function setCompanyGrade(string $chat, int $companyId, int $grade): void
    {
        $company = $this->companyRepository->find($companyId);
        if (!$company) {
            $this->botResponseService->sendNotFound($chat);
            return;
        }
        $context = $this->contextRepository->findByChat($chat);
        if (!$context) $context = new Context();
        $context->chat = $chat;
        $context->status = ReviewBotHelper::CONTEXT_STATUS_WAIT_REVIEW_COMMENT;
        $context->company_id = $companyId;
        $context->grade = $grade;
        $context->comment = '';
        $context->files = json_encode([]);
        $this->contextRepository->save($context);

        $this->botResponseService->sendRequestForReviewComment($chat, $grade, $company);
    }

    public function handleReviewComment(string $chat, string $comment, Context $context): void
    {
        $company = $this->companyRepository->find($context->company_id);
        $context->status = ReviewBotHelper::CONTEXT_STATUS_WAIT_REVIEW_FILES;
        $context->comment = $comment;
        $this->contextRepository->save($context);

        $this->botResponseService->sendRequestForReviewFiles($chat, $context, $company);
    }

    public function handleReviewFiles(string $chat, array $photo, Context $context): void
    {
        $text = 'Загружено фото...';
        $files = $context->files ? json_decode($context->files) : [];
        //телеграм отправляет каждое фото из сообщения в отдельном запросе в разных разрешениях,
        // самое большое 1280х... это последнее в массиве photo
        $photoItem = $photo[count($photo) - 1] ?? null;
        if ($photoItem) {
            $files[] = ['file_id' => $photoItem['file_id'], 'file_unique_id' => $photoItem['file_unique_id']];
        }
        $context->files = json_encode($files);
        $this->contextRepository->save($context);

        $this->botResponseService->sendRequestForSaveReview($chat, $text);
    }

    public function saveReviewFromContext(Chat $user): void
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
                $filePath = $this->saveUserFileAction->handle($fileData['file_id']);
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

    public function setWaitCompanySearch(string $chat): void
    {
        $context = $this->contextRepository->findByChat($chat);
        if (!$context) $context = new Context();
        $context->chat = $chat;
        $context->status = ReviewBotHelper::CONTEXT_STATUS_WAIT_SEARCH_COMPANY;
        $context->comment = '';
        $context->files = json_encode([]);
        $this->contextRepository->save($context);
        $this->botResponseService->sendRequestForCompanySearch($chat);
    }
}

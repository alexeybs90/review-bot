<?php

namespace App\Services;

use App\Actions\HandleReviewCommentAction;
use App\Actions\HandleReviewFilesAction;
use App\Actions\SaveReviewAction;
use App\Actions\SearchCompaniesAction;
use App\Actions\SendCompanyReviewAction;
use App\Actions\SetCompanyGradeAction;
use App\Actions\StartReviewAction;
use App\Actions\StartSearchCompanyAction;
use App\DTO\TelegramUpdate;
use App\Constants\ReviewBotConstants;
use App\Models\Chat;
use App\Repositories\ChatRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ContextRepository;
use App\Repositories\ReviewRepository;

class ReviewBotService
{
    public function __construct(
        protected CompanyRepository $companyRepository,
        protected ContextRepository $contextRepository,
        protected ReviewRepository $reviewRepository,
        protected ChatRepository $chatRepository,
        protected BotResponseService $botResponseService,
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
            app(SearchCompaniesAction::class)->execute($user->chat, 0, $update->text);
            return;
        } elseif ($update->text === '/search_company' || $update->text === 'Поиск компании') {
            app(StartSearchCompanyAction::class)->execute($user->chat);
            return;
        }

        $context = $this->contextRepository->findByChat($user->chat);
        if (!$context || $context->chat !== $user->chat) return;

        match ($context->status) {
            ReviewBotConstants::CONTEXT_STATUS_WAIT_REVIEW_COMMENT =>
                app(HandleReviewCommentAction::class)->execute($user->chat, $update->text, $context),
            ReviewBotConstants::CONTEXT_STATUS_WAIT_REVIEW_FILES =>
                app(HandleReviewFilesAction::class)->execute($user->chat, $update->message['photo'] ?? [], $context),
            ReviewBotConstants::CONTEXT_STATUS_WAIT_SEARCH_COMPANY =>
                app(SearchCompaniesAction::class)->execute($user->chat, 0, $update->text, $context),
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
            case ReviewBotConstants::CALLBACK_ACTION_START_REVIEW:
                $companyId = $callbackQueryData[1];
                app(StartReviewAction::class)->execute($user->chat, $companyId, $user->id);
                break;
            case ReviewBotConstants::CALLBACK_ACTION_SET_GRADE:
                $companyId = $callbackQueryData[1];
                $grade = $callbackQueryData[2];
                app(SetCompanyGradeAction::class)->execute($user->chat, $companyId, $grade);
                break;
            case ReviewBotConstants::CALLBACK_ACTION_SAVE_REVIEW:
                app(SaveReviewAction::class)->execute($user);
                break;
            case ReviewBotConstants::CALLBACK_ACTION_SHOW_REVIEW:
                $companyId = $callbackQueryData[1];
                $page = $callbackQueryData[2] ?? 0;
                app(SendCompanyReviewAction::class)->execute($user->chat, $companyId, $page);
                break;
            case ReviewBotConstants::CALLBACK_ACTION_COMPANY_LIST:
                $page = $callbackQueryData[1] ?? 0;
                $text = $callbackQueryData[2] ?? '';
                $context = $this->contextRepository->findByChat($user->chat);
                app(SearchCompaniesAction::class)->execute($user->chat, $page, $text, $context);
                break;
        }
    }
}

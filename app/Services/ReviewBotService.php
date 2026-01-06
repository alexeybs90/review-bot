<?php

namespace App\Services;

use App\Lib\TelegramBot;
use App\Models\Chat;
use App\Models\Company;
use App\Models\Context;
use App\Models\Review;
use App\Repositories\ChatRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ContextRepository;
use App\Repositories\ReviewRepository;
use Illuminate\Support\Facades\Log;

class ReviewBotService
{
    const CONTEXT_STATUS_WAIT_REVIEW_TEXT = 'waiting_review_comment';

    protected CompanyRepository $companyRepository;
    protected ContextRepository $contextRepository;
    protected ReviewRepository $reviewRepository;
    protected ChatRepository $chatRepository;
    protected TelegramBot $telegramBot;

    public function __construct(
        CompanyRepository $companyRepository,
        ContextRepository $contextRepository,
        ReviewRepository $reviewRepository,
        ChatRepository $chatRepository,
        TelegramBot $telegramBot,
    )
    {
        $this->companyRepository = $companyRepository;
        $this->contextRepository = $contextRepository;
        $this->reviewRepository = $reviewRepository;
        $this->chatRepository = $chatRepository;
        $this->telegramBot = $telegramBot;
    }

    public function info()
    {
        return $this->telegramBot->info();
    }

    public function sendTest()
    {
        return $this->telegramBot->sendMessage('399902343', 'test');
    }

    public function setWebhook()
    {
        return $this->telegramBot->setWebhook();
    }

    public function sendHello($chat)
    {
        $response = $this->telegramBot->sendMessage($chat, 'Выберите кнопку ниже', [
            'keyboard' => [
                [
                    ['text' => 'Поиск компании'],
                    ['text' => 'Мои отзывы'],
                ]
            ],
            'one_time_keyboard' => true, // Кнопка исчезнет после нажатия
            'resize_keyboard' => true    // Оптимизирует размер клавиатуры
        ]);
        Log::debug('sendHello = ' . json_encode($response));
    }

    public function sendPhoneButton($chat)
    {
        $response = $this->telegramBot->sendMessage($chat, 'Пожалуйста, предоставьте доступ к номеру телефона', [
            'keyboard' => [[
                ['text' => 'Поделиться контактом', 'request_contact' => true]
            ]],
            'one_time_keyboard' => true, // Кнопка исчезнет после нажатия
            'resize_keyboard' => true    // Оптимизирует размер клавиатуры
        ]);
        Log::debug('sendPhoneButton = ' . json_encode($response));
    }

    public function sendCompanies($chat, $page, $message_id, $text)
    {
        $companies = $this->companyRepository->get($page);
        $count = $this->companyRepository->count();
        $keys = [];
        foreach ($companies as $company) {
            $reviews = $company->reviews;
            $rating = 0;
            if ($reviews && count($reviews) > 0) {
                foreach ($reviews as $review) {
                    $rating += $review->grade;
                }
                $rating = $rating / count($reviews);
            }
            $keys[] = [
                [
                    'text' => $company->name . ' - ' . $rating . ' ⭐ (' . count($reviews) . ')',
                    'callback_data' => 'show_reviews_company_id:' . $company->id,
                ],
                [
                    'text' => 'Написать',
                    'callback_data' => 'start_review_company_id:' . $company->id,
                ],
            ];
        }
        if (!$companies || !count($companies)) return;
        if ($count > ($page * $this->companyRepository::LIMIT + count($companies))) {
            $keys[] = [[
                'text' => 'Загрузить еще',
                'callback_data' => 'search_company:' . ($page + 1),
            ]];
        }

        if (false && $page > 0) {
            //удалим кнопку загрузить еще
            $response = $this->telegramBot->editMessageText(
                $chat,
                $message_id,
                $text,
                null
            );
            Log::debug('editMessageText = ' . json_encode($response));
        }

        $response = $this->telegramBot->sendMessage(
            $chat,
            'Найдено ' . $count . '. Выберите компанию. Вы можете посмотреть отзывы или написать свой.',
            [
                'keyboard' => [],
                'inline_keyboard' => $keys,
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            ]
        );
        $this->contextRepository->reset($chat);
        Log::debug('sendCompanies = ' . json_encode($response));
    }

    public function sendCompany($chat, $company_id, $user_chat_id)
    {
        $company = $this->companyRepository->find($company_id);
        if (!$company) return; //TODO: send error not found
        $keys = [];
        for ($i = 1; $i <= 5; $i ++) {
            $keys[] = [
                'text' => $i,
                'callback_data' => 'send_company_grade:' . $company_id . ':' . $i,
            ];
        }
        $text = 'Поставьте оценку для ' . $company->name;
        $review = $this->reviewRepository->findByChatIdAndCompanyId($user_chat_id, $company_id);
        if ($review) {
            $text = "Вы уже оставляли отзыв на компанию {$company->name}:" . chr(10)
                . "Оценка: {$review->grade} ⭐" . chr(10)
                . "Текст: {$review->comment}" . chr(10)
                . "Дата: {$review->created_at}" . chr(10)
                . 'Чтобы изменить свой отзыв, поставьте оценку ниже.';
        }
        $response = $this->telegramBot->sendMessage($chat, $text, [
            'keyboard' => [],
            'inline_keyboard' => [$keys],
            'one_time_keyboard' => true,
            'resize_keyboard' => true
        ]);
        $this->contextRepository->reset($chat);
        Log::debug('sendCompany = ' . json_encode($response));
    }

    public function sendCompanyReview($chat, $company_id, $page = 0)
    {
        $review = $this->reviewRepository->findOneByCompanyId($company_id, $page);
        $count = $this->reviewRepository->countByCompanyId($company_id);
        if (!$review) {
            $this->telegramBot->sendMessage($chat, 'Не найдено');
            return;
        }
        $company = $this->companyRepository->find($company_id);
        $user = $this->chatRepository->find($review->chat_id);
        $text = "Отзыв на компанию {$company->name} (" . ($page + 1) . " из {$count}):" . chr(10)
            . 'Имя: ' . $user->name . chr(10)
            . "Оценка: {$review->grade} ⭐" . chr(10)
            . "Текст: {$review->comment}" . chr(10)
            . "Дата: {$review->created_at}" . chr(10);
        $key = [];
        if ($count > $page + 1) {
            $key = [[[
                'text' => 'Следующий >>',
                'callback_data' => 'show_reviews_company_id:' . $company_id . ':' . ($page + 1),
            ]]];
        }

        $response = $this->telegramBot->sendMessage($chat, $text, [
            'keyboard' => [],
            'inline_keyboard' => $key,
            'one_time_keyboard' => true,
            'resize_keyboard' => true
        ]);
        $this->contextRepository->reset($chat);
        Log::debug('sendCompanyReview = ' . json_encode($response));
    }

    public function sendCompanyGrade($chat, $company_id, $grade)
    {
        $company = $this->companyRepository->find($company_id);
        $response = $this->telegramBot->sendMessage(
            $chat,
            'Вы поставили оценку ' . $grade . ' для ' . $company->name . '. Теперь напишите отзыв',
            [
                'keyboard' => [],
                'inline_keyboard' => [],
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            ]);
        Log::debug('sendCompanyGrade = ' . json_encode($response));
        $context = $this->contextRepository->findByChat($chat);
        if (!$context) $context = new Context();
        $context->chat = $chat;
        $context->status = self::CONTEXT_STATUS_WAIT_REVIEW_TEXT;
        $context->company_id = $company_id;
        $context->grade = $grade;
        $this->contextRepository->save($context);
        Log::debug('set session context: id=' . $context->id . ', chat=' . $context->chat
            . ', company_id=' . $context->company_id . ', grade=' . $context->grade);
    }

    public function handleContextActions(string $chat, Chat $user, string $text): bool
    {
        $context = $this->contextRepository->findByChat($chat);
        Log::debug('read session context: id=' . $context?->id . ', chat=' . $context?->chat
            . ', company_id=' . $context?->company_id . ', grade=' . $context?->grade . ', status=' . $context?->status);
        if (!$context || $context->chat !== $chat) return false;
        if ($context->status === self::CONTEXT_STATUS_WAIT_REVIEW_TEXT && $context->company_id && $context->grade) {
            $review = $this->reviewRepository->findByChatIdAndCompanyId($user->id, $context->company_id);
            if (!$review) $review = new Review();
            $review->chat_id = $user->id;
            $review->company_id = $context->company_id;
            $review->grade = $context->grade;
            $review->comment = $text;
            $this->reviewRepository->save($review);
            Log::debug('review created: id=' . $review?->id . ', grade=' . $review?->grade
                . ', company_id=' . $review->company_id . ', comment=' . $review->comment);
            $this->contextRepository->delete($context);
            $this->telegramBot->sendMessage($chat, 'Спасибо за отзыв!');
            return true;
        }
        return false;
    }

    public function findOrCreateUser(string $chat, string $name): Chat //TODO: коряво
    {
        return $this->chatRepository->findOrCreateUser($chat, $name);
    }

    public function saveUser(Chat $user): bool
    {
        return $this->chatRepository->save($user);
    }
}

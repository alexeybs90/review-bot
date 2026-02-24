<?php

namespace App\Services;

use App\Constants\ReviewBotConstants;
use App\Lib\TelegramBot;
use App\Models\Chat;
use App\Models\Company;
use App\Models\Context;
use App\Models\Review;
use App\Repositories\CompanyRepository;
use Illuminate\Support\Facades\Log;

class BotResponseService
{
    public function __construct(protected TelegramBot $bot)
    {}

    public function sendPhoneButton(string $chat)
    {
        $response = $this->bot->sendMessage($chat, 'Пожалуйста, предоставьте доступ к номеру телефона', [
            'keyboard' => [[
                ['text' => 'Поделиться контактом', 'request_contact' => true]
            ]],
            'one_time_keyboard' => true,
            'resize_keyboard' => true,
        ]);
        Log::debug('sendPhoneButton = ' . json_encode($response));
        return $response;
    }

    public function info()
    {
        return $this->bot->info();
    }

    public function sendTest()
    {
        return $this->bot->sendMessage('399902343', 'test');
    }

    public function setWebhook()
    {
        return $this->bot->setWebhook();
    }

    public function sendHello(string $chat)
    {
        $response = $this->bot->sendMessage($chat, 'Выберите кнопку ниже', [
            'keyboard' => [
                [
                    ['text' => 'Все компании'],
                    ['text' => 'Поиск компании'],
                    ['text' => 'Мои отзывы'],
                ]
            ],
            'one_time_keyboard' => true,
            'resize_keyboard' => true,
        ]);
        Log::debug('sendHello = ' . json_encode($response));
        return $response;
    }

    public function searchCompanies(string $chat, int $page, string $text, $companies, int $count)
    {
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
                    'callback_data' => ReviewBotConstants::CALLBACK_ACTION_SHOW_REVIEW . ':' . $company->id,
                ],
                [
                    'text' => 'Написать',
                    'callback_data' => ReviewBotConstants::CALLBACK_ACTION_START_REVIEW . ':' . $company->id,
                ],
            ];
        }
        if (!$companies || !count($companies)) {
            $this->sendNotFound($chat);
            return null;
        }
        if ($count > ($page * ReviewBotConstants::COMPANY_LIMIT + count($companies))) {
            $keys[] = [[
                'text' => 'Загрузить еще',
                'callback_data' => ReviewBotConstants::CALLBACK_ACTION_COMPANY_LIST . ':' . ($page + 1) . ':' . $text,
            ]];
        }

        /*if ($page > 0) {
            //удалим кнопку загрузить еще
            $response = $this->telegramBot->editMessageText(
                $chat,
                $message_id,
                $text,
                null
            );
            Log::debug('editMessageText = ' . json_encode($response));
        }*/

        $response = $this->bot->sendMessage(
            $chat,
            'Найдено ' . $count . "." . chr(10)
            . "Нажмите кнопку слева, чтобы посмотреть отзывы." . chr(10)
            . "Нажмите кнопку справа, чтобы написать/изменить свой отзыв.",
            [
                'keyboard' => [],
                'inline_keyboard' => $keys,
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            ]
        );
//        $this->contextRepository->reset($chat);
        Log::debug('searchCompanies = ' . json_encode($response));
        return $response;
    }

    public function sendSaveSuccess(string $chat)
    {
        return $this->bot->sendMessage($chat, 'Спасибо за отзыв!');
    }

    public function sendRequestForCompanySearch(string $chat)
    {
        return $this->bot->sendMessage($chat, 'Введите название компании');
    }

    public function sendNotFound(string $chat)
    {
        Log::debug('sendNotFound = ' . $chat);
        return $this->bot->sendMessage($chat, 'Не найдено');
    }

    public function sendCompanyReview(string $chat, Review $review, Company $company, Chat $user, int $page, int $count)
    {
        $text = "Отзыв на компанию {$company->name} (" . ($page + 1) . " из {$count}):" . chr(10)
            . 'Имя: ' . $user->name . chr(10)
            . "Оценка: {$review->grade} ⭐" . chr(10)
            . "Текст: {$review->comment}" . chr(10)
            . "Дата: {$review->created_at}" . chr(10);
        $key = [];
        if ($count > $page + 1) {
            $key = [[[
                'text' => 'Следующий >>',
                'callback_data' => ReviewBotConstants::CALLBACK_ACTION_SHOW_REVIEW . ':' . $company->id . ':' . ($page + 1),
            ]]];
        }

        $response = $this->bot->sendMessage($chat, $text, [
            'keyboard' => [],
            'inline_keyboard' => $key,
            'one_time_keyboard' => true,
            'resize_keyboard' => true
        ]);
        Log::debug('sendCompanyReview = ' . json_encode($response));

        if ($review->reviewFiles && count($review->reviewFiles) > 0) {
            $response = $this->bot->sendPhotos($chat, $review->reviewFiles);
        }

        Log::debug('sendPhotos (sendMediaGroup) = ' . json_encode($response));
        return $response;
    }

    public function sendRequestForReviewGrade(string $chat, Company $company, ?Review $review)
    {
        $text = 'Шаг 1. Поставьте оценку для ' . $company->name;
        if ($review) {
            $text = "Вы уже оставляли отзыв на компанию {$company->name}:" . chr(10)
                . "Оценка: {$review->grade} ⭐" . chr(10)
                . "Текст: {$review->comment}" . chr(10)
                . "Дата: {$review->created_at}" . chr(10) . chr(10)
                . 'Чтобы изменить свой отзыв, поставьте оценку ниже.';
        }
        $keys = [];
        for ($i = 1; $i <= 5; $i ++) {
            $keys[] = [
                'text' => $i,
                'callback_data' => ReviewBotConstants::CALLBACK_ACTION_SET_GRADE . ':' . $company->id . ':' . $i,
            ];
        }
        $response = $this->bot->sendMessage($chat, $text, [
            'keyboard' => [],
            'inline_keyboard' => [$keys],
            'one_time_keyboard' => true,
            'resize_keyboard' => true
        ]);
        Log::debug('sendRequestForReviewGrade = ' . json_encode($response));
        return $response;
    }

    public function sendRequestForReviewComment(string $chat, int $grade, Company $company)
    {
        $response = $this->bot->sendMessage(
            $chat,
            'Вы поставили оценку ' . $grade . ' для '
            . $company->name . '.'  . chr(10)  . chr(10)
            . 'Шаг 2. Напишите отзыв'
        );
        Log::debug('sendRequestForReviewComment = ' . json_encode($response));
        return $response;
    }

    public function sendRequestForReviewFiles(string $chat, Context $context, Company $company)
    {
        $response = $this->bot->sendMessage(
            $chat,
            'Вы поставили оценку ' . $context->grade
            . ' для ' . $company->name . '.'  . chr(10)
            . 'Текст: ' . $context->comment  . chr(10)  . chr(10)
            . 'Шаг 3. Отправьте до 3х фото или сразу нажмите Сохранить',
            [
                'keyboard' => [],
                'inline_keyboard' => [[[
                    'text' => 'Сохранить',
                    'callback_data' => ReviewBotConstants::CALLBACK_ACTION_SAVE_REVIEW,
                ]]],
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            ]);
        Log::debug('sendRequestForReviewFiles ' . json_encode($response));
        return $response;
    }

    public function sendRequestForSaveReview(string $chat, string $text): void
    {
        $response = $this->bot->sendMessage($chat, $text,
            [
                'inline_keyboard' => [[[
                    'text' => 'Сохранить отзыв',
                    'callback_data' => ReviewBotConstants::CALLBACK_ACTION_SAVE_REVIEW,
                ]]]
            ]
        );
        Log::debug('sendRequestForSaveReview ' . json_encode($response));
    }
}

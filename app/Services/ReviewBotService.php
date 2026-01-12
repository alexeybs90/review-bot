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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReviewBotService
{
    const CONTEXT_STATUS_WAIT_REVIEW_TEXT = 'waiting_review_comment';
    const CONTEXT_STATUS_WAIT_REVIEW_FILES = 'waiting_review_files';

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
        $text = 'Шаг 1. Поставьте оценку для ' . $company->name;
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
            'Вы поставили оценку ' . $grade . ' для ' . $company->name . '.'  . chr(10)
            . 'Шаг 2. Напишите отзыв'
        );
        Log::debug('sendCompanyGrade = ' . json_encode($response));
        $context = $this->contextRepository->findByChat($chat);
        if (!$context) $context = new Context();
        $context->chat = $chat;
        $context->status = self::CONTEXT_STATUS_WAIT_REVIEW_TEXT;
        $context->company_id = $company_id;
        $context->grade = $grade;
        $context->comment = '';
        $context->files = json_encode([]);
        $this->contextRepository->save($context);
        Log::debug('set session context: id=' . $context->id . ', chat=' . $context->chat
            . ', company_id=' . $context->company_id . ', grade=' . $context->grade . ', status=' . $context->status
            . ', comment=' . $context->comment);
    }

    public function handleReviewComment($chat, $comment, $context)
    {
        if (!$context) return;
        $company = $this->companyRepository->find($context->company_id);
        $context->status = self::CONTEXT_STATUS_WAIT_REVIEW_FILES;
        $context->comment = $comment;
        $this->contextRepository->save($context);
        Log::debug('set session context: id=' . $context->id . ', chat=' . $context->chat
            . ', company_id=' . $context->company_id . ', grade=' . $context->grade . ', status=' . $context->status
            . ', comment=' . $context->comment);

        $response = $this->telegramBot->sendMessage(
            $chat,
            'Вы поставили оценку ' . $context->grade . ' для ' . $company->name . '.'  . chr(10)
            . 'Текст: ' . $comment  . chr(10)
            . 'Шаг 3. Отправьте до 3х фото или сразу нажмите Сохранить',
            [
                'keyboard' => [],
                'inline_keyboard' => [[[
                    'text' => 'Сохранить',
                    'callback_data' => 'save_review_from_context',
                ]]],
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            ]);
        Log::debug('handleReviewComment sendMessage=' . json_encode($response));
    }

    public function handleReviewFiles($chat, $photo, $context)
    {
        if (!$context) return;
        $text = '';
        $files = $context->files ? json_decode($context->files) : [];
        //телеграм отправляет каждое фото из сообщения в отдельном запросе в разных разрешениях,
        // самое большое 1280х... это последнее в массиве photo
        $photoItem = $photo[count($photo) - 1] ?? null;
        if ($photoItem) {
            $file = $this->telegramBot->getFile($photoItem['file_id']);
            $file = $file['result'] ?? [];
            $fileUrl = $this->telegramBot->fileUrl($file['file_path'] ?? '');
            $response = Http::get($fileUrl);
            if ($response->successful()) {
//                $path = Storage::disk('public')->putFile('photos', $response->body());
                $fileContent = $response->body();
                $originalName = basename($fileUrl);
                $mimeType = $response->header('Content-Type');

                $tempFilePath = tempnam(sys_get_temp_dir(), 'laravel_download');
                file_put_contents($tempFilePath, $fileContent);

                $file = new UploadedFile($tempFilePath, $originalName, $mimeType, null, true);

                Storage::disk('public')->put(
                    'photos/' . $file->hashName() . '.' . $file->extension()
                    , $response->body()
                );
                unlink($tempFilePath);
            }
            $text .= 'file_id: ' . $photoItem['file_id']  . chr(10)
                . 'file_unique_id: ' . $photoItem['file_unique_id'] . chr(10)
                . $fileUrl . chr(10)
            ;
            $files[] = $photoItem['file_id'];
        }
        $context->files = json_encode($files);
        $this->contextRepository->save($context);
        Log::debug('set session context: id=' . $context->id . ', chat=' . $context->chat
            . ', company_id=' . $context->company_id . ', grade=' . $context->grade . ', status=' . $context->status
            . ', comment=' . $context->comment. ', files=' . $context->files);

        $response = $this->telegramBot->sendMessage(
            $chat,
            $text,
            [
                'inline_keyboard' => [[[
                    'text' => 'Сохранить',
                    'callback_data' => 'save_review_from_context',
                ]]]
            ]
        );
        Log::debug('handleReviewFiles sendMessage=' . json_encode($response));
    }

    public function handleContextActions(Chat $user, string $text, $photo = []): bool
    {
        $context = $this->contextRepository->findByChat($user->chat);
        Log::debug('read session context: id=' . $context?->id . ', chat=' . $context?->chat
            . ', company_id=' . $context?->company_id . ', grade=' . $context?->grade . ', status=' . $context?->status);
        if (!$context || $context->chat !== $user->chat) return false;
        if ($context->status === self::CONTEXT_STATUS_WAIT_REVIEW_TEXT && $context->company_id && $context->grade) {
            $this->handleReviewComment($user->chat, $text, $context);
            return true;
        } elseif ($context->status === self::CONTEXT_STATUS_WAIT_REVIEW_FILES && $context->company_id && $context->grade) {
            $this->handleReviewFiles($user->chat, $photo, $context);
            return true;
        }
        return false;
    }

    public function saveReviewFromContext(Chat $user)
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
        Log::debug('review saved: id=' . $review?->id . ', grade=' . $review?->grade
            . ', company_id=' . $review?->company_id . ', comment=' . $review?->comment);
        $this->contextRepository->delete($context);
        $this->telegramBot->sendMessage($user->chat, 'Спасибо за отзыв!');
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

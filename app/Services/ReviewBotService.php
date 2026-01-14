<?php

namespace App\Services;

use App\Lib\TelegramBot;
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
    const CONTEXT_STATUS_WAIT_REVIEW_TEXT = 'waiting_review_comment';
    const CONTEXT_STATUS_WAIT_REVIEW_FILES = 'waiting_review_files';
    const CONTEXT_STATUS_WAIT_SEARCH_COMPANY = 'waiting_search_company';

    protected Chat $user;
    protected array $message;
    protected ?array $callback_query;
    protected string $text;
    protected string|int $message_id;

    public function __construct(
        protected CompanyRepository $companyRepository,
        protected ContextRepository $contextRepository,
        protected ReviewRepository $reviewRepository,
        protected ChatRepository $chatRepository,
        protected TelegramBot $telegramBot,
    ) {}

    public function initMessageData($message, $callback_query): bool
    {
        $this->callback_query = $callback_query;
        if (!$message && $this->callback_query) $message = $this->callback_query['message'];
        $this->message = $message;
        $chat = (string)$this->message['chat']['id'];
        $this->text = $this->message['text'] ?? '';
        $this->message_id = $this->message['message_id'] ?? '';

        $name = $this->message['from']['first_name'];
        $phone = $this->message['contact']['phone_number'] ?? '';
        $this->user = $this->chatRepository->findOrCreateUser($chat, $name);

        if (!$phone && !$this->user->phone) {
            $this->sendPhoneButton($chat);
            return true;
        } elseif ($phone && !$this->user->phone) {
            $this->user->phone = $phone;
            $this->chatRepository->save($this->user);
            $this->sendHello();
            return true;
        }
        return false;
    }

    public function handleTextRequest(): bool
    {
        if ($this->text === '/company_list' || $this->text === 'Все компании') {
            $this->contextRepository->reset($this->user->chat);
            $this->searchCompanies($this->user->chat, 0, $this->text);
            return true;
        } elseif ($this->text === '/search_company' || $this->text === 'Поиск компании') {
            $this->setWaitCompanySearch($this->user->chat);
            return true;
        }
        return false;
    }

    public function handleCallbackQueryRequest(): bool
    {
        $callback_query_data_str = $this->callback_query ? $this->callback_query['data'] : '';
        $callback_query_data = explode(':', $callback_query_data_str);
        $callback_query_data_action = $callback_query_data ? $callback_query_data[0] : '';
        if (!$callback_query_data || !$callback_query_data_action) {
            return false;
        }
        switch ($callback_query_data_action) {
            case 'start_review_company_id':
                $company_id = $callback_query_data[1];
                $this->sendCompany($this->user->chat, $company_id, $this->user->id);
                return true;
            case 'send_company_grade':
                $company_id = $callback_query_data[1];
                $grade = $callback_query_data[2];
                $this->sendCompanyGrade($this->user->chat, $company_id, $grade);
                return true;
            case 'save_review_from_context':
                $this->saveReviewFromContext($this->user);
                return true;
            case 'show_reviews_company_id':
                $company_id = $callback_query_data[1];
                $page = $callback_query_data[2] ?? 0;
                $this->sendCompanyReview($this->user->chat, $company_id, $page);
                return true;
            case 'company_list':
                $page = $callback_query_data[1] ?? 0;
                $text = $callback_query_data[2] ?? '';
                $this->searchCompanies($this->user->chat, $page, $text);
                return true;
        }
        return false;
    }

    public function handleContextActions(): bool
    {
        $photo = $this->message['photo'] ?? [];
        $context = $this->contextRepository->findByChat($this->user->chat);
        Log::debug('read session context: id=' . $context?->id . ', chat=' . $context?->chat
            . ', company_id=' . $context?->company_id . ', grade=' . $context?->grade . ', status=' . $context?->status);
        if (!$context || $context->chat !== $this->user->chat) return false;
        if ($context->status === self::CONTEXT_STATUS_WAIT_REVIEW_TEXT && $context->company_id && $context->grade) {
            $this->handleReviewComment($this->user->chat, $this->text, $context);
            return true;
        } elseif ($context->status === self::CONTEXT_STATUS_WAIT_REVIEW_FILES && $context->company_id && $context->grade) {
            $this->handleReviewFiles($this->user->chat, $photo, $context);
            return true;
        } elseif ($context->status === self::CONTEXT_STATUS_WAIT_SEARCH_COMPANY) {
            $this->searchCompanies($this->user->chat, 0, $this->text);
            return true;
        }
        return false;
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

    public function sendHello()
    {
        $response = $this->telegramBot->sendMessage($this->user->chat, 'Выберите кнопку ниже', [
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
    }

    public function sendPhoneButton(string $chat)
    {
        $response = $this->telegramBot->sendMessage($chat, 'Пожалуйста, предоставьте доступ к номеру телефона', [
            'keyboard' => [[
                ['text' => 'Поделиться контактом', 'request_contact' => true]
            ]],
            'one_time_keyboard' => true,
            'resize_keyboard' => true,
        ]);
        Log::debug('sendPhoneButton = ' . json_encode($response));
    }

    public function searchCompanies(string $chat, int $page, $text = '')
    {
        $context = $this->contextRepository->findByChat($chat);
        if ($context && $context->status === self::CONTEXT_STATUS_WAIT_SEARCH_COMPANY && $text) {
            $companies = $this->companyRepository->getByName($text, $page);
            $count = $this->companyRepository->countByName($text);
        } else {
            $companies = $this->companyRepository->get($page);
            $count = $this->companyRepository->count();
        }

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
                'callback_data' => 'company_list:' . ($page + 1) . ':' . $text,
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

        $response = $this->telegramBot->sendMessage(
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
    }

    public function sendCompany(string $chat, int $company_id, int $user_chat_id)
    {
        $company = $this->companyRepository->find($company_id);
        if (!$company) return;
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

    public function sendCompanyReview(string $chat, int $company_id, int $page = 0)
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
        Log::debug('sendCompanyReview = ' . json_encode($response));

        if ($review->reviewFiles && count($review->reviewFiles) > 0) {
            $response = $this->telegramBot->sendPhotos($chat, $review->reviewFiles);
        }

        $this->contextRepository->reset($chat);
        Log::debug('sendPhotos (sendMediaGroup) = ' . json_encode($response));
    }

    public function sendCompanyGrade(string $chat, int $company_id, int $grade)
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

    public function handleReviewComment(string $chat, string $comment, Context $context)
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

    public function handleReviewFiles(string $chat, array $photo, Context $context)
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
        Log::debug('set session context: id=' . $context->id . ', chat=' . $context->chat
            . ', company_id=' . $context->company_id . ', grade=' . $context->grade . ', status=' . $context->status
            . ', comment=' . $context->comment. ', files=' . $context->files);

        $response = $this->telegramBot->sendMessage(
            $chat,
            $text,
            [
                'inline_keyboard' => [[[
                    'text' => 'Сохранить отзыв',
                    'callback_data' => 'save_review_from_context',
                ]]]
            ]
        );
        Log::debug('handleReviewFiles sendMessage=' . json_encode($response));
    }

    public function saveFileFromUser(string $tg_file_id): ?string
    {
        $fileData = $this->telegramBot->getFile($tg_file_id);
        $fileData = $fileData['result'] ?? [];
        if (!$fileData) return null;

        $fileUrl = $this->telegramBot->fileUrl($fileData['file_path'] ?? '');
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
                $filePath = $this->saveFileFromUser($fileData['file_id']);
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
        $this->telegramBot->sendMessage($user->chat, 'Спасибо за отзыв!');
    }

    public function setWaitCompanySearch(string $chat)
    {
        $context = $this->contextRepository->findByChat($chat);
        if (!$context) $context = new Context();
        $context->chat = $chat;
        $context->status = self::CONTEXT_STATUS_WAIT_SEARCH_COMPANY;
        $context->comment = '';
        $context->files = json_encode([]);
        $this->contextRepository->save($context);
        Log::debug('set session context: id=' . $context->id . ', chat=' . $context->chat
            . ', company_id=' . $context->company_id . ', grade=' . $context->grade . ', status=' . $context->status
            . ', comment=' . $context->comment);
        $this->telegramBot->sendMessage($chat, 'Введите название компании');
    }
}

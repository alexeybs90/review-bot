<?php

namespace App\Http\Controllers;

use App\Lib\TelegramBot;
use App\Repositories\ChatRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ContextRepository;
use App\Repositories\ReviewRepository;
use App\Services\ReviewBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewBotController extends Controller
{
    public ReviewBotService $service;

    public function __construct() {
        $companyRepository = new CompanyRepository();
        $contextRepository = new ContextRepository();
        $reviewRepository = new ReviewRepository();
        $chatRepository = new ChatRepository();
        $telegramBot = new TelegramBot(config('app.telegram_bot_api_key'), config('app.telegram_bot_api_webhook_url'));
        $this->service = new ReviewBotService($companyRepository, $contextRepository, $reviewRepository, $chatRepository, $telegramBot);
    }

    public function home(Request $request)
    {
//        Company::create(['name' => 'Сбербанк']);
        $response = $this->service->info();
        dd($response);
    }

    public function sendTest(Request $request)
    {
        $response = $this->service->sendTest();
        dd($response);
    }

    public function setWebhook(Request $request)
    {
        $response = $this->service->setWebhook();
        dd($response);
    }

    public function handle(Request $request)
    {
        Log::debug('handler = ' . json_encode($request->post()));

        $message = $request->post('message');
        $callback_query = $request->post('callback_query');
        if (!$message && $callback_query) $message = $callback_query['message'];
        $chat = (string)$message['chat']['id'];
        $text = $message['text'] ?? '';
        $name = $message['from']['first_name'];
        $phone = $message['contact']['phone_number'] ?? '';
        $callback_query_data_str = $callback_query ? $callback_query['data'] : '';
        $callback_query_data = explode(':', $callback_query_data_str);
        $callback_query_data_action = $callback_query_data ? $callback_query_data[0] : '';

        $user = $this->service->findOrCreateUser($chat, $name); //TODO: убрать из контроллера в сервис
        if (!$phone && !$user->phone) {
            $this->service->sendPhoneButton($chat);
            return response()->json([]);
        } elseif ($phone && !$user->phone) {
            $user->phone = $phone;
            $this->service->saveUser($user);
            $this->service->sendHello($chat);
            return response()->json([]);
        }

        if ($text === '/search_company' || $text === 'Поиск компании' || $callback_query_data_action === 'search_company') {
            $page = $callback_query_data && isset($callback_query_data[1]) ? $callback_query_data[1] : 0;
            $this->service->sendCompanies($chat, $page);
            return response()->json([]);
        }

        if ($callback_query_data && $callback_query_data_action) {
            switch ($callback_query_data_action) {
                case 'start_review_company_id':
                    $company_id = $callback_query_data[1];
                    $this->service->sendCompany($chat, $company_id, $user->id);
                    return response()->json([]);
                case 'send_company_grade':
                    $company_id = $callback_query_data[1];
                    $grade = $callback_query_data[2];
                    $this->service->sendCompanyGrade($chat, $company_id, $grade);
                    return response()->json([]);
                case 'show_reviews_company_id':
                    $company_id = $callback_query_data[1];
                    $page = $callback_query_data[2] ?? 0;
                    $this->service->sendCompanyReview($chat, $company_id, $page);
                    return response()->json([]);
            }
        }

        if ($this->service->handleContextActions($chat, $user, $text)) {
            return response()->json([]);
        }

        $this->service->sendHello($chat);

        return response()->json([]);
    }
}

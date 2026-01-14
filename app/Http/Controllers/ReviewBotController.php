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
        print_r($response);
    }

    public function sendTest(Request $request)
    {
        $response = $this->service->sendTest();
        print_r($response);
    }

    public function setWebhook(Request $request)
    {
        $response = $this->service->setWebhook();
        print_r($response);
    }

    public function handle(Request $request)
    {
        Log::debug('handler = ' . json_encode($request->post()));

        if ($this->service->initMessageData($request->post('message'), $request->post('callback_query'))) {
            return response()->json([]);
        }

        if ($this->service->handleTextRequest()) {
            return response()->json([]);
        }

        if ($this->service->handleCallbackQueryRequest()) {
            return response()->json([]);
        }

        if ($this->service->handleContextActions()) {
            return response()->json([]);
        }

        $this->service->sendHello();

        return response()->json([]);
    }
}

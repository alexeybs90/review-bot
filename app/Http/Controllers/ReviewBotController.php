<?php

namespace App\Http\Controllers;

use App\Http\Requests\TelegramWebHookRequest;
use App\Services\ReviewBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewBotController extends Controller
{
    public function __construct(protected ReviewBotService $service)
    {}

    public function home()
    {
//        Company::create(['name' => 'Сбербанк']);
        $response = $this->service->info();
        print_r($response);
    }

    public function sendTest()
    {
        $response = $this->service->sendTest();
        print_r($response);
    }

    public function setWebhook()
    {
        $response = $this->service->setWebhook();
        print_r($response);
    }

    public function handle(TelegramWebHookRequest $request): JsonResponse
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

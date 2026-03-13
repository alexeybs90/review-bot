<?php

namespace App\Http\Controllers;

use App\DTO\TelegramUpdate;
use App\Http\Requests\TelegramWebHookRequest;
use App\Services\BotResponseService;
use App\Services\ReviewBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReviewBotController extends Controller
{
    public function __construct(protected ReviewBotService $service)
    {}

    public function home(BotResponseService $botResponseService): JsonResponse
    {
//        Company::create(['name' => 'Сбербанк']);
        return response()->json($botResponseService->info());
    }

    public function sendTest(BotResponseService $botResponseService): JsonResponse
    {
        return response()->json($botResponseService->sendTest());
    }

    public function setWebhook(BotResponseService $botResponseService): JsonResponse
    {
        return response()->json($botResponseService->setWebhook());
    }

    public function handle(TelegramWebHookRequest $request): JsonResponse
    {
        Log::debug('handler = ' . json_encode($request->post()));
        $update = TelegramUpdate::fromArray($request->post());

        $this->service->handleUpdate($update);

        return response()->json([]);
    }
}

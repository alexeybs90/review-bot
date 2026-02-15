<?php

namespace App\Providers;

use App\Actions\SaveUserFileAction;
use App\Lib\TelegramBot;
use App\Services\BotResponseService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $botKey = config('app.telegram_bot_api_key');
        $botUrl = config('app.telegram_bot_api_webhook_url');

        $this->app->bind(SaveUserFileAction::class, function () use ($botKey, $botUrl) {
            $telegramBot = new TelegramBot($botKey, $botUrl);
            return new SaveUserFileAction($telegramBot);
        });

        $this->app->bind(BotResponseService::class, function () use ($botKey, $botUrl) {
            $telegramBot = new TelegramBot($botKey, $botUrl);
            return new BotResponseService($telegramBot);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

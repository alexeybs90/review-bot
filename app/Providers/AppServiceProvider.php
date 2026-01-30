<?php

namespace App\Providers;

use App\Lib\TelegramBot;
use App\Repositories\ChatRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ContextRepository;
use App\Repositories\ReviewRepository;
use App\Services\ReviewBotService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReviewBotService::class, function () {
            $companyRepository = new CompanyRepository();
            $contextRepository = new ContextRepository();
            $reviewRepository = new ReviewRepository();
            $chatRepository = new ChatRepository();
            $telegramBot = new TelegramBot(config('app.telegram_bot_api_key'),
                config('app.telegram_bot_api_webhook_url'));
            return new ReviewBotService($companyRepository, $contextRepository, $reviewRepository,
                $chatRepository, $telegramBot);
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

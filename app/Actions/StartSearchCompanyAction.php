<?php

namespace App\Actions;

use App\Constants\ReviewBotConstants;
use App\Models\Context;
use App\Repositories\ContextRepository;
use App\Services\BotResponseService;

class StartSearchCompanyAction
{
    public function __construct(
        protected ContextRepository $contextRepository,
        protected BotResponseService $botResponseService)
    {}

    public function execute(string $chat): void
    {
        $context = $this->contextRepository->findByChat($chat);
        if (!$context) $context = new Context();
        $context->chat = $chat;
        $context->status = ReviewBotConstants::CONTEXT_STATUS_WAIT_SEARCH_COMPANY;
        $context->comment = '';
        $context->files = json_encode([]);
        $this->contextRepository->save($context);
        $this->botResponseService->sendRequestForCompanySearch($chat);
    }
}

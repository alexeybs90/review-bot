<?php

namespace App\Actions;

use App\Repositories\CompanyRepository;
use App\Repositories\ContextRepository;
use App\Repositories\ReviewRepository;
use App\Services\BotResponseService;

class StartReviewAction
{
    public function __construct(
        protected CompanyRepository $companyRepository,
        protected ContextRepository $contextRepository,
        protected ReviewRepository $reviewRepository,
        protected BotResponseService $botResponseService)
    {}

    public function execute(string $chat, int $companyId, int $chatId): void
    {
        $company = $this->companyRepository->find($companyId);
        if (!$company) {
            $this->botResponseService->sendNotFound($chat);
            return;
        }
        $review = $this->reviewRepository->findByChatIdAndCompanyId($chatId, $companyId);
        $this->contextRepository->reset($chat);
        $this->botResponseService->sendRequestForReviewGrade($chat, $company, $review);
    }
}

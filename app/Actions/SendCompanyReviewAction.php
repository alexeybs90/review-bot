<?php

namespace App\Actions;

use App\Repositories\ChatRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ContextRepository;
use App\Repositories\ReviewRepository;
use App\Services\BotResponseService;

class SendCompanyReviewAction
{
    public function __construct(
        protected ChatRepository $chatRepository,
        protected CompanyRepository $companyRepository,
        protected ReviewRepository $reviewRepository,
        protected ContextRepository $contextRepository,
        protected BotResponseService $botResponseService)
    {}

    public function execute(string $chat, int $companyId, int $page = 0): void
    {
        $review = $this->reviewRepository->findOneByCompanyId($companyId, $page);
        $count = $this->reviewRepository->countByCompanyId($companyId);
        if (!$review) {
            $this->botResponseService->sendNotFound($chat);
            return;
        }
        $company = $this->companyRepository->find($companyId);
        $user = $this->chatRepository->find($review->chat_id);
        $this->contextRepository->reset($chat);
        $this->botResponseService->sendCompanyReview($chat, $review, $company, $user, $page, $count);
    }
}

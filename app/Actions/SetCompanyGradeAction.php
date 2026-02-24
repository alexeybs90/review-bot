<?php

namespace App\Actions;

use App\Constants\ReviewBotConstants;
use App\Models\Context;
use App\Repositories\CompanyRepository;
use App\Repositories\ContextRepository;
use App\Services\BotResponseService;

class SetCompanyGradeAction
{
    public function __construct(
        protected ContextRepository $contextRepository,
        protected CompanyRepository $companyRepository,
        protected BotResponseService $botResponseService)
    {}

    public function execute(string $chat, int $companyId, int $grade): void
    {
        $company = $this->companyRepository->find($companyId);
        if (!$company) {
            $this->botResponseService->sendNotFound($chat);
            return;
        }
        $context = $this->contextRepository->findByChat($chat);
        if (!$context) $context = new Context();
        $context->chat = $chat;
        $context->status = ReviewBotConstants::CONTEXT_STATUS_WAIT_REVIEW_COMMENT;
        $context->company_id = $companyId;
        $context->grade = $grade;
        $context->comment = '';
        $context->files = json_encode([]);
        $this->contextRepository->save($context);

        $this->botResponseService->sendRequestForReviewComment($chat, $grade, $company);
    }
}

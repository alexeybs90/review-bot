<?php

namespace App\Actions;

use App\Constants\ReviewBotConstants;
use App\Models\Context;
use App\Repositories\CompanyRepository;
use App\Repositories\ContextRepository;
use App\Services\BotResponseService;

class HandleReviewCommentAction
{
    public function __construct(
        protected ContextRepository $contextRepository,
        protected CompanyRepository $companyRepository,
        protected BotResponseService $botResponseService
    )
    {}

    public function execute(string $chat, string $comment, Context $context): void
    {
        $company = $this->companyRepository->find($context->company_id);
        $context->status = ReviewBotConstants::CONTEXT_STATUS_WAIT_REVIEW_FILES;
        $context->comment = $comment;
        $this->contextRepository->save($context);

        $this->botResponseService->sendRequestForReviewFiles($chat, $context, $company);
    }
}

<?php

namespace App\Actions;

use App\Constants\ReviewBotConstants;
use App\Models\Context;
use App\Repositories\CompanyRepository;
use App\Services\BotResponseService;

class SearchCompaniesAction
{
    public function __construct(
        protected CompanyRepository $companyRepository,
        protected BotResponseService $botResponseService)
    {}

    public function execute(string $chat, int $page, $text = '', Context $context = null): void
    {
        if ($context && $context->status === ReviewBotConstants::CONTEXT_STATUS_WAIT_SEARCH_COMPANY && $text) {
            $companies = $this->companyRepository->getByName($text, $page);
            $count = $this->companyRepository->countByName($text);
        } else {
            $companies = $this->companyRepository->get($page);
            $count = $this->companyRepository->count();
        }
        $this->botResponseService->searchCompanies($chat, $page, $text, $companies, $count);
    }
}

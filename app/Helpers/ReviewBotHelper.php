<?php

namespace App\Helpers;

class ReviewBotHelper
{
    const COMPANY_LIMIT = 10;

    const CONTEXT_STATUS_WAIT_REVIEW_COMMENT = 'waiting_review_comment';
    const CONTEXT_STATUS_WAIT_REVIEW_FILES = 'waiting_review_files';
    const CONTEXT_STATUS_WAIT_SEARCH_COMPANY = 'waiting_search_company';

    const CALLBACK_ACTION_START_REVIEW = 'start_review_company_id';
    const CALLBACK_ACTION_SET_GRADE = 'set_company_grade';
    const CALLBACK_ACTION_SAVE_REVIEW = 'save_review_from_context';
    const CALLBACK_ACTION_SHOW_REVIEW = 'show_reviews_company_id';
    const CALLBACK_ACTION_COMPANY_LIST = 'company_list';
}

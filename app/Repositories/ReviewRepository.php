<?php

namespace App\Repositories;

use App\Models\Review;

class ReviewRepository
{
    public function findByCompanyId(int $company_id)
    {
        return Review::where('company_id', $company_id)->get();
    }

    public function findByChatIdAndCompanyId(int $user_chat_id, int $company_id)
    {
        return Review::where('chat_id', $user_chat_id)->where('company_id', $company_id)->first();
    }

    public function save(Review $review): bool
    {
        return $review->save();
    }

    public function findOneByCompanyId(int $company_id, $page = 0)
    {
        return Review::where('company_id', $company_id)
            ->orderBy('created_at', 'DESC')
            ->offset($page)
            ->first();
    }
}

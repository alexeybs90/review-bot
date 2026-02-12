<?php

namespace App\Repositories;

use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewRepository
{
    public function findByCompanyId(int $companyId)
    {
        return Review::where('company_id', $companyId)->get();
    }

    public function findByChatIdAndCompanyId(int $chatId, int $companyId): ?Review
    {
        return Review::where('chat_id', $chatId)->where('company_id', $companyId)->first();
    }

    public function save(Review $review): bool
    {
        return $review->save();
    }

    public function findOneByCompanyId(int $companyId, $page = 0): ?Review
    {
        return Review::with('reviewFiles')
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'DESC')
            ->offset($page)
            ->first();
    }

    public function countByCompanyId(int $companyId): int
    {
        return DB::table('reviews')->where('company_id', $companyId)->count();
    }
}

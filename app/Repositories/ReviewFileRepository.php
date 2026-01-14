<?php

namespace App\Repositories;

use App\Models\ReviewFile;
use Illuminate\Support\Facades\DB;

class ReviewFileRepository
{
    public function save(ReviewFile $file): bool
    {
        return $file->save();
    }

    public function findByReviewId(int $review_id)
    {
        return ReviewFile::where('review_id', $review_id)
            ->orderBy('created_at', 'DESC')
            ->limit(3)
            ->get();
    }

    public function delete(ReviewFile $file): ?bool
    {
        return $file->delete();
    }
}

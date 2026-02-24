<?php

namespace App\Repositories;

use App\Models\ReviewFile;
use Illuminate\Database\Eloquent\Collection;

class ReviewFileRepository
{
    public function save(ReviewFile $file): bool
    {
        return $file->save();
    }

    public function findByReviewId(int $review_id): Collection
    {
        return ReviewFile::query()->where('review_id', $review_id)
            ->orderBy('created_at', 'DESC')
            ->limit(3)
            ->get();
    }

    public function delete(ReviewFile $file): ?bool
    {
        return $file->delete();
    }
}

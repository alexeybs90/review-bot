<?php

namespace App\Repositories;

use App\Helpers\ReviewBotHelper;
use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CompanyRepository
{
    public function find($id): ?Company
    {
        return Company::find($id);
    }

    public function get($page = 0): Collection
    {
        return Company::with('reviews')
            ->orderBy('name', 'ASC')
            ->offset($page * ReviewBotHelper::COMPANY_LIMIT)
            ->limit(ReviewBotHelper::COMPANY_LIMIT)
            ->get();
    }

    public function getByName(string $name, $page = 0): Collection
    {
        return Company::with('reviews')
            ->where('name', 'LIKE', "%{$name}%")
            ->orderBy('name', 'ASC')
            ->offset($page * ReviewBotHelper::COMPANY_LIMIT)
            ->limit(ReviewBotHelper::COMPANY_LIMIT)
            ->get();
    }

    public function count(): int
    {
        return DB::table((new Company())->getTable())->count();
    }

    public function countByName(string $name): int
    {
        return DB::table((new Company())->getTable())
            ->where('name', 'LIKE', "%{$name}%")
            ->count();
    }
}

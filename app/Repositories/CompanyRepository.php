<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanyRepository
{
    const LIMIT = 10;

    public function find($id)
    {
        return Company::find($id);
    }

    public function get($page = 0): \Illuminate\Database\Eloquent\Collection
    {
        return Company::with('reviews')
            ->orderBy('name', 'ASC')
            ->offset($page * self::LIMIT)
            ->limit(self::LIMIT)
            ->get();
    }

    public function getByName(string $name, $page = 0): \Illuminate\Database\Eloquent\Collection
    {
        return Company::with('reviews')
            ->where('name', 'LIKE', "%{$name}%")
            ->orderBy('name', 'ASC')
            ->offset($page * self::LIMIT)
            ->limit(self::LIMIT)
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

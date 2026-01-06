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

    public function get($page = 0)
    {
        return Company::with('reviews')
            ->orderBy('name', 'ASC')
            ->offset($page * self::LIMIT)
            ->limit(self::LIMIT)
            ->get();
    }

    public function count()
    {
        return DB::table('companies')->count();
    }
}

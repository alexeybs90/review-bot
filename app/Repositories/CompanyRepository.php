<?php

namespace App\Repositories;

use App\Models\Company;

class CompanyRepository
{
    const LIMIT = 5;

    public function find($id)
    {
        return Company::find($id);
    }

    public function get($page = 0)
    {
        return Company::orderBy('name', 'ASC')->offset($page * self::LIMIT)->limit(self::LIMIT)->get();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\CompanyImage;
use App\Repositories\CompanyRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function __construct(protected CompanyRepository $companyRepository)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = (int)$request->query('page', 0);
        $companies = $this->companyRepository->get($page);
        return response()->json($companies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        $company = Company::create($request->all());

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('companies/' . $company->id, 'public');
                $company->images()->create(['path' => $path, 'original_name' => $file->getClientOriginalName()]);
            }
        }

        return response()->json($company->load('reviews')->load('images'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        return response()->json($company->load('reviews')->load('images'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $company->update($request->all());

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('companies/' . $company->id, 'public');
                $company->images()->create(['path' => $path, 'original_name' => $file->getClientOriginalName()]);
            }
        }

        return response()->json($company->load('reviews')->load('images'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $company->delete();
        return response()->noContent();
    }

    public function destroyImage(CompanyImage $image)
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->noContent();
    }
}

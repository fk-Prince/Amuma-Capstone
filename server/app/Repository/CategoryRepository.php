<?php

namespace App\Repository;

use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryRepository
{
    public function paginate(int $perPage, ?string $companyId = null)
    {
        $query = Category::latest();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->paginate($perPage);
    }

    public function getCategories()
    {
        return Category::all();
    }

    public function create(array $payload)
    {
        return Category::create($payload);
    }

    public function findByFields(array $conditions)
    {
        return Category::where($conditions)->first();
    }
}

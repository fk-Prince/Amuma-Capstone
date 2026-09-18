<?php

namespace App\Http\Controllers;

use App\Service\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{

    public function __construct(private CategoryService $categoryService) {}

    public function index(Request $request)
    {
        return $this->categoryService->listCategory($request->user(), $request->all());
    }
}

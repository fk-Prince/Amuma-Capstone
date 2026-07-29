<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Service\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    private ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function store(ReviewRequest $request)
    {
        return $this->reviewService->createReview($request->user(), $request->all());
    }

    public function publicReviews(Request $request)
    {
        return $this->reviewService->retrieveReview($request->all());
    }
}

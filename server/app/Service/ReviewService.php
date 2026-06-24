<?php

namespace App\Service;

use App\Repository\ReviewRepository;
use App\Http\Resources\ReviewResource;
use App\Models\User;
use App\Repository\BranchRepository;
use Exception;

class ReviewService
{
    private ReviewRepository $reviewRepository;
    private  BranchRepository $branchRepository;

    public function __construct(ReviewRepository $reviewRepository, BranchRepository $branchRepository)
    {
        $this->reviewRepository = $reviewRepository;
        $this->branchRepository = $branchRepository;
    }

    public function createReview(User $user, array $payload)
    {

        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);
        if (!$branch) {
            throw new Exception(__('Branch does not exist'), 404);
        }

        $reviewData = [
            'branch_id' => $branch->branch_id ?? null,
            'user_id' => $user->user_id,
            'rate' => $payload['rate'],
            'description' => $payload['description'],
        ];

        $review = $this->reviewRepository->create($reviewData);

        return response()->json([
            'success' => true,
            'message' => __('Review successfully submited.'),
            'data' => $review
        ], 201);
    }
}

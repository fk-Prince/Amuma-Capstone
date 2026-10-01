<?php

namespace App\Service;

use App\Guard\BranchGuard;
use App\Repository\ReviewRepository;
use App\Models\User;
use App\Service\External\SupabaseService;

class ReviewService
{
    private ReviewRepository $reviewRepository;

    public function __construct(ReviewRepository $reviewRepository)
    {
        $this->reviewRepository = $reviewRepository;
    }

    public function createReview(User $user, array $payload)
    {
        $branch = !empty($payload['branch_uuid'])
            ? BranchGuard::resolveBranch($payload['branch_uuid'])
            : null;

        $reviewData = [
            'branch_id' => $branch?->branch_id,
            'user_id' => $user->user_id,
            'rate' => $payload['rate'],
            'description' => $payload['description'],
        ];

        if (!empty($payload['image'])) {
            $reviewData['image'] = SupabaseService::store($payload['image'])['url'];
        }

        $review = $this->reviewRepository->create($reviewData, $branch?->uuid);

        return response()->json([
            'success' => true,
            'message' => __('Review successfully submited.'),
            'data' => $review
        ], 201);
    }

    public function retrieveReview(array $payload)
    {
        $branch = !empty($payload['branch_uuid'])
            ? BranchGuard::resolveBranch($payload['branch_uuid'])
            : null;

        return $this->reviewRepository->paginate($payload['per_page'],  $branch?->uuid,  $payload['rate'] ?? null,   $payload['withComments'] ?? false,   $payload['withMedia'] ?? false);
    }
}

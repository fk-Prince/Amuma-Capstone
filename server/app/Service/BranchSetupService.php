<?php

namespace App\Service;

use App\Models\Branch;
use App\Models\Plan;
use App\Repository\BranchRepository;
use Exception;

class BranchSetupService
{
    public const VISITS = ['details', 'operations', 'subscription'];

    public function __construct(private BranchRepository $branchRepository) {}

    public function status(Branch $branch): array
    {
        $planCode = $branch->subscriptionLink?->effectivePlan()?->plan_code;
        $homecare = Plan::covers($planCode, Plan::CODE_HOMECARE);
        $facility = Plan::covers($planCode, Plan::CODE_FACILITY);

        $visits = $branch->settings['setup_visits'] ?? [];
        $counts = $this->branchRepository->setupCounts($branch);

        $steps = array_values(array_filter([
            [
                'key' => 'details',
                'done' => !empty($visits['details']) && !empty($visits['operations']),
                'visited' => [
                    'details' => !empty($visits['details']),
                    'operations' => !empty($visits['operations']),
                ],
            ],
            ['key' => 'staff', 'done' => $counts['staff'] > 0, 'count' => $counts['staff']],
            ['key' => 'services', 'done' => $counts['services'] > 0, 'count' => $counts['services']],
            $facility ? ['key' => 'rooms', 'done' => $counts['rooms'] > 0, 'count' => $counts['rooms']] : null,
            [
                'key' => 'contracts',
                'done' => ($homecare && $counts['homecare_plans'] > 0)
                    || ($facility && $counts['facility_plans'] > 0)
                    || (!$homecare && !$facility && $counts['homecare_plans'] + $counts['facility_plans'] > 0),
                'homecare' => $homecare,
                'facility' => $facility,
            ],
            ['key' => 'subscription', 'done' => !empty($visits['subscription'])],
        ]));

        $complete = collect($steps)->every('done');
        $completedAt = $branch->settings['setup_completed_at'] ?? null;

        if ($complete && !$completedAt) {
            $completedAt = now()->toDateTimeString();
            $this->branchRepository->putSetting($branch, 'setup_completed_at', $completedAt);
        }

        return [
            'steps' => $steps,
            'complete' => $complete,
            'completed_at' => $completedAt,
        ];
    }

    public function markVisit(Branch $branch, string $step): array
    {
        if (!in_array($step, self::VISITS, true)) {
            throw new Exception('Unknown setup step.', 422);
        }

        if (empty($branch->settings['setup_visits'][$step])) {
            $this->branchRepository->mergeSetting($branch, 'setup_visits', [$step => now()->toDateTimeString()]);
        }

        return $this->status($branch);
    }

    public function assertComplete(Branch $branch): void
    {
        $status = $this->status($branch);

        if ($status['complete']) {
            return;
        }

        $labels = [
            'details' => 'open Branch Information and Operation Settings',
            'staff' => 'add an employee',
            'services' => 'add a service',
            'rooms' => 'add a room with a bed',
            'contracts' => 'add a care plan',
            'subscription' => 'open Manage Subscription',
        ];

        $missing = collect($status['steps'])
            ->reject(fn(array $step) => $step['done'])
            ->map(fn(array $step) => $labels[$step['key']])
            ->implode(', ');

        throw new Exception(
            "Finish setting up {$branch->name} before it goes live: {$missing}.",
            422
        );
    }
}

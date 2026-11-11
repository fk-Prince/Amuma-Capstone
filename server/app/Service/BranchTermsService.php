<?php

namespace App\Service;

use App\Models\Branch;
use App\Repository\BranchRepository;
use Exception;

class BranchTermsService
{
    public const DOCS = ['booking_terms', 'balance_terms'];

    public const BOOKING_TYPES = ['homecare', 'complete', 'pre'];

    public const MAX_SECTIONS = 6;

    public const ANCHORS = [
        'booking_terms' => [
            'scope',
            'request',
            'payment_required',
            'payment_methods',
            'expiry',
            'rejected',
            'approved',
            'cancelling',
            'discharge',
            'accuracy',
            'liability',
            'governing_law',
            'changes',
        ],
        'balance_terms' => [
            'scope',
            'payment_methods',
            'payment_applied',
            'authorization',
            'accuracy',
            'processing',
            'adjustments',
            'early_discharge',
            'refunds',
            'non_refundable',
            'security',
            'changes',
        ],
    ];

    public function __construct(private BranchRepository $branchRepository) {}

    public function forBranch(string $uuid): array
    {
        $branch = $this->branchRepository->findByField('uuid', $uuid);

        if (!$branch) {
            throw new Exception('Branch not found.', 404);
        }

        $settings = $branch->settings ?? [];

        return [
            'name' => $branch->name,
            'booking_percent' => (int) ($settings['complete_admission_booking_percent'] ?? 100),
            'downpayment_percent' => (int) ($settings['homecare_downpayment_percent'] ?? 50),
            'discharge_percent' => (int) ($settings['force_discharge_paid_percent'] ?? 50),
            'booking_terms' => $this->stored($settings, 'booking_terms'),
            'balance_terms' => $this->stored($settings, 'balance_terms'),
        ];
    }

    public function sanitize(Branch $branch, string $doc, mixed $input): array
    {
        $sections = is_array($input) ? ($input['sections'] ?? []) : [];

        if (!is_array($sections)) {
            throw new Exception('The terms sections are invalid.', 422);
        }

        if (count($sections) > self::MAX_SECTIONS) {
            throw new Exception('You can add up to ' . self::MAX_SECTIONS . ' sections.', 422);
        }

        $clean = array_values(array_map(fn($section) => $this->section($doc, $section), $sections));
        $current = $this->stored($branch->settings ?? [], $doc);

        return [
            'updated_at' => $clean === $current['sections'] ? $current['updated_at'] : now()->toIso8601String(),
            'sections' => $clean,
        ];
    }

    private function section(string $doc, mixed $section): array
    {
        $title = trim((string) ($section['title'] ?? ''));
        $body = trim((string) ($section['body'] ?? ''));
        $after = (string) ($section['after'] ?? '');

        if ($title === '' || mb_strlen($title) > 80) {
            throw new Exception('Each section needs a title of up to 80 characters.', 422);
        }

        if ($body === '' || mb_strlen($body) > 1500) {
            throw new Exception("\"{$title}\" needs text of up to 1,500 characters.", 422);
        }

        if (!in_array($after, self::ANCHORS[$doc], true)) {
            throw new Exception("\"{$title}\" has an invalid position.", 422);
        }

        $clean = ['after' => $after, 'title' => $title, 'body' => $body];

        if ($doc === 'booking_terms') {
            $showOn = array_values(array_intersect(self::BOOKING_TYPES, (array) ($section['show_on'] ?? [])));

            if (!$showOn) {
                throw new Exception("Pick at least one booking to show \"{$title}\" on.", 422);
            }

            $clean['show_on'] = $showOn;
        }

        return $clean;
    }

    private function stored(array $settings, string $doc): array
    {
        $terms = $settings[$doc] ?? [];

        return [
            'updated_at' => $terms['updated_at'] ?? null,
            'sections' => array_values($terms['sections'] ?? []),
        ];
    }
}

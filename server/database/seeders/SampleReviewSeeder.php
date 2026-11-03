<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * SAMPLE DATA for local testing and demos only.
 *
 * Adds one AMUMA app review per role, written by existing accounts, so the
 * landing page rating and testimonials have something to show. These are not
 * real user reviews: do not present them as such. Not part of DatabaseSeeder.
 *
 * Add:     php artisan db:seed --class=SampleReviewSeeder
 * Remove:  php artisan tinker --execute="Database\Seeders\SampleReviewSeeder::remove();"
 */
class SampleReviewSeeder extends Seeder
{
    /** role => [rate, review text] */
    public const REVIEWS = [
        RoleEnum::AgencyOwner->value => [5, 'Managing our homecare and facility branches in one place makes subscriptions, staff and billing much easier to keep track of.'],
        RoleEnum::BranchManager->value => [5, 'The branch dashboard shows bookings, bed occupancy and schedules at a glance.'],
        RoleEnum::Admission->value => [4, 'Booking requests and admissions are easy to follow, and the system warns me when a staff assignment conflicts with another schedule.'],
        RoleEnum::Nurse->value => [5, 'Recording doses on the medication grid is quick, and seeing allergies on the same screen helps.'],
        RoleEnum::Caregiver->value => [4, 'QR check-in at visits is simple, and my schedule is clear on my phone.'],
        RoleEnum::Cashier->value => [4, 'Invoices are generated for each admission, and online payments are recorded automatically.'],
        'client' => [5, 'I can see my loved one\'s schedule, medications and updates online, and pay our balance without going to the facility.'],
    ];

    public function run(): void
    {
        $userIds = $this->pickUsers();
        $added = 0;

        foreach (self::REVIEWS as $role => [$rate, $text]) {
            $userId = $userIds[$role] ?? null;

            if (!$userId) {
                $this->command?->warn("No {$role} account found - skipped.");
                continue;
            }

            $review = Review::firstOrCreate(
                ['user_id' => $userId, 'branch_id' => null, 'description' => $text],
                ['rate' => $rate],
            );

            if ($review->wasRecentlyCreated) {
                $added++;
            }
        }

        Cache::forget('reviews:app');

        $this->command?->info("Sample app reviews added: {$added}");
    }

    /** Deletes only the sample reviews this seeder adds. */
    public static function remove(): int
    {
        $texts = array_map(fn($review) => $review[1], self::REVIEWS);

        $deleted = Review::whereNull('branch_id')
            ->whereIn('description', $texts)
            ->get()
            ->each->delete()
            ->count();

        Cache::forget('reviews:app');

        echo "Sample app reviews removed: {$deleted}" . PHP_EOL;

        return $deleted;
    }

    /** First existing account for each role, plus one family client. */
    private function pickUsers(): array
    {
        $users = [];

        DB::table('employee_branches as eb')
            ->join('employees as e', 'e.employee_id', '=', 'eb.employee_id')
            ->orderBy('eb.employee_branch_id')
            ->get(['eb.role_name', 'e.user_id'])
            ->each(function ($row) use (&$users) {
                $role = RoleEnum::slug($row->role_name);
                $users[$role] ??= $row->user_id;
            });

        // A family client who is not also staff (staff roles take priority on reviews).
        $users['client'] = DB::table('clients')
            ->whereNotIn('user_id', DB::table('employees')->whereNotNull('user_id')->select('user_id'))
            ->orderBy('client_id')
            ->value('user_id');

        return $users;
    }
}

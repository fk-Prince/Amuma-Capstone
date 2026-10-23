<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Http\Controllers\SandboxController;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\Patient;
use App\Models\PatientAccess;
use App\Models\Plan;
use App\Models\Room;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\Service\Sandbox\SandboxClient;
use App\Service\Sandbox\SandboxService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class SandboxProvisionTest extends TestCase
{
    use UsesTempDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'owner' => [
                'email' => 'owner@agency.test',
                'password' => Hash::make('secret-password'),
                'provider' => 'local',
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
            ],
            'agency' => [
                'uuid' => (string) Str::uuid(),
                'name' => 'Reyes Care',
                'email' => 'info@reyes.test',
                'location' => ['street' => '1 Main St', 'city' => 'Davao City', 'province' => 'Davao del Sur', 'country' => 'Philippines'],
            ],
            'branch' => [
                'uuid' => (string) Str::uuid(),
                'name' => 'Reyes Care Matina',
                'email' => 'matina@reyes.test',
                'contact_number' => '9171234567',
                'settings' => ['currency' => 'PHP', 'is_open' => '1'],
                'location' => ['street' => '2 Matina Rd', 'city' => 'Davao City', 'province' => 'Davao del Sur', 'country' => 'Philippines'],
            ],
            'plan' => ['plan_code' => 'C', 'type' => Plan::TYPE_SME],
        ], $overrides);
    }

    public function test_the_endpoint_refuses_without_the_sandbox_flag_or_secret(): void
    {
        $controller = app(SandboxController::class);

        config(['app.sandbox' => false, 'services.sandbox.secret' => 's3cret']);
        $this->assertForbidden(fn() => $controller->provision($this->request('s3cret')));

        config(['app.sandbox' => true]);
        $this->assertForbidden(fn() => $controller->provision($this->request('wrong')));
        $this->assertForbidden(fn() => $controller->provision($this->request(null)));
    }

    public function test_provisioning_creates_the_owner_branch_and_demo_data(): void
    {
        $payload = $this->payload();

        app(SandboxService::class)->provision($payload);

        $owner = User::where('email', 'owner@agency.test')->firstOrFail();
        $this->assertTrue(Hash::check('secret-password', $owner->password));

        $branch = Branch::where('uuid', $payload['branch']['uuid'])->firstOrFail();
        $this->assertSame($payload['agency']['uuid'], Agency::findOrFail($branch->agency_id)->uuid);

        $link = BranchSubscription::where('branch_id', $branch->branch_id)->firstOrFail();
        $this->assertSame(BranchSubscription::STATUS_APPROVED, $link->status);

        $subscription = Subscription::findOrFail($link->subscription_id);
        $this->assertSame(Subscription::MODE_LIVE, $subscription->mode);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);

        $this->assertTrue(EmployeeBranch::where('employee_id', $owner->employee->employee_id)
            ->where('branch_id', $branch->branch_id)
            ->where('role_name', RoleEnum::AgencyOwner->value)
            ->exists());

        $this->assertGreaterThan(0, Room::where('branch_id', $branch->branch_id)->count());
        $this->assertGreaterThan(0, Service::where('branch_id', $branch->branch_id)->count());

        $staffEmails = User::whereIn(
            'user_id',
            Employee::whereIn('employee_id', EmployeeBranch::where('branch_id', $branch->branch_id)->pluck('employee_id'))->pluck('user_id')
        )->pluck('email');

        $this->assertTrue($staffEmails->contains(fn($email) => str_starts_with($email, 'nurse.') && str_ends_with($email, '@sandbox.amuma.test')));

        $patients = Patient::where('branch_id', $branch->branch_id)->get();
        $this->assertNotEmpty($patients);

        $family = User::where('email', 'like', 'family.%@sandbox.amuma.test')->firstOrFail();
        $this->assertTrue(PatientAccess::where('client_id', $family->client->client_id)
            ->whereIn('patient_id', $patients->pluck('patient_id'))
            ->exists());
    }

    public function test_provisioning_the_same_branch_twice_changes_nothing(): void
    {
        $payload = $this->payload();

        app(SandboxService::class)->provision($payload);
        $patients = Patient::count();

        app(SandboxService::class)->provision($payload);

        $this->assertSame(1, Branch::where('uuid', $payload['branch']['uuid'])->count());
        $this->assertSame($patients, Patient::count());
    }

    public function test_a_second_branch_joins_the_same_agency_and_owner(): void
    {
        $first = $this->payload();
        $second = $this->payload([
            'agency' => ['uuid' => $first['agency']['uuid']],
            'branch' => ['uuid' => (string) Str::uuid(), 'name' => 'Reyes Care Toril', 'email' => 'toril@reyes.test'],
        ]);

        app(SandboxService::class)->provision($first);
        app(SandboxService::class)->provision($second);

        $this->assertSame(1, Agency::count());
        $this->assertSame(1, User::where('email', 'owner@agency.test')->count());
        $this->assertSame(1, Subscription::count());
        $this->assertSame(2, Branch::where('agency_id', Agency::first()->agency_id)->count());
    }

    public function test_production_sends_the_new_branch_after_the_transaction_commits(): void
    {
        config([
            'app.sandbox' => false,
            'services.sandbox.url' => 'https://sandbox.test',
            'services.sandbox.secret' => 's3cret',
        ]);

        Http::fake(['sandbox.test/*' => Http::response(['message' => 'ok'], 202)]);

        app(ModuleSeeder::class)->run();
        app(PlanSeeder::class)->run();

        $branch = DB::transaction(function () {
            $payload = $this->payload();
            $branch = app(\App\Repository\SandboxRepository::class)->createBranchWithOwner($payload);

            app(SandboxClient::class)->provisionBranch($branch);

            Http::assertNothingSent();

            return $branch;
        });

        Http::assertSent(fn($request) => $request->url() === 'https://sandbox.test/api/sandbox/provision'
            && $request->header('X-Sandbox-Secret')[0] === 's3cret'
            && $request['branch']['uuid'] === $branch->uuid
            && $request['owner']['email'] === 'owner@agency.test'
            && Hash::check('secret-password', $request['owner']['password'])
            && $request['plan']['plan_code'] === 'C');
    }

    public function test_existing_branches_can_be_sent_from_live_in_one_run(): void
    {
        config([
            'app.sandbox' => false,
            'services.sandbox.url' => 'https://sandbox.test',
            'services.sandbox.secret' => 's3cret',
        ]);

        Http::fake(['sandbox.test/*' => Http::response(['message' => 'ok'], 202)]);

        app(ModuleSeeder::class)->run();
        app(PlanSeeder::class)->run();

        $repository = app(\App\Repository\SandboxRepository::class);
        $first = $repository->createBranchWithOwner($this->payload());
        $rejected = $repository->createBranchWithOwner($this->payload(['branch' => ['email' => 'rejected@reyes.test']]));
        BranchSubscription::where('branch_id', $rejected->branch_id)->update(['status' => BranchSubscription::STATUS_REJECTED]);

        $this->artisan('sandbox:provision-existing', ['--pause' => 0])->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(fn($request) => $request['branch']['uuid'] === $first->uuid);
    }

    public function test_sending_existing_branches_refuses_without_sandbox_settings(): void
    {
        config(['app.sandbox' => false, 'services.sandbox.url' => null]);

        $this->artisan('sandbox:provision-existing')->assertFailed();
    }

    public function test_production_does_nothing_without_a_sandbox_url(): void
    {
        config(['app.sandbox' => false, 'services.sandbox.url' => null, 'services.sandbox.secret' => 's3cret']);
        Http::fake();

        app(SandboxClient::class)->provisionBranch(new Branch(['name' => 'x']));

        Http::assertNothingSent();
    }

    private function request(?string $secret): Request
    {
        $request = Request::create('/api/sandbox/provision', 'POST', $this->payload());

        if ($secret !== null) {
            $request->headers->set('X-Sandbox-Secret', $secret);
        }

        return $request;
    }

    private function assertForbidden(callable $call): void
    {
        try {
            $call();
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());

            return;
        }

        $this->fail('Expected a 403.');
    }
}

<?php

namespace Tests\Feature;

use App\Http\Requests\Agency\AgencyUpdateRequest;
use App\Http\Requests\Branch\BranchRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class SettingsUniquenessTest extends TestCase
{
    use UsesTempDatabase;

    private string $branchUuid;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => 'tester@amuma.com',
            'password' => bcrypt('password'),
        ]);

        $agencyId = DB::table('agencies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'AMUMA Incorporation',
            'email' => 'info@amuma.com',
            'registered_by' => $user->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('agencies')->insert([
            'uuid' => (string) Str::uuid(),
            'name' => 'Other Agency',
            'email' => 'other@agency.com',
            'registered_by' => $user->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->branchUuid = (string) Str::uuid();

        DB::table('branches')->insert([
            'uuid' => $this->branchUuid,
            'agency_id' => $agencyId,
            'name' => 'AMUMA Davao City',
            'email' => 'davao@amuma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('branches')->insert([
            'uuid' => (string) Str::uuid(),
            'agency_id' => $agencyId,
            'name' => 'AMUMA Tagum',
            'email' => 'tagum@amuma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function errors(string $requestClass, array $data): array
    {
        $request = $requestClass::create('/x', 'PUT', $data);

        return Validator::make($data, $request->rules())->errors()->toArray();
    }

    private function branchData(array $overrides = []): array
    {
        return array_merge([
            'branch_uuid' => $this->branchUuid,
            'name' => 'AMUMA Davao City',
            'description' => 'desc',
            'location' => [
                'street' => 's',
                'city' => 'c',
                'province' => 'p',
                'country' => 'ph',
            ],
        ], $overrides);
    }

    public function test_branch_can_keep_its_own_name_and_email(): void
    {
        $errors = $this->errors(BranchRequest::class, $this->branchData([
            'email' => 'davao@amuma.com',
        ]));

        $this->assertArrayNotHasKey('name', $errors);
        $this->assertArrayNotHasKey('email', $errors);
    }

    public function test_branch_name_taken_by_a_sibling_is_rejected(): void
    {
        $errors = $this->errors(BranchRequest::class, $this->branchData([
            'name' => 'AMUMA Tagum',
        ]));

        $this->assertArrayHasKey('name', $errors);
    }

    public function test_branch_email_taken_by_another_branch_is_rejected(): void
    {
        $errors = $this->errors(BranchRequest::class, $this->branchData([
            'email' => 'tagum@amuma.com',
        ]));

        $this->assertArrayHasKey('email', $errors);
    }

    public function test_agency_can_keep_its_own_name_and_email(): void
    {
        $errors = $this->errors(AgencyUpdateRequest::class, [
            'branch_uuid' => $this->branchUuid,
            'agency_name' => 'AMUMA Incorporation',
            'agency_email' => 'info@amuma.com',
        ]);

        $this->assertSame([], $errors);
    }

    public function test_agency_name_and_email_taken_by_another_agency_are_rejected(): void
    {
        $errors = $this->errors(AgencyUpdateRequest::class, [
            'branch_uuid' => $this->branchUuid,
            'agency_name' => 'Other Agency',
            'agency_email' => 'other@agency.com',
        ]);

        $this->assertArrayHasKey('agency_name', $errors);
        $this->assertArrayHasKey('agency_email', $errors);
    }
}

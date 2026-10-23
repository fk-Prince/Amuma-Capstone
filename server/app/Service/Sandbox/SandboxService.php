<?php

namespace App\Service\Sandbox;

use App\Repository\SandboxRepository;
use Database\Seeders\BedSeeder;
use Database\Seeders\BranchContractSeeder;
use Database\Seeders\DiagnosisCaseSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\NurseSeeder;
use Database\Seeders\PatientSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SandboxService
{
    private const SETUP_SEEDERS = [
        RoomSeeder::class,
        BedSeeder::class,
        BranchContractSeeder::class,
        ServiceSeeder::class,
        DiagnosisCaseSeeder::class,
        EmployeeSeeder::class,
        NurseSeeder::class,
    ];

    public function __construct(private SandboxRepository $sandboxRepository) {}

    public function setLock(string $agencyUuid, bool $locked): void
    {
        $this->sandboxRepository->setAgencyLock($agencyUuid, $locked);
    }

    public function provision(array $payload): void
    {
        if ($this->sandboxRepository->branchExists($payload['branch']['uuid'])) {
            return;
        }

        set_time_limit(0);
        ignore_user_abort(true);

        if (!$this->sandboxRepository->hasBaseData()) {
            app(ModuleSeeder::class)->run();
            app(PlanSeeder::class)->run();
        }

        $tag = Str::lower(Str::random(6));
        $familyEmail = "family.{$tag}@demo.amuma.test";

        $branch = DB::transaction(function () use ($payload, $familyEmail) {
            $this->sandboxRepository->createFamily($familyEmail);

            return $this->sandboxRepository->createBranchWithOwner($payload);
        });

        try {
            foreach (self::SETUP_SEEDERS as $seeder) {
                app($seeder)->forBranch($branch, $tag)->run();
            }

            app(PatientSeeder::class)
                ->forBranch($branch, $tag)
                ->withGuardian($familyEmail)
                ->run();
        } catch (\Throwable $e) {
            Log::error('Sandbox demo data failed', [
                'branch_uuid' => $branch->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

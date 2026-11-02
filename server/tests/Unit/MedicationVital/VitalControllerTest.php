<?php

namespace Tests\Unit\MedicationVital;

use App\Http\Controllers\VitalController;
use App\Models\Patient;
use App\Models\User;
use App\Models\Vital;
use App\Repository\PatientRepository;
use App\Service\NotificationService;
use App\Service\VitalService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class VitalControllerTest extends TestCase
{
    use UsesTempDatabase;
    use MedicationFixtures;

    private PatientRepository&Stub $patients;
    private NotificationService&MockObject $notifications;
    private VitalController $controller;
    private User $nurse;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patients = $this->createStub(PatientRepository::class);
        $this->notifications = $this->createMock(NotificationService::class);
        $this->controller = new VitalController(
            new VitalService($this->patients, $this->notifications)
        );

        $this->nurse = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => 'nurse@amuma.com',
            'password' => 'password',
        ]);

        $this->setUpMedicationStaff($this->nurse);

        $patientId = DB::table('patients')->insertGetId([
            'branch_id' => $this->branchId,
            'uuid' => (string) Str::uuid(),
            'patient_code' => 'PT-0001',
            'first_name' => 'Lola',
            'last_name' => 'Cruz',
            'gender' => 'female',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->patient = Patient::findOrFail($patientId);
    }

    private function request(string $method, array $data): Request
    {
        return Request::create('/api/vitals', $method, $data + ['branch_uuid' => $this->branchUuid])
            ->setUserResolver(fn() => $this->nurse);
    }

    private function patientFound(): void
    {
        $this->patients->method('findByFields')->willReturn($this->patient);
    }

    private function patientMissing(): void
    {
        $this->patients->method('findByFields')->willReturn(null);
    }

    private function vitalPayload(array $overrides = []): array
    {
        return array_merge([
            'bloodPressureSystolic' => 120,
            'bloodPressureDiastolic' => 80,
            'heartRate' => 72,
            'temperature' => 36.6,
            'oxygenSaturation' => 98,
            'recordedDate' => '2026-09-25',
            'recordedTime' => '08:30',
            'notes' => 'Resting',
        ], $overrides);
    }

    private function assertFailsWith(string $message, callable $action): void
    {
        try {
            $action();
        } catch (Exception $e) {
            $this->assertSame($message, $e->getMessage());

            return;
        }

        $this->fail("Expected the error \"{$message}\".");
    }

    public function test_successfully_saved_vital_signs(): void
    {
        $this->patientFound();

        $response = $this->controller->store($this->request('POST', [
            'patient_uuid' => $this->patient->uuid,
            'payload' => $this->vitalPayload(),
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Successfully saved Vital Signs.', $response->getData(true)['message']);

        $vital = Vital::firstOrFail();

        $this->assertSame($this->patient->patient_id, $vital->patient_id);
        $this->assertSame(120, $vital->blood_pressure_systolic);
        $this->assertSame(72, $vital->heart_rate);
        $this->assertSame('36.60', (string) $vital->temperature);
        $this->assertSame('08:30', $vital->recorded_time);
        $this->assertSame('Resting', $vital->notes);
    }

    public function test_vital_record_not_found(): void
    {
        $this->assertFailsWith('Vital record not found', fn() => $this->controller->update(
            $this->request('PUT', ['patient_uuid' => $this->patient->uuid, 'payload' => ['heartRate' => 90]]),
            '999'
        ));
    }
}

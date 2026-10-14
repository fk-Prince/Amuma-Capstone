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

        $patientId = DB::table('patients')->insertGetId([
            'branch_id' => 1,
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
        return Request::create('/api/vitals', $method, $data)
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

    private function savedVital(array $overrides = []): Vital
    {
        return Vital::create(array_merge([
            'patient_id' => $this->patient->patient_id,
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'heart_rate' => 72,
            'recorded_date' => '2026-09-25',
            'recorded_time' => '08:30',
            'notes' => 'Resting',
        ], $overrides));
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

    public function test_index_fails_when_the_patient_is_not_found(): void
    {
        $this->patientMissing();

        $this->assertFailsWith('Patient not found', fn() => $this->controller->index(
            $this->request('GET', ['patient_uuid' => (string) Str::uuid()])
        ));
    }

    public function test_index_shows_the_newest_entries_first(): void
    {
        $this->patientFound();
        $this->savedVital(['recorded_date' => '2026-09-23', 'recorded_time' => '08:00']);
        $this->savedVital(['recorded_date' => '2026-09-25', 'recorded_time' => '06:00']);
        $this->savedVital(['recorded_date' => '2026-09-25', 'recorded_time' => '14:00']);

        $body = $this->controller->index(
            $this->request('GET', ['patient_uuid' => $this->patient->uuid, 'per_page' => 2])
        )->getData(true);

        $this->assertSame(
            [['2026-09-25', '14:00'], ['2026-09-25', '06:00']],
            collect($body['data'])->map(fn($row) => [$row['recordedDate'], $row['recordedTime']])->all()
        );
        $this->assertSame(3, $body['meta']['total']);
        $this->assertSame(2, $body['meta']['last_page']);
    }

    public function test_patient_not_found(): void
    {
        $this->patientMissing();

        $this->assertFailsWith('Patient not found', fn() => $this->controller->store($this->request('POST', [
            'patient_uuid' => (string) Str::uuid(),
            'payload' => $this->vitalPayload(),
        ])));

        $this->assertSame(0, Vital::count());
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

    public function test_readings_not_taken_are_null_and_heart_rate_is_72(): void
    {
        $this->patientFound();

        $this->controller->store($this->request('POST', [
            'patient_uuid' => $this->patient->uuid,
            'payload' => $this->vitalPayload([
                'bloodPressureSystolic' => null,
                'bloodPressureDiastolic' => null,
                'temperature' => null,
                'oxygenSaturation' => null,
            ]),
        ]));

        $vital = Vital::firstOrFail();

        $this->assertNull($vital->blood_pressure_systolic);
        $this->assertNull($vital->temperature);
        $this->assertSame(72, $vital->heart_rate);
    }

    public function test_notify_patient_access_is_called_once_for_vitals(): void
    {
        $this->patientFound();

        $this->notifications->expects($this->once())
            ->method('notifyPatientAccess')
            ->with(
                $this->identicalTo($this->patient),
                $this->stringContains('Lola Cruz'),
                'Vitals',
                $this->identicalTo($this->nurse)
            );

        $this->controller->store($this->request('POST', [
            'patient_uuid' => $this->patient->uuid,
            'payload' => $this->vitalPayload(),
        ]));
    }

    public function test_no_vital_is_saved_without_a_date(): void
    {
        $this->patientFound();

        $payload = $this->vitalPayload();
        unset($payload['recordedDate']);

        $failed = false;

        try {
            $this->controller->store($this->request('POST', [
                'patient_uuid' => $this->patient->uuid,
                'payload' => $payload,
            ]));
        } catch (\Throwable) {
            $failed = true;
        }

        $this->assertTrue($failed);
        $this->assertSame(0, Vital::count());
    }

    public function test_vital_record_not_found(): void
    {
        $this->assertFailsWith('Vital record not found', fn() => $this->controller->update(
            $this->request('PUT', ['patient_uuid' => $this->patient->uuid, 'payload' => ['heartRate' => 90]]),
            '999'
        ));
    }

    public function test_successfully_updated_vital_signs(): void
    {
        $vital = $this->savedVital();

        $response = $this->controller->update(
            $this->request('PUT', [
                'patient_uuid' => $this->patient->uuid,
                'payload' => ['heartRate' => 90, 'notes' => 'After walking'],
            ]),
            (string) $vital->vital_id
        );

        $this->assertSame('Successfully updated Vital Signs.', $response->getData(true)['message']);

        $vital->refresh();

        $this->assertSame(90, $vital->heart_rate);
        $this->assertSame('After walking', $vital->notes);
        $this->assertSame(120, $vital->blood_pressure_systolic);
        $this->assertSame('08:30', $vital->recorded_time);
    }

    public function test_update_fails_through_another_patient(): void
    {
        $vital = $this->savedVital();

        $this->assertFailsWith('Vital record not found', fn() => $this->controller->update(
            $this->request('PUT', ['patient_uuid' => (string) Str::uuid(), 'payload' => ['heartRate' => 200]]),
            (string) $vital->vital_id
        ));

        $this->assertSame(72, $vital->fresh()->heart_rate);
    }
}

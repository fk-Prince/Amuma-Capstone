<?php

namespace Tests\Unit\MedicationVital;

use App\Http\Controllers\MedicationController;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Repository\PatientRepository;
use App\Service\MedicationService;
use App\Service\NotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class MedicationControllerTest extends TestCase
{
    use UsesTempDatabase;

    private PatientRepository&Stub $patients;
    private NotificationService&MockObject $notifications;
    private MedicationController $controller;
    private User $nurse;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patients = $this->createStub(PatientRepository::class);
        $this->notifications = $this->createMock(NotificationService::class);
        $this->controller = new MedicationController(
            new MedicationService($this->patients, $this->notifications)
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
        return Request::create('/api/medications', $method, $data)
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

    private function medicationPayload(): array
    {
        return [
            'name' => 'Metformin',
            'strength' => '500mg',
            'dosageAmount' => 1,
            'dosageUnit' => 'tablet',
            'route' => 'oral',
            'instructions' => 'Take with food',
            'takenFor' => 'Diabetes',
            'duration' => '30',
            'frequency' => 'everyday',
            'kind' => 'Scheduled',
            'times' => ['08:00', '20:00'],
            'startDate' => '2026-09-25',
        ];
    }

    private function savedMedication(array $overrides = []): Medication
    {
        return Medication::create(array_merge([
            'patient_id' => $this->patient->patient_id,
            'name' => 'Metformin',
            'strength' => '500mg',
            'dosage_amount' => 1,
            'dosage_unit' => 'tablet',
            'route' => 'oral',
            'instructions' => 'Take with food',
            'duration' => '30',
            'frequency' => 'everyday',
            'kind' => 'Scheduled',
            'times' => ['08:00', '20:00'],
            'start_date' => '2026-09-25',
        ], $overrides));
    }

    private function dose(Medication $medication, string $status, array $extra = []): array
    {
        return [
            'patient_uuid' => $this->patient->uuid,
            'medSchedule' => array_merge([
                'medication_id' => $medication->medication_id,
                'date' => '2026-09-25',
                'time' => '08:00',
                'status' => $status,
            ], $extra),
        ];
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

    public function test_index_returns_only_that_patients_medications(): void
    {
        $this->patientFound();
        $this->savedMedication();
        $this->savedMedication(['patient_id' => 999, 'name' => 'Aspirin']);

        $body = $this->controller->index(
            $this->request('GET', ['patient_uuid' => $this->patient->uuid])
        )->getData(true);

        $this->assertSame(1, $body['meta']['total']);
        $this->assertSame('Metformin', $body['data'][0]['name']);
    }

    public function test_patient_not_found(): void
    {
        $this->patientMissing();

        $this->assertFailsWith('Patient not found', fn() => $this->controller->store($this->request('POST', [
            'patient_uuid' => (string) Str::uuid(),
            'payload' => $this->medicationPayload(),
        ])));

        $this->assertSame(0, Medication::count());
    }

    public function test_successfully_saved_medication(): void
    {
        $this->patientFound();

        $response = $this->controller->store($this->request('POST', [
            'patient_uuid' => $this->patient->uuid,
            'payload' => $this->medicationPayload(),
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Successfully saved Medication.', $response->getData(true)['message']);

        $medication = Medication::firstOrFail();

        $this->assertSame($this->patient->patient_id, $medication->patient_id);
        $this->assertSame('Metformin', $medication->name);
        $this->assertSame(['08:00', '20:00'], $medication->times);
    }

    public function test_notify_patient_access_is_called_once_for_a_medication(): void
    {
        $this->patientFound();

        $this->notifications->expects($this->once())
            ->method('notifyPatientAccess')
            ->with(
                $this->identicalTo($this->patient),
                $this->stringContains('Metformin'),
                'Medication',
                $this->identicalTo($this->nurse)
            );

        $this->controller->store($this->request('POST', [
            'patient_uuid' => $this->patient->uuid,
            'payload' => $this->medicationPayload(),
        ]));
    }

    public function test_medication_record_not_found(): void
    {
        $this->assertFailsWith('Medication record not found', fn() => $this->controller->update(
            $this->request('PUT', ['patient_uuid' => $this->patient->uuid, 'payload' => ['strength' => '850mg']]),
            '999'
        ));
    }

    public function test_strength_changes_to_850mg_while_the_name_and_times_stay_the_same(): void
    {
        $medication = $this->savedMedication();

        $this->controller->update(
            $this->request('PUT', ['patient_uuid' => $this->patient->uuid, 'payload' => ['strength' => '850mg']]),
            (string) $medication->medication_id
        );

        $medication->refresh();

        $this->assertSame('850mg', $medication->strength);
        $this->assertSame('Metformin', $medication->name);
        $this->assertSame(['08:00', '20:00'], $medication->times);
    }

    public function test_dose_status_is_taken_and_marked_by_the_nurse(): void
    {
        $medication = $this->savedMedication();

        $response = $this->controller->dosage($this->request('POST', $this->dose($medication, 'taken')));

        $this->assertSame('taken', $response->getData(true)['data']['status']);

        $record = MedicationSchedule::firstOrFail();

        $this->assertSame(MedicationSchedule::STATUS_TAKEN, $record->status);
        $this->assertSame('08:00', $record->time);
        $this->assertSame($this->nurse->user_id, (int) $record->marked_by);
    }

    public function test_dosage_marking_the_same_dose_twice_keeps_one_record(): void
    {
        $medication = $this->savedMedication();

        $this->controller->dosage($this->request('POST', $this->dose($medication, 'taken')));
        $this->controller->dosage($this->request('POST', $this->dose($medication, 'taken')));

        $this->assertSame(1, MedicationSchedule::count());
    }

    public function test_dosage_treats_a_different_time_on_the_same_day_as_a_separate_dose(): void
    {
        $medication = $this->savedMedication();

        $this->controller->dosage($this->request('POST', $this->dose($medication, 'taken', ['time' => '08:00'])));
        $this->controller->dosage($this->request('POST', $this->dose($medication, 'taken', ['time' => '20:00'])));

        $this->assertSame(2, MedicationSchedule::count());
    }

    public function test_dose_status_is_removed_and_no_dose_record_is_left(): void
    {
        $medication = $this->savedMedication();

        $this->controller->dosage($this->request('POST', $this->dose($medication, 'taken')));
        $record = MedicationSchedule::firstOrFail();

        $response = $this->controller->dosage($this->request('POST', $this->dose($medication, 'removed', [
            'schedule_id' => $record->medication_schedule_id,
        ])));

        $this->assertSame('removed', $response->getData(true)['status']);
        $this->assertSame(0, MedicationSchedule::count());
    }

    public function test_dosage_removing_a_dose_that_does_not_exist_changes_nothing(): void
    {
        $medication = $this->savedMedication();

        $response = $this->controller->dosage(
            $this->request('POST', $this->dose($medication, 'removed', ['schedule_id' => 9999]))
        );

        $this->assertNull($response->getData(true)['data']);
    }

    public function test_dosage_fails_for_another_patients_medication(): void
    {
        $medication = $this->savedMedication();

        $payload = $this->dose($medication, 'taken');
        $payload['patient_uuid'] = (string) Str::uuid();

        $this->assertFailsWith('Medication record not found', fn() => $this->controller->dosage(
            $this->request('POST', $payload)
        ));
        $this->assertSame(0, MedicationSchedule::count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\Notification;
use App\Models\User;
use App\Models\Vital;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class MedicationVitalTest extends TestCase
{
    use UsesTempDatabase;

    private User $nurse;
    private User $guardian;
    private string $patientUuid;
    private int $patientId;
    private string $otherPatientUuid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nurse = $this->makeUser('nurse@amuma.com');
        $this->guardian = $this->makeUser('guardian@amuma.com');

        $agencyId = DB::table('agencies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'AMUMA Incorporation',
            'email' => 'info@amuma.com',
            'registered_by' => $this->nurse->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'agency_id' => $agencyId,
            'name' => 'AMUMA Davao City',
            'email' => 'davao@amuma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$this->patientId, $this->patientUuid] = $this->makePatient($branchId, 'PT-0001', 'Lola', 'Cruz');
        [, $this->otherPatientUuid] = $this->makePatient($branchId, 'PT-0002', 'Lolo', 'Reyes');

        $clientId = DB::table('clients')->insertGetId([
            'user_id' => $this->guardian->user_id,
            'first_name' => 'Gina',
            'last_name' => 'Cruz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('patient_accesses')->insert([
            'client_id' => $clientId,
            'patient_id' => $this->patientId,
            'have_access' => true,
            'relationship_type' => 'relative',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->nurse);
    }

    private function makeUser(string $email): User
    {
        return User::create([
            'uuid' => (string) Str::uuid(),
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function makePatient(int $branchId, string $code, string $first, string $last): array
    {
        $uuid = (string) Str::uuid();

        $id = DB::table('patients')->insertGetId([
            'branch_id' => $branchId,
            'uuid' => $uuid,
            'patient_code' => $code,
            'first_name' => $first,
            'last_name' => $last,
            'gender' => 'female',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$id, $uuid];
    }

    private function vitalPayload(array $overrides = []): array
    {
        return array_merge([
            'bloodPressureSystolic' => 120,
            'bloodPressureDiastolic' => 80,
            'heartRate' => 72,
            'respiratoryRate' => 16,
            'temperature' => 36.6,
            'oxygenSaturation' => 98,
            'bloodGlucose' => 100,
            'painLevel' => 2,
            'recordedDate' => '2026-09-25',
            'recordedTime' => '08:30',
            'notes' => 'Resting',
        ], $overrides);
    }

    private function medicationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Metformin',
            'strength' => '500mg',
            'dosageAmount' => 1,
            'dosageUnit' => 'tablet',
            'route' => 'Oral',
            'instructions' => 'Take with food',
            'takenFor' => 'Diabetes',
            'duration' => '30 days',
            'frequency' => 'everyday',
            'kind' => 'Scheduled',
            'times' => ['08:00', '20:00'],
            'startDate' => '2026-09-25',
        ], $overrides);
    }

    private function createMedication(array $overrides = []): Medication
    {
        $this->postJson('/api/medications', [
            'patient_uuid' => $this->patientUuid,
            'payload' => $this->medicationPayload($overrides),
        ])->assertOk();

        return Medication::where('patient_id', $this->patientId)->latest('medication_id')->firstOrFail();
    }

    private function createVital(array $overrides = []): Vital
    {
        $this->postJson('/api/vitals', [
            'patient_uuid' => $this->patientUuid,
            'payload' => $this->vitalPayload($overrides),
        ])->assertOk();

        return Vital::where('patient_id', $this->patientId)->latest('vital_id')->firstOrFail();
    }

    // ---- vital signs

    public function test_recording_vital_signs_stores_every_reading(): void
    {
        $response = $this->postJson('/api/vitals', [
            'patient_uuid' => $this->patientUuid,
            'payload' => $this->vitalPayload(),
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Successfully saved Vital Signs.')
            ->assertJsonPath('data.bloodPressureSystolic', '120')
            ->assertJsonPath('data.bloodPressureDiastolic', '80')
            ->assertJsonPath('data.heartRate', '72')
            ->assertJsonPath('data.oxygenSaturation', '98')
            ->assertJsonPath('data.recordedDate', '2026-09-25')
            ->assertJsonPath('data.recordedTime', '08:30');

        $vital = Vital::where('patient_id', $this->patientId)->firstOrFail();

        $this->assertSame(120, $vital->blood_pressure_systolic);
        $this->assertSame('36.60', (string) $vital->temperature);
        $this->assertSame('Resting', $vital->notes);
    }

    public function test_vital_signs_can_be_recorded_with_only_some_readings(): void
    {
        $vital = $this->createVital([
            'bloodPressureSystolic' => null,
            'bloodPressureDiastolic' => null,
            'temperature' => null,
            'oxygenSaturation' => null,
            'bloodGlucose' => null,
            'painLevel' => null,
            'respiratoryRate' => null,
            'notes' => null,
        ]);

        $this->assertNull($vital->blood_pressure_systolic);
        $this->assertNull($vital->temperature);
        $this->assertSame(72, $vital->heart_rate);
    }

    public function test_recording_vitals_notifies_guardians_with_access(): void
    {
        $this->createVital();

        $notification = Notification::where('to_user_id', $this->guardian->user_id)->first();

        $this->assertNotNull($notification);
        $this->assertSame('Vitals', $notification->message_type);
        $this->assertStringContainsString('Lola Cruz', $notification->message);
        $this->assertSame($this->nurse->user_id, (int) $notification->from_user_id);
    }

    public function test_guardians_without_access_are_not_notified(): void
    {
        DB::table('patient_accesses')->update(['have_access' => false]);

        $this->createVital();

        $this->assertSame(0, Notification::where('to_user_id', $this->guardian->user_id)->count());
    }

    public function test_a_vital_needs_a_known_patient_and_a_recorded_time(): void
    {
        $unknown = $this->postJson('/api/vitals', [
            'patient_uuid' => (string) Str::uuid(),
            'payload' => $this->vitalPayload(),
        ]);

        $this->assertFalse($unknown->isSuccessful());

        $withoutDate = $this->postJson('/api/vitals', [
            'patient_uuid' => $this->patientUuid,
            'payload' => $this->vitalPayload(['recordedDate' => null]),
        ]);

        $this->assertFalse($withoutDate->isSuccessful());
        $this->assertSame(0, Vital::count());
    }

    public function test_vitals_are_listed_newest_first_and_paginated(): void
    {
        $this->createVital(['recordedDate' => '2026-09-23', 'recordedTime' => '08:00']);
        $this->createVital(['recordedDate' => '2026-09-25', 'recordedTime' => '06:00']);
        $this->createVital(['recordedDate' => '2026-09-25', 'recordedTime' => '14:00']);

        $response = $this->getJson('/api/vitals?' . http_build_query([
            'patient_uuid' => $this->patientUuid,
            'per_page' => 2,
        ]))->assertOk();

        $this->assertSame(
            [['2026-09-25', '14:00'], ['2026-09-25', '06:00']],
            collect($response->json('data'))->map(fn($row) => [$row['recordedDate'], $row['recordedTime']])->all()
        );
        $this->assertSame(3, $response->json('meta.total'));
        $this->assertSame(2, $response->json('meta.last_page'));
    }

    public function test_vitals_only_list_the_requested_patient(): void
    {
        $this->createVital();

        $response = $this->getJson('/api/vitals?patient_uuid=' . $this->otherPatientUuid)->assertOk();

        $this->assertSame(0, $response->json('meta.total'));
    }

    public function test_updating_a_vital_changes_only_the_sent_readings(): void
    {
        $vital = $this->createVital();

        $this->putJson("/api/vitals/{$vital->vital_id}", [
            'patient_uuid' => $this->patientUuid,
            'payload' => ['heartRate' => 90, 'notes' => 'After walking'],
        ])->assertOk()->assertJsonPath('message', 'Successfully updated Vital Signs.');

        $vital->refresh();

        $this->assertSame(90, $vital->heart_rate);
        $this->assertSame('After walking', $vital->notes);
        $this->assertSame(120, $vital->blood_pressure_systolic);
        $this->assertSame('08:30', $vital->recorded_time);
    }

    public function test_a_vital_cannot_be_updated_through_another_patient(): void
    {
        $vital = $this->createVital();

        $response = $this->putJson("/api/vitals/{$vital->vital_id}", [
            'patient_uuid' => $this->otherPatientUuid,
            'payload' => ['heartRate' => 200],
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(72, $vital->fresh()->heart_rate);
    }

    // ---- medications (eMAR)

    public function test_adding_a_medication_stores_the_order(): void
    {
        $response = $this->postJson('/api/medications', [
            'patient_uuid' => $this->patientUuid,
            'payload' => $this->medicationPayload(),
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Successfully saved Medication.')
            ->assertJsonPath('data.name', 'Metformin')
            ->assertJsonPath('data.kind', 'Scheduled')
            ->assertJsonPath('data.times', ['08:00', '20:00'])
            ->assertJsonPath('data.startDate', '2026-09-25');

        $medication = Medication::where('patient_id', $this->patientId)->firstOrFail();

        $this->assertSame('500mg', $medication->strength);
        $this->assertSame('Oral', $medication->route);
        $this->assertSame('Diabetes', $medication->taken_for);
    }

    public function test_a_prn_medication_needs_no_fixed_times(): void
    {
        $medication = $this->createMedication(['kind' => 'PRN', 'times' => [], 'name' => 'Paracetamol']);

        $this->assertSame('PRN', $medication->kind);
        $this->assertSame([], $medication->times);
    }

    public function test_adding_a_medication_notifies_guardians_with_access(): void
    {
        $this->createMedication();

        $notification = Notification::where('to_user_id', $this->guardian->user_id)->first();

        $this->assertNotNull($notification);
        $this->assertSame('Medication', $notification->message_type);
        $this->assertStringContainsString('Metformin', $notification->message);
    }

    public function test_a_medication_needs_a_known_patient_and_its_required_details(): void
    {
        $unknown = $this->postJson('/api/medications', [
            'patient_uuid' => (string) Str::uuid(),
            'payload' => $this->medicationPayload(),
        ]);

        $this->assertFalse($unknown->isSuccessful());

        $noName = $this->postJson('/api/medications', [
            'patient_uuid' => $this->patientUuid,
            'payload' => collect($this->medicationPayload())->except('name')->all(),
        ]);

        $this->assertFalse($noName->isSuccessful());
        $this->assertSame(0, Medication::count());
    }

    public function test_medications_are_listed_for_the_patient_with_their_doses(): void
    {
        $medication = $this->createMedication();

        $this->postJson('/api/medications/dosage', [
            'patient_uuid' => $this->patientUuid,
            'medSchedule' => [
                'medication_id' => $medication->medication_id,
                'date' => '2026-09-25',
                'time' => '08:00',
                'status' => 'taken',
            ],
        ])->assertOk();

        $response = $this->getJson('/api/medications?patient_uuid=' . $this->patientUuid)->assertOk();

        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('Metformin', $response->json('data.0.name'));
        $this->assertCount(1, $response->json('data.0.schedules'));
        $this->assertSame('taken', $response->json('data.0.schedules.0.status'));

        $other = $this->getJson('/api/medications?patient_uuid=' . $this->otherPatientUuid)->assertOk();
        $this->assertSame(0, $other->json('meta.total'));
    }

    public function test_updating_a_medication_changes_only_the_sent_fields(): void
    {
        $medication = $this->createMedication();

        $this->putJson("/api/medications/{$medication->medication_id}", [
            'patient_uuid' => $this->patientUuid,
            'payload' => ['strength' => '850mg', 'instructions' => 'After meals'],
        ])->assertOk()->assertJsonPath('message', 'Successfully updated Medication.');

        $medication->refresh();

        $this->assertSame('850mg', $medication->strength);
        $this->assertSame('After meals', $medication->instructions);
        $this->assertSame('Metformin', $medication->name);
        $this->assertSame(['08:00', '20:00'], $medication->times);
    }

    public function test_a_medication_cannot_be_updated_through_another_patient(): void
    {
        $medication = $this->createMedication();

        $response = $this->putJson("/api/medications/{$medication->medication_id}", [
            'patient_uuid' => $this->otherPatientUuid,
            'payload' => ['strength' => '1g'],
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('500mg', $medication->fresh()->strength);
    }

    // ---- giving doses

    private function dose(Medication $medication, string $status, array $extra = [])
    {
        return $this->postJson('/api/medications/dosage', [
            'patient_uuid' => $this->patientUuid,
            'medSchedule' => array_merge([
                'medication_id' => $medication->medication_id,
                'date' => '2026-09-25',
                'time' => '08:00',
                'status' => $status,
            ], $extra),
        ]);
    }

    public function test_marking_a_dose_records_who_gave_it(): void
    {
        $medication = $this->createMedication();

        $this->dose($medication, 'taken')
            ->assertOk()
            ->assertJsonPath('data.status', 'taken')
            ->assertJsonPath('data.time', '08:00');

        $schedule = MedicationSchedule::where('medication_id', $medication->medication_id)->firstOrFail();

        $this->assertSame(MedicationSchedule::STATUS_TAKEN, $schedule->status);
        $this->assertSame($this->nurse->user_id, (int) $schedule->marked_by);
    }

    public function test_marking_the_same_dose_twice_does_not_duplicate_it(): void
    {
        $medication = $this->createMedication();

        $this->dose($medication, 'taken')->assertOk();
        $this->dose($medication, 'taken')->assertOk();

        $this->assertSame(1, MedicationSchedule::where('medication_id', $medication->medication_id)->count());
    }

    public function test_different_times_on_the_same_day_are_separate_doses(): void
    {
        $medication = $this->createMedication();

        $this->dose($medication, 'taken', ['time' => '08:00'])->assertOk();
        $this->dose($medication, 'taken', ['time' => '20:00'])->assertOk();

        $this->assertSame(2, MedicationSchedule::where('medication_id', $medication->medication_id)->count());
    }

    public function test_a_dose_can_be_removed_again(): void
    {
        $medication = $this->createMedication();

        $this->dose($medication, 'taken')->assertOk();

        $schedule = MedicationSchedule::where('medication_id', $medication->medication_id)->firstOrFail();

        $this->dose($medication, 'removed', ['schedule_id' => $schedule->medication_schedule_id])
            ->assertOk()
            ->assertJsonPath('status', 'removed');

        $this->assertSame(0, MedicationSchedule::where('medication_id', $medication->medication_id)->count());
    }

    public function test_removing_a_dose_that_does_not_exist_is_harmless(): void
    {
        $medication = $this->createMedication();

        $this->dose($medication, 'removed', ['schedule_id' => 9999])
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_a_dose_cannot_be_marked_through_another_patient(): void
    {
        $medication = $this->createMedication();

        $response = $this->postJson('/api/medications/dosage', [
            'patient_uuid' => $this->otherPatientUuid,
            'medSchedule' => [
                'medication_id' => $medication->medication_id,
                'date' => '2026-09-25',
                'time' => '08:00',
                'status' => 'taken',
            ],
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(0, MedicationSchedule::count());
    }
}

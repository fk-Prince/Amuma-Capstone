<?php

namespace Tests\Unit\MedicationVital;

use App\Models\Employee;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\Vital;
use App\Utils\MedicationPresenter;
use Tests\TestCase;

class MedicationPresenterTest extends TestCase
{
    private function employee(): Employee
    {
        return (new Employee)->forceFill(['first_name' => 'Nina', 'last_name' => 'Santos']);
    }

    private function medication(array $overrides = []): Medication
    {
        $medication = (new Medication)->forceFill(array_merge([
            'medication_id' => 5,
            'name' => 'Metformin',
            'strength' => '500mg',
            'dosage_amount' => 1,
            'dosage_unit' => 'tablet',
            'route' => 'oral',
            'instructions' => 'Take with food',
            'taken_for' => 'Diabetes',
            'duration' => '30',
            'frequency' => 'everyday',
            'kind' => 'Scheduled',
            'times' => ['08:00', '20:00'],
            'start_date' => '2026-09-25',
            'recorded_at' => '2026-09-25 12:00:00',
        ], $overrides));

        return $medication
            ->setRelation('recordedBy', $this->employee())
            ->setRelation('schedules', collect());
    }

    private function vital(array $overrides = []): Vital
    {
        return (new Vital)->forceFill(array_merge([
            'vital_id' => 9,
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'heart_rate' => 72,
            'recorded_date' => '2026-09-25',
            'recorded_time' => '08:30',
        ], $overrides))->setRelation('recordedBy', $this->employee());
    }

    public function test_a_medication_is_formatted_for_the_screen(): void
    {
        $result = MedicationPresenter::medication($this->medication());

        $this->assertSame('5', $result['id']);
        $this->assertSame('Medication', $result['category']);
        $this->assertSame('Metformin', $result['name']);
        $this->assertSame('1.00', $result['dosageAmount']);
        $this->assertSame('Diabetes', $result['takenFor']);
        $this->assertSame(['08:00', '20:00'], $result['times']);
        $this->assertSame('2026-09-25', $result['startDate']);
        $this->assertSame('Nina Santos', $result['recorded_by']);
    }

    public function test_a_missing_reason_and_times_become_empty(): void
    {
        $result = MedicationPresenter::medication($this->medication(['taken_for' => null, 'times' => null]));

        $this->assertSame('', $result['takenFor']);
        $this->assertSame([], $result['times']);
    }

    public function test_a_medication_carries_its_dose_records(): void
    {
        $dose = (new MedicationSchedule)->forceFill([
            'medication_schedule_id' => 7,
            'date' => '2026-09-25',
            'time' => '08:00',
            'status' => 'taken',
            'marked_by' => 3,
        ]);

        $medication = $this->medication()->setRelation('schedules', collect([$dose]));

        $result = MedicationPresenter::medication($medication);

        $this->assertCount(1, $result['schedules']);
        $this->assertSame('taken', $result['schedules'][0]['status']);
    }

    public function test_a_dose_record_is_formatted(): void
    {
        $dose = (new MedicationSchedule)->forceFill([
            'medication_schedule_id' => 7,
            'date' => '2026-09-25',
            'time' => '08:00',
            'status' => 'taken',
            'marked_by' => 3,
        ]);

        $this->assertSame([
            'id' => '7',
            'date' => '2026-09-25',
            'time' => '08:00',
            'status' => 'taken',
            'marked_by' => 3,
        ], MedicationPresenter::schedule($dose));
    }

    public function test_vital_readings_are_formatted_as_text(): void
    {
        $result = MedicationPresenter::vital($this->vital(['temperature' => 36.6]));

        $this->assertSame('9', $result['id']);
        $this->assertSame('Vital Signs', $result['category']);
        $this->assertSame('120', $result['bloodPressureSystolic']);
        $this->assertSame('72', $result['heartRate']);
        $this->assertSame('36.60', $result['temperature']);
        $this->assertSame('2026-09-25', $result['recordedDate']);
        $this->assertSame('08:30', $result['recordedTime']);
        $this->assertSame('Nina Santos', $result['recordedBy']);
    }

    public function test_readings_that_were_not_taken_become_empty(): void
    {
        $result = MedicationPresenter::vital($this->vital([
            'blood_pressure_systolic' => null,
            'blood_pressure_diastolic' => null,
            'notes' => null,
        ]));

        $this->assertSame('', $result['bloodPressureSystolic']);
        $this->assertSame('', $result['bloodPressureDiastolic']);
        $this->assertSame('', $result['oxygenSaturation']);
        $this->assertSame('', $result['notes']);
    }

    public function test_the_recorder_name_is_empty_when_nobody_is_recorded(): void
    {
        $this->assertNull(MedicationPresenter::recorderName(null));
        $this->assertNull(MedicationPresenter::recorderName(new Employee));
        $this->assertSame('Nina Santos', MedicationPresenter::recorderName($this->employee()));
    }
}

# Client Admission and Staff Service Allocation Tests

## ADM-01 Client admission

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdmissionTest.php --testdox --filter "test_patient_admitted_successfully|test_the_reserved_bed_is_held_for_the_patient|test_admitting_a_waiting_patient_occupies_the_bed|test_patient_already_has_an_active_admission|test_the_selected_bed_is_no_longer_available|test_insufficient_permissions_to_admit_a_patient"
```

- [AdmissionTest.php:147](AdmissionTest.php#L147) Patient admitted successfully
- [AdmissionTest.php:160](AdmissionTest.php#L160) The reserved bed is held for the patient
- [AdmissionTest.php:177](AdmissionTest.php#L177) Admitting a waiting patient occupies the bed
- [AdmissionTest.php:231](AdmissionTest.php#L231) Patient already has an active admission
- [AdmissionTest.php:255](AdmissionTest.php#L255) The selected bed is no longer available
- [AdmissionTest.php:268](AdmissionTest.php#L268) Insufficient permissions to admit a patient

## ADM-02 Allocate staff to a patient service and catch assignment conflicts

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AssignmentTest.php --testdox --filter "test_staff_is_assigned_to_the_medical_service|test_staff_already_booked_at_that_time_cannot_be_assigned|test_only_a_caregiver_can_be_assigned_to_an_adl_service|test_a_medical_service_needs_a_nurse_a_caregiver_can_only_assist|test_only_homecare_staff_can_be_assigned_to_a_homecare_schedule|test_the_end_time_must_be_later_than_the_start_time|test_the_family_is_told_the_care_team_has_changed"
```

- [AssignmentTest.php:61](AssignmentTest.php#L61) Staff is assigned to the medical service
- [AssignmentTest.php:78](AssignmentTest.php#L78) Staff already booked at that time cannot be assigned
- [AssignmentTest.php:94](AssignmentTest.php#L94) Only a caregiver can be assigned to an adl service
- [AssignmentTest.php:107](AssignmentTest.php#L107) A medical service needs a nurse a caregiver can only assist
- [AssignmentTest.php:136](AssignmentTest.php#L136) Only homecare staff can be assigned to a homecare schedule
- [AssignmentTest.php:162](AssignmentTest.php#L162) The end time must be later than the start time
- [AssignmentTest.php:255](AssignmentTest.php#L255) The family is told the care team has changed

## ADM-03 Homecare staff monitor and coordinate patient services

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/MonitoringTest.php --testdox --filter "test_staff_sees_every_schedule_of_the_branch|test_nurse_sees_only_the_schedules_assigned_to_them|test_schedules_can_be_filtered_by_status|test_each_staff_member_sees_the_patients_they_look_after|test_staff_on_leave_is_not_listed|test_insufficient_permissions_to_see_the_staff_workload"
```

- [MonitoringTest.php:70](MonitoringTest.php#L70) Staff sees every schedule of the branch
- [MonitoringTest.php:78](MonitoringTest.php#L78) Nurse sees only the schedules assigned to them
- [MonitoringTest.php:86](MonitoringTest.php#L86) Schedules can be filtered by status
- [MonitoringTest.php:97](MonitoringTest.php#L97) Each staff member sees the patients they look after
- [MonitoringTest.php:121](MonitoringTest.php#L121) Staff on leave is not listed
- [MonitoringTest.php:145](MonitoringTest.php#L145) Insufficient permissions to see the staff workload

## ADM-04 Clock in to an ADL visit by QR

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/AdlShiftQrTest.php --testdox --filter "test_first_scan_clocks_in_and_starts_the_schedule|test_clock_in_is_refused_outside_the_shift|test_clock_in_is_allowed_up_to_an_hour_before_the_shift|test_a_refused_clock_in_keeps_the_qr_usable|test_an_ongoing_visit_with_nobody_on_duty_can_be_picked_up_any_time|test_missed_schedule_has_no_qr|test_family_time_in_is_refused_while_someone_is_on_duty|test_employee_cannot_generate_the_time_in_qr_for_adl"
```

- [AdlShiftQrTest.php:250](../../Feature/AdlShiftQrTest.php#L250) First scan clocks in and starts the schedule
- [AdlShiftQrTest.php:147](../../Feature/AdlShiftQrTest.php#L147) Clock in is refused outside the shift
- [AdlShiftQrTest.php:157](../../Feature/AdlShiftQrTest.php#L157) Clock in is allowed up to an hour before the shift
- [AdlShiftQrTest.php:167](../../Feature/AdlShiftQrTest.php#L167) A refused clock in keeps the qr usable
- [AdlShiftQrTest.php:184](../../Feature/AdlShiftQrTest.php#L184) An ongoing visit with nobody on duty can be picked up any time
- [AdlShiftQrTest.php:296](../../Feature/AdlShiftQrTest.php#L296) Missed schedule has no qr
- [AdlShiftQrTest.php:276](../../Feature/AdlShiftQrTest.php#L276) Family time in is refused while someone is on duty
- [AdlShiftQrTest.php:239](../../Feature/AdlShiftQrTest.php#L239) Employee cannot generate the time in qr for adl

## ADM-05 Hand over an ADL visit to the next shift by QR

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/AdlShiftQrTest.php --testdox --filter "test_second_shift_can_scan_the_first_shifts_handover_qr|test_the_family_is_told_about_a_shift_change|test_shift_change_is_allowed_outside_the_shift|test_family_cannot_generate_a_shift_change_qr|test_only_the_caregiver_on_duty_can_generate_a_handover_qr|test_the_caregiver_on_duty_cannot_scan_their_own_handover"
```

- [AdlShiftQrTest.php:285](../../Feature/AdlShiftQrTest.php#L285) Second shift can scan the first shifts handover qr
- [AdlShiftQrTest.php:197](../../Feature/AdlShiftQrTest.php#L197) The family is told about a shift change
- [AdlShiftQrTest.php:228](../../Feature/AdlShiftQrTest.php#L228) Shift change is allowed outside the shift
- [AdlShiftQrTest.php:267](../../Feature/AdlShiftQrTest.php#L267) Family cannot generate a shift change qr
- [AdlShiftQrTest.php:305](../../Feature/AdlShiftQrTest.php#L305) Only the caregiver on duty can generate a handover qr
- [AdlShiftQrTest.php:314](../../Feature/AdlShiftQrTest.php#L314) The caregiver on duty cannot scan their own handover

## ADM-06 Clock out of an ADL visit by QR

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/AdlShiftQrTest.php --testdox --filter "test_either_shift_can_time_out_with_the_family_qr|test_time_out_completes_the_schedule_when_under_a_minute_remains|test_early_first_scan_moves_the_visit_end_earlier|test_on_time_first_scan_keeps_the_scheduled_end"
```

- [AdlShiftQrTest.php:323](../../Feature/AdlShiftQrTest.php#L323) Either shift can time out with the family qr
- [AdlShiftQrTest.php:336](../../Feature/AdlShiftQrTest.php#L336) Time out completes the schedule when under a minute remains
- [AdlShiftQrTest.php:348](../../Feature/AdlShiftQrTest.php#L348) Early first scan moves the visit end earlier
- [AdlShiftQrTest.php:362](../../Feature/AdlShiftQrTest.php#L362) On time first scan keeps the scheduled end

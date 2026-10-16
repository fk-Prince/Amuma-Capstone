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

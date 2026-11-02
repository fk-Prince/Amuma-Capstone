# AMUMA Unit Tests

Run each command from the `server` folder. Click a test to open it.

## Run all at once

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit --testdox
```

# Objective 1: Online Booking Module

## Table 1. Online Booking Test Case

**OBTC1** [HomecareBookingTest.php:55](Booking/HomecareBookingTest.php#L55) Book homecare with a downpayment
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/HomecareBookingTest.php --testdox --filter test_your_booking_has_been_submitted_successfully
```

**OBTC2** [FacilityBookingTest.php:59](Booking/FacilityBookingTest.php#L59) Submit a pre-admission form
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/FacilityBookingTest.php --testdox --filter test_pre_admission_form_is_submitted_without_payment
```

**OBTC3** [HomecareBookingTest.php:74](Booking/HomecareBookingTest.php#L74) Book homecare without a downpayment
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/HomecareBookingTest.php --testdox --filter test_homecare_bookings_require_a_downpayment_before_they_can_be_submitted
```

**OBTC4** [HomecareBookingTest.php:100](Booking/HomecareBookingTest.php#L100) Book homecare less than 1 hour ahead
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/HomecareBookingTest.php --testdox --filter test_homecare_must_be_booked_at_least_1_hour_ahead
```

**OBTC5** [HomecareBookingTest.php:88](Booking/HomecareBookingTest.php#L88) Book a medical visit without picking a service
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/HomecareBookingTest.php --testdox --filter test_the_booking_amount_could_not_be_determined
```

**OBTC6** [FacilityBookingTest.php:89](Booking/FacilityBookingTest.php#L89) Enter a diagnosis older than 3 months
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/FacilityBookingTest.php --testdox --filter test_diagnosis_must_be_from_the_past_3_months
```

**OBTC7** [FacilityBookingTest.php:98](Booking/FacilityBookingTest.php#L98) Enter a diagnosis date in the future
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/FacilityBookingTest.php --testdox --filter test_diagnosis_date_cannot_be_in_the_future
```

**OBTC8** [FacilityBookingTest.php:105](Booking/FacilityBookingTest.php#L105) Submit without a primary diagnosis
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/FacilityBookingTest.php --testdox --filter test_primary_diagnosis_is_required
```

**OBTC9** [FacilityBookingTest.php:76](Booking/FacilityBookingTest.php#L76) Pay for a pre-admission form
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/FacilityBookingTest.php --testdox --filter test_this_booking_does_not_take_a_payment
```

## Table 2. Booking Review and Walk-in Booking Test Case

**BRTC1** [BookingReviewTest.php:137](Booking/BookingReviewTest.php#L137) Approve a pending booking
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter test_booking_has_been_approved_successfully
```

**BRTC2** [BookingReviewTest.php:165](Booking/BookingReviewTest.php#L165) Reject a booking with a reason
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter test_booking_has_successfully_rejected
```

**BRTC3** [BookingReviewTest.php:155](Booking/BookingReviewTest.php#L155) Approve a booking on an occupied bed
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter test_selected_bed_is_already_occupied
```

**BRTC4** [BookingReviewTest.php:185](Booking/BookingReviewTest.php#L185) Approve a booking that was already rejected
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter test_booking_cannot_be_approved_current_status_rejected
```

**BRTC5** [BookingReviewTest.php:200](Booking/BookingReviewTest.php#L200) Approve a booking without permission
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter test_insufficient_permissions_to_approve
```

**BRTC6** [BookingReviewTest.php:256](Booking/BookingReviewTest.php#L256) Approve an expired booking
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter test_booking_has_expired
```

**BRTC7** [BookingReviewTest.php:225](Booking/BookingReviewTest.php#L225) Create a walk-in booking without a guardian email
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter test_the_guardian_email_field_is_required
```

**BRTC8** [BookingReviewTest.php:247](Booking/BookingReviewTest.php#L247) Create a walk-in booking with an invalid guardian email
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter test_the_guardian_email_field_must_be_a_valid_email_address
```

**BRTC9** [BookingReviewTest.php:234](Booking/BookingReviewTest.php#L234) Pay a walk-in booking with cash 1 centavo short
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter test_cash_1_centavo_short_is_invalid
```

# Objective 2: Client Admission Module with Staff Service Allocation

## Table 3. Client Admission and Staff Assignment Test Case

**CATC1** [AssignmentTest.php:60](Scheduling/AssignmentTest.php#L60) Assign a nurse to a medical service
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AssignmentTest.php --testdox --filter test_staff_is_assigned_to_the_medical_service
```

**CATC2** [AdmissionTest.php:146](Scheduling/AdmissionTest.php#L146) Admit a patient without an admission date
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdmissionTest.php --testdox --filter test_please_select_an_admission_date
```

**CATC3** [AdmissionTest.php:155](Scheduling/AdmissionTest.php#L155) Admit a patient without a room and bed
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdmissionTest.php --testdox --filter test_please_select_accommodation_type_room_and_bed
```

**CATC4** [AdmissionTest.php:164](Scheduling/AdmissionTest.php#L164) Admit a patient who is already admitted
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdmissionTest.php --testdox --filter test_patient_already_has_an_active_admission
```

**CATC5** [AdmissionTest.php:177](Scheduling/AdmissionTest.php#L177) Admit a patient to a bed that is taken
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdmissionTest.php --testdox --filter test_the_selected_bed_is_no_longer_available
```

**CATC6** [AssignmentTest.php:77](Scheduling/AssignmentTest.php#L77) Assign staff who is busy at that time
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AssignmentTest.php --testdox --filter test_staff_already_booked_at_that_time_cannot_be_assigned
```

**CATC7** [AssignmentTest.php:93](Scheduling/AssignmentTest.php#L93) Assign only a caregiver to a medical service
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AssignmentTest.php --testdox --filter test_a_medical_service_needs_a_nurse_a_caregiver_can_only_assist
```

**CATC8** [AssignmentTest.php:108](Scheduling/AssignmentTest.php#L108) Set a shift end time before the start time
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AssignmentTest.php --testdox --filter test_the_end_time_must_be_later_than_the_start_time
```

**CATC9** [AssignmentTest.php:121](Scheduling/AssignmentTest.php#L121) Set a shift start time without an end time
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AssignmentTest.php --testdox --filter test_pick_both_a_start_time_and_an_end_time
```

## Table 4. Staff Service Monitoring and Coordination Test Case

**SMTC1** [MonitoringTest.php:58](Scheduling/MonitoringTest.php#L58) Admission staff opens the schedules
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/MonitoringTest.php --testdox --filter test_staff_sees_every_schedule_of_the_branch
```

**SMTC2** [MonitoringTest.php:66](Scheduling/MonitoringTest.php#L66) Nurse opens the schedules
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/MonitoringTest.php --testdox --filter test_nurse_sees_only_the_schedules_assigned_to_them
```

**SMTC3** [AdlShiftQrTest.php:170](Scheduling/AdlShiftQrTest.php#L170) Caregiver scans the family's time-in QR
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdlShiftQrTest.php --testdox --filter test_first_scan_clocks_in_and_starts_the_schedule
```

**SMTC4** [AdlShiftQrTest.php:187](Scheduling/AdlShiftQrTest.php#L187) Next shift scans the handover QR
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdlShiftQrTest.php --testdox --filter test_second_shift_can_scan_the_first_shifts_handover_qr
```

**SMTC5** [AdlShiftQrTest.php:218](Scheduling/AdlShiftQrTest.php#L218) Caregiver scans the time-out QR at the end
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdlShiftQrTest.php --testdox --filter test_time_out_completes_the_schedule_when_under_a_minute_remains
```

**SMTC6** [AdlShiftQrTest.php:147](Scheduling/AdlShiftQrTest.php#L147) Scan the time-in QR outside the shift
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdlShiftQrTest.php --testdox --filter test_clock_in_is_refused_outside_the_shift
```

**SMTC7** [AdlShiftQrTest.php:158](Scheduling/AdlShiftQrTest.php#L158) Caregiver tries to make the time-in QR
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdlShiftQrTest.php --testdox --filter test_employee_cannot_generate_the_time_in_qr_for_adl
```

**SMTC8** [AdlShiftQrTest.php:208](Scheduling/AdlShiftQrTest.php#L208) Caregiver on duty scans their own handover QR
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdlShiftQrTest.php --testdox --filter test_the_caregiver_on_duty_cannot_scan_their_own_handover
```

**SMTC9** [AdlShiftQrTest.php:198](Scheduling/AdlShiftQrTest.php#L198) Make a QR for a missed schedule
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling/AdlShiftQrTest.php --testdox --filter test_missed_schedule_has_no_qr
```

# Objective 3: Electronic Medication Administration Record (eMAR) Module

## Table 5. eMAR and Vital Signs Test Case

**EMTC1** [MedicationControllerTest.php:155](MedicationVital/MedicationControllerTest.php#L155) Add a medication
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/MedicationVital/MedicationControllerTest.php --testdox --filter test_successfully_saved_medication
```

**EMTC2** [VitalControllerTest.php:107](MedicationVital/VitalControllerTest.php#L107) Record vital signs
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/MedicationVital/VitalControllerTest.php --testdox --filter test_successfully_saved_vital_signs
```

**EMTC3** [MedicationControllerTest.php:182](MedicationVital/MedicationControllerTest.php#L182) Mark a dose as taken
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/MedicationVital/MedicationControllerTest.php --testdox --filter test_dose_status_is_taken_and_marked_by_the_nurse
```

**EMTC4** [MedicationControllerTest.php:197](MedicationVital/MedicationControllerTest.php#L197) Mark the same dose twice
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/MedicationVital/MedicationControllerTest.php --testdox --filter test_dosage_marking_the_same_dose_twice_keeps_one_record
```

**EMTC5** [MedicationControllerTest.php:207](MedicationVital/MedicationControllerTest.php#L207) Undo a dose
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/MedicationVital/MedicationControllerTest.php --testdox --filter test_dose_status_is_removed_and_no_dose_record_is_left
```

**EMTC6** [MedicationControllerTest.php:143](MedicationVital/MedicationControllerTest.php#L143) Add a medication for a patient that does not exist
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/MedicationVital/MedicationControllerTest.php --testdox --filter test_patient_not_found
```

**EMTC7** [MedicationControllerTest.php:174](MedicationVital/MedicationControllerTest.php#L174) Update a medication that does not exist
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/MedicationVital/MedicationControllerTest.php --testdox --filter test_medication_record_not_found
```

**EMTC8** [MedicationControllerTest.php:222](MedicationVital/MedicationControllerTest.php#L222) Mark a dose for another patient's medication
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/MedicationVital/MedicationControllerTest.php --testdox --filter test_dosage_fails_for_another_patients_medication
```

**EMTC9** [VitalControllerTest.php:129](MedicationVital/VitalControllerTest.php#L129) Update vital signs that do not exist
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/MedicationVital/VitalControllerTest.php --testdox --filter test_vital_record_not_found
```

# Objective 4: Electronic Billing and Client Payment Records

## Table 6. Financial Transaction and Billing Test Case

**FBTC1** [InvoiceControllerTest.php:48](Billing/InvoiceControllerTest.php#L48) Pay in cash at the branch
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Billing/InvoiceControllerTest.php --testdox --filter test_patient_payment_has_been_processed_successfully
```

**FBTC2** [PaymentControllerTest.php:74](Billing/PaymentControllerTest.php#L74) Pay the balance online by card
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Billing/PaymentControllerTest.php --testdox --filter test_payment_recorded_successfully
```

**FBTC3** [RefundControllerTest.php:45](Billing/RefundControllerTest.php#L45) Request to withdraw credit
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Billing/RefundControllerTest.php --testdox --filter test_your_withdrawal_request_has_been_sent_to_the_branch_for_review
```

**FBTC4** [InvoiceControllerTest.php:68](Billing/InvoiceControllerTest.php#L68) Enter a cash amount of 0
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Billing/InvoiceControllerTest.php --testdox --filter test_enter_a_cash_amount_greater_than_0
```

**FBTC5** [PaymentControllerTest.php:67](Billing/PaymentControllerTest.php#L67) Pay online with an amount of 0
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Billing/PaymentControllerTest.php --testdox --filter test_store_requires_an_amount
```

**FBTC6** [PaymentControllerTest.php:59](Billing/PaymentControllerTest.php#L59) Use more credit than is available
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Billing/PaymentControllerTest.php --testdox --filter test_only_100_in_credit_is_available_on_this_account
```

**FBTC7** [PaymentControllerTest.php:99](Billing/PaymentControllerTest.php#L99) Pay with a declined card
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Billing/PaymentControllerTest.php --testdox --filter test_payment_failed
```

**FBTC8** [InvoiceControllerTest.php:76](Billing/InvoiceControllerTest.php#L76) Adjust an invoice without a reason
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Billing/InvoiceControllerTest.php --testdox --filter test_a_reason_is_required_to_adjust_an_invoice
```

**FBTC9** [InvoiceControllerTest.php:88](Billing/InvoiceControllerTest.php#L88) Void a paid invoice
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Billing/InvoiceControllerTest.php --testdox --filter test_a_paid_invoice_can_no_longer_be_voided_issue_a_refund_or_credit_instead
```

# Objective 5: Subscription Management Feature

## Table 7. Subscription Registration and Review Test Case

**SRTC1** [SubscribeTest.php:62](Subscription/SubscribeTest.php#L62) Subscribe with all details and a card
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter test_almost_there_we_ll_notify_you_once_your_branch_is_verified
```

**SRTC2** [ReviewTest.php:61](Subscription/ReviewTest.php#L61) Admin approves a branch request
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter test_amuma_davao_city_was_approved_and_is_now_active
```

**SRTC3** [ReviewTest.php:71](Subscription/ReviewTest.php#L71) Admin rejects a branch request with a reason
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter test_amuma_davao_city_was_not_approved_reason_business_permit_is_expired
```

**SRTC4** [SubscribeTest.php:76](Subscription/SubscribeTest.php#L76) Start the free trial without a card
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter test_add_a_card_to_start_your_free_trial
```

**SRTC5** [SubscribeTest.php:83](Subscription/SubscribeTest.php#L83) Subscribe without a branch name
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter test_the_branch_name_field_is_required
```

**SRTC6** [SubscribeTest.php:89](Subscription/SubscribeTest.php#L89) Subscribe with a branch email already in use
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter test_the_branch_email_has_already_been_taken
```

**SRTC7** [SubscribeTest.php:97](Subscription/SubscribeTest.php#L97) Subscribe with an invalid TIN
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter test_the_tin_must_be_a_valid_tin
```

**SRTC8** [SubscribeTest.php:106](Subscription/SubscribeTest.php#L106) Subscribe with an invalid plan type
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter test_invalid_plan_type
```

**SRTC9** [SubscribeTest.php:113](Subscription/SubscribeTest.php#L113) Subscribe to a plan that does not exist
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter test_plan_not_found
```

## Table 8. Subscription Renewal and Branch Management Test Case

**SNTC1** [RenewTest.php:48](Subscription/RenewTest.php#L48) Renew within 7 days of the end date
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter test_subscription_renewed_successfully
```

**SNTC2** [RenewTest.php:59](Subscription/RenewTest.php#L59) Upgrade the plan to Hybrid
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter test_upgraded_to_hybrid
```

**SNTC3** [BranchTest.php:44](Subscription/BranchTest.php#L44) Add a branch with a free slot
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter test_branch_added_and_sent_for_review
```

**SNTC4** [SavedCardTest.php:63](Subscription/SavedCardTest.php#L63) Free trial ends with a saved card
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SavedCardTest.php --testdox --filter test_your_free_trial_ended_and_the_plan_was_charged_to_your_card
```

**SNTC5** [RenewTest.php:38](Subscription/RenewTest.php#L38) Renew more than 7 days before the end date
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter test_renewal_opens_7_days_before_this_subscription_ends
```

**SNTC6** [RenewTest.php:70](Subscription/RenewTest.php#L70) Upgrade to a plan other than Hybrid
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter test_this_plan_can_only_be_upgraded_to_hybrid
```

**SNTC7** [BranchTest.php:57](Subscription/BranchTest.php#L57) Add a branch with no free slot
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter test_that_subscription_has_no_free_branch_slots
```

**SNTC8** [SavedCardTest.php:79](Subscription/SavedCardTest.php#L79) Turn on auto-renew without a saved card
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SavedCardTest.php --testdox --filter test_save_a_card_first_to_turn_on_auto_renew
```

**SNTC9** [TrialTest.php:37](Subscription/TrialTest.php#L37) Cancel the free trial without a reason
```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/TrialTest.php --testdox --filter test_a_cancel_reason_is_required
```

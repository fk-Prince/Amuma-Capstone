# AMUMA Unit Tests

Run each command from the `server` folder. Click a test to open it.

## Run all at once

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit --testdox
```

# Objective 1: Online Booking Module

## Table 1. Online Booking Test Case

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking --testdox --filter "test_your_booking_has_been_submitted_successfully|test_homecare_bookings_require_a_downpayment_before_they_can_be_submitted|test_homecare_must_be_booked_at_least_1_hour_ahead|test_pre_admission_form_is_submitted_without_payment|test_diagnosis_must_be_from_the_past_3_months|test_primary_diagnosis_is_required"
```

- OBTC1 [HomecareBookingTest.php:55](Booking/HomecareBookingTest.php#L55) Your booking has been submitted successfully
- OBTC2 [HomecareBookingTest.php:74](Booking/HomecareBookingTest.php#L74) Homecare bookings require a downpayment before they can be submitted
- OBTC3 [HomecareBookingTest.php:88](Booking/HomecareBookingTest.php#L88) Homecare must be booked at least 1 hour ahead
- OBTC4 [FacilityBookingTest.php:59](Booking/FacilityBookingTest.php#L59) Pre admission form is submitted without payment
- OBTC5 [FacilityBookingTest.php:76](Booking/FacilityBookingTest.php#L76) Diagnosis must be from the past 3 months
- OBTC6 [FacilityBookingTest.php:85](Booking/FacilityBookingTest.php#L85) Primary diagnosis is required

## Table 2. Booking Review Test Case

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox
```

- BRTC1 [BookingReviewTest.php:131](Booking/BookingReviewTest.php#L131) Booking has been approved successfully
- BRTC2 [BookingReviewTest.php:159](Booking/BookingReviewTest.php#L159) Booking has successfully rejected
- BRTC3 [BookingReviewTest.php:149](Booking/BookingReviewTest.php#L149) Selected bed is already occupied
- BRTC4 [BookingReviewTest.php:179](Booking/BookingReviewTest.php#L179) Booking cannot be approved current status rejected
- BRTC5 [BookingReviewTest.php:194](Booking/BookingReviewTest.php#L194) Insufficient permissions to approve
- BRTC6 [BookingReviewTest.php:203](Booking/BookingReviewTest.php#L203) Booking has expired

# Objective 2: Client Admission Module with Staff Service Allocation

## Table 3. Client Admission and Staff Assignment Test Case

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling --testdox --filter "AdmissionTest|AssignmentTest"
```

- CATC1 [AdmissionTest.php:146](Scheduling/AdmissionTest.php#L146) Please select an admission date
- CATC2 [AdmissionTest.php:155](Scheduling/AdmissionTest.php#L155) Patient already has an active admission
- CATC3 [AdmissionTest.php:168](Scheduling/AdmissionTest.php#L168) The selected bed is no longer available
- CATC4 [AssignmentTest.php:60](Scheduling/AssignmentTest.php#L60) Staff is assigned to the medical service
- CATC5 [AssignmentTest.php:77](Scheduling/AssignmentTest.php#L77) Staff already booked at that time cannot be assigned
- CATC6 [AssignmentTest.php:93](Scheduling/AssignmentTest.php#L93) A medical service needs a nurse a caregiver can only assist

## Table 4. Staff Service Monitoring and Coordination Test Case

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Scheduling --testdox --filter "MonitoringTest|AdlShiftQrTest"
```

- SMTC1 [MonitoringTest.php:58](Scheduling/MonitoringTest.php#L58) Staff sees every schedule of the branch
- SMTC2 [MonitoringTest.php:66](Scheduling/MonitoringTest.php#L66) Nurse sees only the schedules assigned to them
- SMTC3 [AdlShiftQrTest.php:157](Scheduling/AdlShiftQrTest.php#L157) First scan clocks in and starts the schedule
- SMTC4 [AdlShiftQrTest.php:147](Scheduling/AdlShiftQrTest.php#L147) Clock in is refused outside the shift
- SMTC5 [AdlShiftQrTest.php:174](Scheduling/AdlShiftQrTest.php#L174) Second shift can scan the first shifts handover qr
- SMTC6 [AdlShiftQrTest.php:185](Scheduling/AdlShiftQrTest.php#L185) Time out completes the schedule when under a minute remains

# Objective 3: Electronic Medication Administration Record (eMAR) Module

## Table 5. eMAR and Vital Signs Test Case

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/MedicationVital --testdox
```

- EMTC1 [MedicationControllerTest.php:155](MedicationVital/MedicationControllerTest.php#L155) Successfully saved medication
- EMTC2 [MedicationControllerTest.php:143](MedicationVital/MedicationControllerTest.php#L143) Patient not found
- EMTC3 [MedicationControllerTest.php:174](MedicationVital/MedicationControllerTest.php#L174) Dose status is taken and marked by the nurse
- EMTC4 [MedicationControllerTest.php:189](MedicationVital/MedicationControllerTest.php#L189) Dosage marking the same dose twice keeps one record
- EMTC5 [MedicationControllerTest.php:199](MedicationVital/MedicationControllerTest.php#L199) Dose status is removed and no dose record is left
- EMTC6 [VitalControllerTest.php:107](MedicationVital/VitalControllerTest.php#L107) Successfully saved vital signs

# Objective 4: Electronic Billing and Client Payment Records

## Table 6. Financial Transaction and Billing Test Case

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Billing --testdox
```

- FBTC1 [InvoiceControllerTest.php:47](Billing/InvoiceControllerTest.php#L47) Patient payment has been processed successfully
- FBTC2 [InvoiceControllerTest.php:67](Billing/InvoiceControllerTest.php#L67) Enter a cash amount greater than 0
- FBTC3 [PaymentControllerTest.php:59](Billing/PaymentControllerTest.php#L59) Payment recorded successfully
- FBTC4 [PaymentControllerTest.php:84](Billing/PaymentControllerTest.php#L84) Payment failed
- FBTC5 [InvoiceControllerTest.php:75](Billing/InvoiceControllerTest.php#L75) A paid invoice can no longer be voided issue a refund or credit instead
- FBTC6 [RefundControllerTest.php:45](Billing/RefundControllerTest.php#L45) Your withdrawal request has been sent to the branch for review

# Objective 5: Subscription Management Feature

## Table 7. Subscription Registration and Review Test Case

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription --testdox --filter "SubscribeTest|ReviewTest"
```

- SRTC1 [SubscribeTest.php:61](Subscription/SubscribeTest.php#L61) Almost there we ll notify you once your branch is verified
- SRTC2 [SubscribeTest.php:75](Subscription/SubscribeTest.php#L75) Add a card to start your free trial
- SRTC3 [SubscribeTest.php:82](Subscription/SubscribeTest.php#L82) The branch email has already been taken
- SRTC4 [SubscribeTest.php:90](Subscription/SubscribeTest.php#L90) The tin must be a valid tin
- SRTC5 [ReviewTest.php:61](Subscription/ReviewTest.php#L61) Amuma davao city was approved and is now active
- SRTC6 [ReviewTest.php:71](Subscription/ReviewTest.php#L71) Amuma davao city was not approved reason business permit is expired

## Table 8. Subscription Renewal and Branch Management Test Case

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription --testdox --filter "RenewTest|BranchTest|SavedCardTest"
```

- SNTC1 [RenewTest.php:48](Subscription/RenewTest.php#L48) Subscription renewed successfully
- SNTC2 [RenewTest.php:38](Subscription/RenewTest.php#L38) Renewal opens 7 days before this subscription ends
- SNTC3 [RenewTest.php:59](Subscription/RenewTest.php#L59) Upgraded to hybrid
- SNTC4 [BranchTest.php:44](Subscription/BranchTest.php#L44) Branch added and sent for review
- SNTC5 [BranchTest.php:57](Subscription/BranchTest.php#L57) That subscription has no free branch slots
- SNTC6 [SavedCardTest.php:63](Subscription/SavedCardTest.php#L63) Your free trial ended and the plan was charged to your card

# Online Booking Tests

## BK-04 Book a Medical homecare visit

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/HomecareBookingTest.php --testdox --filter "test_medical_visit_costs_the_sum_of_the_chosen_services|test_your_booking_has_been_submitted_successfully|test_downpayment_is_saved_as_a_transaction_of_the_booking|test_you_have_a_new_booking_request|test_homecare_bookings_require_a_downpayment_before_they_can_be_submitted"
```

- [HomecareBookingTest.php:67](HomecareBookingTest.php#L67) Medical visit costs the sum of the chosen services
- [HomecareBookingTest.php:72](HomecareBookingTest.php#L72) Your booking has been submitted successfully
- [HomecareBookingTest.php:91](HomecareBookingTest.php#L91) Downpayment is saved as a transaction of the booking
- [HomecareBookingTest.php:104](HomecareBookingTest.php#L104) You have a new booking request
- [HomecareBookingTest.php:116](HomecareBookingTest.php#L116) Homecare bookings require a downpayment before they can be submitted

## BK-05 Book an ADL homecare visit

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/HomecareBookingTest.php --testdox --filter "test_adl_visit_costs_the_hours_times_the_hourly_rate|test_adl_visit_is_submitted_with_half_paid|test_no_active_adl_pricing_contract_is_configured_for_this_branch|test_adl_visit_of_1_hour_is_valid|test_the_adl_pricing_contract_has_an_invalid_price"
```

- [HomecareBookingTest.php:130](HomecareBookingTest.php#L130) Adl visit costs the hours times the hourly rate
- [HomecareBookingTest.php:138](HomecareBookingTest.php#L138) Adl visit is submitted with half paid
- [HomecareBookingTest.php:149](HomecareBookingTest.php#L149) No active adl pricing contract is configured for this branch
- [HomecareBookingTest.php:217](HomecareBookingTest.php#L217) Adl visit of 1 hour is valid
- [HomecareBookingTest.php:222](HomecareBookingTest.php#L222) The adl pricing contract has an invalid price

## BK-06 Medical visit with no service

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/HomecareBookingTest.php --testdox --filter "test_the_booking_amount_could_not_be_determined"
```

- [HomecareBookingTest.php:160](HomecareBookingTest.php#L160) The booking amount could not be determined

## BK-08 Visit less than 1 hour from now

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/HomecareBookingTest.php --testdox --filter "test_homecare_must_be_booked_at_least_1_hour_ahead|test_visit_exactly_1_hour_ahead_is_valid|test_visit_59_minutes_ahead_is_invalid|test_visit_two_hours_ahead_is_allowed"
```

- [HomecareBookingTest.php:172](HomecareBookingTest.php#L172) Homecare must be booked at least 1 hour ahead
- [HomecareBookingTest.php:197](HomecareBookingTest.php#L197) Visit exactly 1 hour ahead is valid
- [HomecareBookingTest.php:206](HomecareBookingTest.php#L206) Visit 59 minutes ahead is invalid
- [HomecareBookingTest.php:231](HomecareBookingTest.php#L231) Visit two hours ahead is allowed

## BK-10 Submit a Pre-Admission form

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/FacilityBookingTest.php --testdox --filter "test_pre_admission_form_is_submitted_without_payment|test_pre_admission_form_stays_valid_for_one_month|test_this_booking_does_not_take_a_payment"
```

- [FacilityBookingTest.php:59](FacilityBookingTest.php#L59) Pre admission form is submitted without payment
- [FacilityBookingTest.php:76](FacilityBookingTest.php#L76) Pre admission form stays valid for one month
- [FacilityBookingTest.php:85](FacilityBookingTest.php#L85) This booking does not take a payment

## BK-11 Book a Complete Admission

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/FacilityBookingTest.php --testdox --filter "test_complete_admission_costs_the_plan_price|test_complete_admission_is_submitted_fully_paid|test_complete_admission_is_valid_until_the_admission_date|test_no_active_vip_yearly_pricing_contract_is_configured_for_this_branch"
```

- [FacilityBookingTest.php:98](FacilityBookingTest.php#L98) Complete admission costs the plan price
- [FacilityBookingTest.php:109](FacilityBookingTest.php#L109) Complete admission is submitted fully paid
- [FacilityBookingTest.php:123](FacilityBookingTest.php#L123) Complete admission is valid until the admission date
- [FacilityBookingTest.php:132](FacilityBookingTest.php#L132) No active vip yearly pricing contract is configured for this branch

## BK-13 Room that is full

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_selected_bed_is_already_occupied"
```

- [BookingReviewTest.php:230](BookingReviewTest.php#L230) Selected bed is already occupied

## BK-14 Diagnosis older than 3 months

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/FacilityBookingTest.php --testdox --filter "test_diagnosis_must_be_from_the_past_6_months|test_diagnosis_date_cannot_be_in_the_future|test_primary_diagnosis_is_required|test_diagnosis_exactly_6_months_old_is_valid|test_diagnosis_dated_today_is_valid|test_diagnosis_6_months_and_1_day_old_is_invalid|test_recent_diagnosis_is_accepted"
```

- [FacilityBookingTest.php:145](FacilityBookingTest.php#L145) Diagnosis must be from the past 3 months
- [FacilityBookingTest.php:154](FacilityBookingTest.php#L154) Diagnosis date cannot be in the future
- [FacilityBookingTest.php:161](FacilityBookingTest.php#L161) Primary diagnosis is required
- [FacilityBookingTest.php:168](FacilityBookingTest.php#L168) Diagnosis exactly 3 months old is valid
- [FacilityBookingTest.php:177](FacilityBookingTest.php#L177) Diagnosis dated today is valid
- [FacilityBookingTest.php:186](FacilityBookingTest.php#L186) Diagnosis 3 months and 1 day old is invalid
- [FacilityBookingTest.php:195](FacilityBookingTest.php#L195) Recent diagnosis is accepted

## BK-15 View bookings

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_staff_sees_the_bookings_of_their_branch|test_status_filter_shows_only_matching_bookings|test_booking_details_show_the_patient_and_service"
```

- [BookingReviewTest.php:146](BookingReviewTest.php#L146) Staff sees the bookings of their branch
- [BookingReviewTest.php:157](BookingReviewTest.php#L157) Status filter shows only matching bookings
- [BookingReviewTest.php:167](BookingReviewTest.php#L167) Booking details show the patient and service

## BK-16 Approve a homecare booking

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_booking_has_been_approved_successfully|test_downpayment_counts_toward_the_invoice|test_your_booking_has_been_approved_by_amuma_davao_city"
```

- [BookingReviewTest.php:178](BookingReviewTest.php#L178) Booking has been approved successfully
- [BookingReviewTest.php:196](BookingReviewTest.php#L196) Downpayment counts toward the invoice
- [BookingReviewTest.php:209](BookingReviewTest.php#L209) Your booking has been approved by amuma davao city

## BK-17 Approve a Complete Admission

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_approved_admission_holds_the_bed_and_settles_the_invoice"
```

- [BookingReviewTest.php:218](BookingReviewTest.php#L218) Approved admission holds the bed and settles the invoice

## BK-18 Accept a Pre-Admission form

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_pre_admission_booking_has_been_approved|test_only_pre_admission_bookings_can_be_approved_without_processing"
```

- [BookingReviewTest.php:240](BookingReviewTest.php#L240) Pre admission booking has been approved
- [BookingReviewTest.php:255](BookingReviewTest.php#L255) Only pre admission bookings can be approved without processing

## BK-19 Reject a paid booking

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_booking_has_successfully_rejected|test_your_booking_was_declined_by_amuma_davao_city_reason_no_caregiver_available|test_unpaid_booking_is_rejected_without_a_refund"
```

- [BookingReviewTest.php:269](BookingReviewTest.php#L269) Booking has successfully rejected
- [BookingReviewTest.php:289](BookingReviewTest.php#L289) Your booking was declined by amuma davao city reason no caregiver available
- [BookingReviewTest.php:301](BookingReviewTest.php#L301) Unpaid booking is rejected without a refund

## BK-20 Approve a booking twice

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_booking_cannot_be_processed_because_its_current_status_is_approved|test_booking_cannot_be_approved_current_status_rejected"
```

- [BookingReviewTest.php:309](BookingReviewTest.php#L309) Booking cannot be processed because its current status is approved
- [BookingReviewTest.php:323](BookingReviewTest.php#L323) Booking cannot be approved current status rejected

## BK-21 Staff without Review permission

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_insufficient_permissions_to_approve|test_insufficient_permissions_to_reject|test_insufficient_permissions_to_list_the_branch_bookings"
```

- [BookingReviewTest.php:338](BookingReviewTest.php#L338) Insufficient permissions to approve
- [BookingReviewTest.php:347](BookingReviewTest.php#L347) Insufficient permissions to reject
- [BookingReviewTest.php:356](BookingReviewTest.php#L356) Insufficient permissions to list the branch bookings

## BK-22 Staff books homecare for a walk-in

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_homecare_booking_created_it_is_waiting_for_review|test_this_branch_has_no_homecare_service_subscription|test_the_guardian_email_field_is_required|test_this_email_belongs_to_a_staff_member|test_walk_in_visit_dated_today_is_valid|test_the_date_field_must_be_a_date_after_or_equal_to_today|test_the_guardian_email_field_must_be_a_valid_email_address"
```

- [BookingReviewTest.php:377](BookingReviewTest.php#L377) Homecare booking created it is waiting for review
- [BookingReviewTest.php:393](BookingReviewTest.php#L393) This branch has no homecare service subscription
- [BookingReviewTest.php:398](BookingReviewTest.php#L398) The guardian email field is required
- [BookingReviewTest.php:407](BookingReviewTest.php#L407) This email belongs to a staff member
- [BookingReviewTest.php:475](BookingReviewTest.php#L475) Walk in visit dated today is valid
- [BookingReviewTest.php:485](BookingReviewTest.php#L485) The date field must be a date after or equal to today
- [BookingReviewTest.php:497](BookingReviewTest.php#L497) The guardian email field must be a valid email address

## BK-23 Take cash at the counter

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_the_cash_received_must_cover_the_amount_due|test_payment_for_booking_received|test_cash_exactly_the_amount_due_is_valid|test_cash_1_centavo_short_is_invalid|test_this_booking_cant_be_paid_right_now"
```

- [BookingReviewTest.php:418](BookingReviewTest.php#L418) The cash received must cover the amount due
- [BookingReviewTest.php:433](BookingReviewTest.php#L433) Payment for booking received
- [BookingReviewTest.php:450](BookingReviewTest.php#L450) Cash exactly the amount due is valid
- [BookingReviewTest.php:462](BookingReviewTest.php#L462) Cash 1 centavo short is invalid
- [BookingReviewTest.php:506](BookingReviewTest.php#L506) This booking cant be paid right now

## BK-24 Booking that was never reviewed

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Booking/BookingReviewTest.php --testdox --filter "test_unreviewed_booking_expires_and_is_refunded|test_booking_still_in_time_is_left_alone|test_booking_has_expired"
```

- [BookingReviewTest.php:517](BookingReviewTest.php#L517) Unreviewed booking expires and is refunded
- [BookingReviewTest.php:530](BookingReviewTest.php#L530) Booking still in time is left alone
- [BookingReviewTest.php:539](BookingReviewTest.php#L539) Booking has expired

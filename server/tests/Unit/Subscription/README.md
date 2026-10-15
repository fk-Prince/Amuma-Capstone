# Subscription Tests

## SUB-01 View the plans

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_plans_are_listed_with_their_prices"
```

- [SubscribeTest.php:68](SubscribeTest.php#L68) Plans are listed with their prices

## SUB-02 Update a plan price

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_plan_updated_successfully"
```

- [SubscribeTest.php:77](SubscribeTest.php#L77) Plan updated successfully

## SUB-03 Subscribe for the first time

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_almost_there_we_ll_notify_you_once_your_branch_is_verified|test_first_time_agency_gets_a_two_week_free_trial|test_subscription_payment_is_recorded|test_user_becomes_the_agency_owner_with_every_permission|test_new_subscription_request_from_amuma_davao_city_is_awaiting_your_review|test_invalid_plan_type|test_plan_not_found"
```

- [SubscribeTest.php:88](SubscribeTest.php#L88) Almost there we ll notify you once your branch is verified
- [SubscribeTest.php:102](SubscribeTest.php#L102) First time agency gets a two week free trial
- [SubscribeTest.php:113](SubscribeTest.php#L113) Subscription payment is recorded
- [SubscribeTest.php:125](SubscribeTest.php#L125) User becomes the agency owner with every permission
- [SubscribeTest.php:135](SubscribeTest.php#L135) New subscription request from amuma davao city is awaiting your review
- [SubscribeTest.php:189](SubscribeTest.php#L189) Invalid plan type
- [SubscribeTest.php:196](SubscribeTest.php#L196) Plan not found

## SUB-04 Subscribe an agency that already has a subscription

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_this_agency_already_has_a_subscription_add_a_branch_to_it_instead"
```

- [SubscribeTest.php:145](SubscribeTest.php#L145) This agency already has a subscription add a branch to it instead

## SUB-05 Leave out or repeat details

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_the_branch_name_field_is_required|test_the_branch_email_has_already_been_taken|test_the_tin_must_be_a_valid_tin|test_tin_ending_in_3_or_5_digits_is_valid"
```

- [SubscribeTest.php:158](SubscribeTest.php#L158) The branch name field is required
- [SubscribeTest.php:164](SubscribeTest.php#L164) The branch email has already been taken
- [SubscribeTest.php:172](SubscribeTest.php#L172) The tin must be a valid tin
- [SubscribeTest.php:181](SubscribeTest.php#L181) Tin ending in 3 or 5 digits is valid

## SUB-06 Card is declined

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_payment_failed|test_card_is_charged_the_plan_price"
```

- [SubscribeTest.php:203](SubscribeTest.php#L203) Payment failed
- [SubscribeTest.php:215](SubscribeTest.php#L215) Card is charged the plan price

## SUB-07 Approve a branch request

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter "test_branch_and_agency_are_verified_on_approval|test_approval_starts_the_free_trial|test_amuma_davao_city_was_approved_and_is_now_active|test_history_shows_the_decision_and_who_made_it"
```

- [ReviewTest.php:62](ReviewTest.php#L62) Branch and agency are verified on approval
- [ReviewTest.php:71](ReviewTest.php#L71) Approval starts the free trial
- [ReviewTest.php:82](ReviewTest.php#L82) Amuma davao city was approved and is now active
- [ReviewTest.php:152](ReviewTest.php#L152) History shows the decision and who made it

## SUB-08 Approve a request twice

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter "test_only_pending_branches_can_be_approved"
```

- [ReviewTest.php:92](ReviewTest.php#L92) Only pending branches can be approved

## SUB-09 Reject the agency's only branch

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter "test_branch_request_rejected|test_amuma_davao_city_was_not_approved_reason_business_permit_is_expired"
```

- [ReviewTest.php:105](ReviewTest.php#L105) Branch request rejected
- [ReviewTest.php:116](ReviewTest.php#L116) Amuma davao city was not approved reason business permit is expired

## SUB-10 Reject an additional branch

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter "test_rejecting_an_additional_branch_refunds_only_its_payment"
```

- [ReviewTest.php:124](ReviewTest.php#L124) Rejecting an additional branch refunds only its payment

## SUB-11 Refund fails while rejecting

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter "test_subscription_cannot_be_rejected_because_the_payment_refund_failed"
```

- [ReviewTest.php:141](ReviewTest.php#L141) Subscription cannot be rejected because the payment refund failed

## SUB-12 Add a branch while a slot is free

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_tenth_branch_on_an_enterprise_plan_is_valid|test_branch_added_and_sent_for_review|test_new_branch_request_from_amuma_tagum_is_awaiting_your_review"
```

- [BranchTest.php:83](BranchTest.php#L83) Tenth branch on an enterprise plan is valid
- [BranchTest.php:124](BranchTest.php#L124) Branch added and sent for review
- [BranchTest.php:137](BranchTest.php#L137) New branch request from amuma tagum is awaiting your review

## SUB-13 Add a branch when the plan is full

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_eleventh_branch_on_an_enterprise_plan_is_invalid|test_that_subscription_has_no_free_branch_slots"
```

- [BranchTest.php:92](BranchTest.php#L92) Eleventh branch on an enterprise plan is invalid
- [BranchTest.php:149](BranchTest.php#L149) That subscription has no free branch slots

## SUB-14 Buy a branch during the free trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_branches_can_be_added_once_your_free_trial_ends"
```

- [BranchTest.php:162](BranchTest.php#L162) Branches can be added once your free trial ends

## SUB-15 Buy an additional branch

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_extra_branch_with_1_month_left_costs_one_twelfth|test_this_subscription_cant_take_an_additional_branch|test_payment_received_branch_sent_for_review|test_extra_branch_is_saved_as_an_additional_pending_request"
```

- [BranchTest.php:106](BranchTest.php#L106) Extra branch with 1 month left costs one twelfth
- [BranchTest.php:115](BranchTest.php#L115) This subscription cant take an additional branch
- [BranchTest.php:171](BranchTest.php#L171) Payment received branch sent for review
- [BranchTest.php:183](BranchTest.php#L183) Extra branch is saved as an additional pending request

## SUB-16 Resubmit a rejected branch

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_branch_resubmitted_for_review"
```

- [BranchTest.php:194](BranchTest.php#L194) Branch resubmitted for review

## SUB-17 Resubmit a branch that was not rejected

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_only_rejected_branches_can_be_resubmitted"
```

- [BranchTest.php:207](BranchTest.php#L207) Only rejected branches can be resubmitted

## SUB-18 Renew too early

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_renewal_opens_7_days_before_this_subscription_ends|test_renewal_with_exactly_7_days_left_is_valid|test_renewal_with_8_days_left_is_invalid"
```

- [RenewTest.php:38](RenewTest.php#L38) Renewal opens 7 days before this subscription ends
- [RenewTest.php:48](RenewTest.php#L48) Renewal with exactly 7 days left is valid
- [RenewTest.php:55](RenewTest.php#L55) Renewal with 8 days left is invalid

## SUB-19 Renew the same plan

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_subscription_renewed_successfully|test_renewal_payment_is_the_plan_price"
```

- [RenewTest.php:63](RenewTest.php#L63) Subscription renewed successfully
- [RenewTest.php:74](RenewTest.php#L74) Renewal payment is the plan price

## SUB-20 Renew onto another plan

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_renewed_homecare_services_starts_on_the_end_date"
```

- [RenewTest.php:84](RenewTest.php#L84) Renewed homecare services starts on the end date

## SUB-21 Upgrade to Hybrid

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_upgraded_to_hybrid"
```

- [RenewTest.php:98](RenewTest.php#L98) Upgraded to hybrid

## SUB-22 Change to a plan that is not Hybrid, or change the size

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_this_plan_can_only_be_upgraded_to_hybrid|test_the_plan_type_of_a_subscription_cant_be_changed"
```

- [RenewTest.php:109](RenewTest.php#L109) This plan can only be upgraded to hybrid
- [RenewTest.php:116](RenewTest.php#L116) The plan type of a subscription cant be changed

## SUB-23 Renew during the free trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_your_paid_year_is_already_paid_for_and_starts_when_your_free_trial_ends"
```

- [RenewTest.php:123](RenewTest.php#L123) Your paid year is already paid for and starts when your free trial ends

## SUB-24 Renewal card is declined

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_payment_failed_leaves_the_subscription_unchanged"
```

- [RenewTest.php:139](RenewTest.php#L139) Payment failed leaves the subscription unchanged

## SUB-25 End the free trial early

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/TrialTest.php --testdox --filter "test_free_trial_ended_your_paid_year_starts_today|test_only_an_active_subscription_still_in_its_free_trial_can_be_ended_early"
```

- [TrialTest.php:51](TrialTest.php#L51) Free trial ended your paid year starts today
- [TrialTest.php:64](TrialTest.php#L64) Only an active subscription still in its free trial can be ended early

## SUB-26 Cancel during the free trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/TrialTest.php --testdox --filter "test_subscription_cancelled_your_payment_is_being_refunded|test_the_subscription_could_not_be_cancelled_because_the_refund_failed"
```

- [TrialTest.php:75](TrialTest.php#L75) Subscription cancelled your payment is being refunded
- [TrialTest.php:86](TrialTest.php#L86) The subscription could not be cancelled because the refund failed

## SUB-27 Cancel a queued plan change

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/TrialTest.php --testdox --filter "test_plan_change_cancelled_your_payment_is_being_refunded"
```

- [TrialTest.php:100](TrialTest.php#L100) Plan change cancelled your payment is being refunded

## SUB-28 Cancel when nothing is queued

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/TrialTest.php --testdox --filter "test_there_is_no_queued_plan_change_to_cancel"
```

- [TrialTest.php:111](TrialTest.php#L111) There is no queued plan change to cancel

## SUB-29 Free trial ends

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/DailyJobsTest.php --testdox --filter "test_trial_turns_into_the_paid_year_when_it_ends|test_plan_waiting_for_a_later_date_is_left_alone"
```

- [DailyJobsTest.php:28](DailyJobsTest.php#L28) Trial turns into the paid year when it ends
- [DailyJobsTest.php:46](DailyJobsTest.php#L46) Plan waiting for a later date is left alone

## SUB-30 Subscription runs out

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/DailyJobsTest.php --testdox --filter "test_subscription_past_its_end_date_is_marked_expired|test_this_branchs_subscription_has_ended"
```

- [DailyJobsTest.php:58](DailyJobsTest.php#L58) Subscription past its end date is marked expired
- [DailyJobsTest.php:67](DailyJobsTest.php#L67) This branchs subscription has ended

## SUB-31 Renewal reminder

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/DailyJobsTest.php --testdox --filter "test_amuma_davao_city_your_homecare_services_subscription_ends_in_3_days|test_no_renewal_reminder_far_from_the_end_date"
```

- [DailyJobsTest.php:81](DailyJobsTest.php#L81) Amuma davao city your homecare services subscription ends in 3 days
- [DailyJobsTest.php:97](DailyJobsTest.php#L97) No renewal reminder far from the end date

## SUB-32 Branch Manager

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/DailyJobsTest.php --testdox --filter "test_insufficient_permissions|test_branch_manager_can_view_the_subscription"
```

- [DailyJobsTest.php:106](DailyJobsTest.php#L106) Insufficient permissions
- [DailyJobsTest.php:118](DailyJobsTest.php#L118) Branch manager can view the subscription

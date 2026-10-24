# Subscription Tests

## SUB-01 View the plans

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_plans_are_listed_with_their_prices"
```

- [SubscribeTest.php:75](SubscribeTest.php#L75) Plans are listed with their prices

## SUB-02 Update a plan price

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_plan_updated_successfully"
```

- [SubscribeTest.php:84](SubscribeTest.php#L84) Plan updated successfully

## SUB-03 Start the free trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_almost_there_we_ll_notify_you_once_your_branch_is_verified|test_first_time_agency_gets_a_two_week_free_trial|test_the_free_trial_request_takes_no_payment|test_user_becomes_the_agency_owner_with_every_permission|test_new_subscription_request_from_amuma_davao_city_is_awaiting_your_review|test_invalid_plan_type|test_plan_not_found"
```

- [SubscribeTest.php:95](SubscribeTest.php#L95) Almost there we ll notify you once your branch is verified
- [SubscribeTest.php:109](SubscribeTest.php#L109) First time agency gets a two week free trial
- [SubscribeTest.php:120](SubscribeTest.php#L120) The free trial request takes no payment
- [SubscribeTest.php:144](SubscribeTest.php#L144) User becomes the agency owner with every permission
- [SubscribeTest.php:154](SubscribeTest.php#L154) New subscription request from amuma davao city is awaiting your review
- [SubscribeTest.php:208](SubscribeTest.php#L208) Invalid plan type
- [SubscribeTest.php:215](SubscribeTest.php#L215) Plan not found

## SUB-04 Subscribe an agency that already has a subscription

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_this_agency_already_has_a_subscription_add_a_branch_to_it_instead"
```

- [SubscribeTest.php:164](SubscribeTest.php#L164) This agency already has a subscription add a branch to it instead

## SUB-05 Leave out or repeat details

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_the_branch_name_field_is_required|test_the_branch_email_has_already_been_taken|test_the_tin_must_be_a_valid_tin|test_tin_ending_in_3_or_5_digits_is_valid"
```

- [SubscribeTest.php:177](SubscribeTest.php#L177) The branch name field is required
- [SubscribeTest.php:183](SubscribeTest.php#L183) The branch email has already been taken
- [SubscribeTest.php:191](SubscribeTest.php#L191) The tin must be a valid tin
- [SubscribeTest.php:200](SubscribeTest.php#L200) Tin ending in 3 or 5 digits is valid

## SUB-06 Pay up front instead of the trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/SubscribeTest.php --testdox --filter "test_paying_up_front_records_the_payment|test_payment_failed|test_card_is_charged_the_plan_price"
```

- [SubscribeTest.php:132](SubscribeTest.php#L132) Paying up front records the payment
- [SubscribeTest.php:222](SubscribeTest.php#L222) Payment failed
- [SubscribeTest.php:234](SubscribeTest.php#L234) Card is charged the plan price

## SUB-07 Approve a free trial request

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter "test_branch_and_agency_are_verified_on_approval|test_approval_starts_the_free_trial|test_amuma_davao_city_was_approved_and_is_now_active|test_history_shows_the_decision_and_who_made_it"
```

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_approving_the_trial_starts_its_two_weeks_from_today|test_a_slow_review_never_locks_the_branch_before_approval"
```

- [ReviewTest.php:62](ReviewTest.php#L62) Branch and agency are verified on approval
- [ReviewTest.php:71](ReviewTest.php#L71) Approval starts the free trial
- [UnpaidTrialTest.php:172](UnpaidTrialTest.php#L172) Approving the trial starts its two weeks from today
- [UnpaidTrialTest.php:159](UnpaidTrialTest.php#L159) A slow review never locks the branch before approval
- [ReviewTest.php:82](ReviewTest.php#L82) Amuma davao city was approved and is now active
- [ReviewTest.php:152](ReviewTest.php#L152) History shows the decision and who made it

## SUB-08 Approve a request twice

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter "test_only_pending_branches_can_be_approved"
```

- [ReviewTest.php:92](ReviewTest.php#L92) Only pending branches can be approved

## SUB-09 Reject a free trial request

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_rejecting_a_free_trial_request_has_nothing_to_refund"
```

- [UnpaidTrialTest.php:114](UnpaidTrialTest.php#L114) Rejecting a free trial request has nothing to refund

## SUB-10 Reject the agency's only branch after paying

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter "test_branch_request_rejected|test_amuma_davao_city_was_not_approved_reason_business_permit_is_expired"
```

- [ReviewTest.php:105](ReviewTest.php#L105) Branch request rejected
- [ReviewTest.php:116](ReviewTest.php#L116) Amuma davao city was not approved reason business permit is expired

## SUB-11 Reject an additional branch

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter "test_rejecting_an_additional_branch_refunds_only_its_payment"
```

- [ReviewTest.php:124](ReviewTest.php#L124) Rejecting an additional branch refunds only its payment

## SUB-12 Refund fails while rejecting

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/ReviewTest.php --testdox --filter "test_subscription_cannot_be_rejected_because_the_payment_refund_failed"
```

- [ReviewTest.php:141](ReviewTest.php#L141) Subscription cannot be rejected because the payment refund failed

## SUB-13 Add a branch while a slot is free

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_tenth_branch_on_an_enterprise_plan_is_valid|test_branch_added_and_sent_for_review|test_new_branch_request_from_amuma_tagum_is_awaiting_your_review"
```

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_a_branch_fills_a_free_slot_while_the_trial_is_unpaid"
```

- [BranchTest.php:83](BranchTest.php#L83) Tenth branch on an enterprise plan is valid
- [BranchTest.php:124](BranchTest.php#L124) Branch added and sent for review
- [UnpaidTrialTest.php:214](UnpaidTrialTest.php#L214) A branch fills a free slot while the trial is unpaid
- [BranchTest.php:137](BranchTest.php#L137) New branch request from amuma tagum is awaiting your review

## SUB-14 Add a branch when the plan is full

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_eleventh_branch_on_an_enterprise_plan_is_invalid|test_that_subscription_has_no_free_branch_slots"
```

- [BranchTest.php:92](BranchTest.php#L92) Eleventh branch on an enterprise plan is invalid
- [BranchTest.php:149](BranchTest.php#L149) That subscription has no free branch slots

## SUB-15 Buy a branch during the free trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_branches_can_be_added_once_your_free_trial_ends"
```

- [BranchTest.php:162](BranchTest.php#L162) Branches can be added once your free trial ends

## SUB-16 Buy an additional branch

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_extra_branch_with_1_month_left_costs_one_twelfth|test_this_subscription_cant_take_an_additional_branch|test_payment_received_branch_sent_for_review|test_extra_branch_is_saved_as_an_additional_pending_request"
```

- [BranchTest.php:106](BranchTest.php#L106) Extra branch with 1 month left costs one twelfth
- [BranchTest.php:115](BranchTest.php#L115) This subscription cant take an additional branch
- [BranchTest.php:171](BranchTest.php#L171) Payment received branch sent for review
- [BranchTest.php:183](BranchTest.php#L183) Extra branch is saved as an additional pending request

## SUB-17 Resubmit a rejected branch

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_branch_resubmitted_for_review"
```

- [BranchTest.php:194](BranchTest.php#L194) Branch resubmitted for review

## SUB-18 Reapply after a rejected free trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_a_rejected_free_trial_is_resubmitted_without_paying"
```

- [UnpaidTrialTest.php:128](UnpaidTrialTest.php#L128) A rejected free trial is resubmitted without paying

## SUB-19 Resubmit into a rejected paid subscription

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_a_rejected_paid_subscription_is_not_reopened_for_free"
```

- [UnpaidTrialTest.php:186](UnpaidTrialTest.php#L186) A rejected paid subscription is not reopened for free

## SUB-20 Resubmit a branch that was not rejected

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/BranchTest.php --testdox --filter "test_only_rejected_branches_can_be_resubmitted"
```

- [BranchTest.php:207](BranchTest.php#L207) Only rejected branches can be resubmitted

## SUB-21 Renew too early

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_renewal_opens_7_days_before_this_subscription_ends|test_renewal_with_exactly_7_days_left_is_valid|test_renewal_with_8_days_left_is_invalid"
```

- [RenewTest.php:38](RenewTest.php#L38) Renewal opens 7 days before this subscription ends
- [RenewTest.php:48](RenewTest.php#L48) Renewal with exactly 7 days left is valid
- [RenewTest.php:55](RenewTest.php#L55) Renewal with 8 days left is invalid

## SUB-22 Renew the same plan

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_subscription_renewed_successfully|test_renewal_payment_is_the_plan_price"
```

- [RenewTest.php:63](RenewTest.php#L63) Subscription renewed successfully
- [RenewTest.php:74](RenewTest.php#L74) Renewal payment is the plan price

## SUB-23 Renew onto another plan

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_renewed_homecare_services_starts_on_the_end_date"
```

- [RenewTest.php:84](RenewTest.php#L84) Renewed homecare services starts on the end date

## SUB-24 Upgrade to Hybrid

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_upgraded_to_hybrid"
```

- [RenewTest.php:98](RenewTest.php#L98) Upgraded to hybrid

## SUB-25 Change to a plan that is not Hybrid, or change the size

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_this_plan_can_only_be_upgraded_to_hybrid|test_the_plan_type_of_a_subscription_cant_be_changed"
```

- [RenewTest.php:109](RenewTest.php#L109) This plan can only be upgraded to hybrid
- [RenewTest.php:116](RenewTest.php#L116) The plan type of a subscription cant be changed

## SUB-26 Renew during a paid-ahead free trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_your_paid_year_is_already_paid_for_and_starts_when_your_free_trial_ends"
```

- [RenewTest.php:123](RenewTest.php#L123) Your paid year is already paid for and starts when your free trial ends

## SUB-27 Renewal card is declined

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/RenewTest.php --testdox --filter "test_payment_failed_leaves_the_subscription_unchanged"
```

- [RenewTest.php:139](RenewTest.php#L139) Payment failed leaves the subscription unchanged

## SUB-28 Subscribe again after cancelling

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_subscribing_again_after_cancelling_is_paid_and_starts_today"
```

- [UnpaidTrialTest.php:249](UnpaidTrialTest.php#L249) Subscribing again after cancelling is paid and starts today

## SUB-29 Pay before the request is approved

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_a_trial_waiting_for_review_cannot_be_paid_yet"
```

- [UnpaidTrialTest.php:147](UnpaidTrialTest.php#L147) A trial waiting for review cannot be paid yet

## SUB-30 Pay ahead during the trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_paying_ahead_keeps_the_trial_until_it_ends|test_paying_ahead_can_pick_another_plan_of_the_same_type|test_a_paid_trial_is_not_awaiting_payment"
```

- [UnpaidTrialTest.php:293](UnpaidTrialTest.php#L293) Paying ahead keeps the trial until it ends
- [UnpaidTrialTest.php:312](UnpaidTrialTest.php#L312) Paying ahead can pick another plan of the same type
- [UnpaidTrialTest.php:375](UnpaidTrialTest.php#L375) A paid trial is not awaiting payment

## SUB-31 Pay and start now

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_paying_and_starting_now_goes_live_today"
```

- [UnpaidTrialTest.php:276](UnpaidTrialTest.php#L276) Paying and starting now goes live today

## SUB-32 End the free trial early

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/TrialTest.php --testdox --filter "test_free_trial_ended_your_paid_year_starts_today|test_only_an_active_subscription_still_in_its_free_trial_can_be_ended_early"
```

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_ending_an_unpaid_trial_early_asks_for_payment"
```

- [TrialTest.php:51](TrialTest.php#L51) Free trial ended your paid year starts today
- [UnpaidTrialTest.php:265](UnpaidTrialTest.php#L265) Ending an unpaid trial early asks for payment
- [TrialTest.php:64](TrialTest.php#L64) Only an active subscription still in its free trial can be ended early

## SUB-33 Cancel during the free trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/TrialTest.php --testdox --filter "test_subscription_cancelled_your_payment_is_being_refunded|test_a_cancel_reason_is_required|test_the_subscription_could_not_be_cancelled_because_the_refund_failed"
```

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_cancelling_an_unpaid_trial_needs_no_refund"
```

- [TrialTest.php:75](TrialTest.php#L75) Subscription cancelled your payment is being refunded
- [UnpaidTrialTest.php:356](UnpaidTrialTest.php#L356) Cancelling an unpaid trial needs no refund
- [TrialTest.php:87](TrialTest.php#L87) A cancel reason is required
- [TrialTest.php:100](TrialTest.php#L100) The subscription could not be cancelled because the refund failed

## SUB-34 Cancel a queued plan change

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/TrialTest.php --testdox --filter "test_plan_change_cancelled_your_payment_is_being_refunded"
```

- [TrialTest.php:114](TrialTest.php#L114) Plan change cancelled your payment is being refunded

## SUB-35 Cancel when nothing is queued

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/TrialTest.php --testdox --filter "test_there_is_no_queued_plan_change_to_cancel"
```

- [TrialTest.php:125](TrialTest.php#L125) There is no queued plan change to cancel

## SUB-36 Free trial ends after paying ahead

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/DailyJobsTest.php --testdox --filter "test_trial_turns_into_the_paid_year_when_it_ends|test_plan_waiting_for_a_later_date_is_left_alone"
```

- [DailyJobsTest.php:28](DailyJobsTest.php#L28) Trial turns into the paid year when it ends
- [DailyJobsTest.php:46](DailyJobsTest.php#L46) Plan waiting for a later date is left alone

## SUB-37 Free trial ends unpaid

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_the_nightly_job_does_not_start_an_unpaid_trial|test_a_trial_that_ended_unpaid_leaves_the_branch_read_only|test_paying_after_the_trial_ended_starts_the_year_today|test_an_unpaid_trial_cannot_buy_an_upgrade_on_its_own"
```

- [UnpaidTrialTest.php:324](UnpaidTrialTest.php#L324) The nightly job does not start an unpaid trial
- [UnpaidTrialTest.php:231](UnpaidTrialTest.php#L231) A trial that ended unpaid leaves the branch read only
- [UnpaidTrialTest.php:341](UnpaidTrialTest.php#L341) Paying after the trial ended starts the year today
- [UnpaidTrialTest.php:367](UnpaidTrialTest.php#L367) An unpaid trial cannot buy an upgrade on its own

## SUB-38 Subscription runs out

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/DailyJobsTest.php --testdox --filter "test_subscription_past_its_end_date_is_marked_expired|test_this_branchs_subscription_has_ended"
```

- [DailyJobsTest.php:58](DailyJobsTest.php#L58) Subscription past its end date is marked expired
- [DailyJobsTest.php:67](DailyJobsTest.php#L67) This branchs subscription has ended

## SUB-39 Renewal and trial reminders

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/DailyJobsTest.php --testdox --filter "test_amuma_davao_city_your_homecare_services_subscription_ends_in_3_days|test_no_renewal_reminder_far_from_the_end_date"
```

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/UnpaidTrialTest.php --testdox --filter "test_unpaid_trials_are_reminded_to_pay_before_the_trial_ends"
```

- [DailyJobsTest.php:81](DailyJobsTest.php#L81) Amuma davao city your homecare services subscription ends in 3 days
- [UnpaidTrialTest.php:388](UnpaidTrialTest.php#L388) Unpaid trials are reminded to pay before the trial ends
- [DailyJobsTest.php:97](DailyJobsTest.php#L97) No renewal reminder far from the end date

## SUB-40 Demo copy follows the trial

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/SandboxProvisionTest.php --testdox --filter "test_provisioning_creates_the_owner_branch_and_demo_data|test_a_branch_under_review_starts_locked_in_the_demo|test_live_tells_the_demo_which_trials_are_locked|test_the_demo_locks_and_unlocks_an_agency"
```

- [SandboxProvisionTest.php:76](../../Feature/SandboxProvisionTest.php#L76) Provisioning creates the owner branch and demo data
- [SandboxProvisionTest.php:265](../../Feature/SandboxProvisionTest.php#L265) A branch under review starts locked in the demo
- [SandboxProvisionTest.php:222](../../Feature/SandboxProvisionTest.php#L222) Live tells the demo which trials are locked
- [SandboxProvisionTest.php:249](../../Feature/SandboxProvisionTest.php#L249) The demo locks and unlocks an agency

## SUB-41 No subscribing in the demo

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/SandboxProvisionTest.php --testdox --filter "test_subscription_changes_are_blocked_in_the_demo"
```

- [SandboxProvisionTest.php:272](../../Feature/SandboxProvisionTest.php#L272) Subscription changes are blocked in the demo

## SUB-42 Branch Manager

```
D:\xampp\php\php.exe -d extension_dir="D:\xampp\php\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Subscription/DailyJobsTest.php --testdox --filter "test_insufficient_permissions|test_branch_manager_can_view_the_subscription"
```

- [DailyJobsTest.php:106](DailyJobsTest.php#L106) Insufficient permissions
- [DailyJobsTest.php:118](DailyJobsTest.php#L118) Branch manager can view the subscription

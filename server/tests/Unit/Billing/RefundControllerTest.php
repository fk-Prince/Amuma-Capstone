<?php

namespace Tests\Unit\Billing;

use App\Enums\PermissionAction;
use App\Http\Controllers\RefundController;
use App\Models\Notification;
use App\Models\Transaction;
use App\Repository\RefundRepository;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class RefundControllerTest extends TestCase
{
    use UsesTempDatabase;
    use BillingFixtures;

    private RefundController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBilling();
        $this->controller = $this->app->make(RefundController::class);
    }

    private function ask(array $overrides = []): array
    {
        return $this->controller->store($this->makeRequest('POST', array_merge([
            'patient_id' => $this->patient->patient_id,
            'method' => 'GCash',
            'account_details' => '09171234567',
            'account_name' => 'Gina Cruz',
            'amount' => 200,
        ], $overrides), $this->guardian));
    }

    private function issue(array $overrides = [], $user = null): array
    {
        return $this->controller->issue($this->makeRequest('POST', array_merge([
            'p_uuid' => $this->patient->uuid,
            'branch_uuid' => $this->branchUuid,
            'amount' => 100,
        ], $overrides), $user));
    }

    private function credit(): float
    {
        return $this->app->make(RefundRepository::class)->creditFor($this->patient->patient_id);
    }

    public function test_your_withdrawal_request_has_been_sent_to_the_branch_for_review(): void
    {
        $this->creditOnAccount(300);

        $result = $this->ask();

        $this->assertTrue($result['success']);
        $this->assertSame('Your withdrawal request has been sent to the branch for review.', $result['message']);
        $this->assertSame(200.0, (float) $result['amount']);

        $withdrawal = Transaction::where('type', Transaction::TYPE_WITHDRAW)->firstOrFail();

        $this->assertSame(Transaction::STATUS_REQUESTED, $withdrawal->status);
        $this->assertSame(Transaction::DIRECTION_DEBIT, $withdrawal->direction);
        $this->assertSame('200.00', (string) $withdrawal->amount);
        $this->assertSame('GCash', $withdrawal->method);
        $this->assertSame('Gina Cruz', $withdrawal->party_name);
        $this->assertSame('0917XXX4567', $withdrawal->masked_account_number);
        $this->assertSame($this->client->client_id, (int) $withdrawal->client_id);
    }

    public function test_store_sets_the_requested_credit_aside(): void
    {
        $this->creditOnAccount(300);

        $this->ask(['amount' => 200]);

        $this->assertSame(100.0, $this->credit());
    }

    public function test_store_withdraws_all_the_credit_when_no_amount_is_given(): void
    {
        $this->creditOnAccount(300);

        $this->ask(['amount' => null]);

        $this->assertSame('300.00', (string) Transaction::where('type', Transaction::TYPE_WITHDRAW)->firstOrFail()->amount);
        $this->assertSame(0.0, $this->credit());
    }

    public function test_there_is_no_credit_on_this_account_to_withdraw(): void
    {
        $this->assertFailsWith('There is no credit on this account to withdraw.', fn() => $this->ask(), 422);
        $this->assertSame(0, Transaction::count());
    }

    public function test_a_withdrawal_is_already_awaiting_a_decision(): void
    {
        $this->creditOnAccount(300);
        $this->ask(['amount' => 100]);

        $this->assertFailsWith('A withdrawal is already awaiting a decision.', fn() => $this->ask(['amount' => 100]), 422);
        $this->assertSame(1, Transaction::where('type', Transaction::TYPE_WITHDRAW)->count());
    }

    public function test_store_rejects_an_amount_above_the_credit(): void
    {
        $this->creditOnAccount(300);

        $this->assertFailsWith('The amount must be between 0 and 300.', fn() => $this->ask(['amount' => 500]), 422);
        $this->assertSame(300.0, $this->credit());
    }

    public function test_store_refuses_a_guardian_without_access(): void
    {
        $this->creditOnAccount(300);
        $this->client->patientAccess()->update(['have_access' => false]);

        $this->assertFailsWith('You do not have access to this patient.', fn() => $this->ask(), 403);
        $this->assertSame(300.0, $this->credit());
    }

    public function test_store_needs_the_payout_account_details(): void
    {
        $this->creditOnAccount(300);

        $this->expectException(ValidationException::class);

        $this->ask(['account_details' => null]);
    }

    public function test_store_alerts_the_cashiers_of_the_branch(): void
    {
        $this->creditOnAccount(300);
        $manager = $this->staff([PermissionAction::Read->value, PermissionAction::Export->value], 'branch_manager');

        $this->ask(['amount' => 200]);

        $alert = Notification::where('to_user_id', $this->cashier->user_id)->firstOrFail();

        $this->assertSame('Billing', $alert->message_type);
        $this->assertSame('A withdrawal of 200.00 in credit was requested for Lola Cruz.', $alert->message);
        $this->assertSame(0, Notification::where('to_user_id', $manager->user_id)->count());
    }

    public function test_a_rejected_withdrawal_gives_the_credit_back(): void
    {
        $this->creditOnAccount(300);
        $this->ask(['amount' => 200]);

        Transaction::where('type', Transaction::TYPE_WITHDRAW)->firstOrFail()->update(['status' => Transaction::STATUS_REJECTED]);

        $this->assertSame(300.0, $this->credit());
    }

    public function test_index_lists_the_requests_and_the_credit_left(): void
    {
        $this->creditOnAccount(300);
        $this->ask(['amount' => 200]);

        $result = $this->controller->index($this->makeRequest('POST', ['patient_id' => $this->patient->patient_id], $this->guardian));

        $this->assertCount(1, $result['requests']);
        $this->assertTrue($result['has_open_request']);
        $this->assertSame(100.0, (float) $result['available_credit']);
    }

    public function test_index_has_no_open_request_before_anything_is_asked(): void
    {
        $this->creditOnAccount(300);

        $result = $this->controller->index($this->makeRequest('POST', ['patient_id' => $this->patient->patient_id], $this->guardian));

        $this->assertSame([], $result['requests']);
        $this->assertFalse($result['has_open_request']);
        $this->assertSame(300.0, (float) $result['available_credit']);
    }

    public function test_index_refuses_a_guardian_without_access(): void
    {
        $this->client->patientAccess()->update(['have_access' => false]);

        $this->assertFailsWith('You do not have access to this patient.', fn() => $this->controller->index(
            $this->makeRequest('POST', ['patient_id' => $this->patient->patient_id], $this->guardian)
        ), 403);
    }

    public function test_withdrawal_recorded(): void
    {
        $this->creditOnAccount(300);

        $result = $this->issue(['amount' => 100]);

        $this->assertSame('Withdrawal recorded.', $result['message']);

        $withdrawal = Transaction::where('type', Transaction::TYPE_WITHDRAW)->firstOrFail();

        $this->assertSame(Transaction::STATUS_COMPLETED, $withdrawal->status);
        $this->assertSame('CASH', $withdrawal->method);
        $this->assertNull($withdrawal->client_id);
        $this->assertSame('100.00', (string) $withdrawal->amount);
        $this->assertSame(200.0, $this->credit());
    }

    public function test_issue_rejects_a_payout_when_there_is_no_credit(): void
    {
        $this->assertFailsWith('There is no credit on this account to withdraw.', fn() => $this->issue(), 422);
    }

    public function test_issue_rejects_an_amount_above_the_credit(): void
    {
        $this->creditOnAccount(300);

        $this->assertFailsWith('The amount must be between 0 and 300.', fn() => $this->issue(['amount' => 400]), 422);
    }

    public function test_issue_requires_the_billing_create_permission(): void
    {
        $this->creditOnAccount(300);
        $viewer = $this->staff([PermissionAction::Read->value, PermissionAction::Export->value], 'branch_manager');

        $this->assertFailsWith('Insufficient permissions', fn() => $this->issue([], $viewer), 403);
        $this->assertSame(0, Transaction::where('type', Transaction::TYPE_WITHDRAW)->count());
    }

    public function test_issue_needs_a_known_patient(): void
    {
        $this->creditOnAccount(300);

        $this->expectException(ValidationException::class);

        $this->issue(['p_uuid' => '00000000-0000-0000-0000-000000000000']);
    }
}

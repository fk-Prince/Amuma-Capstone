<?php

namespace Tests\Unit\Billing;

use App\Enums\PermissionAction;
use App\Http\Controllers\InvoiceController;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Transaction;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use UsesTempDatabase;
    use BillingFixtures;

    private InvoiceController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBilling();
        $this->controller = $this->app->make(InvoiceController::class);
    }

    private function pay(array $overrides = [], $user = null): array
    {
        return $this->controller->store($this->makeRequest('POST', array_merge([
            'branch_uuid' => $this->branchUuid,
            'mode' => 'patient',
            'p_uuid' => $this->patient->uuid,
            'cash' => 1000,
            'payment_method' => 'CASH',
            'payor_name' => 'Gina Cruz',
        ], $overrides), $user));
    }

    private function act(string $type, array $data = [], $user = null)
    {
        return $this->controller->action($this->makeRequest('POST', array_merge([
            'branch_uuid' => $this->branchUuid,
            'type' => $type,
        ], $data), $user));
    }

    private function requestedWithdrawal(float $amount = 200): Transaction
    {
        return Transaction::create([
            'branch_id' => $this->branchId,
            'patient_id' => $this->patient->patient_id,
            'amount' => $amount,
            'type' => Transaction::TYPE_WITHDRAW,
            'direction' => Transaction::DIRECTION_DEBIT,
            'status' => Transaction::STATUS_REQUESTED,
            'method' => 'GCash',
        ]);
    }

    public function test_patient_payment_has_been_processed_successfully(): void
    {
        $invoice = $this->invoice(1000);

        $result = $this->pay(['cash' => 1000]);

        $this->assertSame('Patient payment has been processed successfully.', $result['message']);
        $this->assertSame(0.0, (float) $result['change']);
        $this->assertSame(0.0, (float) $result['remaining_balance']);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);

        $transaction = Transaction::firstOrFail();

        $this->assertSame(Transaction::TYPE_PAYMENT, $transaction->type);
        $this->assertSame(Transaction::DIRECTION_CREDIT, $transaction->direction);
        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->status);
        $this->assertSame('1000.00', (string) $transaction->amount);
        $this->assertSame('CASH', $transaction->method);
    }

    public function test_store_records_the_cashier_on_the_receipt(): void
    {
        $this->invoice(1000);

        $this->pay();

        $this->assertSame($this->cashier->user_id, (int) Payment::firstOrFail()->issued_by);
    }

    public function test_store_marks_an_invoice_partially_paid(): void
    {
        $invoice = $this->invoice(1000);

        $result = $this->pay(['cash' => 400]);

        $this->assertSame(600.0, (float) $result['remaining_balance']);
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
        $this->assertSame(600.0, $invoice->fresh()->balance_due);
    }

    public function test_store_returns_the_change_and_records_only_what_was_kept(): void
    {
        $invoice = $this->invoice(1000);

        $result = $this->pay(['cash' => 1500]);

        $this->assertSame(500.0, (float) $result['change']);
        $this->assertSame('1000.00', (string) Transaction::firstOrFail()->amount);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_store_pays_the_oldest_invoice_first(): void
    {
        $older = $this->invoice(300, 'First bill');
        $newer = $this->invoice(500, 'Second bill');

        $this->pay(['cash' => 400]);

        $this->assertSame(Invoice::STATUS_PAID, $older->fresh()->status);
        $this->assertSame(Invoice::STATUS_PARTIAL, $newer->fresh()->status);
        $this->assertSame(400.0, $newer->fresh()->balance_due);
    }

    public function test_store_splits_the_payment_by_the_amounts_given(): void
    {
        $first = $this->invoice(300, 'First bill');
        $second = $this->invoice(500, 'Second bill');

        $this->pay(['cash' => 300, 'allocations' => [$second->invoice_code => 300]]);

        $this->assertSame(Invoice::STATUS_PENDING, $first->fresh()->status);
        $this->assertSame(200.0, $second->fresh()->balance_due);
    }

    public function test_store_pays_a_single_invoice_by_its_code(): void
    {
        $invoice = $this->invoice(700);

        $result = $this->pay(['mode' => 'invoice', 'invoice_code' => $invoice->invoice_code, 'cash' => 700]);

        $this->assertSame('Invoice payment has been processed successfully.', $result['message']);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_enter_a_cash_amount_greater_than_0(): void
    {
        $this->invoice(1000);

        $this->assertFailsWith('Enter a cash amount greater than 0.', fn() => $this->pay(['cash' => 0]), 422);
        $this->assertSame(0, Payment::count());
    }

    public function test_store_rejects_paying_with_credit_and_cash_together(): void
    {
        $this->invoice(1000);

        $this->assertFailsWith('Pay with credit or with cash, not both.', fn() => $this->pay(['cash' => 100, 'use_credit' => true]), 422);
    }

    public function test_store_rejects_a_payment_when_nothing_is_owed(): void
    {
        $this->assertFailsWith('There is no outstanding balance to pay.', fn() => $this->pay(['cash' => 500]), 422);
        $this->assertSame(0, Payment::count());
    }

    public function test_store_pays_from_the_credit_on_the_account(): void
    {
        $this->creditOnAccount(300);
        $invoice = $this->invoice(500);

        $result = $this->pay(['cash' => 0, 'use_credit' => true]);

        $this->assertSame('Credit applied to the outstanding balance.', $result['message']);
        $this->assertSame(300.0, (float) $result['credit_applied']);
        $this->assertSame(200.0, (float) $result['remaining_balance']);
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
        $this->assertSame(Payment::METHOD_CREDIT, Transaction::where('method', Payment::METHOD_CREDIT)->firstOrFail()->method);
        $this->assertSame(0, Refund::whereNull('transaction_id')->count());
    }

    public function test_store_requires_the_billing_create_permission(): void
    {
        $this->invoice(1000);
        $viewer = $this->staff([PermissionAction::Read->value], 'branch_manager');

        $this->assertFailsWith('Insufficient permissions', fn() => $this->pay([], $viewer), 403);
        $this->assertSame(0, Payment::count());
    }

    public function test_receipts_lists_the_payments_of_the_branch(): void
    {
        $this->invoice(1000);
        $this->pay();

        $body = $this->controller->receipts($this->makeRequest('GET', ['branch_uuid' => $this->branchUuid]))
            ->response()
            ->getData(true);

        $this->assertCount(1, $body['data']);
        $this->assertStringStartsWith('TXN', $body['data'][0]['payment_code']);
        $this->assertSame('counter', $body['data'][0]['channel']);
    }

    public function test_receipts_can_be_viewed_with_the_read_permission_only(): void
    {
        $this->invoice(1000);
        $this->pay();
        $viewer = $this->staff([PermissionAction::Read->value, PermissionAction::Export->value], 'branch_manager');

        $body = $this->controller->receipts($this->makeRequest('GET', ['branch_uuid' => $this->branchUuid], $viewer))
            ->response()
            ->getData(true);

        $this->assertCount(1, $body['data']);
    }

    public function test_invoice_adjusted_successfully(): void
    {
        $invoice = $this->invoice(1000);

        $result = $this->act('adjust', ['invoice_code' => $invoice->invoice_code, 'amount' => -200, 'reason' => 'Discount']);

        $this->assertSame('Invoice adjusted successfully.', $result['message']);

        $adjustment = InvoiceAdjustment::firstOrFail();

        $this->assertSame(InvoiceAdjustment::TYPE_CORRECTION, $adjustment->type);
        $this->assertSame('-200.00', (string) $adjustment->amount);
        $this->assertSame(800.0, $invoice->fresh()->adjusted_total);
    }

    public function test_a_reason_is_required_to_adjust_an_invoice(): void
    {
        $invoice = $this->invoice(1000);

        $this->assertFailsWith('A reason is required to adjust an invoice.', fn() => $this->act('adjust', [
            'invoice_code' => $invoice->invoice_code,
            'amount' => -200,
            'reason' => '  ',
        ]), 422);
        $this->assertSame(0, InvoiceAdjustment::count());
    }

    public function test_adjust_rejects_a_zero_amount(): void
    {
        $invoice = $this->invoice(1000);

        $this->assertFailsWith('Enter a non-zero adjustment amount.', fn() => $this->act('adjust', [
            'invoice_code' => $invoice->invoice_code,
            'amount' => 0,
            'reason' => 'Discount',
        ]), 422);
    }

    public function test_the_deduction_cant_exceed_the_invoices_billed_total(): void
    {
        $invoice = $this->invoice(1000);

        $this->assertFailsWith("The deduction can't exceed the invoice's billed total.", fn() => $this->act('adjust', [
            'invoice_code' => $invoice->invoice_code,
            'amount' => -1500,
            'reason' => 'Discount',
        ]), 422);
    }

    public function test_adjust_raises_the_balance_when_the_bill_goes_up(): void
    {
        $invoice = $this->invoice(1000);

        $this->act('adjust', ['invoice_code' => $invoice->invoice_code, 'amount' => 250, 'reason' => 'Extra care']);

        $this->assertSame(1250.0, $invoice->fresh()->adjusted_total);
        $this->assertSame(1250.0, $invoice->fresh()->balance_due);
    }

    public function test_adjusting_a_paid_invoice_down_turns_the_difference_into_credit(): void
    {
        $invoice = $this->invoice(1000);
        $this->settle($invoice);

        $this->act('adjust', ['invoice_code' => $invoice->invoice_code, 'amount' => -300, 'reason' => 'Rate lowered']);

        $credit = Refund::firstOrFail();

        $this->assertSame('300.00', (string) $credit->amount);
        $this->assertNull($credit->transaction_id);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_adjusting_an_unpaid_invoice_down_creates_no_credit(): void
    {
        $invoice = $this->invoice(1000);

        $this->act('adjust', ['invoice_code' => $invoice->invoice_code, 'amount' => -300, 'reason' => 'Discount']);

        $this->assertSame(0, Refund::count());
    }

    public function test_adjust_refuses_an_unknown_invoice(): void
    {
        $this->assertFailsWith('Invoice not found.', fn() => $this->act('adjust', [
            'invoice_code' => 'INV99-000001',
            'amount' => -100,
            'reason' => 'Discount',
        ]), 404);
    }

    public function test_adjust_requires_the_billing_update_permission(): void
    {
        $invoice = $this->invoice(1000);
        $clerk = $this->staff([PermissionAction::Read->value, PermissionAction::Create->value]);

        $this->assertFailsWith('Insufficient permissions', fn() => $this->act('adjust', [
            'invoice_code' => $invoice->invoice_code,
            'amount' => -100,
            'reason' => 'Discount',
        ], $clerk), 403);
        $this->assertSame(0, InvoiceAdjustment::count());
    }

    public function test_invoice_voided_successfully(): void
    {
        $invoice = $this->invoice(1000);

        $result = $this->act('void', ['invoice_code' => $invoice->invoice_code, 'reason' => 'Issued by mistake']);

        $invoice->refresh();

        $this->assertSame('Invoice voided successfully.', $result['message']);
        $this->assertSame(Invoice::STATUS_VOID, $invoice->status);
        $this->assertSame($this->cashier->user_id, (int) $invoice->voided_by);
        $this->assertSame('Issued by mistake', $invoice->void_reason);
        $this->assertSame(0.0, $invoice->balance_due);
        $this->assertSame(InvoiceAdjustment::TYPE_VOID, InvoiceAdjustment::firstOrFail()->type);
    }

    public function test_void_requires_a_reason(): void
    {
        $invoice = $this->invoice(1000);

        $this->assertFailsWith('A reason is required to void an invoice.', fn() => $this->act('void', [
            'invoice_code' => $invoice->invoice_code,
        ]), 422);
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_a_paid_invoice_can_no_longer_be_voided_issue_a_refund_or_credit_instead(): void
    {
        $invoice = $this->invoice(1000);
        $this->settle($invoice);

        $this->assertFailsWith(
            'A paid invoice can no longer be voided. Issue a refund or credit instead.',
            fn() => $this->act('void', ['invoice_code' => $invoice->invoice_code, 'reason' => 'Mistake']),
            422
        );
    }

    public function test_void_refuses_an_invoice_that_is_already_void(): void
    {
        $invoice = $this->invoice(1000);
        $this->act('void', ['invoice_code' => $invoice->invoice_code, 'reason' => 'Mistake']);

        $this->assertFailsWith('This invoice is already void.', fn() => $this->act('void', [
            'invoice_code' => $invoice->invoice_code,
            'reason' => 'Mistake again',
        ]), 422);
    }

    public function test_a_void_invoice_cannot_be_adjusted(): void
    {
        $invoice = $this->invoice(1000);
        $this->act('void', ['invoice_code' => $invoice->invoice_code, 'reason' => 'Mistake']);

        $this->assertFailsWith('This invoice is void or written off and can no longer be adjusted.', fn() => $this->act('adjust', [
            'invoice_code' => $invoice->invoice_code,
            'amount' => -100,
            'reason' => 'Discount',
        ]), 422);
    }

    public function test_void_requires_the_billing_update_permission(): void
    {
        $invoice = $this->invoice(1000);
        $clerk = $this->staff([PermissionAction::Read->value, PermissionAction::Create->value]);

        $this->assertFailsWith('Insufficient permissions', fn() => $this->act('void', [
            'invoice_code' => $invoice->invoice_code,
            'reason' => 'Mistake',
        ], $clerk), 403);
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_invoice_written_off_successfully(): void
    {
        $invoice = $this->invoice(1000);
        $this->pay(['cash' => 400]);

        $result = $this->act('write-off', ['invoice_code' => $invoice->invoice_code, 'reason' => 'Cannot be collected']);

        $invoice->refresh();

        $this->assertSame('Invoice written off successfully.', $result['message']);
        $this->assertSame(Invoice::STATUS_WRITTEN_OFF, $invoice->status);
        $this->assertSame($this->cashier->user_id, (int) $invoice->written_off_by);
        $this->assertSame(0.0, $invoice->balance_due);
        $this->assertSame(0, Refund::count());
        $this->assertSame(0, InvoiceAdjustment::count());
    }

    public function test_a_reason_is_required_to_write_off_an_invoice(): void
    {
        $invoice = $this->invoice(1000);

        $this->assertFailsWith('A reason is required to write off an invoice.', fn() => $this->act('write-off', [
            'invoice_code' => $invoice->invoice_code,
        ]), 422);
    }

    public function test_write_off_refuses_a_void_invoice(): void
    {
        $invoice = $this->invoice(1000);
        $this->act('void', ['invoice_code' => $invoice->invoice_code, 'reason' => 'Mistake']);

        $this->assertFailsWith('A voided invoice cannot be written off.', fn() => $this->act('write-off', [
            'invoice_code' => $invoice->invoice_code,
            'reason' => 'Cannot be collected',
        ]), 422);
    }

    public function test_the_withdrawal_has_been_completed_successfully(): void
    {
        $withdrawal = $this->requestedWithdrawal();

        $result = $this->act('refund', ['refund_id' => $withdrawal->transaction_id, 'p_uuid' => $this->patient->uuid]);

        $this->assertSame('The withdrawal has been completed successfully.', $result['message']);
        $this->assertSame(Transaction::STATUS_COMPLETED, $withdrawal->fresh()->status);
    }

    public function test_the_withdrawal_request_has_been_declined(): void
    {
        $withdrawal = $this->requestedWithdrawal();

        $result = $this->act('decline-refund', [
            'refund_id' => $withdrawal->transaction_id,
            'p_uuid' => $this->patient->uuid,
            'reason' => 'Account details do not match',
        ]);

        $withdrawal->refresh();

        $this->assertSame('The withdrawal request has been declined.', $result['message']);
        $this->assertSame(Transaction::STATUS_REJECTED, $withdrawal->status);
        $this->assertSame('Account details do not match', $withdrawal->declined_reason);
    }

    public function test_a_reason_is_required_to_decline_a_withdrawal(): void
    {
        $withdrawal = $this->requestedWithdrawal();

        $this->assertFailsWith('A reason is required to decline a withdrawal.', fn() => $this->act('decline-refund', [
            'refund_id' => $withdrawal->transaction_id,
            'p_uuid' => $this->patient->uuid,
        ]), 422);
        $this->assertSame(Transaction::STATUS_REQUESTED, $withdrawal->fresh()->status);
    }

    public function test_this_withdrawal_has_already_been_settled(): void
    {
        $withdrawal = $this->requestedWithdrawal();
        $this->act('refund', ['refund_id' => $withdrawal->transaction_id, 'p_uuid' => $this->patient->uuid]);

        $this->assertFailsWith('This withdrawal has already been settled.', fn() => $this->act('refund', [
            'refund_id' => $withdrawal->transaction_id,
            'p_uuid' => $this->patient->uuid,
        ]), 422);
    }

    public function test_an_unknown_withdrawal_is_refused(): void
    {
        $this->assertFailsWith('Withdrawal not found.', fn() => $this->act('refund', [
            'refund_id' => 9999,
            'p_uuid' => $this->patient->uuid,
        ]), 404);
    }

    public function test_settling_a_withdrawal_requires_the_billing_create_permission(): void
    {
        $withdrawal = $this->requestedWithdrawal();
        $viewer = $this->staff([PermissionAction::Read->value, PermissionAction::Export->value], 'branch_manager');

        $this->assertFailsWith('Insufficient permissions', fn() => $this->act('refund', [
            'refund_id' => $withdrawal->transaction_id,
            'p_uuid' => $this->patient->uuid,
        ], $viewer), 403);
        $this->assertSame(Transaction::STATUS_REQUESTED, $withdrawal->fresh()->status);
    }
}

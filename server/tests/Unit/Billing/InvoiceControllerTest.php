<?php

namespace Tests\Unit\Billing;

use App\Http\Controllers\InvoiceController;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\Payment;
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

    public function test_enter_a_cash_amount_greater_than_0(): void
    {
        $this->invoice(1000);

        $this->assertFailsWith('Enter a cash amount greater than 0.', fn() => $this->pay(['cash' => 0]), 422);
        $this->assertSame(0, Payment::count());
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
}

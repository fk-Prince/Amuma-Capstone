<?php

namespace Tests\Unit\Billing;

use App\Http\Controllers\PaymentController;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use UsesTempDatabase;
    use BillingFixtures;

    private PaymentController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBilling();
        $this->controller = $this->app->make(PaymentController::class);

        config(['services.xendit.secret_key' => 'xnd_test_key']);
    }

    private function pay(array $overrides = []): array
    {
        return $this->controller->store($this->makeRequest('POST', array_merge([
            'patient_id' => $this->patient->patient_id,
            'amount' => 0,
        ], $overrides), $this->guardian));
    }

    private function cardPayment(float $amount, array $overrides = []): array
    {
        return $this->pay(array_merge([
            'amount' => $amount,
            'payment_method' => 'CREDIT-CARD',
            'token_id' => 'tok_123',
            'authentication_id' => 'auth_123',
        ], $overrides));
    }

    private function gatewayAccepts(): void
    {
        Http::fake([
            'api.xendit.co/*' => Http::response([
                'id' => 'chg_123',
                'status' => 'CAPTURED',
                'masked_card_number' => '400000XXXXXX0002',
            ], 200),
        ]);
    }

    public function test_store_pays_from_the_credit_on_the_account(): void
    {
        $this->creditOnAccount(300);
        $invoice = $this->invoice(500);

        $result = $this->pay(['credit_amount' => 300]);

        $this->assertTrue($result['success']);
        $this->assertSame('Credit applied to the outstanding balance.', $result['message']);
        $this->assertSame(300.0, (float) $result['credit_applied']);
        $this->assertSame(200.0, (float) $result['remaining_balance']);
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
        $this->assertNotNull($result['receipt']);
    }

    public function test_only_100_in_credit_is_available_on_this_account(): void
    {
        $this->creditOnAccount(100);
        $this->invoice(500);

        $this->assertFailsWith('Only 100.00 in credit is available on this account.', fn() => $this->pay(['credit_amount' => 300]), 422);
    }

    public function test_store_rejects_paying_with_credit_and_card_together(): void
    {
        $this->creditOnAccount(300);
        $this->invoice(500);

        $this->assertFailsWith('Pay with credit or with a card, not both.', fn() => $this->pay([
            'amount' => 100,
            'credit_amount' => 100,
        ]), 422);
    }

    public function test_store_requires_an_amount(): void
    {
        $this->invoice(500);

        $this->assertFailsWith('Enter an amount greater than 0.', fn() => $this->pay(['amount' => 0]), 422);
    }

    public function test_store_requires_a_patient(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller->store($this->makeRequest('POST', ['amount' => 100], $this->guardian));
    }

    public function test_you_do_not_have_access_to_this_patient(): void
    {
        $this->invoice(500);
        $this->client->patientAccess()->update(['have_access' => false]);

        $this->assertFailsWith('You do not have access to this patient.', fn() => $this->cardPayment(500), 403);
        $this->assertSame(0, Payment::count());
    }

    public function test_payment_recorded_successfully(): void
    {
        $this->gatewayAccepts();
        $invoice = $this->invoice(500);

        $result = $this->cardPayment(500);

        $this->assertTrue($result['success']);
        $this->assertSame('Payment recorded successfully.', $result['message']);
        $this->assertSame(0.0, (float) $result['remaining_balance']);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);

        Http::assertSent(fn($request) => str_contains($request->url(), 'credit_card_charges')
            && (float) $request['amount'] === 500.0
            && $request['token_id'] === 'tok_123');

        $transaction = Transaction::firstOrFail();

        $this->assertSame('CREDIT-CARD', $transaction->method);
        $this->assertSame('chg_123', $transaction->transaction_reference_id);
        $this->assertSame('400000XXXXXX0002', $transaction->masked_account_number);
        $this->assertSame($this->client->client_id, (int) $transaction->client_id);
        $this->assertSame('Gina Cruz', $transaction->party_name);
    }

    public function test_store_leaves_no_cashier_on_an_online_receipt(): void
    {
        $this->gatewayAccepts();
        $this->invoice(500);

        $result = $this->cardPayment(500);

        $this->assertNull(Payment::firstOrFail()->issued_by);
        $this->assertSame('portal', $result['receipt']->resolve()['channel']);
    }

    public function test_store_pays_a_part_of_the_balance_by_card(): void
    {
        $this->gatewayAccepts();
        $invoice = $this->invoice(500);

        $result = $this->cardPayment(200);

        $this->assertSame(300.0, (float) $result['remaining_balance']);
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
    }

    public function test_store_pays_the_selected_invoices_only(): void
    {
        $this->gatewayAccepts();
        $first = $this->invoice(300, 'First bill');
        $second = $this->invoice(500, 'Second bill');

        $this->cardPayment(500, ['invoice_codes' => [$second->invoice_code]]);

        $this->assertSame(Invoice::STATUS_PENDING, $first->fresh()->status);
        $this->assertSame(Invoice::STATUS_PAID, $second->fresh()->status);
    }

    public function test_payment_failed(): void
    {
        Http::fake(['api.xendit.co/*' => Http::response(['error_code' => 'CARD_DECLINED'], 400)]);
        $invoice = $this->invoice(500);

        $this->assertFailsWith('Payment failed.', fn() => $this->cardPayment(500), 502);
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Transaction::count());
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_store_needs_the_card_token_and_authentication(): void
    {
        Http::fake();
        $this->invoice(500);

        $this->assertFailsWith('Card details are required to pay online.', fn() => $this->pay([
            'amount' => 500,
            'payment_method' => 'CREDIT-CARD',
        ]), 422);

        Http::assertNothingSent();
    }

    public function test_store_refuses_a_payment_when_nothing_is_owed(): void
    {
        $this->creditOnAccount(300);

        $this->assertFailsWith('There is no outstanding balance to pay.', fn() => $this->pay(['credit_amount' => 100]), 422);
        $this->assertSame(0.0, (float) Transaction::where('method', Payment::METHOD_CREDIT)->sum('amount'));
    }

    public function test_receipt_shows_a_payment_of_a_resident_the_guardian_can_access(): void
    {
        $payment = $this->settle($this->invoice(500));

        $resource = $this->controller->receipt($this->makeRequest('POST', ['payment_code' => $payment->payment_code], $this->guardian));

        $this->assertSame($payment->payment_code, $resource->resolve()['payment_code']);
    }

    public function test_receipt_is_refused_when_the_guardian_has_no_access(): void
    {
        $payment = $this->settle($this->invoice(500));
        $this->client->patientAccess()->update(['have_access' => false]);

        $this->assertFailsWith('You do not have access to this receipt.', fn() => $this->controller->receipt(
            $this->makeRequest('POST', ['payment_code' => $payment->payment_code], $this->guardian)
        ), 403);
    }

    public function test_receipt_is_refused_for_an_unknown_code(): void
    {
        $this->assertFailsWith('Receipt not found.', fn() => $this->controller->receipt(
            $this->makeRequest('POST', ['payment_code' => 'TXN01-999999'], $this->guardian)
        ), 404);
    }
}

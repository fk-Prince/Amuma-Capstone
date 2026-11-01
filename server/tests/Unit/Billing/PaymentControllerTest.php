<?php

namespace Tests\Unit\Billing;

use App\Http\Controllers\PaymentController;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
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

    public function test_payment_failed(): void
    {
        Http::fake(['api.xendit.co/*' => Http::response(['error_code' => 'CARD_DECLINED'], 400)]);
        $invoice = $this->invoice(500);

        $this->assertFailsWith('Payment failed.', fn() => $this->cardPayment(500), 502);
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Transaction::count());
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }
}

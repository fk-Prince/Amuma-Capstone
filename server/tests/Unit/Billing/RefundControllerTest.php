<?php

namespace Tests\Unit\Billing;

use App\Http\Controllers\RefundController;
use App\Models\Transaction;
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
}

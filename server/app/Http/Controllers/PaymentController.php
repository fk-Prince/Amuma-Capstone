<?php

namespace App\Http\Controllers;

use App\Guard\AuthGuard;
use App\Service\PaymentService;
use Exception;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function store(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());

        if (!$user->client) {
            throw new Exception('Only family/client accounts can submit a payment here.', 403);
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'token_id' => ['required', 'string'],
            'authentication_id' => ['required', 'string'],
            'invoice_codes' => ['sometimes', 'array'],
            'invoice_codes.*' => ['string'],
        ]);

        return $this->paymentService->payBalance($user->client, $validated);
    }

    public function receipt(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());

        if (!$user->client) {
            throw new Exception('Only family/client accounts can view a receipt here.', 403);
        }

        $validated = $request->validate([
            'receipt_no' => ['required', 'string', 'max:50'],
        ]);

        return $this->paymentService->receipt($user->client, $validated);
    }
}

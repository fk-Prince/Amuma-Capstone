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

        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'credit_amount' => ['sometimes', 'numeric', 'min:0'],
            'token_id' => ['nullable', 'string'],
            'authentication_id' => ['nullable', 'string'],
            'invoice_codes' => ['sometimes', 'array'],
            'invoice_codes.*' => ['string'],
        ]);

        return $this->paymentService->payBalance($user->client, $validated);
    }

    public function receipt(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());

        $validated = $request->validate([
            'payment_code' => ['required', 'string', 'max:50'],
        ]);

        return $this->paymentService->receipt($user->client, $validated);
    }
}

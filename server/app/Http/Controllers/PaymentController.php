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
            'method' => ['required', 'string', 'max:100'],
            'account_details' => ['required', 'string', 'max:255'],
        ]);

        return $this->paymentService->payBalance($user->client, $validated);
    }
}

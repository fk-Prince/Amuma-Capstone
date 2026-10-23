<?php

namespace App\Http\Controllers;

use App\Service\Sandbox\SandboxService;
use Illuminate\Http\Request;

class SandboxController extends Controller
{
    public function __construct(private SandboxService $sandboxService) {}

    public function provision(Request $request)
    {
        $secret = (string) config('services.sandbox.secret');

        abort_unless(
            config('app.sandbox') && $secret !== '' && hash_equals($secret, (string) $request->header('X-Sandbox-Secret')),
            403
        );

        $payload = $request->validate([
            'owner.email' => ['required', 'email'],
            'owner.password' => ['nullable', 'string'],
            'owner.provider' => ['nullable', 'string'],
            'owner.first_name' => ['nullable', 'string'],
            'owner.last_name' => ['nullable', 'string'],
            'owner.avatar' => ['nullable', 'string'],
            'agency.uuid' => ['required', 'uuid'],
            'agency.name' => ['required', 'string'],
            'agency.description' => ['nullable', 'string'],
            'agency.email' => ['nullable', 'string'],
            'agency.image' => ['nullable', 'string'],
            'agency.location' => ['nullable', 'array'],
            'branch.uuid' => ['required', 'uuid'],
            'branch.name' => ['required', 'string'],
            'branch.description' => ['nullable', 'string'],
            'branch.email' => ['nullable', 'string'],
            'branch.contact_number' => ['nullable', 'string'],
            'branch.image' => ['nullable', 'string'],
            'branch.settings' => ['nullable', 'array'],
            'branch.location' => ['nullable', 'array'],
            'plan.plan_code' => ['required', 'string'],
            'plan.type' => ['required', 'string'],
        ]);

        dispatch(fn() => $this->sandboxService->provision($payload))->afterResponse();

        return response()->json(['message' => 'Sandbox provisioning started.'], 202);
    }
}

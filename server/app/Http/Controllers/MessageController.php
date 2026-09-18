<?php

namespace App\Http\Controllers;

use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Service\MessageService;
use Exception;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private MessageService $messageService
    ) {}

    public function clientIndex(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());

        return $this->messageService->clientConversations($user->client);
    }

    public function clientContacts(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());

        return $this->messageService->clientContacts($user->client);
    }

    public function openContact(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());

        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
            'employee_id' => ['required', 'integer'],
        ]);

        return $this->messageService->openContact($user, $validated);
    }

    public function branchIndex(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);

        return $this->messageService->branchConversations($request->all(), $user);
    }

    public function staffIndex(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);

        return $this->messageService->staffConversations($user, $request->all());
    }

    public function colleagues(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);

        return $this->messageService->colleagues($user, $request->all());
    }

    public function openWithStaff(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
        ]);

        $validated['branch_uuid'] = $request->branch_uuid;

        return $this->messageService->openWithStaff($user, $validated);
    }

    public function recipients(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);

        return $this->messageService->recipients($user, $request->all());
    }

    public function openWith(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);

        $validated = $request->validate([
            'client_id' => ['required', 'integer'],
        ]);

        $validated['branch_id'] = $request->branch_id;

        return $this->messageService->openWith($user, $validated);
    }

    public function thread(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());

        $validated = $request->validate([
            'conversation_id' => ['required', 'integer'],
        ]);

        return $this->messageService->thread($user, $validated, $request->boolean('as_staff'));
    }

    public function send(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'integer'],
            'patient_id' => ['nullable', 'integer'],
            'body' => ['nullable', 'required_without:attachment', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:10240'],
        ]);

        $validated['attachment'] = $request->file('attachment');

        return $this->messageService->send($user, $validated, $request->boolean('as_staff'));
    }
}

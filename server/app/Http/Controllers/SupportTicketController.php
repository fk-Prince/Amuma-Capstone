<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Service\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    private const ATTACHMENT_RULES = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

    public function __construct(private SupportTicketService $supportTicketService) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'branch_uuid' => ['nullable', 'uuid'],
            'status' => ['nullable', Rule::in(SupportTicket::STATUSES)],
            'category' => ['nullable', Rule::in(SupportTicket::CATEGORIES)],
            'source' => ['nullable', Rule::in(['branch', 'public'])],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->supportTicketService->list($validated, $request->user());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_uuid' => ['required', 'uuid'],
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => self::ATTACHMENT_RULES,
        ]);

        return $this->supportTicketService->create($validated, $request->user());
    }

    public function storePublic(Request $request)
    {
        $user = auth('sanctum')->user();

        $validated = $request->validate([
            'name' => [$user ? 'nullable' : 'required', 'string', 'max:150'],
            'email' => [$user ? 'nullable' : 'required', 'email', 'max:255'],
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => self::ATTACHMENT_RULES,
        ]);

        return $this->supportTicketService->createPublic($validated, $user);
    }

    public function show(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'branch_uuid' => ['nullable', 'uuid'],
        ]);

        return $this->supportTicketService->show($uuid, $validated, $request->user());
    }

    public function reply(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'branch_uuid' => ['nullable', 'uuid'],
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => self::ATTACHMENT_RULES,
        ]);

        return $this->supportTicketService->reply($uuid, $validated, $request->user());
    }

    public function updateStatus(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'branch_uuid' => ['nullable', 'uuid'],
            'status' => ['required', Rule::in([SupportTicket::STATUS_OPEN, SupportTicket::STATUS_RESOLVED])],
        ]);

        return $this->supportTicketService->updateStatus($uuid, $validated, $request->user());
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inquiry\StoreInquiryRequest;
use App\Models\Inquiry;
use App\Service\InquiryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    public function __construct(private InquiryService $inquiryService) {}

    public function store(StoreInquiryRequest $request, string $uuid)
    {
        return $this->inquiryService->createInquiry(
            $uuid,
            $request->validated(),
            auth('sanctum')->user(),
        );
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'branch_uuid' => ['required', 'string'],
            'status' => ['nullable', Rule::in([Inquiry::STATUS_NEW, Inquiry::STATUS_READ, Inquiry::STATUS_RESOLVED])],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->inquiryService->listInquiries($validated, $request->user());
    }

    public function updateStatus(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'branch_uuid' => ['required', 'string'],
            'status' => ['required', Rule::in([Inquiry::STATUS_NEW, Inquiry::STATUS_READ, Inquiry::STATUS_RESOLVED])],
        ]);

        return $this->inquiryService->updateStatus($uuid, $validated, $request->user());
    }

    public function reply(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'branch_uuid' => ['required', 'string'],
            'reply' => ['required', 'string', 'max:5000'],
        ]);

        return $this->inquiryService->reply($uuid, $validated, $request->user());
    }
}

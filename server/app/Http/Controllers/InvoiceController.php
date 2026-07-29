<?php

namespace App\Http\Controllers;

use App\Service\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    private InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        return $this->invoiceService->listInvoice(
            $request->user(), 
            $request->input('per_page', 15)
        );
    }

    public function store(Request $request)
    {
        return $this->invoiceService->createInvoice($request->user(), $request->all());
    }

    public function show(Request $request, string $uuid)
    {
        return $this->invoiceService->getInvoice($request->user(), $uuid);
    }

    public function update(Request $request, string $uuid)
    {
        return $this->invoiceService->updateInvoice($request->user(), $uuid, $request->all());
    }

    public function destroy(Request $request, string $uuid)
    {
        $this->invoiceService->deleteInvoice($request->user(), $uuid);
        return response()->json(['message' => 'Deleted successfully'], 200);
    }
    
    public function restore(Request $request, string $uuid)
    {
        return $this->invoiceService->restoreInvoice($request->user(), $uuid);
    }
}
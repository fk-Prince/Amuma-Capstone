<?php

namespace App\Http\Controllers;

use App\Service\BookingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    private BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request)
    {
        return $this->bookingService->listBooking($request->user(), $request->all());
    }

    public function store(Request $request)
    {
        return $this->bookingService->createBooking($request->user(), $request->all());
    }

    public function action(Request $request)
    {
        return $this->bookingService->bookingAccepted($request->user(), $request->all());
    }
}

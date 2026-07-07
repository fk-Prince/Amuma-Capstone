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

    // public function index(Request $request)
    // {
    //     return $this->bookingService->listBooking(
    //         $request->user(),
    //         $request->input('per_page', 15)
    //     );
    // }

    public function store(Request $request)
    {
        return $this->bookingService->createBooking($request->user(), $request->all());
    }
}

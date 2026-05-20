<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected BookingService $service;

    public function __construct(BookingService $service)
    {
        $this->service = $service;
    }

    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();
        $booking = $this->service->create($data, $request->user()->id);

        return response()->json(['success'=>true,'data'=>new BookingResource($booking)],201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isProfessional()) {
            $bookings = Booking::where('professional_id', $user->id)->latest()->get();
        } else {
            $bookings = Booking::where('client_id', $user->id)->latest()->get();
        }

        return response()->json(['success'=>true,'data'=>BookingResource::collection($bookings)]);
    }

    public function show(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('view', $booking);
        return response()->json(['success'=>true,'data'=>new BookingResource($booking)]);
    }

    public function cancel(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('cancel', $booking);
        $booking->status = 'cancelled';
        $booking->save();

        return response()->json(['success'=>true,'message'=>'Reserva cancelada','data'=>new BookingResource($booking)]);
    }
}


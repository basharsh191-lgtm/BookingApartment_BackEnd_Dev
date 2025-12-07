<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\apartmentDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'apartment_id' => 'required|exists:apartment_details,apartment_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $apartment = apartmentDetail::find($request->apartment_id);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $availableStart = Carbon::parse($apartment->available_from);
        $availableEnd = Carbon::parse($apartment->available_to);

        if ($start < $availableStart || $end > $availableEnd) {
            return response()->json([
                'status' => false,
                'message' => 'مدة الحجز تتجاوز فترة توافر الشقة!'
            ], 400);
        }

        $days = $start->diffInDays($end) + 1;


        $dailyPrice = $apartment->price / 30;
        $totalPrice = $dailyPrice * $days;

        $booking = Booking::create([
            'apartment_id' =>  auth()->id(),
            'tenant_id' => auth()->id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending',
            'total_price' => $totalPrice,
        ]);

        $apartment->update([
            'start_date' => $end->copy()->addDay()
        ]);
        return response()->json([
            'status' => true,
            'message' => 'تم تقديم طلب الحجز بنجاح',
            'data' => $booking
        ], 201);
    }




}

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
            'apartment_id' => 'required|exists:apartment_details,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $apartment = apartmentDetail::find($request->apartment_id);

        if ($apartment->owner_id == auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكنك حجز شقتك الخاصة'
            ], 403);
        }

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $availableStart = Carbon::parse($apartment->available_from);
        $availableEnd = Carbon::parse($apartment->available_to);

        if ($start < $availableStart || $end > $availableEnd) {
            return response()->json([
                'status' => false,
                'message' => 'مدة الحجز تتجاوز فترة توافر الشقة'
            ], 400);
        }

        $overlap = Booking::where('apartment_id', $apartment->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            })
            ->exists();
        if ($overlap) {
            return response()->json([
                'status' => false,
                'message' => 'هناك حجز آخر متداخل مع هذه الفترة'
            ], 409);
        }
        // منع إرسال أكثر من طلب pending لنفس الشقة
        $hasPending = Booking::where('apartment_id', $apartment->id)
            ->where('tenant_id', auth()->id())
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'status' => false,
                'message' => 'لديك طلب سابق قيد الانتظار لهذه الشقة'
            ], 409);
        }

        // حساب السعر
        $days = $start->diffInDays($end) + 1;
        $dailyPrice = $apartment->price / 30;
        $totalPrice = $dailyPrice * $days;

        // إنشاء حجز بنتظار موافقة المالك فقط
        $booking = Booking::create([
            'apartment_id' => $apartment->id,
            'tenant_id' => auth()->id(),
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'pending',
            'total_price' => $totalPrice,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم تقديم طلب الحجز وبانتظار الموافقة',
            'data' => $booking
        ], 201);
    }
}


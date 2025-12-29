<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ApartmentDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'apartment_id' => 'required|exists:apartment_details,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        //  جلب الفترات المعروضة مع الشقة
        $apartment = ApartmentDetail::with('displayPeriods')->find($request->apartment_id);

        if ($apartment->scheduled_for_deletion) {
            return response()->json([
                'status' => false,
                'message' => 'هذه الشقة غير متاحة للحجز حالياً'
            ], 400);
        }

        if ($apartment->owner_id == Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكنك حجز شقتك الخاصة'
            ], 403);
        }

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $availableStart = Carbon::parse($apartment->available_from);
        $availableEnd = $apartment->available_to
            ? Carbon::parse($apartment->available_to)
            : Carbon::create(2100, 1, 1);

        if ($start < $availableStart || $end > $availableEnd) {
            return response()->json([
                'status' => false,
                'message' => 'مدة الحجز تتجاوز فترة توافر الشقة'
            ], 400);
        }
        // التحقق من الفترات المعروضة
        $isWithinDisplayPeriod = false;
        foreach ($apartment->displayPeriods as $period) {
            $periodStart = Carbon::parse($period->display_start_date);
            $periodEnd = Carbon::parse($period->display_end_date);

            // التحقق إذا كانت فترة الحجز كاملة ضمن فترة معروضة
            if ($start >= $periodStart && $end <= $periodEnd) {
                $isWithinDisplayPeriod = true;
                break;
            }
        }

        if (!$isWithinDisplayPeriod) {
            return response()->json([
                'status' => false,
                'message' => 'الفترة المطلوبة غير متاحة للحجز (غير ضمن الفترات المعروضة)'
            ], 400);
        }

        // التحقق من التداخل مع حجوزات مقبولة
        $overlap = Booking::where('apartment_id', $apartment->id)
            ->where('status', 'accepted')
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
            ->where('tenant_id', Auth::id())
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
        $totalPrice = $apartment->price *$days ;

        // إنشاء حجز بنتظار موافقة المالك فقط
        $booking = Booking::create([
            'apartment_id' => $apartment->id,
            'tenant_id' => Auth::id(),
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


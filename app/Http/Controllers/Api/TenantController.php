<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApartmentDetail;
use App\Models\Booking;
use App\Models\favorit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{

    // عرض كل الحجوزات للمستأجر

    public function cancel($id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking || $booking->status === 'canceled') {
            return response()->json(['status' => false, 'message' => 'الحجز غير موجود'], 404);
        }

        // تحقق أن المستأجر هو من أنشأ الحجز
        if ($booking->tenant_id != Auth::id()) {
            return response()->json(['status' => false, 'message' => 'غير مصرح لك بإلغاء هذا الحجز'], 403);
        }

        // إذا كان الحجز "بانتظار الموافقة" → نحذف مباشرة
        if ($booking->status === 'pending') {
            $booking->delete();
            return response()->json([
                'status' => true,
                'message' => 'تم حذف الحجز قبل الموافقة'
            ]);
        }
        // إذا كان الحجز موافَق عليه → نلغيه ونرسل إشعار للمالك
        if ($booking->status === 'accepted') {

            $booking->update(['status' => 'cancelled']);

            return response()->json([
                'status' => true,
                'message' => 'تم إلغاء الحجز وإعلام المالك'
            ]);

        }
        // إذا كان مرفوض أو ملغي بالأصل
        return response()->json(['status' => false,
            'message' => 'لا يمكن إلغاء هذا الحجز'], 400);

    }
    public function updateBooking(Request $request, $id): JsonResponse
    {
        try {
            $booking = Booking::with(['apartment', 'apartment.displayPeriods'])->find($id);

            if (!$booking) {
                return response()->json([
                    'status' => false,
                    'message' => 'الحجز غير موجود'
                ], 404);
            }

            if ($booking->status === 'cancelled') {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن تعديل حجز ملغى'
                ], 400);
            }

            if ($booking->tenant_id != Auth::id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح لك بتعديل هذا الحجز'
                ], 403);
            }

            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $apartment = $booking->apartment;
            $start = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);

            //  التحقق من فترة توافر الشقة
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

            $isWithinDisplayPeriod = false;
            foreach ($apartment->displayPeriods as $period) {
                $periodStart = Carbon::parse($period->display_start_date);
                $periodEnd = Carbon::parse($period->display_end_date);

                if ($start >= $periodStart && $end <= $periodEnd) {
                    $isWithinDisplayPeriod = true;
                    break;
                }
            }

            if (!$isWithinDisplayPeriod) {
                return response()->json([
                    'status' => false,
                    'message' => 'الفترة المطلوبة غير متاحة للحجز '
                ], 400);
            }

           //التحقق من التداخل عدا الاجار الحالي
            $overlap = Booking::where('apartment_id', $apartment->id)
                ->where('id', '!=', $booking->id) // استثناء الحجز الحالي
                ->where('status', 'accepted')
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_date', '<=', $start)
                                ->where('end_date', '>=', $end);
                        });
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'status' => false,
                    'message' => 'هناك حجز مقبول آخر في نفس الفترة'
                ], 409);
            }


            $days = $start->diffInDays($end) + 1;

            $totalPrice = $apartment->price *$days ;
            $booking->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'total_price' => $totalPrice,
                'status' => 'pending'
            ]);


            return response()->json([
                'status' => true,
                'message' => 'تم تعديل الحجز بنجاح وبانتظار موافقة المالك',
                'data' =>   $booking->fresh(['apartment', 'tenant'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تعديل الحجز',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // عرض جميع حجوزات المستأجر
    public function tenantBookings(): JsonResponse
    {
        $tenantId = Auth::id();

        $bookings = Booking::with([
            'apartment.images',
        ])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }
    //هاد تابع للاضافة والازالة نستعمله مع ايقونة القلب

    public function toggleFavorite($apartment_id): JsonResponse
    {
        $user_id=Auth::id();
        $favorite=favorit::where('user_id',$user_id)->where('apartment_id',$apartment_id)->first();
        if($favorite)
        {
            $favorite->delete();
            return response()->json([
                'status'=>true,
                'message'=>'Removed form favorites'
            ], 200);
        }
        favorit::create([
            'user_id'=>$user_id,
            'apartment_id'=>$apartment_id
        ]);
        return response()->json([
            'status'=>true,
            'message'=>'Added to favorites'
        ], 200);

    }
    public function showFavorite(): JsonResponse
    {
        $favorite=favorit::with('apartment')->where('user_id',Auth::id())->get();
        return response()->json([
            'status'=>true,
            'message'=>'Favorites fetched successfully',
            'data'=>$favorite
        ], 200);
    }
}

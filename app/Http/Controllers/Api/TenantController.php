<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApartmentDetail;
use App\Models\Booking;
use App\Models\favorit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            // 1. جلب الحجز مع العلاقات المطلوبة
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
                    'message' => 'لا يمكن تعديل حجار ملغى'
                ], 400);
            }

            // 2. التحقق من أن المستخدم هو المستأجر
            if ($booking->tenant_id != Auth::id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح لك بتعديل هذا الحجز'
                ], 403);
            }

            // 3. التحقق من البيانات
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $apartment = $booking->apartment;
            $start = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);

            // 4. التحقق من فترة توافر الشقة
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

            // ⭐⭐ **5. التحقق الجديد: أن تكون الفترة ضمن الفترات المعروضة**
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
                    'message' => 'الفترة المطلوبة غير متاحة للحجز (غير ضمن الفترات المعروضة)'
                ], 400);
            }

            // ⭐⭐ **6. التحقق من التداخل مع حجوزات مقبولة أخرى (استثناء الحجز الحالي)**
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
                    'message' => 'هناك حجار مقبول آخر في نفس الفترة'
                ], 409);
            }

            // 7. حساب السعر الجديد
            $days = $start->diffInDays($end) + 1;
            $dailyPrice = $apartment->price / 30;
            $totalPrice = $dailyPrice * $days;

            // 8. تحديث الحجز
            $booking->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'total_price' => $totalPrice,
                'status' => 'pending' // ⭐ يعود إلى انتظار موافقة المالك
            ]);

        //  // ⭐⭐ **9. إرسال إشعار للمالك عن التعديل**
        //  if ($booking->apartment->owner) {
        //      // باستخدام NotificationService إذا كان لديك
        //      // (new NotificationService())->notifyBookingModified($booking, $modifications);

        //      // أو إنشاء إشعار مباشر
        //      \App\Models\Notification::create([
        //          'user_id' => $booking->apartment->owner_id,
        //          'event_type' => 'booking_modified',
        //          'title' => 'تعديل على الحجز',
        //          'message' => "قام المستأجر بتعديل الحجز لشقتك '{$apartment->apartment_description}'",
        //          'metadata' => [
        //              'booking_id' => $booking->id,
        //              'old_start_date' => $booking->getOriginal('start_date'),
        //              'old_end_date' => $booking->getOriginal('end_date'),
        //              'new_start_date' => $request->start_date,
        //              'new_end_date' => $request->end_date,
        //              'old_price' => $booking->getOriginal('total_price'),
        //              'new_price' => $totalPrice,
        //              'modified_by' => Auth::user()->FirstName . ' ' . Auth::user()->LastName,
        //          ],
        //          'priority' => 'medium',
        //          'action_url' => "/owner/bookings/{$booking->id}",
        //          'icon' => 'edit'
        //      ]);
        //  }

            // 10. إرجاع النتيجة
            return response()->json([
                'status' => true,
                'message' => 'تم تعديل الحجز بنجاح وبانتظار موافقة المالك',
                'data' => $booking->fresh(['apartment', 'tenant'])
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

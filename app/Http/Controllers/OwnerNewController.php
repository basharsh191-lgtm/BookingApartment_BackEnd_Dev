<?php

namespace App\Http\Controllers;

use App\Models\apartment_detail;
use App\Models\ApartmentDetail;
use App\Models\Booking;
use App\Models\Province;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OwnerNewController extends Controller
{
    /**
     * إنشاء شقة جديدة)
     */
public function store(Request $request): JsonResponse
    {
        // 1. التحقق من البيانات
        $validated = $request->validate([
            'apartment_description' => 'required|string|max:255',
            'floorNumber' => 'required|integer|min:0',
            'roomNumber' => 'required|integer|min:1',
            'free_wifi' => 'required|boolean',
            'image' => 'required|array|min:1',
            'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'available_from' => 'required|date|after_or_equal:today',
            'available_to' => 'nullable|date|after_or_equal:available_from',
            'city' => 'required|string|max:100',
            'governorate_id' => 'required|exists:provinces,id',
            'area' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        //  إضافة بيانات المالك
        $validated['owner_id'] = Auth::id();
        $validated['scheduled_for_deletion'] = false;

        //  إنشاء الشقة
        $apartment = ApartmentDetail::create($validated);

        // حفظ الصور
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $image) {
                $path = $image->store('apartments', 'public');
                $apartment->images()->create([
                    'image_path' => $path
                ]);
            }
        }

        $apartment->load([
            'images',
            'governorate',
            'displayPeriods',
            'user:id,FirstName,LastName,mobile'
        ]);

        //  تنسيق النتيجة
        $responseData = [
            'id' => $apartment->id,
            'apartment_description' => $apartment->apartment_description,
            'floorNumber' => $apartment->floorNumber,
            'roomNumber' => $apartment->roomNumber,
            'free_wifi' => $apartment->free_wifi,
            'available_from' => $apartment->available_from,
            'available_to' => $apartment->available_to,
            'city' => $apartment->city,
            'governorate' => $apartment->governorate,
            'area' => $apartment->area,
            'price' => $apartment->price,
            'scheduled_for_deletion' => $apartment->scheduled_for_deletion,
            'images' => $apartment->images,
            'displayPeriods' => $apartment->displayPeriods,
            'owner_info' => [
                'FirstName' => $apartment->user->FirstName ?? null,
                'LastName' => $apartment->user->LastName ?? null,
                'mobile' => $apartment->user->mobile ?? null,
            ]
        ];

        // إرجاع النتيجة
        return response()->json([
            'status' => true,
            'message' => 'تم إضافة الشقة بنجاح مع فترة معروضة كاملة',
            'data' => $responseData
        ], 201);
    }

    /**
     * تعديل بيانات الشقة
     */
public function update(Request $request, $id)
{
    $apartment = ApartmentDetail::where('id', $id)
        ->where('owner_id', Auth::id())->with('images')
        ->first();

    if (!$apartment) {
        return response()->json([
            'status' => false,
            'message' => 'غير مصرح لك أو الشقة غير موجودة'
        ], 403);
    }

    $validated = $request->validate([
        'apartment_description' => 'nullable|string|max:255',
        'available_from' => 'nullable|date',
        'available_to' => 'nullable|date|after_or_equal:available_from',
        'city' => 'nullable|string|max:100',
        'governorate' => 'nullable|string|max:100',
        'area' => 'nullable|numeric|min:1',
        'price' => 'nullable|numeric|min:0',
        'free_wifi' => 'nullable|boolean',
        'image' => 'nullable|array',
        'image.*' => 'image|mimes:jpeg,png,jpg,gif',
    ]);

    $apartment->update($validated);

    if ($request->hasFile('image')) {

        foreach ($apartment->images as $oldImage) {
            if (Storage::disk('public')->exists($oldImage->image_path)) {
                Storage::disk('public')->delete($oldImage->image_path);
            }
            $oldImage->delete();
        }

        // حفظ الصور الجديدة
        foreach ($request->file('image') as $image) {
            $path = $image->store('apartments', 'public');

            $apartment->images()->create([
                'image_path' => $path
            ]);
        }
    }

    return response()->json([
        'message' => 'تم تعديل الشقة بنجاح',
        'data' => $apartment->load('images'),


    ]);
}
    /**
     * تعديل فترة التوافر
     */
    public function setAvailability(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'available_from' => 'required|date|after_or_equal:today',
            'available_to' => 'required|date|after_or_equal:available_from',
        ]);

        $apartment = ApartmentDetail::findOrFail($id);

        // تحقق من الملكية
        if ($apartment->owner_id !== Auth::id()) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $apartment->update($validated);

        return response()->json([
            'message' => 'تم تحديث تواريخ الإتاحة بنجاح',
            'data' => $apartment
        ]);
    }

    /**
     * حذف الشقة
     */
    public function destroy($id): JsonResponse
    {
        $apartment = ApartmentDetail::where('id', $id)
            ->where('owner_id', Auth::id())
            ->firstOrFail();

        $hasActiveBookings = Booking::where('apartment_id', $id)
            ->whereIn('status', ['accepted', 'pending'])
            ->where('end_date', '>=', Carbon::now())
            ->exists();

        if ($hasActiveBookings) {
            $apartment->update([
                'scheduled_for_deletion' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'الشقة مؤجرة حالياً، سيتم حذفها تلقائياً بعد انتهاء آخر حجز'
            ]);
        }

        // لا يوجد أي حجز فعّال → حذف فوري
        Booking::where('apartment_id', $id)->delete();
        $apartment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الشقة بنجاح'
        ]);
    }

    /**
     * الموافقة على الحجز
     */
    /**
     * قبول الحجز وتقسيم الفترات المعروضة
     */
    public function approve($id): JsonResponse
    {
        $booking = Booking::with('apartment','apartment.displayPeriods')->findOrFail($id);

        // التحقق من أن المستخدم هو مالك الشقة
        if ($booking->apartment->owner_id != Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بقبول هذا الحجز'
            ], 403);
        }

        // التحقق من أن الحجز قيد الانتظار
        if ($booking->status != 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'الحجز ليس قيد الانتظار'
            ], 400);
        }

        // التحقق من التداخل مع حجوزات مقبولة أخرى
        $overlap = Booking::where('apartment_id', $booking->apartment_id)
            ->where('id', '!=', $booking->id)
            ->where('status', 'accepted')
            ->where(function ($q) use ($booking) {
                $q->whereBetween('start_date', [$booking->start_date, $booking->end_date])
                    ->orWhereBetween('end_date', [$booking->start_date, $booking->end_date])
                    ->orWhere(function ($q2) use ($booking) {
                        $q2->where('start_date', '<=', $booking->start_date)
                            ->where('end_date', '>=', $booking->end_date);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'status' => false,
                'message' => 'هناك حجز آخر مقبول في نفس الفترة'
            ], 409);
        }

        // تحديث حالة الحجز
        $booking->update(['status' => 'accepted']);

        // ⭐⭐⭐ **تقسيم الفترات المعروضة**
        $this->splitDisplayPeriodsAfterBooking(
            $booking->apartment,
            Carbon::parse($booking->start_date),
            Carbon::parse($booking->end_date)
        );

        return response()->json([
            'status' => true,
            'message' => 'تم قبول الحجز وتحديث الفترات المعروضة',
            'data' => $booking->fresh(['apartment.displayPeriods'])
        ]);
    }

    /**
     * تقسيم الفترات المعروضة بعد الحجز
     */
    private function splitDisplayPeriodsAfterBooking($apartment, Carbon $bookingStart, Carbon $bookingEnd): void
    {
        $displayPeriods = $apartment->displayPeriods()->orderBy('display_start_date')->get();

        foreach ($displayPeriods as $period) {
            $periodStart = Carbon::parse($period->display_start_date);
            $periodEnd = Carbon::parse($period->display_end_date);

            // التحقق إذا كان الحجز داخل هذه الفترة المعروضة
            if ($bookingStart >= $periodStart && $bookingEnd <= $periodEnd) {

                // الحجز في منتصف الفترة
                if ($bookingStart > $periodStart && $bookingEnd < $periodEnd) {
                    // تحديث نهاية الفترة الأولى
                    $period->update([
                        'display_end_date' => $bookingStart->copy()->subDay()
                    ]);

                    // إنشاء الفترة الثانية
                    $apartment->displayPeriods()->create([
                        'display_start_date' => $bookingEnd->copy()->addDay(),
                        'display_end_date' => $periodEnd,
                    ]);
                }

                //  الحجز من بداية الفترة
                elseif ($bookingStart->eq($periodStart) && $bookingEnd < $periodEnd) {
                    $period->update([
                        'display_start_date' => $bookingEnd->copy()->addDay(),
                    ]);
                }

                //  الحجز حتى نهاية الفترة
                elseif ($bookingStart > $periodStart && $bookingEnd->eq($periodEnd)) {
                    $period->update([
                        'display_end_date' => $bookingStart->copy()->subDay()
                    ]);
                }

                //  الحجز يغطي الفترة كاملة
                elseif ($bookingStart->eq($periodStart) && $bookingEnd->eq($periodEnd)) {
                    $period->delete();
                }

                break;
            }
        }
    }
    /**
     * رفض الحجز
     */
    public function reject($id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'الحجز غير موجود'
            ], 404);
        }

        if ($booking->apartment->owner_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك برفض هذا الحجز'
            ], 403);
        }

        $booking->update(['status' => 'rejected']);

        return response()->json([
            'status' => true,
            'message' => 'تم رفض الحجز بنجاح',
            'data' => $booking
        ]);
    }

    /**
     * عرض حجوزات المالك
     */
    public function ownerBookings(): JsonResponse
    {
        $bookings = Booking::with(['apartment','tenant']) // إزالة user مؤقتاً
        ->whereHas('apartment', function($q) {
            $q->where('owner_id', Auth::id());
        })
        ->orderBy('created_at', 'desc')
        ->get();


        if ($bookings->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'لا توجد حجوزات حالياً',
                'data' => []
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'جميع الحجوزات الخاصة بك',
            'data' => $bookings
        ]);
    }
}

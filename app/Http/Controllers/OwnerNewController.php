<?php

namespace App\Http\Controllers;

use App\Notifications\ApartmentDeletionNotification;

use App\Notifications\ApartmentUpdated;
use App\Notifications\NewRentalRequest;
use App\Notifications\RentalRequestAccepted;
use App\Notifications\RentalRequestRejected;
use App\Notifications\RentalCancelled;

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
        // التحقق من البيانات
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

        // إضافة بيانات المالك
        $validated['owner_id'] = Auth::id();
        $validated['scheduled_for_deletion'] = false;

        // إنشاء الشقة
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

        // تحميل العلاقات بدون فلترة الأعمدة
        $apartment->load(['images', 'governorate', 'displayPeriods', 'user']);

        Auth::user()->notify(new ApartmentUpdated($apartment));

        // إرجاع البيانات كما هي بدون تنسيق
        return response()->json([
            'status' => true,
            'message' => 'تم إضافة الشقة بنجاح',
            'data' => $apartment
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

        Auth::user()->notify(new ApartmentUpdated($apartment));

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
            'available_from' => 'required|date',
            'available_to' => 'required|date|after_or_equal:available_from',
        ]);

        $apartment = ApartmentDetail::findOrFail($id);

        if ($apartment->owner_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك'
            ], 403);
        }

        $newStart = Carbon::parse($validated['available_from']);
        $newEnd = Carbon::parse($validated['available_to']);

        //  منع التعارض مع الحجوزات المقبولة
        $hasAcceptedOverlap = Booking::where('apartment_id', $apartment->id)
            ->where('status', 'accepted')
            ->where(function ($q) use ($newStart, $newEnd) {
                $q->whereBetween('start_date', [$newStart, $newEnd])
                    ->orWhereBetween('end_date', [$newStart, $newEnd])
                    ->orWhere(function ($q2) use ($newStart, $newEnd) {
                        $q2->where('start_date', '<=', $newStart)
                            ->where('end_date', '>=', $newEnd);
                    });
            })
            ->exists();

        if ($hasAcceptedOverlap) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن تعديل الإتاحة بسبب وجود حجز مقبول ضمن هذه الفترة'
            ], 409);
        }

        $apartment->displayPeriods()->delete();
        $apartment->displayPeriods()->create([
            'display_start_date' => $newStart->toDateString(),
            'display_end_date' => $newEnd->toDateString(),
        ]);
        $apartment->update([
            'available_from' => $newStart->toDateString(),
            'available_to' => $newEnd->toDateString(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث فترة الإتاحة بنجاح',
            'data' => $apartment->fresh('displayPeriods')
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
            ->whereIn('status', ['accepted', 'pending', 'approved'])
            ->where('end_date', '>=', Carbon::now())
            ->exists();

        if ($hasActiveBookings) {
            // حساب معلومات الحجوزات النشطة
            $activeBookingsCount = Booking::where('apartment_id', $id)
                ->whereIn('status', ['accepted', 'pending', 'approved'])
                ->where('end_date', '>=', Carbon::now())
                ->count();

            $latestEndDate = Booking::where('apartment_id', $id)
                ->whereIn('status', ['accepted', 'pending', 'approved'])
                ->where('end_date', '>=', Carbon::now())
                ->max('end_date');

            // تحديث حالة الشقة
            $apartment->update([
                'scheduled_for_deletion' => true,
            ]);

            // رسالة الإشعار
            $message = 'Apartment "' . $apartment->apartment_description .
                '" has ' . $activeBookingsCount .
                ' active booking(s). It will be automatically deleted after the last booking ends';

            if ($latestEndDate) {
                $message .= ' (on ' . Carbon::parse($latestEndDate)->format('Y-m-d') . ')';

                // إضافة حقل تاريخ الحذف المتوقع (اختياري)
                $apartment->update([
                    'deletion_scheduled_date' => Carbon::parse($latestEndDate)->addDay()
                ]);
            }

            // إرسال إشعار للمالك
            Auth::user()->notify(new ApartmentDeletionNotification(
                $apartment,
                'scheduled',
                $message
            ));

            return response()->json([
                'success' => true,
                'message' => 'الشقة مؤجرة حالياً، سيتم حذفها تلقائياً بعد انتهاء آخر حجز',
                'active_bookings_count' => $activeBookingsCount,
                'latest_end_date' => $latestEndDate,
                'scheduled_for_deletion' => true,
                'note' => 'Command "apartments:delete-scheduled" will handle the automatic deletion',
            ]);
        }

        // لا يوجد أي حجز فعّال → حذف فوري
        Booking::where('apartment_id', $id)->delete();

        // إرسال إشعار قبل الحذف
        Auth::user()->notify(new ApartmentDeletionNotification(
            $apartment,
            'deleted',
            'Apartment "' . $apartment->apartment_description . '" has been deleted successfully.'
        ));

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
        $booking = Booking::with('apartment', 'apartment.displayPeriods')->findOrFail($id);

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


        $this->splitDisplayPeriodsAfterBooking(
            $booking->apartment,
            Carbon::parse($booking->start_date),
            Carbon::parse($booking->end_date)
        );

        if ($booking->tenant) {
            $booking->tenant->notify(new RentalRequestAccepted(
                $booking->apartment,
                Auth::user()
            ));
        }
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
                } //  الحجز من بداية الفترة
                elseif ($bookingStart->eq($periodStart) && $bookingEnd < $periodEnd) {
                    $period->update([
                        'display_start_date' => $bookingEnd->copy()->addDay(),
                    ]);
                } //  الحجز حتى نهاية الفترة
                elseif ($bookingStart > $periodStart && $bookingEnd->eq($periodEnd)) {
                    $period->update([
                        'display_end_date' => $bookingStart->copy()->subDay()
                    ]);
                } //  الحجز يغطي الفترة كاملة
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

        if ($booking->tenant) {
            $booking->tenant->notify(new RentalRequestRejected(
                $booking->apartment,
                Auth::user(),
            ));
        }

        return response()->json([
            'status' => true,
            'message' => 'تم رفض الحجز بنجاح',
            'data' => $booking
        ]);
    }

    /**
     * عرض حجوزات المالك
     */
    public function ownerApartmentBookings($apartmentId): JsonResponse
    {
        $apartment = ApartmentDetail::where('id', $apartmentId)
            ->where('owner_id', Auth::id())
            ->firstOrFail();

        $bookings = Booking::with(['tenant:id,FirstName,LastName,mobile,ProfileImage'])
            ->where('apartment_id', $apartmentId)
            ->orderByRaw("
            CASE status
                WHEN 'pending' THEN 1
                WHEN 'accepted' THEN 2
                WHEN 'finished' THEN 3
                WHEN 'cancelled' THEN 4
            END
        ")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'status' => $booking->status,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'created_at' => $booking->created_at,

                    'tenant' => [
                        'FirstName' => $booking->tenant->FirstName ?? null,
                        'LastName' => $booking->tenant->LastName ?? null,
                        'mobile' => $booking->tenant->mobile ?? null,
                        'ProfileImage' => $booking->tenant->ProfileImage
                            ? asset('storage/' . $booking->tenant->ProfileImage)
                            : null,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'حجوزات الشقة',
            'apartment' => $apartment,
            'total_bookings' => $bookings->count(),
            'data' => $bookings
        ]);
    }

    public function ownerApartments(): JsonResponse
    {
        $apartments = ApartmentDetail::with([
            'images',
            'governorate',
            'displayPeriods'
        ])
            ->withCount('bookings')
            ->where('owner_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'شققك مع عدد الحجوزات',
            'data' => $apartments
        ]);
    }


}

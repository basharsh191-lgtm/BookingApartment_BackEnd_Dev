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
        $apartment->load(['images', 'governorate', 'displayPeriods', 'owner']);

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
    /**
     * قبول الحجز وتقسيم الفترات المعروضة - النسخة المحسنة
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

        // تقسيم الفترات المعروضة مع دمج الفترات المتداخلة
        $this->splitAndMergeDisplayPeriods(
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
            'data' => $booking->fresh(['apartment.displayPeriods', 'apartment'])
        ]);
    }

    /**
     * تقسيم الفترات المعروضة مع دمج الفترات المتداخلة
     */
    private function splitAndMergeDisplayPeriods($apartment, Carbon $bookingStart, Carbon $bookingEnd): void
    {
        \Log::info('Starting splitAndMergeDisplayPeriods', [
            'apartment_id' => $apartment->id,
            'booking_start' => $bookingStart->toDateString(),
            'booking_end' => $bookingEnd->toDateString(),
            'current_periods_count' => $apartment->displayPeriods()->count()
        ]);

        // 1. الحصول على جميع الفترات الحالية
        $displayPeriods = $apartment->displayPeriods()
            ->orderBy('display_start_date')
            ->get();

        // 2. إذا لم توجد فترات، أنشئ فترة من التواريخ الرئيسية
        if ($displayPeriods->isEmpty() && $apartment->available_from && $apartment->available_to) {
            $apartment->displayPeriods()->create([
                'display_start_date' => $apartment->available_from,
                'display_end_date' => $apartment->available_to,
            ]);
            $displayPeriods = $apartment->displayPeriods()->orderBy('display_start_date')->get();
        }

        $newPeriods = [];

        foreach ($displayPeriods as $period) {
            $periodStart = Carbon::parse($period->display_start_date);
            $periodEnd = Carbon::parse($period->display_end_date);

            \Log::info('Processing period', [
                'period_id' => $period->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString()
            ]);

            // 3. التحقق من التداخل مع الحجز
            // الحجز خارج هذه الفترة تماماً
            if ($bookingEnd < $periodStart || $bookingStart > $periodEnd) {
                \Log::info('No overlap - keeping period as is');
                $newPeriods[] = [
                    'start' => $periodStart,
                    'end' => $periodEnd
                ];
                continue;
            }

            // 4. الحجز داخل هذه الفترة - تقسيمها
            // جزء قبل الحجز
            if ($periodStart < $bookingStart) {
                \Log::info('Adding period before booking', [
                    'start' => $periodStart->toDateString(),
                    'end' => $bookingStart->copy()->subDay()->toDateString()
                ]);
                $newPeriods[] = [
                    'start' => $periodStart,
                    'end' => $bookingStart->copy()->subDay()
                ];
            }

            // جزء بعد الحجز
            if ($periodEnd > $bookingEnd) {
                \Log::info('Adding period after booking', [
                    'start' => $bookingEnd->copy()->addDay()->toDateString(),
                    'end' => $periodEnd->toDateString()
                ]);
                $newPeriods[] = [
                    'start' => $bookingEnd->copy()->addDay(),
                    'end' => $periodEnd
                ];
            }
        }

        // 5. دمج الفترات المتجاورة
        $mergedPeriods = $this->mergeAdjacentPeriods($newPeriods);

        Log::info('Merged periods', [
            'count' => count($mergedPeriods),
            'periods' => array_map(function ($p) {
                return [
                    'start' => $p['start']->toDateString(),
                    'end' => $p['end']->toDateString()
                ];
            }, $mergedPeriods)
        ]);

        // 6. حذف الفترات القديمة وخلق الجديدة
        $apartment->displayPeriods()->delete();

        foreach ($mergedPeriods as $period) {
            $apartment->displayPeriods()->create([
                'display_start_date' => $period['start']->toDateString(),
                'display_end_date' => $period['end']->toDateString(),
            ]);
        }

        // 7. تحديث التواريخ الرئيسية
        $this->updateMainAvailabilityDates($apartment);
    }

    /**
     * دمج الفترات المتجاورة أو المتداخلة
     */
    private function mergeAdjacentPeriods(array $periods): array
    {
        if (empty($periods)) {
            return [];
        }

        // ترتيب الفترات حسب تاريخ البدء
        usort($periods, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        $merged = [];
        $current = $periods[0];

        for ($i = 1; $i < count($periods); $i++) {
            $next = $periods[$i];

            // التحقق إذا كانت الفترات متداخلة أو متجاورة
            $currentEndPlusOne = $current['end']->copy()->addDay();

            if ($currentEndPlusOne >= $next['start']) {
                // دمج الفترات
                $current['end'] = $current['end']->greaterThan($next['end'])
                    ? $current['end']
                    : $next['end'];
            } else {
                // حفظ الفترة الحالية وبدء فترة جديدة
                $merged[] = [
                    'start' => $current['start'],
                    'end' => $current['end']
                ];
                $current = $next;
            }
        }

        // إضافة الفترة الأخيرة
        $merged[] = [
            'start' => $current['start'],
            'end' => $current['end']
        ];

        return $merged;
    }

    /**
     * تحديث التواريخ الرئيسية للشقة
     */
    private function updateMainAvailabilityDates($apartment): void
    {
        $firstPeriod = $apartment->displayPeriods()
            ->orderBy('display_start_date')
            ->first();

        $lastPeriod = $apartment->displayPeriods()
            ->orderBy('display_end_date', 'desc')
            ->first();

        if ($firstPeriod && $lastPeriod) {
            $apartment->update([
                'available_from' => $firstPeriod->display_start_date,
                'available_to' => $lastPeriod->display_end_date
            ]);

            \Log::info('Updated main availability dates', [
                'available_from' => $firstPeriod->display_start_date,
                'available_to' => $lastPeriod->display_end_date
            ]);
        } else {
            // لا توجد فترات متاحة
            $apartment->update([
                'available_from' => null,
                'available_to' => null
            ]);

            \Log::info('No available periods - set dates to null');
        }

        // تحديث النموذج من قاعدة البيانات
        $apartment->refresh();
    }

    /**
     * دالة خاصة لتنظيف الفترات المكررة في شقة محددة
     */
    public function fixDuplicatePeriods($apartmentId): JsonResponse
    {
        $apartment = ApartmentDetail::with('displayPeriods')->findOrFail($apartmentId);

        if ($apartment->owner_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك'
            ], 403);
        }

        \Log::info('Fixing duplicate periods for apartment', ['apartment_id' => $apartmentId]);

        // استخدام نفس منطق الدمج
        $this->splitAndMergeDisplayPeriods($apartment, Carbon::now()->addYear(), Carbon::now()->addYear());

        return response()->json([
            'status' => true,
            'message' => 'تم تنظيف الفترات المكررة بنجاح',
            'data' => $apartment->fresh('displayPeriods')
        ]);
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

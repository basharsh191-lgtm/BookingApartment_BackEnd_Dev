<?php

namespace App\Http\Controllers;

use App\Models\apartment_detail;
use App\Models\apartmentDetail;
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

    // ربط الشقة بالمستخدم
    $validated['owner_id'] = Auth::id();
    $validated['scheduled_for_deletion'] = false;

    // إنشاء الشقة (بلا الصور)
    $apartment = apartmentDetail::create($validated);
    // حفظ الصور (كل صورة ب حقل بادلت بيز)
    if($request->hasFile('image'))
    {
    foreach ($request->file('image') as $image)
        {
        $path = $image->store('apartments', 'public');
        $apartment->images()->create([
            'image_path' => $path
        ]);
        }
    }

    $apartment->load('images', 'governorate');

    // 2. إرجاع استجابة JSON نظيفة ومنظمة
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
    $apartment = apartmentDetail::where('id', $id)
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

        $apartment = apartmentDetail::findOrFail($id);

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
        $apartment = apartmentDetail::where('id', $id)
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
    public function approve($id): JsonResponse
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
                'message' => 'غير مصرح لك بالموافقة على هذا الحجز'
            ], 403);
        }

        // التحقق من عدم وجود حجز آخر معتمد يتداخل معه
        $overlap = Booking::where('apartment_id', $booking->apartment_id)
            ->where('status', 'accepted')
            ->where(function ($q) use ($booking) {
                $q->whereBetween('start_date', [$booking->start_date, $booking->end_date])
                    ->orWhereBetween('end_date', [$booking->start_date, $booking->end_date]);
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'status' => false,
                'message' => 'هناك حجز آخر موافَق عليه في نفس الفترة'
            ], 409);
        }

        // الموافقة على الحجز
        $booking->update(['status' => 'accepted']);

        return response()->json([
            'status' => true,
            'message' => 'تمت الموافقة على الحجز بنجاح',
            'data' => $booking
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

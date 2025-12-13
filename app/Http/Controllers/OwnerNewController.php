<?php

namespace App\Http\Controllers;

use App\Models\apartment_detail;
use App\Models\apartmentDetail;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OwnerNewController extends Controller
{
    /**
     * إنشاء شقة جديدة (إصدار محسّن)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'apartment_description' => 'required|string|max:255',
            'floorNumber' => 'required|integer|min:0',
            'roomNumber' => 'required|integer|min:1',
            'free_wifi' => 'required|boolean',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'available_from' => 'required|date|after_or_equal:today',
            'available_to' => 'required|date|after_or_equal:available_from',
            'city' => 'required|string|max:100',
            'governorate' => 'required|string|max:100',
            'area' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        // معالجة الصورة
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('apartments', 'public');
            $validated['image'] = $imagePath;
        }

        $validated['owner_id'] = Auth::id();
        $detail = apartmentDetail::create($validated);

        return response()->json([
            'message' => 'تم إضافة الشقة بنجاح',
            'data' => $detail,
            'image_url' => asset('storage/' . $detail->image)
        ], 201);
    }

    /**
     * تعديل بيانات الشقة
     */
    public function update(Request $request, apartmentDetail $apartment): JsonResponse
    {
        // تحقق من الملكية
        if ($apartment->owner_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بتعديل هذه الشقة'
            ], 403);
        }

        $validated = $request->validate([
            'apartment_description' => 'string|nullable|max:255',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048|nullable',
            'available_from' => 'date|nullable',
            'available_to' => 'date|after_or_equal:available_from|nullable',
            'city' => 'string|max:100|nullable',
            'governorate' => 'string|max:100|nullable',
            'area' => 'numeric|min:1|nullable',
            'price' => 'numeric|min:0|nullable',
            'free_wifi' => 'boolean|nullable',
        ]);

        // معالجة الصورة إذا تم رفع جديدة
        if ($request->hasFile('image')) {

            $imagePath = $request->file('image')->store('apartments', 'public');
            $validated['image'] = $imagePath;
        }

        $apartment->update($validated);

        return response()->json([
            'message' => 'تم تعديل الشقة بنجاح',
            'data' => $apartment
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

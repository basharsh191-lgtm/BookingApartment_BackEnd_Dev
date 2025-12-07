<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\apartmentDetail;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OwnerController extends Controller
{
    // إنشاء شقة جديدة
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'area' => 'required|numeric',
            'price' => 'required|numeric',
            'floorNumber'=> 'required|numeric',
            'roomNumber'=> 'required|numeric',
            'images' =>  'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif',
            'available_from' => 'required|date',
            'available_to' => 'required|date|after_or_equal:available_from',
            'governorate' => 'required|string|max:50',
            'city'=>'required|string|max:50',
            'apartment_description' => 'nullable|string',
            'is_furnished'=>'required|boolean',
        ]);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('apartments', 'public');
            }
        }

        $validated['images'] = $imagePaths;
        $validated['owner_id'] = Auth::id();

        $detail = apartmentDetail::create($validated);

        return response()->json($detail, 201);
    }

    // تعديل بيانات الشقة
    public function update(Request $request, apartmentDetail $apartmentDetail): JsonResponse
    {
        $request->validate([
            'apartment_description' => 'string|nullable',
            'images' => 'array|nullable',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif',
            'available_from' => 'date|nullable',
            'available_to' => 'date|after_or_equal:available_from|nullable',
            'governorate' => 'string|max:50|nullable',
            'area' => 'numeric|nullable',
            'price' => 'numeric|nullable',
        ]);

        $data = $request->only([
            'apartment_description',
            'available_from',
            'available_to',
            'governorate',
            'area',
            'price',
        ]);

        // رفع الصور الجديدة وإضافتها للمصفوفة
        if ($request->hasFile('images')) {
            $imagePaths = $apartmentDetail->images ?? [];
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('apartments', 'public');
            }
            $data['images'] = $imagePaths;
        }

        $apartmentDetail->update($data);

        return response()->json($apartmentDetail);
    }

    // تعديل فترة التوافر للشقة
    public function setAvailability(Request $request, apartmentDetail $apartmentDetail): JsonResponse
    {
        $request->validate([
            'available_from' => 'required|date',
            'available_to' => 'required|date|after_or_equal:available_from',
        ]);

        $apartmentDetail->update([
            'available_from' => $request->input('available_from'),
            'available_to' => $request->input('available_to'),
        ]);

        return response()->json([
            'message' => 'Availability updated successfully',
            'data' => $apartmentDetail,
        ]);
    }

    // حذف الشقة
    public function destroy(apartmentDetail $apartmentDetail): JsonResponse
    {
        $apartmentDetail->delete();

        return response()->json([
            'message' => 'Apartment deleted successfully'
        ], 200);
    }

    // الموافقة على الحجز
    public function approve($id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'الحجز غير موجود'], 404);
        }

        if ($booking->apartment->owner_id != Auth::id()) {
            return response()->json(['status' => false, 'message' => 'غير مصرح لك بالموافقة على هذا الحجز'], 403);
        }

        $booking->update(['status' => 'approved']);

        return response()->json(['status' => true, 'message' => 'تمت الموافقة على الحجز بنجاح', 'data' => $booking]);
    }

    // رفض الحجز
    public function reject($id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'الحجز غير موجود'], 404);
        }

        if ($booking->apartment->owner_id != Auth::id()) {
            return response()->json(['status' => false, 'message' => 'غير مصرح لك برفض هذا الحجز'], 403);
        }

        $booking->update(['status' => 'rejected']);

        return response()->json(['status' => true, 'message' => 'تم رفض الحجز بنجاح', 'data' => $booking]);
    }

    // عرض كل الحجوزات للمالك
    public function ownerBookings(): JsonResponse
    {
        $bookings = Booking::whereHas('apartment', function($q){
            $q->where('owner_id', Auth::id());
        })->get();

        return response()->json(['status' => true, 'message' => 'جميع الحجوزات الخاصة بك', 'data' => $bookings]);
    }
}

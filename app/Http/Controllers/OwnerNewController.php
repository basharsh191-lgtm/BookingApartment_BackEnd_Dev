<?php

namespace App\Http\Controllers;

<<<<<<< HEAD:app/Http/Controllers/OwnerNewController.php
use App\Models\apartment_detail;
=======
use App\Http\Controllers\Controller;
use App\Models\apartmentDetail;
>>>>>>> 84ffdf7a2cdb3d9fe5f93f90797a757d961f0d2e:app/Http/Controllers/Api/OwnerController.php
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OwnerNewController extends Controller
{
<<<<<<< HEAD:app/Http/Controllers/OwnerNewController.php
    public function store(Request $request)
    {

        $validated = $request->validate([
        'apartment_description' => 'required|string|max:255',
        'floorNumber' => 'required|integer|min:0',
        'roomNumber' => 'required|integer|min:1',
        'is_furnished' => 'required|boolean',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'available_from' => 'required|date|after_or_equal:today',
        'available_to' => 'required|date|after_or_equal:available_from',
        'city' => 'required|string|max:100',
        'governorate' => 'required|string|max:100',
        'area' => 'required|numeric|min:1',
        'price' => 'required|numeric|min:0',
        ]);
        if ($request->hasFile('image'))
        {
            $imagePath = $request->file('image')->store('apartments', 'public');
            $validated['image'] = $imagePath;
        }

        $validated['owner_id'] = Auth::id();
        $detail = apartment_detail::create($validated);
=======
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
>>>>>>> 84ffdf7a2cdb3d9fe5f93f90797a757d961f0d2e:app/Http/Controllers/Api/OwnerController.php

        return response()->json([
            'message' => 'تم إضافة الشقة بنجاح',
            'data' => $detail,
            'image_url' => asset('storage/' . $detail->image)
        ], 201);
    }
<<<<<<< HEAD:app/Http/Controllers/OwnerNewController.php
     public function update(Request $request, apartment_detail $apartment_details)
=======

    // تعديل بيانات الشقة
    public function update(Request $request, apartmentDetail $apartmentDetail): JsonResponse
>>>>>>> 84ffdf7a2cdb3d9fe5f93f90797a757d961f0d2e:app/Http/Controllers/Api/OwnerController.php
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
        if ($apartmentDetail->owner_id != Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بتعديل هذه الشقة'
            ], 403);
        }
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
public function setAvailability(Request $request, $id)
{
    $validated = $request->validate([
        'available_from' => 'required|date|after_or_equal:today',
        'available_to' => 'required|date|after_or_equal:available_from',
    ]);

<<<<<<< HEAD:app/Http/Controllers/OwnerNewController.php
    $apartment = apartment_detail::findOrFail($id);
=======
    // تعديل فترة التوافر للشقة
    public function setAvailability(Request $request, apartmentDetail $apartmentDetail): JsonResponse
    {
        if ($apartmentDetail->owner_id != Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بتعديل هذه الشقة'
            ], 403);
        }
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
        if ($apartmentDetail->owner_id != Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بتعديل هذه الشقة'
            ], 403);
        }
        $apartmentDetail->delete();
>>>>>>> 84ffdf7a2cdb3d9fe5f93f90797a757d961f0d2e:app/Http/Controllers/Api/OwnerController.php

    if ($apartment->owner_id !== Auth::id()) {
        return response()->json(['error' => 'Unauthenticated 😊'], 403);
    }

<<<<<<< HEAD:app/Http/Controllers/OwnerNewController.php
    $apartment->update($validated);

    return response()->json([
        'message' => 'Availability updated successfully',
        'data' => $apartment
    ]);
}
public function destroy($id)
    {
    $apartment=apartment_detail::where('id',$id)->where('owner_id',Auth::id())->firstOrFail();
    $apartment->delete();
    return response()->json([
            'success' => true,
            'message' => 'Availability deleted successfully'
    ]);
    }
    public function approve($id)
=======
    // الموافقة على الحجز
    public function approve($id): JsonResponse
>>>>>>> 84ffdf7a2cdb3d9fe5f93f90797a757d961f0d2e:app/Http/Controllers/Api/OwnerController.php
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'الحجز غير موجود'], 404);
        }

        if ($booking->apartment->owner_id != Auth::id()) {
            return response()->json(['status' => false, 'message' => 'غير مصرح لك بالموافقة على هذا الحجز'], 403);
        }

        // التحقق من عدم وجود حجز آخر معتمد يتداخل معه
        $overlap = Booking::where('apartment_id', $booking->apartment_id)
            ->where('status', 'accepted')
            ->where(function ($q) use ($booking) {
                $q->whereBetween('start_date', [$booking->start_date, $booking->end_date])
                    ->orWhereBetween('end_date', [$booking->start_date, $booking->end_date])
                    ->orWhereRaw('? BETWEEN start_date AND end_date', [$booking->start_date])
                    ->orWhereRaw('? BETWEEN start_date AND end_date', [$booking->end_date]);
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

        // تحديث فترة توافر الشقة بعد الموافقة فقط
        $apartment = $booking->apartment;
        $apartment->update([
            'available_from' => date('Y-m-d', strtotime($booking->end_date . ' +1 day'))
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تمت الموافقة على الحجز بنجاح',
            'data' => $booking
        ]);
    }

<<<<<<< HEAD:app/Http/Controllers/OwnerNewController.php
    public function reject($id)
=======
    // رفض الحجز
    public function reject($id): JsonResponse
>>>>>>> 84ffdf7a2cdb3d9fe5f93f90797a757d961f0d2e:app/Http/Controllers/Api/OwnerController.php
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
    public function ownerBookings()
    {
        $bookings = Booking::whereHas('apartment', function($q){
            $q->where('owner_id', Auth::id());
        })->get();
<<<<<<< HEAD:app/Http/Controllers/OwnerNewController.php
        if ($bookings->isEmpty())
        {
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
=======

        return response()->json(['status' => true, 'message' => 'جميع الحجوزات الخاصة بك', 'data' => $bookings]);
>>>>>>> 84ffdf7a2cdb3d9fe5f93f90797a757d961f0d2e:app/Http/Controllers/Api/OwnerController.php
    }
}

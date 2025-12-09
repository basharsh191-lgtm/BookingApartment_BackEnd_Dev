<?php

namespace App\Http\Controllers;

use App\Models\apartment_detail;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerNewController extends Controller
{
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

        return response()->json([
            'message' => 'تم إضافة الشقة بنجاح',
            'data' => $detail,
            'image_url' => asset('storage/' . $detail->image)
        ], 201);
    }
     public function update(Request $request, apartment_detail $apartment_details)
    {
        $request->validate([

            'apartment_description' => 'string',
            'image' => 'image|mimes:jpeg,png,jpg,gif',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'governorate' => 'string|max:50',
            'area' => 'numeric',
            'price' => 'numeric',
        ]);

        $data = $request->only([
            'apartment_description',
            'start_date',
            'end_date',
            'governorate',
            'area',
            'price',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('apartments', 'public');
        }

        $apartment_details->update($data);

        return response()->json($apartment_details);
    }
public function setAvailability(Request $request, $id)
{
    $validated = $request->validate([
        'available_from' => 'required|date|after_or_equal:today',
        'available_to' => 'required|date|after_or_equal:available_from',
    ]);

    $apartment = apartment_detail::findOrFail($id);

    if ($apartment->owner_id !== Auth::id()) {
        return response()->json(['error' => 'Unauthenticated 😊'], 403);
    }

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
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'الحجز غير موجود'
            ], 404);
        }

        if ($booking->apartment->owner_id != Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بالموافقة على هذا الحجز'
            ], 403);
        }

        $booking->update(['status' => 'approved']);

        return response()->json([
            'status' => true,
            'message' => 'تمت الموافقة على الحجز بنجاح',
            'data' => $booking
        ]);
    }

    public function reject($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'الحجز غير موجود'
            ], 404);
        }

        if ($booking->apartment->owner_id !=Auth::id()) {
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
    public function ownerBookings()
    {
        $bookings = Booking::whereHas('apartment', function($q){
            $q->where('owner_id', Auth::id());
        })->get();
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
    }
}

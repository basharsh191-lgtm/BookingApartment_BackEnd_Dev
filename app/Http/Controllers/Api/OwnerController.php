<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\apartment_detail;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
// <<<<<<< HEAD
//         $request->validate([
//             'apartment_description' => 'required|string|min:10',
//=======
        $validated = $request->validate([
//>>>>>>> 1f86d529aa4c51508bfc080f1bc5c495f8693e58
            'area' => 'required',
            'price' => 'required',
            'floorNumber'=> 'required',
            'roomNumber'=> 'required',
            'image' =>  'required|image|mimes:jpeg,png,jpg,gif',
            'available_from' => 'required|date',
            'available_to' => 'required|date|after_or_equal:start_date',
            'governorate' => 'required|string',
            'city'=>'required|string',
            'owner_id'=>'required',
            'is_furnished'=>'required',


        ]);
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('apartments', 'public');
            $request->merge(['image' => $imagePath]);
        }
        $validated['owner_id'] = Auth::id();
        $detail = apartment_detail::create($request->all());

        return response()->json($detail, 201);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, apartment_detail $apartment_details): JsonResponse
    {
        $request->validate([

            'apartment_description' => 'string',
            'image' => 'image|mimes:jpeg,png,jpg,gif',
            'available_from' => 'date',
            'available_to' => 'date|after_or_equal:start_date',
            'governorate' => 'string|max:50',
            'area' => 'numeric',
            'price' => 'numeric',
        ]);

        $data = $request->only([
            'apartment_description',
            'available_from',
            'available_to',
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

    public function setAvailability(Request $request, apartment_detail $apartment_details): JsonResponse
    {
        $request->validate([
            'available_from' => 'required|date',
            'available_to' => 'required|date|after_or_equal:start_date',
        ]);
        $apartment_details->update([

                'available_from' => $request->input('available_from'),
                'available_to' => $request->input('available_to'),
            ]);
        return response()->json([
            'message' => 'Availability updated successfully',
            'data' => $apartment_details,
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(apartment_detail $apartment_details): JsonResponse
    {
        $apartment_details->delete();

        return response()->json([
            'message' => 'Apartment deleted successfully'
        ], 200);
    }



    // المالك يوافق على الحجز
    public function approve($id): JsonResponse
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

    // المالك يرفض الحجز
    public function reject($id): JsonResponse
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

    // عرض كل الحجوزات للمالك
    public function ownerBookings(): JsonResponse
    {
        $bookings = Booking::whereHas('apartment', function($q){
            $q->where('owner_id', Auth::id());
        })->get();

        return response()->json([
            'status' => true,
            'message' => 'جميع الحجوزات الخاصة بك',
            'data' => $bookings
        ]);
    }

}

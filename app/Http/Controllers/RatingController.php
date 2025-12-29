<?php

namespace App\Http\Controllers;

use App\Models\ApartmentDetail;
use App\Models\Booking;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{

    //هاد شغال بس ناقصو انو حالة البيت يلي استأجر منهي ولا لا الخ
    public function storeRating(Request $request, $apartmentId)
    {
        $request->validate([
            'stars'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $userId = Auth::id();

        $hasFinishedBooking = Booking::where('tenant_id', $userId)
            ->where('apartment_id', $apartmentId)
            ->where('status', 'finished')
            ->exists();

        if (! $hasFinishedBooking) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تقييم هذه الشقة لأنك لم تحجزها أو لم ينتهِ الحجز بعد'
            ], 403);
        }

        //  منع التقييم أكثر من مرة
        $alreadyRated = Rating::where('user_id', $userId)
            ->where('apartment_id', $apartmentId)
            ->exists();

        if ($alreadyRated) {
            return response()->json([
                'success' => false,
                'message' => 'لقد قمت بتقييم هذه الشقة مسبقًا'
            ], 409);
        }

        $rating = Rating::create([
            'user_id'      => $userId,
            'apartment_id' => $apartmentId,
            'stars'        => $request->stars,
            'comment'      => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التقييم بنجاح',
            'data'    => $rating
        ], 201);
    }


    public function showRating($apartment)
    {
        $ratings = Rating::where('apartment_id', $apartment)
            ->with('user:id,FirstName,LastName')
            ->get();

        return response()->json([
            'success' => true,
            'ratings' => $ratings,
        ]);
    }
}

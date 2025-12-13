<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{

    //هاد شغال بس ناقصو انو حالة البيت يلي استأجر منهي ولا لا الخ
    public function storeRating(Request $request,$apartment)
    {
        $request->validate([
            'stars'=>'required|integer',
            'comment'=>'nullable|string'
        ]);
        $user_id=Auth::id();
        $hasbooking=Booking::where('tenant_id',$user_id)
        ->where('apartment_id',$apartment)->where('status','finished')->exists();
        if(!$hasbooking)
        {
            return response()->json([
                'success'=>false,
                'massage'=>'Erorr,انت ما حجزت الشقة لتقيمها 🙁'
            ]
            , 401);
        }
        Rating::create([
            'user_id'=>$user_id,
            'apartment_id'=>$apartment,
            'stars'=>$request->stars,
            'comment'=>$request->comment,
        ]);
        return response()->json([
            'success'=>'تم اضافة التقييم بنجاح',
        ], 200);
    }
}

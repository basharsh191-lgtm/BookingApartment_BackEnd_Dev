<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function showProfile($id)
    {
        $profile=User::findOrFail($id);
        $profile->makeHidden(['user_type','is_approved','created_at','updated_at']);
        return response()->json([
            'message' => 'The profile successfully displayed.',
            'profile' => $profile,
        ], 200);
    }
    public function UpdateProfile(Request $request)
    {
        $id=Auth::id();
        $user_id=Auth::user()->id;

        $validate=$request->validate([
            'FirstName'=>'nullable|string|max:255',
            'LastName'=>'nullable|string|max:255',
            //الرقم ما فيك تعدلو لانو unique
            'ProfileImage'=>'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
            'BirthDate'=>'nullable|date|before:today',
            'CardImage'=>'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
        ]);

        $profile=User::where('id',$id)->firstOrFail();
        $profile->makeHidden(['user_type','is_approved','created_at','updated_at']);
        $validate['id']=$user_id;


        if ($request->has('mobail'))
        {
            return response()->json([
            'message' => 'لا يمكن تعديل رقم الهاتف بعد التسجيل'
            ], 422);
        }
        $profile->update($validate);
        $profile->makeHidden(['user_type','is_approved','created_at','updated_at']);
        return response()->json([
            'message' => 'The profile successfully displayed.',
            'profile' => $profile,
        ], 200);
    }
}



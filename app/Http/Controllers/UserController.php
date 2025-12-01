<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestRegister;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(RequestRegister $request): JsonResponse
    {
        $validate=$request->validated();
        if($request->hasFile('ProfileImage'))
        {
            $path=$request->file('ProfileImage')->store('Profile','public');
            $validate['ProfileImage']=$path;
        }
        if($request->hasFile('CardImage'))
        {
            $path=$request->file('CardImage')->store('Card','public');
            $validate['CardImage']=$path;
        }

        $user=User::create([
            'FirstName'=>$request->FirstName,
            'LastName'=>$request->LastName,
            'mobile'=>$request->mobile,
            'password'=>Hash::make($request->password),
            'ProfileImage'=>$request->ProfileImage,
            'BirthDate'=>$request->BirthDate,
            'CardImage'=>$request->CardImage,
            'is_approved'=>0
        ]);

        return response()->json([
            'message' => 'Account created successfully! Please wait for admin approval.',
            'user_id' => $user->id,
            'approved' => false
        ], 200);
    }
    public function login(Request $request): JsonResponse
    {
        $request->validate([
        'mobile'=>'required|string',
        'password'=>'required|string|max:15',
        ]);
        if(!Auth::attempt($request->only('mobile','password')))
        {
            return response()->json([
                'massage'=>'Invalid phone of number or password'
            ], 401);
        }

        $user=User::where('mobile',$request->mobile)->firstOrFail();

    if (!$user->is_approved) {
            Auth::logout();
            return response()->json([
                'message' => 'Account awaiting admin approval'
            ], 403);
        }
        else if ($user->is_approved==-1) {
            Auth::logout();
            return response()->json([
                'message' => 'تم رفض طلبك من قبل الادمن '
            ], 403);
        }
            else
        $token = $user->createToken('auth_Token')->plainTextToken;
        return response()->json(['message' => 'Account log in successfully !', 'user' => $user, "Token" => $token], 200);
    }
    public function logout(Request $request): JsonResponse
    {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'The logged out successfully'], 200);
    }

}

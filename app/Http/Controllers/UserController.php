<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestRegister;
use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function sendOtpForForgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => 'required|string'
        ]);

        // تأكد أن المستخدم موجود
        $user = User::where('mobile', $request->mobile)->first();
        if (!$user) {
            return response()->json(['message' => 'رقم الهاتف غير مسجل'], 404);
        }

        // إنشاء OTP
        $otp = rand(100000, 999999);

        // حفظ أو تحديث الـ OTP
        UserOtp::updateOrCreate(
            ['mobile' => $request->mobile],
            ['otp' => $otp]
        );

        // إرسال عبر UltraMsg
        $params = [
            'token' => env('ULTRAMSG_TOKEN'),
            'to'    => $request->mobile,
            'body'  => "رمز استعادة كلمة السر هو: $otp"
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.ultramsg.com/".env('ULTRAMSG_INSTANCE')."/messages/chat",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => ["content-type: application/x-www-form-urlencoded"],
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ]);

        curl_exec($curl);
        curl_close($curl);

        return response()->json(['message' => 'تم إرسال رمز إعادة تعيين كلمة السر'], 200);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'mobile'   => 'required|string',
            'otp'      => 'required|string',
            'password' => 'required|string|min:6'
        ]);

        $otp = UserOtp::where('mobile', $request->mobile)->first();

        if (!$otp || $otp->otp != $request->otp) {
            return response()->json(['message' => 'رمز التحقق غير صحيح'], 401);
        }

        User::where('mobile', $request->mobile)->update([
            'password' => Hash::make($request->password)
        ]);

        // حذف OTP بعد الاستخدام
        $otp->delete();

        return response()->json(['message' => 'تم تغيير كلمة السر بنجاح'], 200);
    }

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
            'is_approved'=>1
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
        //- إذا كان المستخدم لديه عدة توكنات  فهذا يستهدف التوكن الحالي فقط
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'The logged out successfully'], 200);
    }

}

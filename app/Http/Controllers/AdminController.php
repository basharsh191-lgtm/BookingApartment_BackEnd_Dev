<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use \Illuminate\Http\JsonResponse;
class AdminController extends Controller
{
    public function pendingUsers(): JsonResponse
    {
        $users = User::where('is_approved', 0)->get();

        return response()->json([
            'status' => true,
            'message' => 'مستخدمون بانتظار الموافقة',
            'data' => $users
        ]);
    }
     public function AllUsers(): JsonResponse
     {
        $users = User::where('user_type'==!'admin')->get();

        return response()->json([
            'status' => true,
            'message' => 'مستخدمون  جميعا',
            'data' => $users
        ]);
    }
    public function approve($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        $user->is_approved = 1;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'تمت الموافقة على المستخدم بنجاح'
        ]);
    }
      public function rejected($id): JsonResponse
      {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        $user->is_approved = -1;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'تمت رفض طلب المستخدم بنجاح'
        ]);
    }
     public function deleteUsers($id): JsonResponse
     {
       $user=User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }
        $user->delete();

  return response()->json([
            'status' => true,
            'message' => 'تم حذف المستخم بنجاح'
        ]);
}
}

<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\ApartmentController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\OwnerNewController;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

    Route::get('/admin/users', [AdminController::class, 'pendingUsers']);//عرض كل يلي ما نقبلو
    Route::get('/admin/Allusers', [AdminController::class, 'AllUsers']);//عرض الكل المقبولين والغير مقبولين
    Route::post('/admin/users/approve/{id}', [AdminController::class, 'approve']);//قبول يوزر واحد محدد
    Route::post('/admin/users/rejected/{id}', [AdminController::class, 'rejected']);//رفض واحد محدد
    Route::post('/admin/users/delete/{id}', [AdminController::class, 'deleteUsers']);//حذف مستخدم معين


Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/forgot-password/send-otp', [UserController::class, 'sendOtpForForgotPassword']);
Route::post('/forgot-password/reset', [UserController::class, 'resetPassword']);


//المالك (OwnerController)
Route::middleware('auth:sanctum')->group(function () {
    // إضافة شقة جديدة
    Route::post('/owner/apartments', [OwnerNewController::class, 'store']);
    // تعديل بيانات الشقة
    Route::patch('/owner/apartments/{apartment_details}', [OwnerNewController::class, 'update']);
    // تعديل فترة التوافر للشقة
    Route::patch('/owner/apartments/{apartment_details}/availability', [OwnerNewController::class, 'setAvailability']);
    // حذف الشقة
    Route::delete('/owner/apartments/{apartment_details}', [OwnerNewController::class, 'destroy']);
    // الموافقة على حجز
    Route::patch('/owner/bookings/{id}/approve', [OwnerNewController::class, 'approve']);
    // رفض الحجز
    Route::patch('/owner/bookings/{id}/reject', [OwnerNewController::class, 'reject']);
    // عرض كل الحجوزات الخاصة بالمالك
    Route::get('/owner/bookings', [OwnerNewController::class, 'ownerBookings']);
});

// المستأجر (TenantController)
Route::middleware('auth:sanctum')->group(function () {
    // حجز شقة
    Route::post('/bookings', [BookingController::class, 'store']);
    // عرض جميع حجوزات المستأجر
    Route::get('/tenant/bookings', [TenantController::class, 'tenantBookings']);
    // إلغاء حجز
    Route::patch('/tenant/bookings/{id}/cancel', [TenantController::class, 'cancel']);
    // تعديل حجز
    Route::patch('/tenant/bookings/{id}/update', [TenantController::class, 'updateBooking']);
   //تقييم الشقة
    Route::post('user/rating/{apartment_id}',[RatingController::class,'storeRating']);
});

// عرض الشقق (ApartmentController)
Route::get('/apartments', [ApartmentController::class, 'index']);
//عرض تفاصيل الشقة
Route::get('/apartments/{apartmentDetail}', [ApartmentController::class, 'show']);
//عرض التقييم الخاص بالشقة
Route::get('/apartments/{apartmentDetail}', [RatingController::class, 'showRating']);


Route::get('showProfile/{id}',[ProfileController::class,'showProfile']);
Route::put('updateProfile',[ProfileController::class,'UpdateProfile'])->middleware('auth:sanctum');

Route::post('/user/searchApartment',[ApartmentController::class,'searchApartment']);
Route::post('/apartment/toggleFavorite/{apartmentId}',[TenantController::class,'toggleFavorite'])->middleware('auth:sanctum');
Route::get('/apartment/showFavorite',[TenantController::class,'showFavorite'])->middleware('auth:sanctum');

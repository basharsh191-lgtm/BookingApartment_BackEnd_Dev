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
    Route::post('/owner/apartments/{apartment_details}', [OwnerNewController::class, 'update']);
    // تعديل فترة التوافر للشقة
    Route::patch('/owner/apartments/{apartment_details}/availability', [OwnerNewController::class, 'setAvailability']);
    // حذف الشقة
    Route::delete('/owner/apartments/{apartment_details}', [OwnerNewController::class, 'destroy']);
    // الموافقة على حجز
    Route::patch('/owner/bookings/{id}/approve', [OwnerNewController::class, 'approve']);
    // رفض الحجز
    Route::patch('/owner/bookings/{id}/reject', [OwnerNewController::class, 'reject']);
    // عرض كل الحجوزات الخاصة بشقة معينة للمالك مع تفاصيل الشقة
    Route::get('/owner/apartments/{apartment_details}/bookings', [OwnerNewController::class, 'ownerApartmentBookings']);
    // عرش شقق المالك
    Route::get('/owner/apartments', [OwnerNewController::class, 'ownerApartments']);
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
    //فلترة حسب محافظة /مدينة/تاريخ متاح
    Route::get('/filter/goverCityDate',[ApartmentController::class,'filterApartment']);
    //فلترة سعر/واي فاي/عدد غرف
    Route::get('/filter/PriceWiFi',[ApartmentController::class,'filterApartmentPrice']);
    //رؤية المفضلة
    Route::get('/apartment/showFavorite',[TenantController::class,'showFavorite']);
     //لاضافة او ازالة الشقة من المفضلة
    Route::post('/apartment/toggleFavorite/{apartmentId}',[TenantController::class,'toggleFavorite']);
    //تحديث البروفايل
    Route::post('updateProfile',[ProfileController::class,'UpdateProfile']);
    //تغير كلمة المرور في بروفايل
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);

});

// عرض الشقق (ApartmentController)
Route::get('/apartments', [ApartmentController::class, 'index']);
//عرض تفاصيل الشقة
Route::get('/apartments/{apartmentDetail}', [ApartmentController::class, 'show']);
//عرض التقييم الخاص بالشقة
Route::get('/apartments/{apartmentDetail}/ratings', [RatingController::class, 'showRating']);
//عرض البروفايل
Route::get('showProfile/{id}',[ProfileController::class,'showProfile']);


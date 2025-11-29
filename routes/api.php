<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\ApartmentController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function(){
Route::get('/admin/users', [AdminController::class, 'pendingUsers']);//عرض كل يلي ما نقبلو
Route::get('/admin/Allusers', [AdminController::class, 'AllUsers']);//عرض الكل المقبولين والغير مقبولين
Route::post('/admin/users/approve/{id}', [AdminController::class, 'approve']);//قبول يوزر واحد محدد
Route::post('/admin/users/approveAll', [AdminController::class, 'approveAll']);//قبول الكل
Route::post('/admin/users/rejected/{id}', [AdminController::class, 'rejected']);//رفض واحد محدد
Route::post('/admin/users/rejectedAll', [AdminController::class, 'rejectedAll']);//رفض الكل
Route::post('/admin/users/delete/{id}', [AdminController::class, 'deleteUsers']);//حذف مستخدم معين
});


Route::post('register',[UserController::class,'register']);
Route::post('login',[UserController::class,'login']);
Route::post('logout',[UserController::class,'logout'])->middleware('auth:sanctum');


Route::get('apartments', [ApartmentController::class, 'index']); // all apartments
Route::post('apartments', [ApartmentController::class, 'store']);// add apartment
Route::get('apartments/{apartment_details}', [ApartmentController::class, 'show']);// show specific apartment
Route::put('apartments/{apartment_details}', [ApartmentController::class, 'update']);// update all information about specific apartment
Route::patch('apartments/{apartment_details}', [ApartmentController::class, 'update']);// change specific information
Route::delete('apartments/{apartment_details}', [ApartmentController::class, 'destroy']);
Route::patch('apartments/{apartment_details}/availability', [ApartmentController::class, 'setAvailability']);// change time




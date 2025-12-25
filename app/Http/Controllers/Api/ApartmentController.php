<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentDetailResource;
use App\Models\ApartmentDetail;
use App\Models\Apartment_details;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $apartmentDetail = ApartmentDetail::all();
        $apartmentDetail->load('governorate');
        $apartmentDetail->load('images');
        $apartmentDetail->load('displayPeriods');
        return response()->json($apartmentDetail);
    }

    /**
     * Store a newly created resource in storage.
     */


    /**
     * Display the specified resource.
     */
    public function show(ApartmentDetail $apartmentDetail): JsonResponse
    {
        $apartmentDetail->load(['ratings.user']);
        $apartmentDetail->load('images');

        $owner = User::select('id', 'FirstName', 'LastName', 'mobile')
            ->where('id', $apartmentDetail->owner_id)
            ->first();

        $apartmentDetail->owner_info = $owner;
        $apartmentDetail->load('governorate');
        $apartmentDetail->load('displayPeriods');

        return response()->json($apartmentDetail);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ApartmentDetail $apartmentDetail)
    {
        //
    }

    public function searchApartment(Request $request)
    {
        $query = ApartmentDetail::query();

        if ($request->has('governorate') && $request->input('governorate') != '') {
            $query->where('governorate', 'LIKE', "%{$request->input('governorate')}%");
        }

        if ($request->has('city') && $request->input('city') != '') {
            $query->where('city', 'LIKE', "%{$request->input('city')}%");
        }


        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }


        if ($request->has('roomNumber') && $request->input('roomNumber') != '') {
            $query->where('roomNumber', '=', $request->input('roomNumber'));
        }

        $apartments = $query->get();

        if ($apartments->count() == 0) {
            return response()->json(['message' => "Your search did not yield any results. "], 200);
        }

        return response()->json([
            'success' => true,
            'data' => $apartments,
            'count' => $apartments->count(),
        ], 200);
    }
}


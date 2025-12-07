<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\apartmentDetail;
use App\Models\Apartment_details;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Database\Eloquent\Collection
    {
        return apartmentDetail::all();

    }

    /**
     * Store a newly created resource in storage.
     */


    /**
     * Display the specified resource.
     */
    public function show(apartmentDetail $apartmentDetail): apartmentDetail
    {
        return $apartmentDetail;
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
    public function destroy(apartmentDetail $apartmentDetail)
    {
     //
    }

    public function searchApartment(Request $request)
{
    $query = apartmentDetail::query();

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


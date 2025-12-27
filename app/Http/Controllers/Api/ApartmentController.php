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
public function show(apartmentDetail $apartmentDetail): JsonResponse
{
    $apartmentDetail->load(['ratings.user']);
    $apartmentDetail->load('images');
    $owner = User::select('id', 'FirstName','LastName', 'mobile')
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
public function filterApartment(Request $request)
{
    $governorateId = $request->input('governorate_id');
    $city = $request->input('city');
    $startDate = $request->input('display_start_date');
    $endDate = $request->input('display_end_date');

    if (empty($governorateId) && empty($city) && empty($startDate) && empty($endDate))
    {
        return response()->json(['message' => 'Please provide at least one filter criteria.'], 204);
    }

    $results = apartmentDetail::query()
        ->filterByGovernorate($governorateId)
        ->filterByCity($city)
        ->availableForEntirePeriod($startDate, $endDate)
        ->get();
        $results->load('governorate');
        $results->load('images');
        $results->load('displayPeriods');
    return response()->json($results, 200);
}


public function filterApartmentPrice(Request $request)
{
    $query = apartmentDetail::query();
    $hasSearchCriteria=false;
    if ($request->filled('min_price')) {
        $query->where('price', '>=', $request->input('min_price'));
        $hasSearchCriteria=true;
    }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
            $hasSearchCriteria=true;
        }
        if ($request->has('roomNumber') && $request->input('roomNumber') != '')
        {
            $query->where('roomNumber', '=', $request->input('roomNumber'));
            $hasSearchCriteria=true;
        }
        if($request->has('free_wifi') && $request->input('free_wifi') != '')
        {
            $query->where('free_wifi','=',$request->input('free_wifi'));
            $hasSearchCriteria=true;
        }
        if(!$hasSearchCriteria)
        {
            return response()->json(['message'=>'No search criteria provided,please enter at least one filter'], 204);
        }
        $apartments = $query->get();
        if ($apartments->count() == 0) {
            return response()->json(['message' => "Sorry, no results found."], 204);
        }
        $apartments->load('governorate');
        $apartments->load('images');
        $apartments->load('displayPeriods');
        return response()->json([
            'success' => true,
            'data' => $apartments,
            'count' => $apartments->count(),
        ], 200);
    }
}


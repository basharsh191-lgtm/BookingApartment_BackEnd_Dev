<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentDetailResource;
use App\Models\ApartmentDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
/* Display a listing of the resource.
*/
    public function index()
    {
        $apartments = ApartmentDetail::with([
            'governorate',
            'images',
            'displayPeriods',
            'ratings'
        ])->get();

        $response = $apartments->map(function ($apartment) {
            return [
                'id' => $apartment->id,
                //'owner_id' => $apartment->owner_id,
                'apartment_description' => $apartment->apartment_description,
                'floorNumber' => $apartment->floorNumber,
                'roomNumber' => $apartment->roomNumber,
                'free_wifi' => $apartment->free_wifi,
                'available_from' => $apartment->available_from,
                'available_to' => $apartment->available_to,
                'city' => $apartment->city,
                'governorate' => $apartment->governorate,
                'area' => $apartment->area,
                'price' => $apartment->price,
                'images' => $apartment->images,
                'avg_rating' => $apartment->avg_rating,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }


    public function show(ApartmentDetail $apartmentDetail): JsonResponse
    {
        $apartmentDetail->load([
            'owner:id,FirstName,LastName,mobile',
            'ratings.user',
            'images',
            'governorate',
            'displayPeriods'
        ]);


        $owner = User::select('id')
            ->find($apartmentDetail->owner_id);

        $response = [
            'id' => $apartmentDetail->id,
            'owner_id' => $apartmentDetail->owner?->id,

            'apartment_description' => $apartmentDetail->apartment_description,
            'floorNumber' => $apartmentDetail->floorNumber,
            'roomNumber' => $apartmentDetail->roomNumber,
            'free_wifi' => $apartmentDetail->free_wifi,
            'available_from' => $apartmentDetail->available_from,
            'available_to' => $apartmentDetail->available_to,
            'city' => $apartmentDetail->city,
            'governorate' => $apartmentDetail->governorate,
            'area' => $apartmentDetail->area,
            'price' => $apartmentDetail->price,
            'scheduled_for_deletion' => $apartmentDetail->scheduled_for_deletion,
            'images' => $apartmentDetail->images,
            'displayPeriods' => $apartmentDetail->displayPeriods,
            'ratings' => $apartmentDetail->ratings()->with('user')->get()->map(function($rating) {
                return [
                    'id' => $rating->id,
                    'stars' => $rating->stars,
                    'user' => [
                        'FirstName' => $rating->user->FirstName ?? null,
                        'LastName' => $rating->user->LastName ?? null,
                    ],
                    'created_at' => $rating->created_at,
                ];
            }),
            'avg_rating' => $apartmentDetail->avg_rating,
        ];

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }
/* Update the specified resource in storage.
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

        if (empty($governorateId) && empty($city) && empty($startDate) && empty($endDate)) {
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
        $hasSearchCriteria = false;
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
            $hasSearchCriteria = true;
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
            $hasSearchCriteria = true;
        }
        if ($request->has('roomNumber') && $request->input('roomNumber') != '') {
            $query->where('roomNumber', '=', $request->input('roomNumber'));
            $hasSearchCriteria = true;
        }
        if ($request->has('free_wifi') && $request->input('free_wifi') != '') {
            $query->where('free_wifi', '=', $request->input('free_wifi'));
            $hasSearchCriteria = true;
        }
        if (!$hasSearchCriteria) {
            return response()->json(['message' => 'No search criteria provided,please enter at least one filter'], 204);
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

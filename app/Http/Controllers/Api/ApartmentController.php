<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apartment_detail;
use App\Models\Apartment_details;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Database\Eloquent\Collection
    {
        return Apartment_detail::all();

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'area' => 'required',
            'price' => 'required',
            'image' =>  'required|image|mimes:jpeg,png,jpg,gif',
            'owner_id' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'governorate' => 'required|string',

        ]);
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('apartments', 'public');
            $request->merge(['image' => $imagePath]);
        }
        $detail = Apartment_detail::create($request->all());

        return response()->json($detail, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Apartment_detail $apartment_details): Apartment_detail
    {
        return $apartment_details;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Apartment_detail $apartment_details): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'department_description' => 'string',
            'image' => 'image|mimes:jpeg,png,jpg,gif',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'governorate' => 'string|max:50',
            'area' => 'numeric',
            'price' => 'numeric',
        ]);

        $data = $request->only([
            'department_description',
            'start_date',
            'end_date',
            'governorate',
            'area',
            'price',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('apartments', 'public');
        }

        $apartment_details->update($data);

        return response()->json($apartment_details);
    }

    public function setAvailability(Request $request, Apartment_detail $apartment_details): \Illuminate\Http\JsonResponse
    {
       $request->validate([
           'start_date' => 'required|date',
           'end_date' => 'required|date|after_or_equal:start_date',
       ]);
       $apartment_details->update([
           'start_date'=> $request->input('start_date'),
           'end_date' => $request->input('end_date'),
       ]);
       return response()->json([
           'message' => 'Availability updated successfully',
           'data' => $apartment_details,
       ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(apartment_detail $apartment_details): \Illuminate\Http\JsonResponse
    {
        $apartment_details->delete();

        return response()->json([
            'message' => 'Apartment deleted successfully'
        ], 200);
    }
}

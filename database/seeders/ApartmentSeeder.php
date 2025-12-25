<?php

namespace Database\Seeders;

use App\Models\ApartmentDetail;


use App\Models\ApartmentImage;
use App\Models\DisplayPeriod;
use Illuminate\Database\Seeder;


class ApartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apartment1=ApartmentDetail::create([
            'owner_id' => 2,
            'apartment_description' => 'Inside the building: It is preferable to choose the apartment in the middle,
             so that the remaining space or floor space is available.',
            'floorNumber' => 1,
            'roomNumber' => 5,
            'free_wifi' => 1,
            'available_from' => '20-12-2025',
            'available_to' => '20-12-2026',
            'status' => 'available',
            'governorate_id' => 3,
            'city' => 'Al hadara',
            'area' => 65.02,
            'price' => 100.20,
        ]);

        DisplayPeriod::create([
            'apartment_id' => $apartment1->id,
            'display_start_date' => '20-12-2025',
            'display_end_date' =>'20-12-2026',
        ]);

        ApartmentImage::create([
            'apartment_details_id' => $apartment1->id,
            'image_path' => 'apartments/download.jpg']);
        ApartmentImage::create([
            'apartment_details_id' => $apartment1->id,
            'image_path' => 'apartments/download (3).jpg']);

        $apartment2=ApartmentDetail::create([
            'owner_id' => 3,
            'apartment_description' => 'Inside the building: It is preferable to choose the apartment in the middle,
            so that the remaining space or floor space is available.',
            'floorNumber' => 1,
            'roomNumber' => 5,
            'free_wifi' => 1,
            'available_from' => '20-12-2025',
            'available_to' => '20-12-2026',
            'status' => 'available',
            'governorate_id' => 3,
            'city' => 'Karam al sham',
            'area' => 65.02,
            'price' => 500.20,
        ]);
        DisplayPeriod::create([
            'apartment_id' => $apartment2->id,
            'display_start_date' => '20-12-2025',
            'display_end_date' => '20-12-2026',
        ]);
        ApartmentImage::create([
            'apartment_details_id' => $apartment2->id,
            'image_path' => 'apartments/download (2).jpg']);
        ApartmentImage::create([
            'apartment_details_id' => $apartment2->id,
            'image_path' => 'apartments/download (4).jpg']);

        $apartment3=ApartmentDetail::create([
            'owner_id' => 2,
            'apartment_description' => 'Inside the building: It is preferable to choose the apartment in the middle,
             so that the remaining space or floor space is available.',
            'floorNumber' => 1,
            'roomNumber' => 2,
            'free_wifi' => 0,
            'available_from' => '20-12-2025',
            'available_to' => '20-12-2026',
            'status' => 'available',
            'governorate_id' => 1,
            'city' => 'Maza',
            'area' => 65.02,
            'price' => 1000.20,
       ]);
        DisplayPeriod::create([
            'apartment_id' => $apartment3->id,
            'display_start_date' => '20-12-2025',
            'display_end_date' => '20-12-2026',
        ]);
        ApartmentImage::create([
            'apartment_details_id' => $apartment3->id,
            'image_path' => 'apartments/download (1).jpg']);
        ApartmentImage::create([
            'apartment_details_id' => $apartment3->id,
            'image_path' => 'apartments/download.jpg']);
    }
}

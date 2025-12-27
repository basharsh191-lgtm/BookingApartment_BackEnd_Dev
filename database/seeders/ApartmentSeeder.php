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
        $apartment1 = apartmentDetail::create([
            'owner_id' => 2,
            'apartment_description' => 'Luxurious in the heart of the city with a stunning panoramic view.',
            'floorNumber' => 1,
            'roomNumber' => 5,
            'free_wifi' => 1,
            'available_from' => '2026-01-10',
            'available_to' => '2028-12-20',
            'status' => 'available',
            'governorate_id' => 4,
            'city' => 'Al hadara',
            'area' => 65.02,
            'price' => 100.20,
        ]);
        DisplayPeriod::create([
            'apartment_id' => $apartment1->id,
            'display_start_date' => '2026-01-10',
            'display_end_date' =>'2028-12-20',
        ]);
        ApartmentImage::create([
            'apartment_details_id' => $apartment1->id,
            'image_path' => 'apartments/download.jpg']);
        ApartmentImage::create([
            'apartment_details_id' => $apartment1->id,
            'image_path' => 'apartments/download (3).jpg']);

        $apartment2 = ApartmentDetail::create([
            'owner_id' => 3,
            'apartment_description' => 'A luxurious duplex apartment featuring a modern and elegant design with high-quality decor.',
            'floorNumber' => 2,
            'roomNumber' => 5,
            'free_wifi' => 1,
            'available_from' => '2026-01-10',
            'available_to' => '2028-12-20',
            'status' => 'available',
            'governorate_id' => 4,
            'city' => 'Karam al sham',
            'area' => 65.02,
            'price' => 500.20,
        ]);
        DisplayPeriod::create([
            'apartment_id' => $apartment2->id,
            'display_start_date' => '2026-01-10',
            'display_end_date' =>'2028-12-20',
        ]);
        ApartmentImage::create([
            'apartment_details_id' => $apartment2->id,
            'image_path' => 'apartments/download (2).jpg']);
        ApartmentImage::create([
            'apartment_details_id' => $apartment2->id,
            'image_path' => 'apartments/download (4).jpg']);

        $apartment3 = ApartmentDetail::create([
            'owner_id' => 2,
            'apartment_description' => 'An ideal family apartment in a quiet and safe location, featuring spacious areas suitable for large families.',
            'floorNumber' => 3,
            'roomNumber' => 4,
            'free_wifi' => 0,
            'available_from' => '2026-01-10',
            'available_to' => '2028-01-10',
            'status' => 'available',
            'governorate_id' => 1,
            'city' => 'Maza',
            'area' => 65.02,
            'price' => 1000.20,
        ]);
        DisplayPeriod::create([
            'apartment_id' => $apartment3->id,
            'display_start_date' => '2026-01-10',
            'display_end_date' =>'2028-12-20',
        ]);
        ApartmentImage::create([
            'apartment_details_id' => $apartment3->id,
            'image_path' => 'apartments/download (1).jpg']);
        ApartmentImage::create([
            'apartment_details_id' => $apartment3->id,
            'image_path' => 'apartments/download.jpg']);

        $apartment4 = ApartmentDetail::create([
            'owner_id' => 3,
            'apartment_description' => 'Mini Malstick design with modern touches.',
            'floorNumber' => 5,
            'roomNumber' => 5,
            'free_wifi' => 0,
            'available_from' => '2026-01-10',
            'available_to' => '2028-12-20',
            'status' => 'available',
            'governorate_id' => 1,
            'city' => 'Kafr souseh',
            'area' => 65.02,
            'price' => 1000.20,
        ]);
        DisplayPeriod::create([
            'apartment_id' => $apartment4->id,
            'display_start_date' => '2026-01-10',
            'display_end_date' =>'2028-12-20',
        ]);
        ApartmentImage::create([
            'apartment_details_id' => $apartment4->id,
            'image_path' => 'apartments/download (1).jpg']);
        ApartmentImage::create([
            'apartment_details_id' => $apartment4->id,
            'image_path' => 'apartments/download.jpg']);

        $apartment5 = ApartmentDetail::create([
            'owner_id' => 4,
            'apartment_description' => 'Enjoy luxurious living in an apartment with a breathtaking sea view',
            'floorNumber' => 1,
            'roomNumber' => 2,
            'free_wifi' => 0,
            'available_from' => '2026-01-10',
            'available_to' => '2028-12-20',
            'status' => 'available',
            'governorate_id' => 5,
            'city' => 'Maza',
            'area' => 65.02,
            'price' => 1000.20,
        ]);
        DisplayPeriod::create([
            'apartment_id' => $apartment5->id,
            'display_start_date' => '2026-01-10',
            'display_end_date' =>'2028-12-20',    
        ]);
        ApartmentImage::create([
            'apartment_details_id' => $apartment5->id,
            'image_path' => 'apartments/download (1).jpg']);
        ApartmentImage::create([
            'apartment_details_id' => $apartment5->id,
            'image_path' => 'apartments/download.jpg']);
    }
}

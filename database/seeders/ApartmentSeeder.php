<?php

namespace Database\Seeders;

use App\Models\apartmentDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        apartmentDetail::create([
            'owner_id' => 2,
            'apartment_description' => 'Inside the building: It is preferable to choose the apartment in the middle, so that the remaining space or floor space is available.',
            'floorNumber' => 1,
            'roomNumber' => 5,
            'free_wifi' => 1,
            'available_from' => '1990-01-01',
            'available_to' => '1990-02-01',
            'status' => 'available',
            'governorate' => 'homs',
            'city'=>'Al hadara',
            'area' => 65.02,
            'price' => 100.20,
        ]);
          apartmentDetail::create([
            'owner_id' => 3,
            'apartment_description' => 'Inside the building: It is preferable to choose the apartment in the middle, so that the remaining space or floor space is available.',
            'floorNumber' => 1,
            'roomNumber' => 5,
            'free_wifi' => 1,
            'available_from' => '1990-01-01',
            'available_to' => '1990-02-01',
            'status' => 'available',
            'governorate' => 'homs',
            'city'=>'Karam al sham',
            'area' => 65.02,
            'price' => 500.20,
        ]);
         apartmentDetail::create([
            'owner_id' => 2,
            'apartment_description' => 'Inside the building: It is preferable to choose the apartment in the middle, so that the remaining space or floor space is available.',
            'floorNumber' => 1,
            'roomNumber' => 2,
            'free_wifi' => 0,
            'available_from' => '2025-01-01',
            'available_to' => '2025-02-01',
            'status' => 'available',
            'governorate' => 'Damascuse',
            'city'=>'Maza',
            'area' => 65.02,
            'price' => 1000.20,
        ]);
    }
}

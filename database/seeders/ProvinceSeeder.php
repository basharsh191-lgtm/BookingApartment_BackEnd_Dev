<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run()
{
    $provinces = [
        'Damascus',
        'Rif Dimashq',
        'Aleppo',
        'Homs',
        'Latakia',
        'Hama',
        'Idlib',
        'Tartus',
        'Al-Hasakah',
        'Deir ez-Zor',
        'As-Suwayda',
        'Raqqa',
        'Daraa',
        'Quneitra',
    ];

    foreach ($provinces as $province) {
        Province::create(['name' => $province]);
    }
}
}

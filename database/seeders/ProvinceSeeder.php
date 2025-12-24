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
        'دمشق',
        'ريف دمشق',
        'حلب',
        'حمص',
        'حماة',
        'اللاذقية',
        'طرطوس',
        'إدلب',
        'دير الزور',
        'الرقة',
        'الحسكة',
        'السويداء',
        'درعا',
        'القنيطرة',
    ];

    foreach ($provinces as $province) {
        Province::create(['name' => $province]);
    }
}
}

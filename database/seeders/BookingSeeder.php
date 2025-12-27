<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    Booking::create([
        'apartment_id'=>2,
        'tenant_id'=>2,
        'start_date'=>'21-6-2026',
        'end_date'=>'20-6-2027',
        'status'=>'pending',
        'total_price'=>100.2,
    ]);
    Booking::create([
        'apartment_id'=>1,
        'tenant_id'=>3,
        'start_date'=>'21-6-2026',
        'end_date'=>'20-6-2027',
        'status'=>'accepted',
        'total_price'=>100.2,
    ]);
    Booking::create([
        'apartment_id'=>3,
        'tenant_id'=>3,
        'start_date'=>'21-6-2026',
        'end_date'=>'20-6-2027',
        'status'=>'finished',
        'total_price'=>100.2,
    ]);
    }
}

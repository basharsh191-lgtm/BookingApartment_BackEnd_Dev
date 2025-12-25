<?php

namespace App\Console\Commands;

use App\Models\ApartmentDetail;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteScheduledApartments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apartments:delete-scheduled';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete apartments scheduled for deletion after all bookings end';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting scheduled apartments deletion...');

        $apartments = ApartmentDetail::where('scheduled_for_deletion', true)
            ->get();

        $this->info("Found {$apartments->count()} apartments scheduled for deletion");

        foreach ($apartments as $apartment) {
            $this->line("Checking apartment ID: {$apartment->id}");

            $hasActiveBookings = Booking::where('apartment_id', $apartment->id)
                ->whereIn('status', ['approved', 'pending'])
                ->where('end_date', '>=', now())
                ->exists();


            if (!$hasActiveBookings) {
                // حذف جميع الحجوزات المرتبطة
                Booking::where('apartment_id', $apartment->id)->delete();
                $apartment->delete();

                $this->info(" Apartment {$apartment->id} deleted successfully.");
            } else {
                $this->line(" Apartment {$apartment->id} has active bookings, skipping...");
            }
        }

        $this->info('Scheduled apartments deletion completed.');

        return 0;
    }
}

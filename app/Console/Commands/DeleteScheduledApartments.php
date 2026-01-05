<?php

namespace App\Console\Commands;

use App\Models\ApartmentDetail;
use App\Models\Booking;
use App\Notifications\ApartmentDeletionNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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

        // أضف تأريخ للتتبع
        $deletedCount = 0;
        $skippedCount = 0;

        $apartments = ApartmentDetail::with('owner')
            ->where('scheduled_for_deletion', true)
            ->get();

        $this->info("Found {$apartments->count()} apartments scheduled for deletion");

        foreach ($apartments as $apartment) {
            $this->line("Checking apartment ID: {$apartment->id} - '{$apartment->apartment_description}'");

            // التحقق من الحجوزات النشطة
            $activeBookings = Booking::where('apartment_id', $apartment->id)
                ->whereIn('status', ['approved', 'pending', 'accepted']) // أضفت 'accepted' للتوافق مع كودك
                ->where('end_date', '>=', Carbon::now()->toDateString())
                ->get();

            if ($activeBookings->isEmpty()) {
                try {
                    // حذف جميع الحجوزات المرتبطة
                    Booking::where('apartment_id', $apartment->id)->delete();

                    // حفظ معلومات الشقة قبل الحذف للإشعار
                    $apartmentTitle = $apartment->apartment_description;
                    $owner = $apartment->owner;

                    // حذف الشقة
                    $apartment->delete();

                    // إرسال إشعار للمالك
                    if ($owner) {
                        $owner->notify(new ApartmentDeletionNotification(
                            (object) ['apartment_description' => $apartmentTitle], // كائن مؤقت
                            'deleted',
                            'Apartment "' . $apartmentTitle . '" has been automatically deleted after all bookings ended.'
                        ));
                    }

                    $this->info(" Apartment {$apartment->id} deleted successfully.");
                    $deletedCount++;

                    // تسجيل في اللوغ
                    Log::info("Apartment {$apartment->id} deleted via scheduled command", [
                        'apartment_id' => $apartment->id,
                        'title' => $apartmentTitle,
                        'owner_id' => $owner ? $owner->id : null,
                        'deleted_at' => now(),
                    ]);

                } catch (\Exception $e) {
                    $this->error(" Failed to delete apartment {$apartment->id}: " . $e->getMessage());
                    Log::error("Failed to delete scheduled apartment {$apartment->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                $this->line("⏳ Apartment {$apartment->id} has {$activeBookings->count()} active booking(s), skipping...");

                // يمكنك إضافة إشعار تذكير للمالك
                if ($apartment->owner) {
                    $latestEndDate = $activeBookings->max('end_date');
                    $apartment->owner->notify(new ApartmentDeletionNotification(
                        $apartment,
                        'scheduled',
                        'Apartment "' . $apartment->apartment_description .
                        '" still has ' . $activeBookings->count() .
                        ' active booking(s). Will be deleted after ' .
                        Carbon::parse($latestEndDate)->format('Y-m-d')
                    ));
                }

                $skippedCount++;
            }
        }

        $this->info(" Scheduled apartments deletion completed.");
        $this->info(" Results: {$deletedCount} deleted, {$skippedCount} skipped");

        // تسجيل النتائج
        Log::info("Scheduled apartments deletion command executed", [
            'total_apartments' => $apartments->count(),
            'deleted' => $deletedCount,
            'skipped' => $skippedCount,
            'executed_at' => now(),
        ]);

        return 0;
    }
}

<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RentalCancelled extends Notification
{
    use Queueable;

    public User $user;
    public Booking $booking;
    public $apartment;
    public string $message;

    public function __construct(User $user, Booking $booking, string $message)
    {
        $this->user = $user;
        $this->booking = $booking;
        $this->apartment = $booking->apartment; // تعيين Apartment تلقائياً
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Rental Request Cancelled',
            'message' => $this->user->FirstName . ' has cancelled a rental request for apartment: ' . $this->apartment->apartment_description,
            'type' => 'rental_cancelled',
            'apartment_id' => $this->apartment->id,
            'user_id' => $this->user->id,
            'url' => '/owner/apartments/' . $this->apartment->id,
            'icon' => 'x-circle',
            'color' => 'danger',
        ];
    }
}

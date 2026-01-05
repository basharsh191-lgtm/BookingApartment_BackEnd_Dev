<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RentalRequestAccepted extends Notification
{
    use Queueable;

    protected $apartment;
    protected $owner;

    public function __construct($apartment, $owner)
    {
        $this->apartment = $apartment;
        $this->owner = $owner;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Your Request Has Been Accepted! ✅',
            'message' => 'Congratulations! Your rental request for apartment: ' . $this->apartment->title . ' has been accepted.',
            'type' => 'rental_request_accepted',
            'apartment_id' => $this->apartment->id,
            'owner_id' => $this->owner->id,
            'url' => '/renter/reservations',
            'icon' => 'check-circle',
            'color' => 'success',
        ];
    }
}

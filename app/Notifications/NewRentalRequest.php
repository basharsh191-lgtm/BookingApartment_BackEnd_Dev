<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewRentalRequest extends Notification
{
    use Queueable;

    protected $apartment;
    protected $user;

    public function __construct($apartment, $user)
    {
        $this->apartment = $apartment;
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Rental Request ',
            'message' => $this->user->name . ' has submitted a rental request for apartment: ' . $this->apartment->title,
            'type' => 'new_rental_request',
            'apartment_id' => $this->apartment->id,
            'user_id' => $this->user->id,
            'url' => '/owner/rental-requests',
            'icon' => 'file-text',
            'color' => 'info',
        ];
    }
}

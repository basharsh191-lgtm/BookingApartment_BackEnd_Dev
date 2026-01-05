<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApartmentUpdated extends Notification
{
    use Queueable;

    protected $apartment;

    public function __construct($apartment)
    {
        $this->apartment = $apartment;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Apartment Modified ✏',
            'message' => 'Apartment details have been modified: ' . $this->apartment->title,
            'type' => 'apartment_updated',
            'apartment_id' => $this->apartment->id,
            'url' => '/owner/apartments/' . $this->apartment->id . '/edit',
            'icon' => 'edit',
            'color' => 'warning',
        ];
    }
}

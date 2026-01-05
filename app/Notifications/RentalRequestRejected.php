<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RentalRequestRejected extends Notification
{
    use Queueable;

    protected $apartment;
    protected $owner;
    protected $reason;

    public function __construct($apartment, $owner, $reason = null)
    {
        $this->apartment = $apartment;
        $this->owner = $owner;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $message = 'تم رفض طلبك لإيجار الشقة: ' . $this->apartment->title;
        if ($this->reason) {
            $message .= ' (السبب: ' . $this->reason . ')';
        }

        return [
            'title' => 'Your Request Has Been Rejected!',
            'message' => $message,
            'type' => 'rental_request_rejected',
            'apartment_id' => $this->apartment->id,
            'owner_id' => $this->owner->id,
            'reason' => $this->reason,
            'url' => '/apartments',
            'icon' => 'x-circle',
            'color' => 'danger',
        ];
    }
}

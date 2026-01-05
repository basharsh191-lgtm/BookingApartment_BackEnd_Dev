<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApartmentDeletionNotification extends Notification
{
    use Queueable;

    protected $apartment;
    protected $action; // 'scheduled' or 'deleted'
    protected $message;

    public function __construct($apartment, $action, $message = null)
    {
        $this->apartment = $apartment;
        $this->action = $action;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $apartmentTitle = is_object($this->apartment) && isset($this->apartment->apartment_description)
            ? $this->apartment->apartment_description
            : (is_string($this->apartment) ? $this->apartment : 'Apartment');

        if ($this->action === 'scheduled') {
            return [
                'title' => 'Apartment Deletion Scheduled ⏰',
                'message' => $this->message ?? 'Apartment "' . $apartmentTitle . '" has been scheduled for automatic deletion after all active bookings end.',
                'type' => 'apartment_deletion_scheduled',
                'action' => 'scheduled',
                'url' => '/owner/apartments',
                'icon' => 'clock',
                'color' => 'warning',
                'timestamp' => now()->toDateTimeString(),
            ];
        } else {
            return [
                'title' => 'Apartment Deleted Successfully ✅',
                'message' => $this->message ?? 'Apartment "' . $apartmentTitle . '" has been deleted successfully.',
                'type' => 'apartment_deleted',
                'action' => 'deleted',
                'url' => '/owner/apartments',
                'icon' => 'trash',
                'color' => 'danger',
                'timestamp' => now()->toDateTimeString(),
            ];
        }
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\DayOffChangeRequest;

class DayOffChangeRequestStatusChanged extends Notification
{
    use Queueable;

    protected $request;
    protected $status;

    public function __construct(DayOffChangeRequest $request, $status)
    {
        $this->request = $request;
        $this->status  = $status;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject("Your Day Off Change Request has been {$this->status}")
                    ->greeting("Hello {$notifiable->first_name},")
                    ->line("Your request for changing your day off on {$this->request->old_date->format('M d, Y')} to {$this->request->new_date->format('M d, Y')} has been {$this->status}.")
                    ->line('Thank you for using our HR portal.');
    }
}

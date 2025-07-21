<?php

namespace App\Notifications;

use App\Models\OffsetRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OffsetRequestSubmitted extends Notification
{
    use Queueable;

    public $offsetRequest;

    public function __construct(OffsetRequest $offsetRequest)
    {
        $this->offsetRequest = $offsetRequest;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $employeeName = $this->offsetRequest->employee->user->name ?? 'An employee';

        return (new MailMessage())
            ->subject('New Offset Request Submitted')
            ->greeting("Hello {$notifiable->name},")
            ->line("{$employeeName} submitted an offset request.")
            ->line('Date: ' . $this->offsetRequest->date)
            ->line('Time: ' . $this->offsetRequest->time_start . ' to ' . $this->offsetRequest->time_end)
            ->line('Project/Description: ' . $this->offsetRequest->project)
            ->line('Number of Hours: ' . $this->offsetRequest->number_of_hours)
            ->action('View Request', url(route('offset_requests.show', $this->offsetRequest->id)))
            ->line('Please review and take action.');
    }
}

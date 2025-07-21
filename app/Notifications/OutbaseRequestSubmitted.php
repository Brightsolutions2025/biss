<?php

namespace App\Notifications;

use App\Models\OutbaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OutbaseRequestSubmitted extends Notification
{
    use Queueable;

    public $outbaseRequest;

    public function __construct(OutbaseRequest $outbaseRequest)
    {
        $this->outbaseRequest = $outbaseRequest;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $employeeName = $this->outbaseRequest->employee->user->name ?? 'An employee';

        return (new MailMessage())
            ->subject('New Outbase Request Submitted')
            ->greeting("Hello {$notifiable->name},")
            ->line("{$employeeName} submitted an outbase request.")
            ->line("Date: {$this->outbaseRequest->date}")
            ->line("Time: {$this->outbaseRequest->time}")
            ->line("Location: {$this->outbaseRequest->location}")
            ->line("Reason: {$this->outbaseRequest->reason}")
            ->action('View Request', url(route('outbase_requests.show', $this->outbaseRequest->id)))
            ->line('Please review and take action.');
    }
}

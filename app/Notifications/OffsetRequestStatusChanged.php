<?php

namespace App\Notifications;

use App\Models\OffsetRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OffsetRequestStatusChanged extends Notification
{
    use Queueable;

    public $offsetRequest;
    public $status;

    public function __construct(OffsetRequest $offsetRequest, $status)
    {
        $this->offsetRequest = $offsetRequest;
        $this->status        = $status;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $statusLabel = ucfirst($this->status);

        $statusMessage = match ($this->status) {
            'approved' => 'has been approved.',
            'rejected' => 'was rejected.',
            default    => 'was updated.',
        };

        $mail = (new MailMessage())
            ->subject("Offset Request {$statusLabel}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your offset request dated {$this->offsetRequest->date} {$statusMessage}")
            ->line("Project: {$this->offsetRequest->project}")
            ->line("Time: {$this->offsetRequest->time_start} to {$this->offsetRequest->time_end}");

        if ($this->status === 'rejected' && $this->offsetRequest->rejection_reason) {
            $mail->line("Rejection Reason: {$this->offsetRequest->rejection_reason}");
        }

        return $mail
            ->action('View Request', route('offset_requests.show', $this->offsetRequest->id))
            ->line('Thank you for using our system!');
    }
}

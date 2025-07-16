<?php

namespace App\Notifications;

use App\Models\OutbaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OutbaseRequestStatusChanged extends Notification
{
    use Queueable;

    public $outbaseRequest;
    public $status;

    public function __construct(OutbaseRequest $outbaseRequest, $status)
    {
        $this->outbaseRequest = $outbaseRequest;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $statusLabel = ucfirst($this->status);
        $statusMessage = match ($this->status) {
            'approved' => 'has been approved. Congratulations!',
            'rejected' => 'was rejected. Please contact your supervisor if you need clarification.',
            default => 'was updated.',
        };

        $mail = (new MailMessage)
            ->subject("Outbase Request {$statusLabel}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your outbase request dated {$this->outbaseRequest->date} {$statusMessage}")
            ->line("Location: {$this->outbaseRequest->location}")
            ->line("Reason: {$this->outbaseRequest->reason}");

        if ($this->status === 'rejected' && $this->outbaseRequest->rejection_reason) {
            $mail->line("Rejection Reason: {$this->outbaseRequest->rejection_reason}");
        }

        return $mail
            ->action('View Request', route('outbase_requests.show', $this->outbaseRequest->id))
            ->line('Thank you for using our system!');
    }
}

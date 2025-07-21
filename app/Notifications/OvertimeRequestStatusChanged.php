<?php

namespace App\Notifications;

use App\Models\OvertimeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimeRequestStatusChanged extends Notification
{
    use Queueable;

    public $overtimeRequest;
    public $status;

    public function __construct(OvertimeRequest $overtimeRequest, $status)
    {
        $this->overtimeRequest = $overtimeRequest;
        $this->status          = $status;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $statusLabel = ucfirst($this->status);

        if ($this->status === 'approved') {
            $statusMessage = 'has been approved. Congratulations!';
        } elseif ($this->status === 'rejected') {
            $statusMessage = 'was rejected. Please contact your supervisor if you need clarification.';
        } else {
            $statusMessage = 'was updated.';
        }

        $mail = (new MailMessage())
            ->subject("Overtime Request {$statusLabel}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your overtime request dated {$this->overtimeRequest->date} {$statusMessage}")
            ->line("Reason: {$this->overtimeRequest->reason}");

        // ✅ Only add rejection reason if rejected
        if ($this->status === 'rejected' && $this->overtimeRequest->rejection_reason) {
            $mail->line("Rejection Reason: {$this->overtimeRequest->rejection_reason}");
        }

        return $mail
            ->action('View Request', route('overtime_requests.show', $this->overtimeRequest->id))
            ->line('Thank you for using our system!');
    }
}

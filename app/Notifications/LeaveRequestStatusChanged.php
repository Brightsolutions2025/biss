<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestStatusChanged extends Notification
{
    use Queueable;

    public $leaveRequest;
    public $status;

    public function __construct(LeaveRequest $leaveRequest, $status)
    {
        $this->leaveRequest = $leaveRequest;
        $this->status       = $status;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $statusLabel = ucfirst($this->status);
        $message     = $this->status === 'approved'
            ? 'has been approved.'
            : 'was rejected.';

        $mail = (new MailMessage())
            ->subject("Leave Request {$statusLabel}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your leave request from {$this->leaveRequest->date_start} to {$this->leaveRequest->date_end} {$message}")
            ->line("Reason: {$this->leaveRequest->reason}");

        if ($this->status === 'rejected' && $this->leaveRequest->rejection_reason) {
            $mail->line("Rejection Reason: {$this->leaveRequest->rejection_reason}");
        }

        return $mail
            ->action('View Request', route('leave_requests.show', $this->leaveRequest->id))
            ->line('Thank you for using our system!');
    }
}

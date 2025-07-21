<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification
{
    use Queueable;

    public $leaveRequest;

    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $employeeName = $this->leaveRequest->employee->user->name ?? 'An employee';

        return (new MailMessage())
            ->subject('New Leave Request Submitted')
            ->greeting("Hello {$notifiable->name},")
            ->line("{$employeeName} submitted a leave request.")
            ->line('Date: ' . $this->leaveRequest->date_start . ' to ' . $this->leaveRequest->date_end)
            ->line('Reason: ' . $this->leaveRequest->reason)
            ->action('View Request', route('leave_requests.show', $this->leaveRequest->id))
            ->line('Please review and take action.');
    }
}

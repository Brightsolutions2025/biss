<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Employee;

class DtrReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $employee;
    public $status;
    public $payrollPeriod;

    public function __construct(Employee $employee, $status, $payrollPeriod)
    {
        $this->employee = $employee;
        $this->status = $status;
        $this->payrollPeriod = $payrollPeriod;
    }

    public function build()
    {
        return $this->subject('Reminder: Submit your DTR')
                    ->view('emails.dtr_reminder');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Submit your DTR',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.dtr_reminder',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

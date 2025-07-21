<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function build()
    {
        return $this->subject('Scheduled Email')
                    ->view('emails.test');
    }
}

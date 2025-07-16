<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;

class SendTestEmail extends Command
{
    protected $signature = 'email:send-test';
    protected $description = 'Send a test email every minute';

    public function handle()
    {
        Mail::to('boropeza@bsm.ph')->send(new TestEmail());
        $this->info('Email sent.');
    }
}

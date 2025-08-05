<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LarkWebhookController extends Controller
{
    public function receive(Request $request)
    {
        // Lark may send a challenge verification on setup
        if ($request->has('challenge')) {
            return response()->json(['challenge' => $request->challenge]);
        }

        // Log the data for debugging
        Log::info('Lark Webhook Payload:', $request->all());

        // You can handle different event types
        $eventType = $request->input('header.event_type');
        $eventData = $request->input('event');

        if ($eventType === 'attendance.checkin' || $eventType === 'attendance.checkout') {
            // Store or process the event
            // Example: Save to database or dispatch job
        }

        return response()->json(['status' => 'received']);
    }
}

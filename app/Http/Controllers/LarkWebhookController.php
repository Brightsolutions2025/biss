<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LarkWebhookController extends Controller
{
    public function receive(Request $request)
    {
        if ($request->type === 'url_verification') {
            return response()->json(['challenge' => $request->challenge]);
        }

        // Handle other types (event_callback, etc)
        return response()->json(['status' => 'ok']);
    }
}

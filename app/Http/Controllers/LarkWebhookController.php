<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LarkWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Log or dump to see if it works
        \Log::info('Lark webhook received', ['data' => $request->all()]);
        return response()->json(['status' => 'ok']);
    }
}

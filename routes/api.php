<?php

use App\Http\Controllers\LarkWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/webhooks/lark', function (Request $request) {
    $data = $request->all();

    // Handle URL verification
    if (($data['type'] ?? '') === 'url_verification') {
        return response()->json(['challenge' => $data['challenge']]);
    }

    // You can handle other event types below (e.g., attendance events)
    return response()->json(['message' => 'ok']);
});

Route::post('/webhook/lark', [LarkWebhookController::class, 'handle']);
//Route::post('/webhooks/lark', [LarkWebhookController::class, 'receive']);

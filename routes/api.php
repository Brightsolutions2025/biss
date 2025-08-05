<?php

use App\Http\Controllers\LarkWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

//Route::post('/webhooks/lark', [LarkWebhookController::class, 'handle']);
Route::post('/webhooks/lark', [LarkWebhookController::class, 'receive']);

<?php

use Illuminate\Support\Facades\Route;
use Telegram\Bot\Laravel\Facades\Telegram;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return response()->json(['API MAK3RS']);
});

// Example of POST Route:
Route::post(env('TELEGRAM_BOT_TOKEN', '').'/webhook', function () {
    $updates = Telegram::getWebhookUpdates();

    return 'ok';
});
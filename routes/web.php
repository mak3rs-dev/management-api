<?php

use Illuminate\Support\Facades\Route;

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

// Put this inside either the POST route '/<token>/webhook' closure (see below) or
// whatever Controller is handling your POST route
$updates = Telegram::getWebhookUpdates();

// Example of POST Route:
Route::post('/<token>/webhook', function () {
    $updates = Telegram::getWebhookUpdates();

    return 'ok';
});
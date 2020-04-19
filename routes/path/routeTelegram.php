<?php

Route::group(['prefix' => 'telegram'], function () {
    Route::post('sendmessage', 'SendMessageTelegramController@SendMessage');
});
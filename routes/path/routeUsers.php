<?php

Route::group(['prefix' => 'users'], function () {
    Route::get('communities', 'UserController@communities');
});
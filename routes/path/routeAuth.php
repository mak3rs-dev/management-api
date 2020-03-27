<?php

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('register', 'AuthController@register');
    Route::get('verified-hash/{hash}', 'AuthController@verifiedHash')->name('verified_hash');
    Route::get('me', 'AuthController@me');
    Route::get('logout', 'AuthController@logout');
    Route::get('refresh', 'AuthController@refresh');

    Route::get('not-login', function () {
        return response()->json(['errors' => 'Tienes que loguearte']);
    })->name('not-login');
});
<?php

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('register', 'AuthController@register');
    Route::get('verified-hash/{hash}', 'AuthController@verifiedHash')->name('verified_hash');
    Route::post('recovery-password', 'AuthController@recoveryPasword');
    Route::post('recovery-hash', 'AuthController@recoveryHash');
    Route::get('me', 'AuthController@me');
    Route::get('logout', 'AuthController@logout');
    Route::get('refresh', 'AuthController@refresh');
    Route::patch('policy', 'AuthController@updatePolicy');

    Route::get('not-login', function () {
        return response()->json(['error' => 'Tienes que loguearte'], 500);
    })->name('not-login');

    Route::get('not-policy', function () {
        return response()->json(['error' => 'Tienes que aceptar las politicas de privacidad'], -100);
    })->name('not-policy');
});
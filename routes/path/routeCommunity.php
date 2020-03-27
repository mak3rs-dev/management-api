<?php

Route::group(['prefix' => 'communities'], function () {
    Route::get('all', 'CommunityController@communities');
    Route::get('alias/{alias}', 'CommunityController@alias');
    Route::post('create', 'CommunityController@create');
    Route::put('update', 'CommunityController@update');
    Route::delete('delete', 'CommunityController@delete');
});
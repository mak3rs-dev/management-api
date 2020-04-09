<?php

Route::group(['prefix' => 'pieces'], function () {
    Route::get('all', 'PiecesController@pieces');
    Route::get('{uuid}', 'PiecesController@uuid');
    Route::post('create', 'PiecesController@create');
    Route::put('update', 'PiecesController@update');
    Route::delete('delete', 'PiecesController@delete');
});
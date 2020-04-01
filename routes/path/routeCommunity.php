<?php

Route::group(['prefix' => 'communities'], function () {
    Route::get('all', 'CommunityController@communities');
    Route::get('alias/{alias?}', 'CommunityController@alias');
    Route::post('create', 'CommunityController@create');
    Route::put('update', 'CommunityController@update');
    Route::delete('delete', 'CommunityController@delete');
    Route::get('ranking/{alias?}/{export?}', 'InCommunityController@ranking');
    Route::post('join', 'UserController@joinCommunity');
    Route::get('pieces/{alias}', 'PiecesController@piecesOfCommunity');
    Route::post('piece/add-or-update', 'StockControlController@addOrUpdatePieceStock');
    Route::post('piece/add-collect', 'CollectControlController@addPieceCollection');
    Route::get('collect-control/{alias?}/{export?}', 'CollectControlController@getCollectControl');
});
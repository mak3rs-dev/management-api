<?php

Route::group(['prefix' => 'communities'], function () {
    Route::get('all', 'CommunityController@communities');
    Route::get('alias/{alias?}', 'CommunityController@alias');
    Route::post('create', 'CommunityController@create');
    Route::put('update', 'CommunityController@update');
    Route::delete('delete', 'CommunityController@delete');
    Route::get('ranking/{alias?}/{export?}', 'InCommunityController@ranking');
    Route::post('join', 'UserController@joinCommunity');
    //Route::get('pieces/{alias}', 'PiecesController@piecesOfCommunity');
    Route::post('piece/add-or-update', 'StockControlController@addOrUpdatePieceStock');
    Route::post('collect/add', 'CollectControlController@add');
    Route::put('collect/update', 'CollectControlController@update');
    Route::get('collect/{community}', 'CollectControlController@getCollectControl');
    Route::post('collect/add', 'CollectControlController@add');
    Route::put('collect/update', 'CollectControlController@update');
    Route::post('materials/add-or-update', 'MaterialsRequestController@addOrUpdate');
    Route::get('materials/{community}', 'MaterialsRequestController@get');
});
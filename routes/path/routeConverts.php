<?php

Route::group(['prefix' => 'converts'], function () {
    Route::post('img-to-base64', 'ImageConvertController@ImgToBase64');
});
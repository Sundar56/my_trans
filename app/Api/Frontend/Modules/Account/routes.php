<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Api\Frontend\Modules\Account\Controllers',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    Route::get('/viewmyprofile', 'AccountController@viewMyProfile')->name('contractor.myprofile.view');
    Route::post('/updatemyprofile', 'AccountController@updateMyProfile')->name('contractor.myprofile.update');

});
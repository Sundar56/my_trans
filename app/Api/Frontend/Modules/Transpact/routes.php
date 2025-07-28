<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Api\Frontend\Modules\Transpact\Controllers',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    Route::post('/createtranspact', 'TranspactController@createTranspact')->name('customer.transpact.create');
    Route::post('/viewtranspact', 'TranspactController@viewTranspactHistory')->name('customer.transpact.view');

});






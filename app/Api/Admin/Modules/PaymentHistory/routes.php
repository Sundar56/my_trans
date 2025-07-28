<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Api\Admin\Modules\PaymentHistory\Controllers',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    Route::get('/getpaymenthistory', 'PaymentHistoryController@getPaymentHistoryList')->name('admin.payment.get');
    Route::post('/admintranspacthistory', 'PaymentHistoryController@adminTranspactHistory')->name('admin.transpact.history');

});
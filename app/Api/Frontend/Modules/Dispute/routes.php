<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Api\Frontend\Modules\Dispute\Controllers',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    Route::post('/createdispute', 'DisputeController@createDispute')->name('common.dispute.create');
    Route::get('/viewdispute', 'DisputeController@viewDispute')->name('common.dispute.view');
    Route::get('/getdisputelist', 'DisputeController@getDisputeList')->name('common.dispute.get');
    Route::post('/disputechat', 'DisputeController@disputeChat')->name('common.dispute.chat');

});
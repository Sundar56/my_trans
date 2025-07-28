<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Api\Admin\Modules\Dispute\Controllers',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
   Route::get('/admindisputelist', 'AdminDisputeController@getDisputeList')->name('admin.dispute.get');
   Route::get('/admindisputeview', 'AdminDisputeController@viewDisputeByAdmin')->name('admin.dispute.view');
   Route::post('/admindisputecreate', 'AdminDisputeController@createDisputeByAdmin')->name('admin.dispute.create');
   Route::post('/admindisputechat', 'AdminDisputeController@adminDisputeChat')->name('admin.dispute.chat');
   Route::post('/admindisputeresolve', 'AdminDisputeController@disputeResolve')->name('admin.dispute.resolve');

});
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use App\Events\InviteChannelBroadcast;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/broadcast-test', function () {
    $data = [
        'message' => 'Broad catsting message from laravel',
        'forContractor' => 1729,
        'projectId' => 78633,
    ];

    broadcast(new InviteChannelBroadcast($data, 'invitechannel'));

    return ['status' => 'event broadcasted', 'data' => $data];
});

Route::group([], function () {

    Route::any('webhook/callbacktranspact', 'App\Api\Common\WebhookController@callbackTranspact');
});

Route::group([
    'namespace' => 'App\Api\Frontend\Modules\Project\Controllers',

], function () {
    Route::get('/viewstatus', 'ProjectController@viewStatus')->name('customer.project.status');
});

Broadcast::routes();

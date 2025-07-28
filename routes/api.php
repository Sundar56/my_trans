<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Broadcast;

$proxy_enabled   = getenv('PROXY_ENABLED');
if (!empty($proxy_enabled) && $proxy_enabled == true) {
    $proxy_url    = getenv('PROXY_URL');
    $proxy_schema = getenv('PROXY_SCHEMA');

    if (!empty($proxy_url)) {
        URL::forceRootUrl($proxy_url);
    }

    if (!empty($proxy_schema)) {
        URL::forceScheme($proxy_schema);
    }
}

Route::group(['namespace' => 'App\Http\Controllers\Api'], function()
{  
    Route::get('/getroles', 'LoginController@getRoles')->name('getroles');
    Route::post('/signup', 'LoginController@userSignup'); 
    Route::post('/login', 'LoginController@userLogin'); 
    Route::post('/forgotpassword', 'LoginController@forgotPassword')->name('forgotpassword');
    Route::post('/activate-account', 'LoginController@activateAccount')->name('activate.account');

});

Route::group([
    'namespace' => 'App\Http\Controllers\Api',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    Route::post('/signout', 'LoginController@logOut')->name('signout');
    Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
    Route::get('/moduleslist', 'DashboardController@modulesList')->name('modules');
    Route::post('/resetpassword', 'LoginController@resetPassword')->name('resetpassword');
    Route::get('/getnotification', 'NotificationController@notificationList')->name('notifications');
    Route::get('/notificationcount', 'NotificationController@notificationCount')->name('notificationscount');
    Route::post('/readmessage', 'NotificationController@readMessage')->name('readmessage');
    Route::post('/deletenotification', 'NotificationController@notificationDelete')->name('deletenotification');

});

Route::group([
    
], function () {

    Route::any('/webhook/callbacktranspact', 'App\Api\Common\WebhookController@callbackTranspact'); 
});

Broadcast::routes();

<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'App\Api\Admin\Modules\Adminlogin\Controllers'], function()
{  
    Route::post('/adminlogin', 'AdminloginController@adminLogin')->name('adminlogin');
    Route::post('/adminforgotpassword', 'AdminloginController@forgotPassword')->name('admin.forgotpassword');

});

Route::group([
    'namespace' => 'App\Api\Admin\Modules\Adminlogin\Controllers',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    
    Route::post('/adminlogout', 'AdminloginController@logOut')->name('admin.signout');
    Route::post('/adminresetpassword', 'AdminloginController@adminResetPassword')->name('admin.resetpassword');
    Route::post('/useractivate', 'AdminloginController@activateUser')->name('admin.useractivate');

});
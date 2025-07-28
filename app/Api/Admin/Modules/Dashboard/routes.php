<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Api\Admin\Modules\Dashboard\Controllers',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    Route::get('/admindashboard', 'AdmindashboardController@index')->name('admin.dashboard');
    Route::get('/adminmodules', 'AdmindashboardController@adminModulesList')->name('admin.modules');

});
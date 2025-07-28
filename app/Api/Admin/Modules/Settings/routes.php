<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Api\Admin\Modules\Settings\Controller',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    Route::get('/viewadminsettings', 'AdminSettingsController@viewAdminSettings')->name('admin.settings.view');
    Route::post('/updateadminsettings', 'AdminSettingsController@updateAdminSettings')->name('admin.settings.update');

});
<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Api\Admin\Modules\Users\Controllers',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    Route::get('/userslist', 'UserController@usersList')->name('admin.users.get');
    Route::post('/usersview', 'UserController@viewUser')->name('admin.users.view');
    Route::post('/usersupdate', 'UserController@updateUser')->name('admin.users.update');
    Route::post('/userscreate', 'UserController@createUsers')->name('admin.users.create');
    Route::get('/usersrole', 'UserController@getUsersRole')->name('admin.users.getroles');

    Route::get('/viewadminprofile', 'UserController@veiwAdminProfile')->name('admin.profile.view');
    Route::post('/updateadminprofile', 'UserController@updateAdminProfile')->name('admin.profile.update');
});
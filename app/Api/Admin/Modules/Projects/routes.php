<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Api\Admin\Modules\Projects\Controllers',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    Route::get('/adminprojectslist', 'TotalProjectController@getProjectList')->name('admin.project.get');
    Route::get('/adminviewprojects', 'TotalProjectController@viewProject')->name('admin.project.view');
    Route::post('/adminupdateprojects', 'TotalProjectController@updateProject')->name('admin.project.update');

    Route::post('/adminupdatetasks', 'TotalProjectController@updateTasksbyAdmin')->name('admin.tasks.update');

});
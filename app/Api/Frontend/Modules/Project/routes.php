<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Api\Frontend\Modules\Project\Controllers',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {

    Route::get('/projectlist', 'ProjectController@getProjectList')->name('contractor.project.get');
    Route::post('/createproject', 'ProjectController@createProject')->name('contractor.project.create');
    Route::get('/viewproject', 'ProjectController@viewProject')->name('contractor.project.view');
    Route::post('/updateproject', 'ProjectController@updateProject')->name('contractor.project.update');
    Route::post('/projectstatus', 'ProjectController@acceptInvitation')->name('contractor.project.updatestatus');
    Route::post('/reinviteproject', 'ProjectController@reInviteProject')->name('contractor.project.reinvite');
    Route::get('/viewcontract', 'ProjectController@viewProjectContract')->name('contractor.project.contractview');
    Route::get('/projecthistory', 'ProjectController@projectHistory')->name('contractor.project.history');
    Route::post('/projectdelete', 'ProjectController@projectDelete')->name('contractor.project.delete');
    Route::post('/verifyproject', 'ProjectController@verifyProject')->name('contractor.project.verify');
    Route::post('/agreement', 'ProjectController@projectAgreement')->name('contractor.project.agreement');
    Route::post('/updatesignature', 'ProjectController@updateUserSignature')->name('contractor.project.updatesign');
    Route::post('/agreementinfo', 'ProjectController@agreementInfo')->name('contractor.project.agreementinfo');
    Route::post('/requestpayment', 'ProjectController@requestPayment')->name('contractor.project.paymentrequest');

    Route::post('/createtasks', 'ProjectController@createTasks')->name('contractor.tasks.create');
    Route::get('/tasklist', 'ProjectController@taskList')->name('contractor.tasks.get');
    Route::post('/updatetasks', 'ProjectController@updateTasks')->name('contractor.tasks.update');
    Route::post('/taskview', 'ProjectController@taskView')->name('contractor.tasks.view');
    Route::post('/taskstatus', 'ProjectController@taskStatusUpdate')->name('contractor.tasks.taskstatus');
    Route::post('/verifytask', 'ProjectController@verifyTask')->name('contractor.tasks.verifytask');
    Route::post('/taskdelete', 'ProjectController@taskDelete')->name('contractor.tasks.delete');
});

Route::group([
    'namespace' => 'App\Http\Controllers\Api',
    'middleware' => [
        \App\Http\Middleware\CustomTokenValidation::class,
    ]
], function () {
    Route::post('/invitemail', 'NotificationController@invitationMail')->name('contractor.project.invitemail');
});
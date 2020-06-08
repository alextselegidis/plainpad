<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'prefix' => 'v1',
    'namespace' => 'V1',
], function () {
    // Application
    Route::get('', 'ApplicationController@retrieve');
    Route::post('', 'ApplicationController@install');

    // Sessions
    Route::post('sessions', 'SessionsController@create');
    Route::delete('sessions/{token}', 'SessionsController@delete');

    // Users
    Route::post('users/recovery', 'UsersController@recoverPassword');

    Route::group([
        'middleware' => 'auth'
    ], function () {
        // Notes
        Route::get('notes', 'NotesController@list');
        Route::get('notes/{id}', 'NotesController@retrieve');
        Route::post('notes', 'NotesController@create');
        Route::put('notes/{id}', 'NotesController@update');
        Route::delete('notes/{id}', 'NotesController@delete');
        Route::patch('notes/{id}/pinned', 'NotesController@pinned');

        // Users
        Route::get('users/{id}', 'UsersController@retrieve');
        Route::put('users/{id}', 'UsersController@update');

        Route::group([
            'middleware' => 'admin'
        ], function () {
            // Application
            Route::put('', 'ApplicationController@update');

            // Settings
            Route::get('settings', 'SettingsController@retrieve');
            Route::put('settings', 'SettingsController@update');

            // Users
            Route::get('users', 'UsersController@list');
            Route::post('users', 'UsersController@create');
            Route::delete('users/{id}', 'UsersController@delete');
        });
    });
});

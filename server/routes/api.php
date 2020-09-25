<?php

use App\Http\Controllers\V1\ApplicationController;
use App\Http\Controllers\V1\NotesController;
use App\Http\Controllers\V1\SessionsController;
use App\Http\Controllers\V1\SettingsController;
use App\Http\Controllers\V1\UsersController;
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
], function () {
    // Application
    Route::get('', [ApplicationController::class, 'retrieve']);
    Route::post('', [ApplicationController::class, 'install']);

    // Sessions
    Route::post('sessions', [SessionsController::class, 'create']);
    Route::delete('sessions/{token}', [SessionsController::class, 'delete']);

    // Users
    Route::post('users/recovery', [UsersController::class, 'recoverPassword']);

    Route::group([
        'middleware' => 'auth'
    ], function () {
        // Notes
        Route::get('notes', [NotesController::class, 'list']);
        Route::get('notes/{id}', [NotesController::class, 'retrieve']);
        Route::post('notes', [NotesController::class, 'create']);
        Route::put('notes/{id}', [NotesController::class, 'update']);
        Route::delete('notes/{id}', [NotesController::class, 'delete']);
        Route::patch('notes/{id}/pinned', [NotesController::class, 'pinned']);

        // Users
        Route::get('users/{id}', [UsersController::class, 'retrieve']);
        Route::put('users/{id}', [UsersController::class, 'update']);

        Route::group([
            'middleware' => 'admin'
        ], function () {
            // Application
            Route::put('', [ApplicationController::class, 'update']);
            Route::post('refresh', [ApplicationController::class, 'refresh']);

            // Settings
            Route::get('settings', [SettingsController::class, 'retrieve']);
            Route::put('settings', [SettingsController::class, 'update']);

            // Users
            Route::get('users', [UsersController::class, 'list']);
            Route::post('users', [UsersController::class, 'create']);
            Route::delete('users/{id}', [UsersController::class, 'delete']);
        });
    });
});

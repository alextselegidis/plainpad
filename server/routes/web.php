<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group([
    'prefix' => 'v1',
    'namespace' => 'V1'
], function () use ($router) {
    // Application
    $router->get('', 'ApplicationController@retrieve');
    $router->post('', 'ApplicationController@install');

    // Sessions
    $router->post('sessions', 'SessionsController@create');
    $router->delete('sessions/{token}', 'SessionsController@delete');

    // Users
    $router->post('users/recovery', 'UsersController@recoverPassword');

    $router->group([
        'middleware' => 'auth'
    ], function () use ($router) {
        // Notes
        $router->get('notes', 'NotesController@list');
        $router->get('notes/{id}', 'NotesController@retrieve');
        $router->post('notes', 'NotesController@create');
        $router->put('notes/{id}', 'NotesController@update');
        $router->delete('notes/{id}', 'NotesController@delete');
        $router->patch('notes/{id}/pinned', 'NotesController@pinned');

        // Users
        $router->get('users/{id}', 'UsersController@retrieve');
        $router->put('users/{id}', 'UsersController@update');

        $router->group([
            'middleware' => 'admin'
        ], function () use ($router) {
            // Application
            $router->put('', 'ApplicationController@update');

            // Settings
            $router->get('settings', 'SettingsController@retrieve');
            $router->put('settings', 'SettingsController@update');

            // Users
            $router->get('users', 'UsersController@list');
            $router->post('users', 'UsersController@create');
            $router->delete('users/{id}', 'UsersController@delete');
        });
    });
});

<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('index');
});

Route::group(['prefix' => 'api'], function()
{
	Route::resource('authenticate', 'AuthenticateController', ['only' => ['index']]);
	Route::post('authenticate', 'AuthenticateController@authenticate');
	Route::post('register', 'AuthenticateController@register');
	Route::get('authenticate/with/{provider}', 'AuthenticateController@authenticateWith');
	Route::get('authenticate/callback/{provider}', 'AuthenticateController@authenticateCallback');

	Route::get('authenticate/user', 'AuthenticateController@getAuthenticatedUser');
});

<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/
Route::get('/', ['uses' => 'HomeController@index', 'as' => 'home.index']);

Route::get('skypeclass', function(){

    //SkypeClass::sendMessage(["login" => "from", "password" => "secret"], "to", "message");
    //SkypeClass::sendFriendInvite(["login" => "from", "password" => "secret"], "to", "message");
    //SkypeClass::sendFrom(["login" => "from", "password" => "secret"], "to", "message");
    //SkypeClass::sendRandom("to", "message");
});



Route::group(['prefix' => 'user', 'middleware' => 'auth'], function () {
    Route::get('/', ['uses' => 'UserController@index', 'as' => 'user.index']);
    Route::get('/create', ['uses' => 'UserController@create', 'as' => 'user.create']);
    Route::post('/create', ['uses' => 'UserController@store', 'as' => 'user.store']);
    Route::get('/edit/{id}', ['uses' => 'UserController@edit', 'as' => 'user.edit']);
    Route::post('/edit/{id}', ['uses'=>'UserController@update', 'as'=>'user.update']);
    Route::get('/delete/{id}', ['uses'=>'UserController@delete', 'as'=>'user.delete']);
});

Route::group(['prefix' => 'accounts-data', 'middleware' => 'auth'], function () {
    Route::get('/', ['uses' => 'AccountsDataController@index', 'as' => 'accounts_data.index']);
    Route::get('vk', ['uses' => 'AccountsDataController@vk', 'as' => 'accounts_data.vk']);
    Route::get('ok', ['uses' => 'AccountsDataController@ok', 'as' => 'accounts_data.ok']);
    Route::get('emails', ['uses' => 'AccountsDataController@emails', 'as' => 'accounts_data.emails']);
    Route::get('/create/{type}', ['uses' => 'AccountsDataController@create', 'as' => 'accounts_data.create']);
    Route::post('/create/{type}', ['uses' => 'AccountsDataController@store', 'as' => 'accounts_data.store']);
    Route::get('/edit/{id}', ['uses' => 'AccountsDataController@edit', 'as' => 'accounts_data.edit']);
    Route::post('/edit/{id}', ['uses'=>'AccountsDataController@update', 'as'=>'accounts_data.update']);
    Route::get('/delete/{id}', ['uses'=>'AccountsDataController@delete', 'as'=>'accounts_data.delete']);
    Route::get('/destroy', ['uses'=>'AccountsDataController@destroy', 'as'=>'accounts_data.destroy']);
    Route::get('/destroy-vk', ['uses'=>'AccountsDataController@destroyVk', 'as'=>'accounts_data.destroy.vk']);
    Route::get('/destroy-ok', ['uses'=>'AccountsDataController@destroyOk', 'as'=>'accounts_data.destroy.ok']);
    Route::get('/destroy-emails', ['uses'=>'AccountsDataController@destroyEmails', 'as'=>'accounts_data.destroy.emails']);
    Route::post('/vk-upload', ['uses'=>'AccountsDataController@vkupload', 'as'=>'accounts_data.vk.upload']);
    Route::post('/ok-upload', ['uses'=>'AccountsDataController@okupload', 'as'=>'accounts_data.ok.upload']);
    Route::post('/mails-upload', ['uses'=>'AccountsDataController@mailsupload', 'as'=>'accounts_data.mails.upload']);
});

Route::group(['prefix' => 'smtp-base', 'middleware' => 'auth'], function () {
    Route::get('/', ['uses' => 'SmtpBaseController@index', 'as' => 'smtpbase.index']);
    Route::get('/create', ['uses' => 'SmtpBaseController@create', 'as' => 'smtpbase.create']);
    Route::post('/create', ['uses' => 'SmtpBaseController@store', 'as' => 'smtpbase.store']);
    Route::get('/edit/{id}', ['uses' => 'SmtpBaseController@edit', 'as' => 'smtpbase.edit']);
    Route::post('/edit/{id}', ['uses'=>'SmtpBaseController@update', 'as'=>'smtpbase.update']);
    Route::get('/delete/{id}', ['uses'=>'SmtpBaseController@delete', 'as'=>'smtpbase.delete']);
});

Route::group(['prefix' => 'settings', 'middleware' => 'auth'], function () {
    Route::get('/', ['uses' => 'SettingsController@index', 'as' => 'settings.index']);
    Route::post('/', ['uses' => 'SettingsController@store', 'as' => 'settings.store']);
});

Route::group(['prefix' => 'search-queries', 'middleware' => 'auth'], function () {
    Route::get('/', ['uses' => 'SearchQueriesController@index', 'as' => 'search_queries.index']);
});
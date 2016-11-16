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
Route::get('/', ['uses' =>'HomeController@index', 'as' =>'home']);

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


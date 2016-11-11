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
use App\MyFacade\Skype;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/skype', function(){
    // new Skype("petlyakssphone@gmail.com", "azerty99", ["method" => "sendFriendInvite", "to" => "petliak.serhii", "message" => "Testing"]); //sendFriendInvite()
    // new Skype("petlyakssphone@gmail.com", "azerty99", ["method" => "sendMessage", "to" => "petliak.serhii", "message" => "Тестовое сообщение"]); //sendMessage()
    new Skype("petlyakssphone@gmail.com", "azerty99", ["method" => "sendFrom", "to" => "petliak.serhii", "message" => "Тестовое сообщение"]);
});
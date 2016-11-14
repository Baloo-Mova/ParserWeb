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

Route::get('/', function () {
    return view('welcome');
});

Route::get('skypeclass', function(){

    //SkypeClass::sendMessage(["login" => "from", "password" => "pass"], "to", "MyMessage");
    //SkypeClass::sendFriendInvite(["login" => "from", "password" => "pass"], "to", "MyMessage");
    //SkypeClass::sendFrom(["login" => "from", "password" => "pass"], "to", "MyMessage");
    //SkypeClass::sendRandom("to", "MyRandomMessage14");
});

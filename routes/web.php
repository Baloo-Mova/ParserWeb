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


//Route::get('skypeclass', function(){
     // new Skype("petlyakss@gmail.com", "azerty99", ["method" => "sendFriendInvite", "to" => "petliak.serhii", "message" => "Testing"]); //sendFriendInvite()
    // new Skype("petlyakssphone@gmail.com", "azerty99", ["method" => "sendMessage", "to" => "petliak.serhii", "message" => "Тестовое сообщение"]); //sendMessage()
     // new Skype("petliak.serhii", "azerty99", ["method" => "sendFrom", "to" => "papapetlyak@gmail.com", "message" => "Тестовое сообщение"]);
     // new Skype("petlyakss@gmail.com", "azerty99", ["method" => "sendRandom", "to" => "petliak.serhii", "message" => "Тестовое сообщение - Random(message)"]);
//});
Route::get('skypeclass', function(){

    $imagepath = SkypeClass::two('image.jpg');

    print_r($imagepath);

});

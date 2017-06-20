<?php

use Illuminate\Http\Request;

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

Route::get('/actualParsed/{taskId}/{lastId}/{pageNumber}', ["uses" => "APIController@getTaskParsedInfo", "as" => "get.task.parsed.info"]);
Route::get('/selectEmailTemplate/{id}', ["uses" => "APIController@getSelectEmailTemplate", "as" => "get.select.email.template"]);
Route::get('/getProxy/{type}',["uses"=> "APIController@getRandomProxy", "as"=>"get.proxy"]);
Route::post('/addAccs/{type}',["uses"=> "APIController@addAccs", "as"=>"add.accs"]);

Route::get('/getEmailSendData',['uses'=>'APIController@getEmailSendData','as'=>'send.email-data']);
Route::post('/getEmailSendResult',['uses'=>'APIController@getEmailSendResult','as'=>'get.email-data']);

/*fb*/
Route::post('/updateFB',['uses'=>'APIController@updateFBAcc','as'=>'update.acc.fb']);
Route::get('/getAccFB/{type}',['uses'=>'APIController@getFBAcc', 'as'=>'get.acc.fb']);
Route::get('/getTaskFB',['uses'=>'APIController@getTaskFB', 'as'=>'get.task.fb']);
Route::post('/updateTaskFB',['uses'=>'APIController@updateTaskFB', 'as'=>'update.task.fb']);
Route::get('/getFBLinks',['uses'=>'APIController@getFBLinks', 'as'=>'get.links.fb']);
Route::post('/setFBLinks',['uses'=>'APIController@setFBLinks', 'as'=>'set.links.fb']);
Route::get('/getQueryFB',['uses'=>'APIController@getQueryFB', 'as'=>'get.query.fb']);
Route::get('/updateQueryFB',['uses'=>'APIController@updateQueryFB', 'as'=>'update.query.fb']);



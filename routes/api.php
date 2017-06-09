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

Route::get('/actualParsed/{taskId}/{lastId}', ["uses" => "APIController@getActualTaskData", "as" => "get.actual.parsed.data"]);
Route::get('/paginateParsed/{page}/{taskId}', ["uses" => "APIController@getPaginateTaskData", "as" => "get.paginate.parsed.data"]);
Route::get('/selectEmailTemplate/{id}', ["uses" => "APIController@getSelectEmailTemplate", "as" => "get.select.email.template"]);
Route::get('/getProxy/{type}',["uses"=> "APIController@getRandomProxy", "as"=>"get.proxy"]);
Route::post('/addAccs/{type}',["uses"=> "APIController@addAccs", "as"=>"add.accs"]);


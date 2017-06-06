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
    Route::get('tw', ['uses' => 'AccountsDataController@tw', 'as' => 'accounts_data.tw']);
    Route::get('fb', ['uses' => 'AccountsDataController@fb', 'as' => 'accounts_data.fb']);
    Route::get('ins', ['uses' => 'AccountsDataController@ins', 'as' => 'accounts_data.ins']);
    Route::get('emails', ['uses' => 'AccountsDataController@emails', 'as' => 'accounts_data.emails']);
    Route::get('/create/{type}', ['uses' => 'AccountsDataController@create', 'as' => 'accounts_data.create']);
    Route::post('/create/{type}', ['uses' => 'AccountsDataController@store', 'as' => 'accounts_data.store']);
    Route::get('/edit/{id}', ['uses' => 'AccountsDataController@edit', 'as' => 'accounts_data.edit']);
    Route::post('/edit/{id}', ['uses'=>'AccountsDataController@update', 'as'=>'accounts_data.update']);
    Route::get('/delete/{id}', ['uses'=>'AccountsDataController@delete', 'as'=>'accounts_data.delete']);
    Route::get('/destroy', ['uses'=>'AccountsDataController@destroy', 'as'=>'accounts_data.destroy']);
    Route::get('/destroy-vk', ['uses'=>'AccountsDataController@destroyVk', 'as'=>'accounts_data.destroy.vk']);
    Route::get('/destroy-ok', ['uses'=>'AccountsDataController@destroyOk', 'as'=>'accounts_data.destroy.ok']);
    Route::get('/destroy-tw', ['uses'=>'AccountsDataController@destroyTw', 'as'=>'accounts_data.destroy.tw']);
    Route::get('/destroy-ins', ['uses'=>'AccountsDataController@destroyIns', 'as'=>'accounts_data.destroy.ins']);
    Route::get('/destroy-fb', ['uses'=>'AccountsDataController@destroyFb', 'as'=>'accounts_data.destroy.fb']);
    Route::get('/destroy-emails', ['uses'=>'AccountsDataController@destroyEmails', 'as'=>'accounts_data.destroy.emails']);
    Route::post('/vk-upload', ['uses'=>'AccountsDataController@vkupload', 'as'=>'accounts_data.vk.upload']);
    Route::post('/ok-upload', ['uses'=>'AccountsDataController@okupload', 'as'=>'accounts_data.ok.upload']);
    Route::post('/tw-upload', ['uses'=>'AccountsDataController@twupload', 'as'=>'accounts_data.tw.upload']);
    Route::post('/ins-upload', ['uses'=>'AccountsDataController@insupload', 'as'=>'accounts_data.ins.upload']);
    Route::post('/fb-upload', ['uses'=>'AccountsDataController@fbupload', 'as'=>'accounts_data.fb.upload']);
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
    Route::post('/config/create/', ['uses' => 'SettingsController@config_store', 'as' => 'settings.config.store']);
    Route::post('/config/edit/{config}', ['uses' => 'SettingsController@config_edit', 'as' => 'settings.config.edit']);
    Route::get('/thread/start/{process}', ['uses' => 'SettingsController@proc_start', 'as' => 'settings.process.start']);
    Route::get('/thread/stop/{process}', ['uses' => 'SettingsController@proc_stop', 'as' => 'settings.process.stop']);
    Route::get('/thread/startall/', ['uses' => 'SettingsController@proc_startall', 'as' => 'settings.process.start.all']);
    Route::get('/thread/stopall/', ['uses' => 'SettingsController@proc_stopall', 'as' => 'settings.process.stop.all']);
});

Route::group(['prefix' => 'search-queries', 'middleware' => 'auth'], function () {
    Route::get('/', ['uses' => 'SearchQueriesController@index', 'as' => 'search_queries.index']);
    Route::post('/', ['uses' => 'SearchQueriesController@getCsv', 'as' => 'search_queries.getCsv']);
});

Route::group(['prefix' => 'parsing-tasks', 'middleware' => 'auth'], function () {
    Route::get('/', ['uses' => 'ParsingTasksController@index', 'as' => 'parsing_tasks.index']);
    Route::get('/create', ['uses' => 'ParsingTasksController@create', 'as' => 'parsing_tasks.create']);
    Route::get('/show/{id}', ['uses' => 'ParsingTasksController@show', 'as' => 'parsing_tasks.show']);
    Route::get('/start/{id}', ['uses' => 'ParsingTasksController@start', 'as' => 'parsing_tasks.start']);
    Route::get('/stop/{id}', ['uses' => 'ParsingTasksController@stop', 'as' => 'parsing_tasks.stop']);

    Route::get('/start-delivery/{id}', ['uses' => 'ParsingTasksController@startDelivery', 'as' => 'parsing_tasks.startDelivery']);
    Route::get('/stop-delivery/{id}', ['uses' => 'ParsingTasksController@stopDelivery', 'as' => 'parsing_tasks.stopDelivery']);
    Route::post('/change-delivery-info', ['uses' => 'ParsingTasksController@changeDeliveryInfo', 'as' => 'parsing_tasks.changeDeliveryInfo']);

    Route::get('/reserved/{id}', ['uses' => 'ParsingTasksController@reserved', 'as' => 'parsing_tasks.reserved']);
    Route::post('/create', ['uses' => 'ParsingTasksController@store', 'as' => 'parsing_tasks.store']);
    Route::get('/get-csv/{id}', ['uses' => 'ParsingTasksController@getCsv', 'as' => 'parsing_tasks.getCsv']);
    Route::post('/get-from-csv', ['uses' => 'ParsingTasksController@getFromCsv', 'as' => 'parsing_tasks.getFromCsv']);

    Route::get('/testing-delivery-mails', ['uses' => 'ParsingTasksController@testingDeliveryMails', 'as' => 'parsing_tasks.testingDeliveryMails']);
    Route::post('/testing-delivery-mails', ['uses' => 'ParsingTasksController@storeTestingDeliveryMails', 'as' => 'parsing_tasks.storeTestingDeliveryMails']);
    Route::get('/testing-delivery-skypes', ['uses' => 'ParsingTasksController@testingDeliverySkypes', 'as' => 'parsing_tasks.testingDeliverySkypes']);
    Route::post('/testing-delivery-skypes', ['uses' => 'ParsingTasksController@storeTestingDeliverySkypes', 'as' => 'parsing_tasks.storeTestingDeliverySkypes']);
    Route::get('/testing-delivery-vk', ['uses' => 'ParsingTasksController@testingDeliveryVK', 'as' => 'parsing_tasks.testingDeliveryVK']);
    Route::post('/testing-delivery-vk', ['uses' => 'ParsingTasksController@storeTestingDeliveryVK', 'as' => 'parsing_tasks.storeTestingDeliveryVK']);
    Route::get('/testing-delivery-ok', ['uses' => 'ParsingTasksController@testingDeliveryOK', 'as' => 'parsing_tasks.testingDeliveryOK']);
    Route::post('/testing-delivery-ok', ['uses' => 'ParsingTasksController@storeTestingDeliveryOK', 'as' => 'parsing_tasks.storeTestingDeliveryOK']);
    Route::get('/testing-delivery-tw', ['uses' => 'ParsingTasksController@testingDeliveryTW', 'as' => 'parsing_tasks.testingDeliveryTW']);
    Route::post('/testing-delivery-tw', ['uses' => 'ParsingTasksController@storeTestingDeliveryTW', 'as' => 'parsing_tasks.storeTestingDeliveryTW']);
    Route::get('/testing-delivery-fb', ['uses' => 'ParsingTasksController@testingDeliveryFB', 'as' => 'parsing_tasks.testingDeliveryFB']);
    Route::post('/testing-delivery-fb', ['uses' => 'ParsingTasksController@storeTestingDeliveryFB', 'as' => 'parsing_tasks.storeTestingDeliveryFB']);
    Route::get('/testing-delivery-android-bots', ['uses' => 'ParsingTasksController@testingDeliveryAndroidBots', 'as' => 'parsing_tasks.testingDeliveryAndroidBots']);
    Route::post('/testing-delivery-android-bots', ['uses' => 'ParsingTasksController@storeTestingDeliveryAndroidBots', 'as' => 'parsing_tasks.storeTestingDeliveryAndroidBots']);
});

Route::group(['prefix' => 'skypes-accounts', 'middleware' => 'auth'], function () {
    Route::get('/', ['uses' => 'SkypesAccountsController@index', 'as' => 'skypes_accounts.index']);
    Route::get('/create', ['uses' => 'SkypesAccountsController@create', 'as' => 'skypes_accounts.create']);
    Route::post('/create', ['uses' => 'SkypesAccountsController@store', 'as' => 'skypes_accounts.store']);
    Route::post('/mass-upload', ['uses'=>'SkypesAccountsController@massupload', 'as'=>'skypes_accounts.mass.upload']);
    Route::get('/edit/{id}', ['uses' => 'SkypesAccountsController@edit', 'as' => 'skypes_accounts.edit']);
    Route::post('/edit/{id}', ['uses'=>'SkypesAccountsController@update', 'as'=>'skypes_accounts.update']);
    Route::get('/delete/{id}', ['uses'=>'SkypesAccountsController@delete', 'as'=>'skypes_accounts.delete']);
    Route::get('/destroy-sk', ['uses'=>'SkypesAccountsController@destroySk', 'as'=>'skypes_accounts.destroy.sk']);
});
Route::group(['prefix' => 'android-bots', 'middleware' => 'auth'], function () {
    Route::get('/', ['uses' => 'AndroidBotsController@index', 'as' => 'android_bots.index']);
    Route::get('/create', ['uses' => 'AndroidBotsController@create', 'as' => 'android_bots.create']);
    Route::post('/create', ['uses' => 'AndroidBotsController@store', 'as' => 'android_bots.store']);
    Route::post('/mass-upload', ['uses'=>'AndroidBotsController@massupload', 'as'=>'android_bots.mass.upload']);
    Route::get('/edit/{id}', ['uses' => 'AndroidBotsController@edit', 'as' => 'android_bots.edit']);
    Route::post('/edit/{id}', ['uses'=>'AndroidBotsController@update', 'as'=>'android_bots.update']);
    Route::get('/delete/{id}', ['uses'=>'AndroidBotsController@delete', 'as'=>'android_bots.delete']);
    Route::get('/destroy-androids', ['uses'=>'AndroidBotsController@destroyAndroidBots', 'as'=>'android_bots.destroy']);
});

Route::group(['prefix' => 'email-templates', 'middleware' => 'auth'], function () {
    Route::get('/', ['uses' => 'EmailTemplatesController@index', 'as' => 'email_templates.index']);
    Route::get('/create', ['uses' => 'EmailTemplatesController@create', 'as' => 'email_templates.create']);
    Route::post('/create', ['uses' => 'EmailTemplatesController@store', 'as' => 'email_templates.store']);
    Route::post('/mass-upload', ['uses'=>'EmailTemplatesController@massupload', 'as'=>'email_templates.mass.upload']);
    Route::get('/edit/{id}', ['uses' => 'EmailTemplatesController@edit', 'as' => 'email_templates.edit']);
    Route::post('/edit/{id}', ['uses'=>'EmailTemplatesController@update', 'as'=>'email_templates.update']);
    Route::get('/delete/{id}', ['uses'=>'EmailTemplatesController@delete', 'as'=>'email_templates.delete']);
    Route::get('/destroy-androids', ['uses'=>'EmailTemplatesController@destroyAndroidBots', 'as'=>'email_templates.destroy']);
});

Route::group(['prefix' => 'proxy', 'middleware' => 'auth'], function () {
    Route::get('/get-proxies', ['uses' => 'ProxyController@getProxies', 'as' => 'proxy.getproxies']);
    Route::post('/get-proxies', ['uses' => 'ProxyController@saveProxies', 'as' => 'proxy.saveproxies']);
});
Route::group(['prefix' => 'get-numbers'/*, 'middleware' => 'auth'*/], function () {
    Route::get('/get-whatsapp/{name}', ['uses' => 'GetNumbersController@getWhatsappTask', 'as' => 'getnumbers.getwhatsapptask']);
    Route::get('/get-viber/{name}', ['uses' => 'GetNumbersController@getViberTask', 'as' => 'getnumbers.getvibertask']);
    Route::post('/set-bot', ['uses' => 'GetNumbersController@setBotAndroid', 'as' => 'getnumbers.setbotandroid']);
    Route::post('/replace-bot', ['uses' => 'GetNumbersController@replaceBotAndroid', 'as' => 'getnumbers.replacebotandroid']);
    
});
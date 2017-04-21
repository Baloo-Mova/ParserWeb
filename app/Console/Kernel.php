<?php

namespace App\Console;

use App\Console\Commands\Parsers\ParseGoogle;
use App\Console\Commands\Parsers\ParseSite;
use App\Console\Commands\Parsers\ParseOk;
use App\Console\Commands\Parsers\ParseOkGroups;
use App\Console\Commands\Parsers\Proxy;
use App\Console\Commands\Parsers\ParseVKGetGroups;
use App\Console\Commands\Parsers\ParseVKGetUsers;
use App\Console\Commands\Parsers\ParseVK;
use App\Console\Commands\Parsers\ParseTw;
use App\Console\Commands\Parsers\ParseIns;
use App\Console\Commands\Parsers\ParseTwGroups;
use App\Console\Commands\Parsers\ParseInsGroups;
use App\Console\Commands\Senders\VKSender;
use App\Console\Commands\Senders\TwitterSender;
use App\Console\Commands\Parsers\ParseFB;
use App\Console\Commands\Parsers\ParseFBGetUsers;
use App\Console\Commands\Parsers\ParseFBGetGroups;
use App\Console\Commands\Parsers\TestFB;
use App\Console\Commands\Senders\FBSender;
use App\Console\Commands\Senders\EmailSender;
use App\Console\Commands\Tester;
use App\Console\Commands\AndroidBotManager;
use App\Console\Commands\Senders\SkypeSender;
use App\Console\Commands\Senders\OkSender;
use App\Console\Commands\Cleaner\NotValidMailsCleaner;

use App\Console\Commands\Parsers\ParseGoogleUa;
//use App\Console\Commands\Parsers\ParseYandexUa;
use App\Console\Commands\Parsers\ParseYandexRu;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
         Proxy::class,
         ParseGoogle::class,
         ParseSite::class,
         ParseVKGetGroups::class,
         ParseVKGetUsers::class,
         ParseTwGroups::class,
         ParseInsGroups::class,
         ParseVK::class,
         ParseTw::class,
         ParseIns::class,
         VKSender::class,
         ParseTw::class,
         ParseFB::class,
         ParseFBGetGroups::class,
         ParseFBGetUsers::class,
         TestFB::class,
         FBSender::class,
         TwitterSender::class,
         ParseOk::class,
         ParseOkGroups::class,
         EmailSender::class,
         Tester::class,
         SkypeSender::class,
         OkSender::class,
         NotValidMailsCleaner::class,
        
        ParseGoogleUa::class,
        ParseYandexRu::class,
        //ParseYandexUa::class,
        AndroidBotManager::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}

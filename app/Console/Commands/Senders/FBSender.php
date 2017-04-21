<?php

namespace App\Console\Commands\Senders;

use Illuminate\Console\Command;

use App\Models\TemplateDeliveryFB;
use App\Models\SearchQueries;
use App\Models\Parser\FBLinks;
use App\Models\Tasks;
use App\Helpers\FB;
use App\Models\Parser\ErrorLog;

class FBSender extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:fb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'try to send FB message';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        while (true) {
            try {
                 $sk_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                    ['search_queries.fb_id','<>',''],
                     'search_queries.fb_sended'   => 0,
                    'search_queries.fb_reserved' => 0,
                    'tasks.need_send'            => 1,
                    'tasks.active_type'          => 1, 
                    
                ])->select('search_queries.*')->first();
               
                if ( !isset($sk_query)) {
                    sleep(10);
                     
                    continue;
                }

                //$sk_query->vk_reserved = 1;
                //$sk_query->save();

                
                $message = TemplateDeliveryFB::where('task_id', '=', $sk_query->task_id)->first();
                // dd($message);
                
                if ( ! isset($message)) {
                    sleep(10);
                    $sk_query->fb_reserved = 0;
                    $sk_query->save();
                    continue;
                }

                 $web = new FB();
                $web->sendRandomMessage($sk_query->fb_id, $message->text);
                    sleep(random_int(1, 5));
                

                $sk_query->fb_sended = 1;
                $sk_query->save();

            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getTraceAsString();
                $log->task_id = 0;
                $log->save();
            }
        }
    }

}

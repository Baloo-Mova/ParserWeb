<?php

namespace App\Console\Commands\Senders;

use Illuminate\Console\Command;
use App\MyFacades\SkypeClassFacade;
use App\Models\TemplateDeliveryVK;
use App\Models\SearchQueries;
use App\Models\Parser\VKLinks;
use App\Models\Tasks;
use App\Helpers\VK;
use App\Models\Parser\ErrorLog;

class VKSender extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:vk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'try to send vk message';

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
        sleep(random_int(1,3));
        while (true) {
            try {
                 $sk_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                    ['search_queries.vk_id','<>',''],
                     'search_queries.vk_sended'   => 0,
                    'search_queries.vk_reserved' => 0,
                    'tasks.need_send'            => 1,
                    'tasks.active_type'          => 1,
                    
                ])->select('search_queries.*')->first();
               
                if ( !isset($sk_query)) {
                    sleep(10);
                     
                    continue;
                }

                //$sk_query->vk_reserved = 1;
                $sk_query->save();

                
                $message = TemplateDeliveryVK::where('task_id', '=', $sk_query->task_id)->first();
                // dd($message);
                
                if ( ! isset($message)) {
                    sleep(10);
                    $sk_query->vk_reserved = 0;
                    $sk_query->save();
                    continue;
                }

                 $web = new VK();
                if($web->sendRandomMessage($sk_query->vk_id, $message->text)==true){
                $sk_query->vk_sended = 1;
                $sk_query->vk_reserved = 0;
                
                $sk_query->save();
                }
                else{
                $sk_query->vk_reserved = 2;
                $sk_query->save();
                    
                }
                sleep(random_int(1, 5));
                

               

            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getTraceAsString();
                $log->task_id = 0;
                $log->save();
            }
        }
    }

}

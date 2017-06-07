<?php

namespace App\Console\Commands\Senders;

use Illuminate\Console\Command;

use App\Models\TemplateDeliveryFB;
use App\Models\SearchQueries;
use App\Models\Parser\FBLinks;
use App\Models\Tasks;
use App\Helpers\FB;
use App\Models\Parser\ErrorLog;
use Illuminate\Support\Facades\DB;
use App\Helpers\Macros;
class FBSender extends Command
{
    public $content;
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
                $this->content['query'] = null;
                DB::transaction(function () {
                    $sk_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                        ['search_queries.fb_id', '<>', ''],
                        'search_queries.fb_sended' => 0,
                        'search_queries.fb_reserved' => 0,
                        'tasks.need_send' => 1,
                        'tasks.active_type' => 1,

                    ])->select('search_queries.*')->lockForUpdate()->first();
                    if ( !isset($sk_query)) {
                        return;
                    }
                    $sk_query->fb_reserved = 1;
                    $sk_query->save();
                    $this->content['query'] = $sk_query;
                });
                if ( !isset($this->content['query'])) {
                    sleep(10);
                     
                    continue;
                }

               // $sk_query->fb_reserved = 1;
               // $sk_query->save();

                
                $message = TemplateDeliveryFB::where('task_id', '=', $this->content['query']->task_id)->first();
                // dd($message);
                
                if ( ! isset($message)) {
                    sleep(10);
                    $this->content['query']->fb_reserved = 0;
                    $this->content['query']->save();
                    continue;
                }
                if(substr_count ($message,"{")==substr_count ($message,"}")) {
                    if ((substr_count($message, "{") == 0 && substr_count($message, "}") == 0)) {
                        $str_mes = $message->text;
                    } else {
                        $str_mes = Macros::convertMacro($message->text);
                    }
                }
                else {

                    $log          = new ErrorLog();
                    $log->message = "FB_SEND: MESSAGE NOT CORRECT - update and try again";
                    $log->task_id = $this->content['query']->task_id;
                    $log->save();
                    $this->content['query']->fb_reserved=0;
                    $this->content['query']->save();
                    sleep(random_int(2,3));
                    continue;
                }

                 $web = new FB();
                $web->sendRandomMessage($this->content['query']->fb_id, $str_mes);
                    sleep(random_int(1, 5));


                $this->content['query']->fb_sended = 1;
                $this->content['query']->save();

            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getTraceAsString();
                $log->task_id = 0;
                $log->save();
            }
        }
    }

}

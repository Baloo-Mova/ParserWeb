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
use Illuminate\Support\Facades\DB;
use App\Helpers\Macros;
class VKSender extends Command
{
    public $content;

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
        while (true) {
            try {
                $this->content['vkquery'] = null;
                DB::transaction(function () {
                    $sk_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                        ['search_queries.vk_id', '<>', ''],
                        'search_queries.vk_sended'   => 0,
                        'search_queries.vk_reserved' => 0,
                        'tasks.need_send'            => 1,
                        'tasks.active_type'          => 1,

                    ])->select('search_queries.*')->lockForUpdate()->first();
                    if ( ! isset($sk_query)) {
                        return;
                    }
                    $sk_query->vk_reserved = 1;
                    $sk_query->save();
                    $this->content['vkquery'] = $sk_query;
                });
                if ( ! isset($this->content['vkquery'])) {
                    sleep(10);
                    continue;
                }

                $message = TemplateDeliveryVK::where('task_id', '=', $this->content['vkquery']->task_id)->first();
                if ( ! isset($message)) {
                    sleep(10);
                    $this->content['vkquery']->vk_reserved = 0;
                    $this->content['vkquery']->save();
                    continue;
                }
                if(substr_count ($message,"{")==substr_count ($message,"}")||
                    (substr_count ($message,"{")==0 && substr_count ($message,"}")==0)) {
                    $str_mes = Macros::convertMacro($message->text);

                    sleep(random_int(7, 10));
                    $web = new VK();
                    if ($web->sendRandomMessage($this->content['vkquery']->vk_id, $str_mes)) {
                        $this->content['vkquery']->vk_sended = 1;
                        $this->content['vkquery']->vk_reserved = 0;
                        $this->content['vkquery']->save();
                    } else {
                        $this->content['vkquery']->vk_reserved = 2;
                        $this->content['vkquery']->save();
                    }
                    sleep(random_int(10, 20));
                }else{
                    $log          = new ErrorLog();
                    $log->message = "VK_SEND: MESSAGE NOT CORRECT - update and try again";
                    $log->task_id = $this->content['vkquery']->task_id;
                    $log->save();
                    $this->content['vkquery']->vk_reserved=0;
                    $this->content['vkquery']->save();
                    continue;
                }

            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getTraceAsString() . " VK_SEND " . $ex->getLine();
                $log->task_id = 8888;
                $log->save();
            }
        }
    }

}

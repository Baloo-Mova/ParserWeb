<?php

namespace App\Console\Commands\Senders;

use Illuminate\Console\Command;
use App\MyFacades\SkypeClassFacade;
use App\Models\TemplateDeliverySkypes;
use App\Models\SearchQueries;
use App\Models\Parser\ErrorLog;
use Illuminate\Support\Facades\DB;
class SkypeSender extends Command
{
    public $content;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:skype';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'try to send skype message';

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
                        ['search_queries.skypes', '<>', ''],
                        'search_queries.sk_sended' => 0,
                        'search_queries.sk_recevied' => 0,
                        'tasks.need_send' => 1,
                        'tasks.active_type' => 1,
                    ])->select('search_queries.*')->lockForUpdate()->first();

                    if ( !isset($sk_query)) {
                        return;
                    }
                    $sk_query->sk_recevied = 1;
                    $sk_query->save();
                    $this->content['query'] = $sk_query;
                });
                if ( ! isset($this->content['query'])) {
                    sleep(10);
                    continue;
                }


                $skypes  = array_filter(explode(",", trim($this->content['query']->skypes)));
                $message = TemplateDeliverySkypes::where('task_id', '=', $this->content['query']->task_id)->first();

                if ( ! isset($message)) {
                    sleep(10);
                    continue;
                }

                foreach ($skypes as $skype) {
                    if(empty($skype)){
                        continue;
                    }
                    SkypeClassFacade::sendRandom($skype, $message->text);
                    sleep(random_int(1, 5));
                }

                $this->content['query']->sk_sended = count($skypes);
                $this->content['query']->save();

            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = "SKYPE " . $ex->getMessage() . " " . $ex->getLine();
                $log->task_id = 0;
                $log->save();
            }
        }
    }

}

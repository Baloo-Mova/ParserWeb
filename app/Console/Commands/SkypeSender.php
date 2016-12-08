<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\MyFacades\SkypeClassFacade;
use App\Models\TemplateDeliverySkypes;
use App\Models\SearchQueries;
use ErrorException;

class SkypeSender extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skypesender';

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
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        //while(true){
        while (true) {
            try {
                $sk_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                            ['search_queries.skypes', '<>', ''],
                            'search_queries.sk_sended' => 0,
                            'tasks.need_send' => 1,
                        ])->first();
                if ($sk_query->sk_recevied == 0)
                    break;
            } catch (ErrorException $e) {
                
            }
        }

        $sk_query->sk_recevied = 1;
        $sk_query->save();

        $skypes = array_filter(explode(",", trim($sk_query->skypes)));

        

        try {
            $message = TemplateDeliverySkypes::where('task_id', '=', $sk_query->task_id)->first();
            foreach ($skypes as $skype) {
                //dd($skype);
                SkypeClassFacade::sendRandom($skype, $message->text);
                sleep(3);
            }
            $sk_query->sk_sended = 1;
            $sk_query->sk_recevied = 0;
        $sk_query->save();
        } catch (ErrorExceprion $e) {
            
        }



        //dd($skypes);
//        $emails = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
//                    ['search_queries.mails', '<>', ''],
//                    'search_queries.email_reserved' => 0,
//                    'tasks.need_send' => 1,
//                ])->first();
        // SkypeClassFacade::sendRandom("tvv1994","Добавь меня))");
        sleep(100);
        //}
    }

}

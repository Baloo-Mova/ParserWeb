<?php

namespace App\Console\Commands\Senders;

use App\Helpers\Skype;
use Illuminate\Console\Command;
use App\Models\TemplateDeliverySkypes;
use App\Models\SearchQueries;
use App\Models\Parser\ErrorLog;
use Illuminate\Support\Facades\DB;
use App\Models\SkypeLogins;
use App\Helpers\Macros;
use App\Models\Contacts;

class SkypeSender extends Command
{
    public $content;
    /**
     * @var SearchQueries
     */
    public $task = null;
    /**
     * @var SkypeLogins
     */
    public $sender = null;
    /**
     * @var Skype
     */
    public $skype = null;
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
                $this->task   = null;
                $this->sender = null;

                DB::transaction(function () {
                    $this->sender = SkypeLogins::where([
                            ['reserved', '=', '0'],
                            ['valid', '=', '1']
                        ])->orderBy('count_request', 'asc')->lockForUpdate()->first();

                    if (isset($this->sender)) {
                        $this->sender->reserved = 1;
                        $this->sender->save();
                    }
                });

                $this->skype = new Skype($this->sender);

//                if ( ! $this->skype->checkLogin()) {
//                    $this->sender->valid = 0;
//                    $this->sender->save();
//
//                    $log          = new ErrorLog();
//                    $log->message = ErrorLog::SKYPE_NOT_VALID_USER . " user id = " . $this->sender->id;
//                    $log->task_id = 0;
//                    $log->save();
//
//                    sleep(10);
//                    continue;
//                }

                DB::transaction(function () {
                    $this->task = Contacts::join('search_queries', 'search_queries.id', '=', 'contacts.search_queries_id')
                        ->join('tasks', 'tasks.id', '=', 'search_queries.task_id')
                        ->where([
                            ['contacts.type', '=', 3],
                            ['contacts.sended', '=', 0],
                            ['contacts.reserved', '=', 0],
                            ['tasks.need_send', '=', 1],
                        ])
                        ->lockForUpdate()->limit(10)->get(['contacts.*', 'search_queries.task_id']);
                    if (isset($this->task) && count($this->task) > 0) {
                        foreach ($this->task as $contact_item){
                            $contact_item->reserved = 1;
                            $contact_item->save();
                        }
                    }
                });

                if ( ! isset($this->task) || count($this->task) == 0) {
                    $this->sender->reserved = 0;
                    $this->sender->save();
                    sleep(10);
                    continue;
                }

                //$skypes  = array_filter(explode(",", trim($this->task->skypes)));
                $message = TemplateDeliverySkypes::where('task_id', '=', $this->task[0]->task_id)->first();

                if ( ! isset($message)) {
                    $this->sender->reserved = 0;
                    $this->sender->save();

                    $log          = new ErrorLog();
                    $log->message = ErrorLog::SKYPE_NO_MESSAGE;
                    $log->task_id = $this->task[0]->task_id;
                    $log->save();
                    sleep(10);
                    continue;
                }

                if (substr_count($message, "{") == substr_count($message, "}")) {
                    $str_mes = Macros::convertMacro($message->text);
                } else {
                    $this->sender->reserved = 0;
                    $this->sender->save();

                    $log          = new ErrorLog();
                    $log->message = ErrorLog::SKYPE_MESSAGE_TEXT_ERROR;
                    $log->task_id = $this->task[0]->id;
                    sleep(10);
                    continue;
                }

                $sendedCounter = 0;
                foreach ($this->task as $skype) {
                    if (empty($skype)) {
                        continue;
                    }

                    $is_friend = $this->skype->isMyFrined($skype->value);

                    if ($is_friend) {
                        if ($this->skype->sendMessage($skype->value, $str_mes)) {
                            $sendedCounter++;
                        }
                    } else {
                        if ($this->skype->addFriend($skype->value, $str_mes)) {
                            $sendedCounter++;
                        }
                    }

                    $skype->sended = 1;
                    $skype->save();

                    sleep(random_int(1, 5));
                }

                $this->sender->reserved = 0;
                $this->sender->save();

                SearchQueries::where('id', $skype->search_queries_id)->update(["sk_sended" => $sendedCounter]);

                sleep(random_int(10, 15));
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = "SKYPE sender" . $ex->getMessage() . " " . $ex->getLine();
                $log->task_id = 0;
                $log->save();
            }
        }
    }

}

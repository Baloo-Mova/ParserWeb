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
                    $this->sender = SkypeLogins::
                    where('reserved', '=', '0')->orderBy('count_request', 'asc')->lockForUpdate()->first();

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
                    $this->task = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                        ['search_queries.skypes', '<>', ''],
                        ['search_queries.sk_sended', '=', 0],
                        ['search_queries.sk_recevied', '=', 0],
                        ['tasks.need_send', '=', 1],
                    ])->select('search_queries.*')->lockForUpdate()->first();

                    if (isset($this->task)) {
                        $this->task->sk_recevied = 1;
                        $this->task->save();
                    }
                });

                if ( ! isset($this->task)) {
                    $this->sender->reserved = 0;
                    $this->sender->save();
                    sleep(10);
                    continue;
                }

                $skypes  = array_filter(explode(",", trim($this->task->skypes)));
                $message = TemplateDeliverySkypes::where('task_id', '=', $this->task->task_id)->first();

                if ( ! isset($message)) {
                    $this->sender->reserved = 0;
                    $this->sender->save();

                    $log          = new ErrorLog();
                    $log->message = ErrorLog::SKYPE_NO_MESSAGE;
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
                    sleep(10);
                    continue;
                }

                $sendedCounter = 0;
                foreach ($skypes as $skype) {
                    if (empty($skype)) {
                        continue;
                    }

                    $is_friend = $this->skype->isMyFrined($skype);

                    if ($is_friend) {
                        if ($this->skype->sendMessage($skype, $str_mes)) {
                            $sendedCounter++;
                        }
                    } else {
                        if ($this->skype->addFriend($skype, $str_mes)) {
                            $sendedCounter++;
                        }
                    }

                    sleep(random_int(1, 5));
                }

                $this->sender->reserved = 0;
                $this->sender->save();

                $this->task->sk_sended = $sendedCounter;
                $this->task->save();
                sleep(random_int(10, 15));
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = "SKYPE sender" . $ex->getMessage() . " " . $ex->getLine();
                $log->task_id = $this->task->id;
                $log->save();
            }
        }
    }

}

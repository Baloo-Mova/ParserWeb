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
use League\Flysystem\Exception;
use malkusch\lock\mutex\FlockMutex;

class SkypeSender extends Command
{
    public $content;
    /**
     * @var Contacts
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
                $this->task = null;
                $this->sender = null;

                $mutex = new FlockMutex(fopen(__FILE__, "r"));
                $mutex->synchronized(function () {
                    $this->task = Contacts::join('task_groups', 'task_groups.id', '=',
                        'contacts.task_group_id')->where([
                        ['contacts.type', '=', 3],
                        ['contacts.sended', '=', 0],
                        ['contacts.reserved', '=', 0],
                        ['task_groups.need_send', '=', 1],
                    ])->first(['contacts.*']);

                    if (isset($this->task)) {
                        Contacts::whereId($this->task->id)->update(['reserved' => 1]);
                    }
                });

                if (!isset($this->task)) {
                    sleep(5);
                    continue;
                }

                $mutex = new FlockMutex(fopen(__FILE__, "r"));
                $mutex->synchronized(function () {
                    $this->sender = SkypeLogins::where([
                        ['reserved', '=', '0'],
                        ['valid', '=', '1']
                    ])->orderBy('count_request', 'asc')->first();

                    if (isset($this->sender)) {
                        $this->sender->reserved = 1;
                        $this->sender->save();
                    }
                });

                if (!isset($this->sender)) {
                    Contacts::whereId($this->task->id)->update(['reserved' => 0]);
                    sleep(5);
                    continue;
                }

                $this->skype = new Skype($this->sender);

                $message = $this->task->deliveryData;

                if (!isset($message)) {
                    $this->sender->reserved = 0;
                    $this->sender->save();
                    Contacts::whereId($this->task->id)->update(['reserved' => 2]);
                    $log = new ErrorLog();
                    $log->message = ErrorLog::SKYPE_NO_MESSAGE;
                    $log->task_id = $this->task->task_id;
                    $log->save();
                    sleep(5);
                    continue;
                }

                $message = $message->getParam('skype')['text'];

                if (substr_count($message, "{") == substr_count($message, "}")) {
                    $str_mes = Macros::convertMacro($message);
                } else {
                    $this->sender->reserved = 0;
                    $this->sender->save();
                    Contacts::whereId($this->task->id)->update(['reserved' => 2]);
                    $log = new ErrorLog();
                    $log->message = ErrorLog::SKYPE_MESSAGE_TEXT_ERROR;
                    $log->task_id = $this->task->id;
                    sleep(5);
                    continue;
                }

                $is_friend = $this->skype->isMyFrined($this->task->value);

                if ($is_friend === Skype::NOT_VALID_ACCOUNT) {
                    $this->sender->valid = 0;
                    $this->sender->save();
                    Contacts::whereId($this->task->id)->update(['reserved' => 2]);
                    sleep(random_int(5, 15));
                    continue;
                }

                if ($is_friend === true) {
                    $this->skype->sendMessage($this->task->value, $str_mes);
                } else {
                    $this->skype->addFriend($this->task->value, $str_mes);
                }

                Contacts::whereId($this->task->id)->update(['reserved' => 3, 'sended' => 1]);

                $this->sender->reserved = 0;
                $this->sender->save();
                sleep(random_int(5, 15));
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->message = "SKYPE sender " . $ex->getMessage() . " " . $ex->getTraceAsString();
                $log->task_id = 0;
                $log->save();
            }
        }
    }

}

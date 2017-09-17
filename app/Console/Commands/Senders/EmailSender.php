<?php

namespace App\Console\Commands\Senders;

use App\Models\AccountsData;
use App\Models\AccountsDataTypes;
use App\Models\Contacts;
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
use malkusch\lock\mutex\FlockMutex;


class EmailSender extends Command
{
    /**
     * @var Contacts
     */
    public $content;
    /**
     * @var AccountsData
     */
    public $account;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email sender process';
    public $client = null;
    public $cur_proxy = null;
    public $proxy_arr, $proxy_string;
    public $accountData = null;

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
                $this->content = null;
                $this->account = AccountsData::getSenderAccount(AccountsDataTypes::VK);
                if (!isset($this->account)) {
                    sleep(10);
                    continue;
                }


                $mutex = new FlockMutex(fopen(__FILE__, "r"));
                $mutex->synchronized(function () {
                    $this->content = Contacts::with('deliveryData')->where([
                        'contacts.type' => Contacts::MAILS,
                        'contacts.sended' => 0,
                        'task_groups.need_send' => 1,
                        'contacts.reserved' => 0,
                    ])
                        ->join('task_groups', 'contacts.task_group_id', '=', 'task_groups.id')
                        ->orderBy('actual_mark', 'desc')
                        ->select(['contacts.*'])
                        ->first();

                    if (isset($this->content)) {
                        $this->content->reserve();
                    }

                });


                if (!isset($this->content)) {
                    $this->account->release();
                    sleep(10);
                    continue;
                }

                $sendThis = $this->content->getSendData('mail');

                if (!isset($sendThis) || count($sendThis) < 2 || empty($sendThis['text'])) {
                    $this->account->release();
                    $this->content->realise();
                    sleep(10);
                    continue;
                }

                if (substr_count($sendThis['text'], "{") != substr_count($sendThis['text'], "}")) {
                    $log = new ErrorLog();
                    $log->message = "VK_SEND: MESSAGE NOT CORRECT - update and try again";
                    $log->task_id = $this->content->task_group_id;
                    $log->save();

                    $this->content->realise();
                    $this->account->release();
                    sleep(10);
                    continue;
                }

                $str_mes = Macros::convertMacro($sendThis['text']);


                $vk = new VK();
                if (!$vk->setAccount($this->account)) {
                    $this->content->realise();
                    sleep(10);
                    continue;
                }

                if ($vk->sendMailMessage($this->content->value, $str_mes)) {
                    $this->content->sended = 1;
                    $this->content->save();
                }

                $this->account->actionDone();


            } catch (\Exception $ex) {

                if (isset($this->account)) {
                    $this->account->release();
                }

                if (isset($this->content)) {
                    $this->content->realise();
                }

                $log = new ErrorLog();
                $log->message = $ex->getTraceAsString() . " VK_SEND " . $ex->getLine();
                $log->task_id = VK::VK_SEND_ERROR;
                $log->save();
            }

            sleep(rand(20, 40));
        }
    }
}

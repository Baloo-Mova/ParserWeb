<?php

namespace App\Console\Commands\Senders;

use App\Models\AccountsData;
use App\Models\AccountsDataTypes;
use App\Models\NotValidMessages;
use App\Models\SearchQueries;
use App\Models\Parser\ErrorLog;
use App\Models\ProxyTemp;
use Illuminate\Console\Command;
use PHPMailer;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

class EmailSender extends Command {

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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify' => false,
            'cookies' => true,
            'allow_redirects' => true,
            'timeout' => 40,
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
       sleep(random_int(1,2));
        while (true) {
            sleep(1);
            try {
                $emails = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                            ['search_queries.mails', '<>', ''],
                            'search_queries.email_reserved' => 0,
                            'search_queries.email_sended' => 0,
                            'tasks.need_send' => 1,
                            'tasks.active_type' => 1,
                        ])->select('search_queries.*')->first();

                if (!isset($emails)) {

                    sleep(10);
                    continue;
                }

                $emails->email_reserved = 1;
                $emails->save();

                $template = $emails->getEmailTemplate();

                if (empty($template->text) || empty($template->subject)) {
                    $log = new ErrorLog();
                    $log->message = "Невозможно отравить email сообщение без шаблона";
                    $log->task_id = $emails->task_id;
                    $log->save();
                    $emails->email_reserved = 0;
                    $emails->save();

                    sleep(10);
                    continue;
                }

                $ids = DB::table('not_valid_messages')->where(['id_text' => $template->id])->select('id_sender')->get();
                $temp = [];
                foreach ($ids as $id) {
                    $temp[] = $id->id_sender;
                }

                $from = AccountsData::where(['type_id' => 3], ['count_sended_messages', '<', config("config.max_count_for_sended_messages")])->whereNotIn('id', $temp)->first();
                //echo("------".$from."-------");
                // dd($from);
                if (!isset($from)) {
                    $log = new ErrorLog();
                    $log->message = "Невозможно отравить email сообщение без отправителей";
                    $log->task_id = $emails->task_id;
                    $log->save();
                    $emails->email_reserved = 0;
                    $emails->save();

                    sleep(10);
                    continue;
                }
                $to = explode(',', $emails->mails);

                if ($this->sendMessage([
                            'text' => $template->text,
                            'subject' => $template->subject,
                            'attaches' => $template->attaches,
                            "from" => $from,
                            "to" => $to,
                            "id" => $emails->id,
                        ])
                ) {

                    $emails->email_sended = count($to);
                    //  dd("hhhhhhh");
                } else {

                    $notvalidmess = new NotValidMessages;
                    $notvalidmess->id_text = $template->id;
                    $notvalidmess->id_sender = $from->id;
                    $notvalidmess->save();
                    $emails->email_reserved = 0;
                    $emails->save();
                }
                $emails->email_sended=1;
                $emails->save();
                sleep(30);
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->task_id = 1;

                $log->save();
               // dd($ex->getMessage());
                if (strpos($ex->getMessage(), "Operation timed out") !==false ) {
                    $proxy = ProxyTemp::where(['mail' => 1])->orWhere(['mail' => 2])->first();
                   // dd($ex->getMessage());
                    try {

                        $proxy->delete();
                    } catch (\Exception $ex) {
                        
                    }
                    continue;
                }
            }
        }
    }

    public function sendMessage($arguments) {

//        $mail = new PHPMailer;
//        //$mail->SMTPDebug = 3;                               // Enable verbose debug output
//        $mail->isSMTP();                                      // Set mailer to use SMTP
//        $mail->Host = $arguments['from']->smtp_address;  // Specify main and backup SMTP servers
//        $mail->SMTPAuth = true;                               // Enable SMTP authentication
//        $mail->Username = $arguments['from']->login;                 // SMTP username
//        $mail->Password = $arguments['from']->password;                           // SMTP password
//        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
//        $mail->Port = $arguments['from']->smtp_port;                                    // TCP port to connect to
//        $mail->CharSet = 'UTF-8';
//        $mail->setFrom($arguments['from']->login);
//
//        foreach ($arguments['to'] as $email) {
//            if (!empty(trim($email))) {
//                $mail->addAddress($email);     // Add a recipient
//            }
//        }
        $number_con_time_out = 0;
        while (true) {
            $att_list = [];
            if (isset($arguments['attaches'])) {
                foreach ($arguments['attaches'] as $attach) {
//              $mail->addAttachment(storage_path("app/" . $attach->path));
                    array_push($att_list, ['path' => storage_path("app/" . $attach->path)]);
                }
            }
            // //dd($att_list);
//       $mail->Subject = $arguments['subject'];
//       $mail->Body = $arguments['text'];



            if (!isset($arguments['to'])) {
                return false;
            }

            $mails_to = trim(implode(',', $arguments['to']));

            $proxy = ProxyTemp::where(['mail' => 1])->orWhere(['mail' => 2])->first();

            if (!isset($proxy)) {

                $proxy = '';
            } else {
                $proxytext = str_replace("\r", "/", $proxy->proxy);
            }

            $request = $this->client->request("POST", "http://localhost:25555/sendmail", [
               // 'proxy' => '127.0.0.1:8888',
                'json' => [
                    'hostname' => $arguments['from']->smtp_address,
                    'port' => $arguments['from']->smtp_port,
                    'login' => $arguments['from']->login, // SMTP username
                    'password' => $arguments['from']->password,
                    'to' => $mails_to,
                    'subject' => $arguments['subject'],
                    'html' => $arguments['text'],
                    'attach' => $att_list,
                    'id' => $arguments["id"],
                    'proxy' => $proxytext
                ]
                    ]
            );

            $response = mb_strtolower($request->getBody()->getContents());


            if ($response == "success") {
                $arguments["from"]->count_sended_messages += 1;
                $arguments['from']->save();
                return true;
            } else {
                $log = new ErrorLog();
                $log->message = 'Mailer Error: ' . $response;
                $log->task_id = 0;
                $log->save();
                if (strpos($response, "spam") > 0 || strpos($response, "auth") > 0) {
                    $arguments["from"]->valid = 0;
                    $arguments['from']->save();

                    //echo "SEND SPAM";
                }
                //dd($response);
                if (strlen(stristr($response, "connection")) > 0 || strpos($response, 'connect etimedout')!==false) {
                    $number_con_time_out++;
                    echo "\n" . $number_con_time_out . ":" . $response . $proxy->proxy;
                    if ($number_con_time_out >= 2) {
                        try {
                            $proxy->delete();
                        } catch (\Exception $ex) {
                            
                        }
                        $number_con_time_out = 0;
                    }
                    continue;
                    //echo "SEND SPAM";
                }

                if (strlen(stristr($response, "socket closed")) > 0 || strlen(stristr($response, 'rejected (')) > 0 || strlen(stristr($response, 'negotiation error')) > 0 
                        || strpos($response, 'mailer error: connect etimedout')!==false) {
                    echo "\nSEND bad socket" . $proxy->proxy;
                    try {
                        $proxy->delete();
                    } catch (\Exception $ex) {
                      //  dd($ex->getMessage());
                    }

                    continue;
                }



                return false;
            }
        }
        /* if (!$mail->send()) {
          $log = new ErrorLog();
          $log->message = 'Mailer Error: ' . $mail->ErrorInfo;
          $log->task_id = 0;
          $log->save();

          if (strpos($mail->ErrorInfo, "SPAM") > 0) {
          $arguments["from"]->valid = 0;
          $arguments['from']->save();

          //echo "SEND SPAM";
          }


          return false;
          } else {
          $arguments["from"]->count_sended_messages += 1;
          $arguments['from']->save();

          return true;
          } */
    }

}

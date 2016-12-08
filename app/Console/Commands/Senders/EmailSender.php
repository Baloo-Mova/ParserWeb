<?php

namespace App\Console\Commands\Senders;

use App\Models\AccountsData;
use App\Models\AccountsDataTypes;
use App\Models\SearchQueries;
use App\Models\Parser\ErrorLog;
use Illuminate\Console\Command;
use PHPMailer;

class EmailSender extends Command
{
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
                $emails = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                    ['search_queries.mails', '<>', ''],
                    'search_queries.email_reserved' => 0,
                    'search_queries.email_sended'   => 0,
                    'tasks.need_send'               => 1,
                ])->select('search_queries.*')->first();

                if ( ! isset($emails)) {
                    sleep(10);
                    continue;
                }

                $emails->email_reserved = 1;
                $emails->save();

                $template = $emails->getEmailTemplate();

                if (empty($template->text) || empty($template->subject)) {
                    $log          = new ErrorLog();
                    $log->message = "Невозможно отравить email сообщение без шаблона";
                    $log->task_id = $emails->task_id;
                    $log->save();
                    continue;
                }

                $from = AccountsData::where(['type_id' => 3])->orderByRaw("RAND()")->first();

                if ( ! isset($from)) {
                    $log          = new ErrorLog();
                    $log->message = "Невозможно отравить email сообщение без отправителей";
                    $log->task_id = $emails->task_id;
                    $log->save();
                    continue;
                }
                $to = explode(',', $emails->mails);
                if ($this->sendMessage([
                    'text'     => $template->text,
                    'subject'  => $template->subject,
                    'attaches' => $template->attaches,
                    "from"     => $from,
                    "to"       => $to,
                ])
                ) {
                    $emails->email_sended = count($to);
                }

                $emails->save();
                sleep(1);
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->task_id = 0;
                $log->save();
            }
        }
    }

    public function sendMessage($arguments)
    {

        $mail = new PHPMailer;
        //$mail->SMTPDebug = 3;                               // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host       = $arguments['from']->smtp_address;  // Specify main and backup SMTP servers
        $mail->SMTPAuth   = true;                               // Enable SMTP authentication
        $mail->Username   = $arguments['from']->login;                 // SMTP username
        $mail->Password   = $arguments['from']->password;                           // SMTP password
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port       = $arguments['from']->smtp_port;                                    // TCP port to connect to

        $mail->setFrom($arguments['from']->login);

        foreach ($arguments['to'] as $email) {
            if ( ! empty(trim($email))) {
                $mail->addAddress($email);     // Add a recipient
            }
        }

        if (isset($arguments['attaches'])) {
            foreach ($arguments['attaches'] as $attach) {
                $mail->addAttachment(storage_path("app/" . $attach->path));
            }
        }

        $mail->Subject = $arguments['subject'];
        $mail->Body    = $arguments['text'];

        if ( ! $mail->send()) {
            $log          = new ErrorLog();
            $log->message = 'Mailer Error: ' . $mail->ErrorInfo;
            $log->task_id = 0;
            $log->save();

//            if(strpos($mail->ErrorInfo, )){
//
//            }

            return false;
        } else {
            return true;
        }
    }
}

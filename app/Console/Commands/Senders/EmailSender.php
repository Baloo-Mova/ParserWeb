<?php

namespace App\Console\Commands\Senders;

use App\Models\SearchQueries;
use App\MyFacades\SkypeClassFacade;
use App\Models\Parser\ErrorLog;
use Illuminate\Console\Command;

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

                $emails = SearchQueries::where([
                    ['mails', '<>', ''],
                    'email_reserved' => 0,
                ])->first();

                if(!isset($emails)){
                    sleep(10);
                    continue;
                }

                $emails->email_reserved = 1;
                $emails->save();

                $template = $emails->getEmailTemplate();




            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->task_id = 0;
                $log->save();
            }
        }
    }

    public function sendMessage($arguments){


        $mail = new PHPMailer;

        //$mail->SMTPDebug = 3;                               // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'sergious91@gmail.com';                 // SMTP username
        $mail->Password = 'qwerty418390';                           // SMTP password
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465;                                    // TCP port to connect to

        $mail->setFrom('sergious91@gmail.com', 'Mailer');
        $mail->addAddress('sergious-91@mail.ru');     // Add a recipient

        $mail->Subject = 'Here is the subject';
        $mail->Body    = 'This is the HTML message body';

        if(!$mail->send()) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        } else {
            echo 'Message has been sent';
        }
    }
}

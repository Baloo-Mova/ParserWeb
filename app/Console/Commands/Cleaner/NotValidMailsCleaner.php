<?php

namespace App\Console\Commands\Cleaner;

use Illuminate\Console\Command;
use App\Models\Parser\ErrorLog;
use App\Models\AccountsData;
use App\Models\NotValidMessages;
use PHPMailer;

class NotValidMailsCleaner extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notvalidmails:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean nod valid emails';

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

        $mailsDB = AccountsData::orderBy('id', 'desc')->get();

        foreach ($mailsDB as $mailDB) {
            try {



                $mail = new PHPMailer;
                // $mail->SMTPDebug = 3;                               // Enable verbose debug output
                $mail->isSMTP();                                      // Set mailer to use SMTP
                $mail->Host = $mailDB->smtp_adress;  // Specify main and backup SMTP servers
                $mail->SMTPAuth = true;                               // Enable SMTP authentication
                $mail->Username = $mailDB->login;                 // SMTP username
                $mail->Password = $mailDB->password;                           // SMTP password
                $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
                $mail->Port = $mailDB->smtp_port;                                    // TCP port to connect to

                $mail->setFrom($mailDB->login);
                $mail->addAddress($mailDB->login);     // Add a recipient

                $randwords = file(storage_path('app/')."corn.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
               
              
                
                $points = array("\r\n","\n",'. ','; ',': ',', ',"! ","? ");
                
                //dd(gettype($randwords));
                $subject="";
                $body = "";
                for($i=0;$i<=rand(5, 11);$i+=1){
                $subject = $subject.$randwords[array_rand($randwords)]." ";
                //dd($subject);
                }
                 for($i=0;$i<=rand(15, 40);$i+=1){
                $body += $body.$randwords[array_rand($randwords)]." ";
                 }
                $body =$body.".";
                //dd($subject);
                $mail->Subject = $subject;
                $mail->Body = $body;

                if (!$mail->send()) {
                    $log = new ErrorLog();
                    $log->message = 'Mailer Error: ' . $mail->ErrorInfo;
                    $log->task_id = 0;
                    $log->save();
                    $mailDB->delete();
                   // return false;
                } else {
                   // return true;
                }
            } catch (\Exception $ex) {
                $mailDB->delete();
                //return false;
            }
        }
    }

}

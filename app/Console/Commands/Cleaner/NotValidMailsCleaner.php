<?php

namespace App\Console\Commands\Cleaner;

use Illuminate\Console\Command;
use App\Models\Parser\ErrorLog;
use App\Models\AccountsData;
use App\Models\NotValidMessages;
use PHPMailer;

class NotValidMailsCleaner extends Command
{

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
        $mailsDB   = AccountsData::where(["type_id" => 3])->get();
        $randwords = explode("\n", file_get_contents(storage_path('app/') . "corn.txt"));
        $max       = count($randwords);
        foreach ($mailsDB as $mailDB) {
            $subject = "";
            $body    = "";

            for ($i = 0; $i <= rand(3, 6); $i++) {
                $subject .= trim($randwords[random_int(0, $max)]) . " ";
            }

            for ($i = 0; $i <= rand(15, 40); $i++) {
                $body .= trim($randwords[random_int(0, $max)]) . " ";
            }
            $body = $body . ".";

            if ( ! $this->testEmail([
                "login"    => $mailDB->login,
                "password" => $mailDB->password,
                "smtp"     => $mailDB->smtp_address,
                "port"     => $mailDB->smtp_port,
                'subject'  => $subject,
                'text'     => $body,
            ])
            ) {
                $notValidMessages = NotValidMessages::where(['id_send' => $mailDB->id])-get();
                foreach($notValidMessages as $notValidMessage){
                    $notValidMessage->delete();
                } 
                $mailDB->delete();
            }
        }
    }

    public function testEmail($data)
    {
        try {
            $mail = new PHPMailer;
            // $mail->SMTPDebug = 3;                               // Enable verbose debug output
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host       = $data['smtp'];                  // Specify main and backup SMTP servers
            $mail->SMTPAuth   = true;                               // Enable SMTP authentication
            $mail->Username   = $data['login'];                 // SMTP username
            $mail->Password   = $data['password'];                           // SMTP password
            $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port       = $data['port'];                                    // TCP port to connect to
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($data['login']);
            $mail->addAddress($data['login']);     // Add a recipient

            $mail->Subject = $data['subject'];
            $mail->Body    = $data['text'];

            if ( ! $mail->send()) {
                $log          = new ErrorLog();
                $log->message = 'Mailer Error: ' . $mail->ErrorInfo;
                $log->task_id = 0;
                $log->save();

                return false;
            } else {
                return true;
            }
        } catch (\Exception $ex) {
            echo $ex->getTraceAsString();

            return false;
        }
    }
}

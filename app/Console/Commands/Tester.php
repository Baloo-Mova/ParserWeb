<?php

namespace App\Console\Commands;

use App\Jobs\GetProxies;
use App\Jobs\TestProxies;
use PHPMailer;
use Illuminate\Console\Command;
use App\MyFacades\SkypeClassFacade;
use App\Helpers\VK;
use App\Helpers\FB;
use App\Models\AccountsData;
use App\Helpers\SimpleHtmlDom;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\GoodProxies;


class Tester extends Command
{
    public $client  = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        $proxy = GoodProxies::getProxy(GoodProxies::GOOGLE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY, $proxy['proxy']);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_CONNECT_ONLY,true);
        curl_exec($ch);
        echo curl_error($ch);


        /*$mail = new PHPMailer;
        //$mail->SMTPDebug = 3;
        $mail->isSMTP();
        $mail->Host       = $arguments['from']->smtp_address;
        $mail->SMTPAuth   = true;
        $mail->Username   = $arguments['from']->login;
        $mail->Password   = $arguments['from']->password;
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = $arguments['from']->smtp_port;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($arguments['from']->login);

        foreach ($arguments['to'] as $email) {
            if ( ! empty(trim($email))) {
                $mail->addAddress($email);
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

            if (strpos($mail->ErrorInfo, "SPAM") > 0) {
                $arguments["from"]->valid = 0;
                $arguments['from']->save();

                //echo "SEND SPAM";
            }
        }*/

    }



}

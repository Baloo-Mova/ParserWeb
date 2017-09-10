<?php

namespace App\Http\Controllers;

use App\Models\Parser\ErrorLog;
use App\Models\Proxy;
use Illuminate\Http\Request;
use App\Models\AccountsData;
use Illuminate\Support\Facades\DB;
use App\Models\SmtpBase;
use Illuminate\Support\Facades\Storage;
use PHPMailer;

class AccountsDataController extends Controller
{

    /**
     * Главный экшен. Перенаправление на список акков ВК
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect()->route('accounts_data.vk');
    }

    /**
     * Вывод всех записей типа ВК
     *
     * @return \Illuminate\Http\Response
     */
    public function vk()
    {
        $data = AccountsData::vk()->paginate(config('config.accountsdatapaginate'));

        return view('accounts_data.vk', ['data' => $data]);
    }

    /**
     * Вывод всех записей типа ОК
     *
     * @return \Illuminate\Http\Response
     */
    public function ok()
    {
        $data = AccountsData::ok()->paginate(config('config.accountsdatapaginate'));

        return view('accounts_data.ok', ['data' => $data]);
    }

    /**
     * Вывод всех записей типа TWITTER
     *
     * @return \Illuminate\Http\Response
     */
    public function tw()
    {
        $data = AccountsData::tw()->paginate(config('config.accountsdatapaginate'));

        return view('accounts_data.tw', ['data' => $data]);
    }

    public function fb()
    {
        $data = AccountsData::fb()->paginate(config('config.accountsdatapaginate'));

        return view('accounts_data.fb', ['data' => $data]);
    }

    /**
     * Вывод всех записей типа Instagram
     *
     * @return \Illuminate\Http\Response
     */
    public function ins()
    {
        $data = AccountsData::ins()->paginate(config('config.accountsdatapaginate'));

        return view('accounts_data.ins', ['data' => $data]);
    }

    /**
     * Вывод всех записей типа MAILS
     *
     * @return \Illuminate\Http\Response
     */
    public function emails()
    {
        $data = AccountsData::emails()->paginate(config('config.accountsdatapaginate'));

        return view('accounts_data.emails', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($type)
    {
        return view('accounts_data.create', ["type" => $type]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = new AccountsData;
        $data->fill($request->all());
        $type_id = $request->get("type_id");
        $proxyLimitNumber = 3;

        $proxyInfo = $this->findProxyId($type_id, $proxyLimitNumber);
        $proxyLimitNumber = $proxyInfo["counter"];

        $data->proxy_id = $proxyInfo["proxy_id"];

        if (!(empty($request->get("smtp_port")))) {
            $data->smtp_port = $request->get("smtp_port");
        }
        if (!(empty($request->get("smtp_address")))) {
            $data->smtp_address = $request->get("smtp_address");
        }
        if ($type_id == 3 && (empty($data->smtp_address) || empty($data->smtp_port))) {
            $domain = substr($data->login, strrpos($data->login, '@') + 1);
            $smtp_data = SmtpBase::whereDomain($domain)->first();
            if (!(empty($smtp_data))) {
                $data->smtp_port = $smtp_data->port;
                $data->smtp_address = $smtp_data->smtp;
            }
        }


        $data->save();

        switch ($type_id) {
            case 1:
                return redirect()->route('accounts_data.vk');
                break;

            case 2:
                return redirect()->route('accounts_data.ok');
                break;

            case 3:
                return redirect()->route('accounts_data.emails');
                break;

            case 4:
                return redirect()->route('accounts_data.tw');
                break;

            case 5:
                return redirect()->route('accounts_data.ins');
                break;
            case 6:
                return redirect()->route('accounts_data.fb');
                break;
        }
    }

    private function findProxyId($type)
    {
        $acc = AccountsData::where(['type_id' => $type])->max('proxy_id');

        if (!isset($acc)) {
            return 1;
        }

        if ($acc < 50) {
            return ++$acc;
        }

        $data = AccountsData::selectRaw('count(proxy_id) as counts , proxy_id')->where(['type_id' => $type])->groupBy('proxy_id')->orderByRaw('counts,proxy_id asc')->first();
        if (isset($data)) {
            return $data->proxy_id;
        }
    }

    public function testEmail($data)
    {
        try {
            $mail = new PHPMailer;
            // $mail->SMTPDebug = 3;                               // Enable verbose debug output
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = $data['smtp'];  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = $data['login'];                 // SMTP username
            $mail->Password = $data['password'];                           // SMTP password
            $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $data['port'];                                    // TCP port to connect to
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($data['login']);
            $mail->addAddress($data['login']);     // Add a recipient

            $mail->Subject = "Работа";
            $mail->Body = "Завтра нужно принести по 100 грн каждому, на день рождение АНИ.";

            if (!$mail->send()) {
                $log = new ErrorLog();
                $log->message = 'Mailer Error: ' . $mail->ErrorInfo;
                $log->task_id = 0;
                $log->save();

                return false;
            } else {
                return true;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = AccountsData::whereId($id)->first();

        return view("accounts_data.edit", ["data" => $data]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = AccountsData::whereId($id)->first();
        $data->fill($request->all());
        $type_id = $request->get("type_id");
        if (!(empty($request->get("smtp_port")))) {
            $data->smtp_port = $request->get("smtp_port");
        }
        if (!(empty($request->get("smtp_address")))) {
            $data->smtp_address = $request->get("smtp_address");
        }
        if ($type_id == 3 && (empty($data->smtp_address) || empty($data->smtp_port))) {
            $domain = substr($data->login, strrpos($data->login, '@') + 1);
            $smtp_data = SmtpBase::whereDomain($domain)->first();
            if (!(empty($smtp_data))) {
                $data->smtp_port = $smtp_data->port;
                $data->smtp_address = $smtp_data->smtp;
            }
        }
        $data->save();

        switch ($type_id) {
            case 1:
                return redirect()->route('accounts_data.vk');
                break;

            case 2:
                return redirect()->route('accounts_data.ok');
                break;

            case 3:
                return redirect()->route('accounts_data.emails');
                break;

            case 4:
                return redirect()->route('accounts_data.tw');
                break;

            case 5:
                return redirect()->route('accounts_data.ins');
                break;
            case 6:
                return redirect()->route('accounts_data.fb');
                break;
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $data = AccountsData::whereId($id)->first();
        $data->delete();

        return redirect()->back();
    }

    /**
     * Удаление всех записей типа ВК
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyVk()
    {

        DB::table('accounts_data')->where('type_id', '=', 1)->delete();

        return redirect()->route('accounts_data.vk');
    }

    /**
     * Удаление всех записей типа ОК
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyOk()
    {

        DB::table('accounts_data')->where('type_id', '=', 2)->delete();

        return redirect()->route('accounts_data.ok');
    }

    /**
     * Удаление всех записей типа Twitter
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyTw()
    {

        DB::table('accounts_data')->where('type_id', '=', 4)->delete();

        return redirect()->route('accounts_data.tw');
    }

    /**
     * Удаление всех записей типа Facebook
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyFb()
    {

        DB::table('accounts_data')->where('type_id', '=', 6)->delete();

        return redirect()->route('accounts_data.fb');
    }

    /**
     * Удаление всех записей типа Twitter
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyIns()
    {

        DB::table('accounts_data')->where('type_id', '=', 5)->delete();

        return redirect()->route('accounts_data.ins');
    }

    /**
     * Удаление всех записей типа Маилс
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyEmails()
    {

        DB::table('accounts_data')->where('type_id', '=', 3)->delete();

        return redirect()->route('accounts_data.emails');
    }

    /**
     * Очистка таблицы
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        DB::table('accounts_data')->truncate();

        return redirect()->back();
    }

    /**
     * Массовая загрузка ВК
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function vkupload(Request $request)
    {
        if (!(empty($request->get('text')))) {
            $accounts = explode("\n", $request->get('text'));
            $this->vkokParse($accounts, $request->get('user_id'), 1);
        }
        return redirect()->route('accounts_data.vk');
    }

    public function fbupload(Request $request)
    {
        if (!(empty($request->get('text')))) {
            $accounts = explode("\r\n", $request->get('text'));
            $this->vkokParse($accounts, $request->get('user_id'), 6);
        }

        return redirect()->route('accounts_data.fb');
    }

    /**
     * Сохранялка для массовой загрузки ВК м ОК
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function vkokParse($data, $user, $type)
    {
        foreach ($data as $line) {
            $proxy = $this->findProxyId($type);
            $tmp = explode(":", trim($line));
            if (count($tmp) > 1) {
                $accData = new AccountsData;
                $accData->login = $tmp[0];
                $accData->password = $tmp[1];
                $accData->proxy_id = $proxy;
                $accData->type_id = $type;
                $accData->user_id = $user;
                $accData->is_sender = 1;
                $accData->save();
            }
        }
    }


    /**
     * Массовая загрузка ОК
     *
     *
     * @return \Illuminate\Http\Response
     */
    public
    function okupload(Request $request)
    {
        if (!(empty($request->get('text')))) {
            $accounts = explode("\r\n", $request->get('text'));
            $this->vkokParse($accounts, $request->get('user_id'), 2);
        } else {
            if ($request->hasFile('text_file')) {
                $filename = uniqid('ok_file', true) . '.' . $request->file('text_file')->getClientOriginalExtension();
                $request->file('text_file')->storeAs('tmp_files', $filename);
                $file = file(storage_path(config('config.tmp_folder')) . $filename);
                $this->vkokParse($file, $request->get('user_id'), 2);
            }
        }

        return redirect()->route('accounts_data.ok');
    }

    /**
     * Массовая загрузка TW
     *
     *
     * @return \Illuminate\Http\Response
     */
    public
    function twupload(Request $request)
    {
        if (!(empty($request->get('text')))) {
            $accounts = explode("\r\n", $request->get('text'));
            $this->vkokParse($accounts, $request->get('user_id'), 4);
        } else {
            if ($request->hasFile('text_file')) {
                $filename = uniqid('ok_file', true) . '.' . $request->file('text_file')->getClientOriginalExtension();
                $request->file('text_file')->storeAs('tmp_files', $filename);
                $file = file(storage_path(config('config.tmp_folder')) . $filename);
                $this->vkokParse($file, $request->get('user_id'), 4);
            }
        }

        return redirect()->route('accounts_data.tw');
    }

    /**
     * Массовая загрузка Ins
     *
     *
     * @return \Illuminate\Http\Response
     */
    public
    function insupload(Request $request)
    {
        if (!(empty($request->get('text')))) {
            $accounts = explode("\r\n", $request->get('text'));
            $this->vkokParse($accounts, $request->get('user_id'), 5);
        } else {
            if ($request->hasFile('text_file')) {
                $filename = uniqid('ok_file', true) . '.' . $request->file('text_file')->getClientOriginalExtension();
                $request->file('text_file')->storeAs('tmp_files', $filename);
                $file = file(storage_path(config('config.tmp_folder')) . $filename);
                $this->vkokParse($file, $request->get('user_id'), 5);
            }
        }

        return redirect()->route('accounts_data.ins');
    }

    /**
     * Массовая загрузка Мыл
     *
     *
     * @return \Illuminate\Http\Response
     */
    public
    function mailsupload(Request $request)
    {
        if (!(empty($request->get('text')))) {
            $accounts = explode("\n", $request->get('text'));
            $this->mailsParse($accounts, $request->get('user_id'));
        } else {
            if ($request->hasFile('text_file')) {
                $filename = uniqid('smtp_file', true) . '.' . $request->file('text_file')->getClientOriginalExtension();
                $request->file('text_file')->storeAs('tmp_files', $filename);
                $file = file(storage_path(config('config.tmp_folder')) . $filename);
                $this->mailsParse($file, $request->get('user_id'));
            }
        }

        return redirect()->route('accounts_data.emails');
    }

    /**
     * Сохранялка для массовой загрузки Мыл
     *
     *
     * @return \Illuminate\Http\Response
     */
    public
    function mailsParse($data, $user)
    {

        $proxyNumber = 3;

        $proxy = $this->findProxyId(3, $proxyNumber);
        $proxyNumber = $proxy["counter"];
        $proxy_number = $proxy["number"];

        foreach ($data as $line) {

            if ($proxy_number >= $proxyNumber) {
                $proxy = $this->findProxyId(3, $proxyNumber);
                $proxyNumber = $proxy["counter"];
            }

            $tmp = explode(":", trim($line));
            $accData = new AccountsData;
            if (count($tmp) < 3) {
                $domain = substr($tmp[0], strrpos($tmp[0], '@') + 1);
                $smtp_data = SmtpBase::whereDomain($domain)->first();
                if (!(empty($smtp_data))) {
                    $accData->smtp_port = $smtp_data->port;
                    $accData->smtp_address = $smtp_data->smtp;
                } else {
                    continue;
                }
            } else {
                if (is_int($tmp[3])) {
                    $accData->smtp_address = $tmp[2];
                    $accData->smtp_port = $tmp[3];
                } else {
                    $domain = substr($tmp[0], strrpos($tmp[0], '@') + 1);
                    $smtp_data = SmtpBase::whereDomain($domain)->first();
                    if (!(empty($smtp_data))) {
                        $accData->smtp_port = $smtp_data->port;
                        $accData->smtp_address = $smtp_data->smtp;
                    } else {
                        continue;
                    }
                }
            }

            $accData->login = $tmp[0];
            $accData->password = $tmp[1];
            $accData->type_id = 3;
            $accData->user_id = $user;

            $accData->proxy_id = $proxy["proxy_id"];
            $proxy_number++;
            $accData->save();

            unset($tmp);
        }

    }

}

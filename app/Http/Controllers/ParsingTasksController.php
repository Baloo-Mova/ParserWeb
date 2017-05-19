<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TasksType;
use App\Models\Tasks;
use App\Models\TemplateDeliveryMails;
use App\Models\TemplateDeliverySkypes;
use App\Models\TemplateDeliveryMailsFiles;
use App\Models\TemplateDeliveryVK;
use App\Models\TemplateDeliveryOK;
use App\Models\TemplateDeliveryFB;
use App\Models\TemplateDeliveryViber;
use App\Models\TemplateDeliveryWhatsapp;
use App\Models\Parser\SiteLinks;
use App\Models\SearchQueries;
use App\Models\TemplateDeliveryTw;
use App\Models\EmailTemplates;
use Illuminate\Support\Facades\Auth;
use Supervisor\Api;

class ParsingTasksController extends Controller {

    public function index() {
        $data = Tasks::orderBy('id', 'desc')->paginate(config('config.accountsdatapaginate'));
        return view('parsing_tasks.index', ['data' => $data]);
    }

    public function create() {
        $types = TasksType::all();
        return view('parsing_tasks.create', ['types' => $types]);
    }

    public function store(Request $request) {
        //Записываем в таблицу тасков
        $task = new Tasks();
        $task->task_type_id = $request->get('task_type_id');
        $task->task_query = $request->get('task_query');
        $task->active_type = 1;
        $task->reserved = 0;
        $task->google_offset = 0;
        $task->tw_offset = "1";
        $task->need_send = $request->get('send_directly') != null;
        $task->save();
        //Записываем в таблицу тасков
        //Обрабатываем список сайтов, если есть
        $site_list = $request->get('site_list');
        if ($task->task_type_id == 2 && !empty($site_list)) {
            $sites = explode("\r\n", $site_list);
            foreach ($sites as $item) {
                $site_links = new SiteLinks;
                $site_links->task_id = $task->id;
                $site_links->link = $item;
                $site_links->reserved = 0;
                $site_links->save();
                unset($site_links);
            }
        }
        //Обрабатываем список сайтов, если есть
        //Записываем в таблицу шаблонов mails
        if (!empty($request->get('subject')) && !empty($request->get('mails_text'))) {
            $mails = new TemplateDeliveryMails;
            $mails->subject = $request->get('subject');
            $mails->text = $request->get('mails_text');
            $mails->task_id = $task->id;
            $mails->save();
        }
        //Записываем в таблицу шаблонов mails
        //Записываем в таблицу шаблонов вложений для mails
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
                $filename = uniqid('mail_' . $mails->id, true) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('mail_files', $filename);

                $file_model = new TemplateDeliveryMailsFiles;
                $file_model->mail_id = $mails->id;
                $file_model->name = $filename;
                $file_model->path = 'mail_files/' . $filename;
                $file_model->save();

                unset($file_model);
            }
        }
        //Записываем в таблицу шаблонов вложений для mails
        //Записываем в таблицу шаблонов skypes
        if (!empty($request->get('skype_text'))) {
            $skype = new TemplateDeliverySkypes();
            $skype->text = $request->get('skype_text');
            $skype->task_id = $task->id;
            $skype->save();
        }
        if (!empty($request->get('vk_text'))) {
            $vk = new TemplateDeliveryVK();
            $vk->text = $request->get('vk_text');
            $vk->task_id = $task->id;
            $vk->save();
        }
        if (!empty($request->get('ok_text'))) {
            $vk = new TemplateDeliveryOK();
            $vk->text = $request->get('ok_text');
            $vk->task_id = $task->id;
            $vk->save();
        }
        if (!empty($request->get('tw_text'))) {
            $tw = new TemplateDeliveryTw();
            $tw->text = $request->get('tw_text');
            $tw->task_id = $task->id;
            $tw->save();
        }
        if (!empty($request->get('fb_text'))) {
            $fb = new TemplateDeliveryFB();
            $fb->text = $request->get('fb_text');
            $fb->task_id = $task->id;
            $fb->save();
        }
        if (!empty($request->get('viber_text'))) {
            $viber = new TemplateDeliveryViber();
            $viber->text = $request->get('viber_text');
            $viber->task_id = $task->id;
            $viber->save();
        }
        if (!empty($request->get('whats_text'))) {
            $whatsapp = new TemplateDeliveryWhatsapp();
            $whatsapp->text = $request->get('whats_text');
            $whatsapp->task_id = $task->id;
            $whatsapp->save();
        }
        //Записываем в таблицу шаблонов skypes

        return redirect()->route('parsing_tasks.index');
    }

    public function show($id) {
        $task = Tasks::whereId($id)->first();
        $mails = $task->getMail()->first();
        $skype = $task->getSkype()->first();
        $vk = $task->getVK()->first();
        $ok = $task->getOK()->first();
        $tw = $task->getTW()->first();
        $fb = $task->getFB()->first();
        $viber = $task->getViber()->first();
        $whats = $task->getWhatsapp()->first();
        $search_queries = SearchQueries::where(['task_id' => $id])->orderBy('id', 'desc')->paginate(10);

        $active_type = "";

        switch ($task->active_type) {
            case 0:
                $active_type = "Пауза";
                break;
            case 1:
                $active_type = "Работает";
                break;
            case 2:
                $active_type = "Остановлен";
                break;
        }

        return view('parsing_tasks.show', [
            'data' => $task,
            'active_type' => $active_type,
            'mails' => $mails,
            'skype' => $skype,
            'vk' => $vk,
            'ok' => $ok,
            'tw' => $tw,
            'fb' => $fb,
            'search_queries' => $search_queries,
            'viber' => $viber,
            'whats' => $whats
        ]);
    }

    public function start($id) {
        $task = Tasks::whereId($id)->first();
        $task->active_type = 1;
        $task->save();

        return redirect()->back();
    }

    public function stop($id) {
        $task = Tasks::whereId($id)->first();
        $task->active_type = 2;
        $task->save();

        return redirect()->back();
    }

    public function reserved($id) {
        $task = Tasks::whereId($id)->first();
        $task->reserved = 0;
        $task->save();

        return redirect()->back();
    }

    public function startDelivery($id) {
        $task = Tasks::whereId($id)->first();
        $task->need_send = 1;
        $task->save();

        return redirect()->back();
    }

    public function stopDelivery($id) {
        $task = Tasks::whereId($id)->first();
        $task->need_send = 0;
        $task->save();

        return redirect()->back();
    }

    public function changeDeliveryInfo(Request $request) {
        $skype_text = $request->get("skype_text");
        $mail_subj = $request->get("mail_subject");
        $mail_text = $request->get("mail_text");
        $ok_text = $request->get("ok_text");
        $tw_text = $request->get("tw_text");
        $fb_text = $request->get("fb_text");
        $viber_text = $request->get("viber_text");
        $whats_text = $request->get("whats_text");


        if (isset($skype_text)) {
            $skype = TemplateDeliverySkypes::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($skype)) {
                $skype = new TemplateDeliverySkypes;
                $skype->task_id = $request->get("delivery_id");
            }
            $skype->text = $skype_text;
            $skype->save();
        }

        if (isset($ok_text)) {
            $ok = TemplateDeliveryOK::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($ok)) {
                $ok = new TemplateDeliveryOK;
                $ok->task_id = $request->get("delivery_id");
            }
            $ok->text = $ok_text;
            $ok->save();
        }

        if (isset($tw_text)) {
            $tw = TemplateDeliveryTw::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($tw)) {
                $tw = new TemplateDeliveryTw();
                $tw->task_id = $request->get("delivery_id");
            }
            $tw->text = $tw_text;
            $tw->save();
        }
        if (isset($fb_text)) {
            $fb = TemplateDeliveryFB::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($fb)) {
                $fb = new TemplateDeliveryFB();
                $fb->task_id = $request->get("delivery_id");
            }
            $fb->text = $fb_text;
            $fb->save();
        }

        if (isset($mail_subj)) {
            $mail = TemplateDeliveryMails::where("task_id", "=", $request->get("delivery_id"))->first();

            if (empty($mail)) {
                $mail = new TemplateDeliveryMails;
                $mail->task_id = $request->get("delivery_id");
            }

            $mail->subject = $mail_subj;

            if (isset($mail_text)) {
                $mail->text = $mail_text;
            }

            $mail->save();
        }

        if (isset($viber_text)) {
            $count_lines = substr_count($viber_text, "\r\n");
           
                $viber_text = str_pad($viber_text,strlen($viber_text)+5*2,"\r\n", STR_PAD_RIGHT);
            
            //dd((strlen($viber_text)/42).$viber_text);
            $viber = TemplateDeliveryViber::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($viber)) {
                $viber = new TemplateDeliveryViber();
                $viber->task_id = $request->get("delivery_id");
            }
            $viber->text = $viber_text;
            $viber->save();
        }
        if (isset($whats_text)) {
            $whatsapp = TemplateDeliveryWhatsapp::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($whatsapp)) {
                $whatsapp = new TemplateDeliveryWhatsapp();
                $whatsapp->task_id = $request->get("delivery_id");
            }
            $whatsapp->text = $whats_text;
            $whatsapp->save();
        }
        return redirect()->back();
    }

    public function getCsv($id) {

        $table = SearchQueries::where('task_id', '=', $id)->get()->toArray();

        if (count($table) > 0) {
            chmod("search_queries_result.csv", 0755);
            $file = fopen('search_queries_result.csv', 'w');
            foreach ($table as $row) {

                foreach ($row as $key => $item) {
                    $row[$key] = $item == null ? null : iconv("UTF-8", "Windows-1251", $item);
                }
                fputcsv($file, $row);
            }
            fclose($file);

            return response()->download('search_queries_result.csv');
        } else {
            return redirect()->back();
        }
    }

    public function testingDeliveryMails() {
        $user_id = Auth::user()->id;
        $email_templates = EmailTemplates::where(['user_id'=>$user_id])->get();
        //dd($email_templates);
        return view("parsing_tasks.testingDeliveryMails", ["data" => $email_templates]);
    }

    public function storeTestingDeliveryMails(Request $request) {
        //Записываем в таблицу тасков

        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->reserved = 0;
        $task->google_offset = 0;
        $task->tw_offset = "-1";
        $task->need_send = 1;
        $task->save();
        $task_id = $task->id;
        //Записываем в таблицу тасков
        //Обрабатываем список сайтов, если есть
        $mails_list = $request->get('mails_list');
        if (!empty($mails_list)) {

            $mails = explode("\r\n", $mails_list);
            $mails_number = count($mails);
            $tmp_mail = [];

            if ($mails_number > 3) {
                foreach ($mails as $key => $value) {
                    ++$key;
                    $tmp_mail[] = $value;

                    if ($key % 3 == 0) {
                        $search_query = new SearchQueries;
                        $search_query->link = "Тестовая рассылка";
                        $search_query->mails = implode(",", $tmp_mail);
                        $search_query->phones = null;
                        $search_query->skypes = null;
                        $search_query->task_id = $task_id;
                        $search_query->email_reserved = 0;
                        $search_query->email_sended = 0;
                        $search_query->sk_recevied = 0;
                        $search_query->sk_sended = 0;
                        $search_query->save();

                        unset($tmp_mail);
                    }
                    if ($key == $mails_number) {
                        $search_query = new SearchQueries;
                        $search_query->link = "Тестовая рассылка";
                        $search_query->mails = implode(",", $tmp_mail);
                        $search_query->phones = null;
                        $search_query->skypes = null;
                        $search_query->task_id = $task_id;
                        $search_query->email_reserved = 0;
                        $search_query->email_sended = 0;
                        $search_query->sk_recevied = 0;
                        $search_query->sk_sended = 0;
                        $search_query->save();
                    }
                }
            } else {
                foreach ($mails as $item) {
                    $tmp_mail[] = $item;
                }
                $search_query = new SearchQueries;
                $search_query->link = "Тестовая рассылка";
                $search_query->mails = implode(",", $tmp_mail);
                $search_query->phones = null;
                $search_query->skypes = null;
                $search_query->task_id = $task_id;
                $search_query->email_reserved = 0;
                $search_query->email_sended = 0;
                $search_query->sk_recevied = 0;
                $search_query->sk_sended = 0;
                $search_query->save();
            }
        }
        //Обрабатываем список сайтов, если есть
        //Записываем в таблицу шаблонов mails
        if (!empty($request->get('subject')) && !empty($request->get('mails_text'))) {
            $mails = new TemplateDeliveryMails;
            $mails->subject = $request->get('subject');
            $mails->text = $request->get('mails_text');
            $mails->task_id = $task_id;
            $mails->save();
        }
        //Записываем в таблицу шаблонов mails
        //Записываем в таблицу шаблонов вложений для mails
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
                $filename = uniqid('mail_' . $mails->id, true) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('mail_files', $filename);

                $file_model = new TemplateDeliveryMailsFiles;
                $file_model->mail_id = $mails->id;
                $file_model->name = $filename;
                $file_model->path = 'mail_files/' . $filename;
                $file_model->save();

                unset($file_model);
            }
        }
        //Записываем в таблицу шаблонов вложений для mails

        return redirect()->route('parsing_tasks.index');
    }

    public function testingDeliverySkypes() {
        return view("parsing_tasks.testingDeliverySkypes");
    }

    public function storeTestingDeliverySkypes(Request $request) {
        //Записываем в таблицу тасков
        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->reserved = 0;
        $task->google_offset = 0;
        $task->need_send = 1;
        $task->tw_offset = "-1";
        $task->fb_complete = "1";
        $task->save();
        $task_id = $task->id;
        //Записываем в таблицу тасков

        $skypes_list = $request->get('skypes_list');
        if (!empty($skypes_list)) {

            $skypes = explode("\r\n", $skypes_list);

            foreach ($skypes as $item) {
                $search_query = new SearchQueries;
                $search_query->link = "Тестовая рассылка";
                $search_query->mails = null;
                $search_query->phones = null;
                $search_query->skypes = $item;
                $search_query->task_id = $task_id;
                $search_query->email_reserved = 0;
                $search_query->email_sended = 0;
                $search_query->sk_recevied = 0;
                $search_query->sk_sended = 0;
                $search_query->save();
            }
        }

        //Записываем в таблицу шаблонов skypes
        if (!empty($request->get('skypes_text'))) {
            $skype = new TemplateDeliverySkypes();
            $skype->text = $request->get('skypes_text');
            $skype->task_id = $task_id;
            $skype->save();
        }
        //Записываем в таблицу шаблонов skypes

        return redirect()->route('parsing_tasks.index');
    }

    public function testingDeliveryVK() {
        return view("parsing_tasks.testingDeliveryVK");
    }

    public function storeTestingDeliveryVK(Request $request) {
        //Записываем в таблицу тасков
        $task = new Tasks();

        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->reserved = 0;
        $task->google_offset = 0;
        $task->need_send = 1;
        $task->tw_offset = "-1";
        $task->fb_complete = "1";
        $task->save();
        $task_id = $task->id;
        //Записываем в таблицу тасков

        $vks_list = $request->get('vk_list');
        if (!empty($vks_list)) {

            $vks = explode("\r\n", $vks_list);

            foreach ($vks as $item) {
                $search_query = new SearchQueries;
                $search_query->link = "Тестовая рассылка";
                $search_query->mails = " ";
                $search_query->phones = " ";
                $search_query->skypes = "";
                $search_query->vk_id = $item;
                $search_query->task_id = $task_id;
                $search_query->email_reserved = 0;
                $search_query->email_sended = 0;
                $search_query->sk_recevied = 0;
                $search_query->sk_sended = 0;
                $search_query->save();
            }
        }

        //Записываем в таблицу шаблонов skypes
        if (!empty($request->get('vk_text'))) {
            $vk = new TemplateDeliveryVK();
            $vk->text = $request->get('vk_text');
            $vk->task_id = $task_id;
            $vk->save();
        }
        //Записываем в таблицу шаблонов skypes

        return redirect()->route('parsing_tasks.index');
    }

    public function testingDeliveryOK() {
        return view("parsing_tasks.testingDeliveryOK");
    }

    public function storeTestingDeliveryOK(Request $request) {

        //Записываем в таблицу тасков
        $task = new Tasks();

        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->reserved = 0;
        $task->google_offset = 0;
        $task->need_send = 1;
        $task->tw_offset = "-1";
        $task->fb_complete = "1";
        $task->save();
        $task_id = $task->id;
        //Записываем в таблицу тасков

        $oks_list = $request->get('ok_list');
        if (!empty($oks_list)) {

            $oks = explode("\r\n", $oks_list);

            foreach ($oks as $item) {
                $search_query = new SearchQueries;
                $search_query->link = "Тестовая рассылка";
                $search_query->mails = " ";
                $search_query->phones = " ";
                $search_query->skypes = "";
                $search_query->vk_id = null;
                $search_query->ok_user_id = $item;
                $search_query->task_id = $task_id;
                $search_query->email_reserved = 0;
                $search_query->email_sended = 0;
                $search_query->sk_recevied = 0;
                $search_query->sk_sended = 0;
                $search_query->save();
            }
        }

        //Записываем в таблицу шаблонов ok
        if (!empty($request->get('ok_text'))) {
            $vk = new TemplateDeliveryOK();
            $vk->text = $request->get('ok_text');
            $vk->task_id = $task_id;
            $vk->save();
        }
        //Записываем в таблицу шаблонов skypes

        return redirect()->route('parsing_tasks.index');
    }

    public function testingDeliveryTW() {
        return view("parsing_tasks.testingDeliveryTW");
    }

    public function storeTestingDeliveryTW(Request $request) {

        //Записываем в таблицу тасков
        $task = new Tasks();

        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->reserved = 0;
        $task->google_offset = 0;
        $task->need_send = 1;
        $task->tw_offset = "-1";
        $task->fb_complete = "1";
        $task->save();
        $task_id = $task->id;
        //Записываем в таблицу тасков

        $tws_list = $request->get('tw_list');
        if (!empty($tws_list)) {

            $tws = explode("\r\n", $tws_list);

            foreach ($tws as $item) {
                $search_query = new SearchQueries;
                $search_query->link = "Тестовая рассылка";
                $search_query->mails = " ";
                $search_query->phones = " ";
                $search_query->skypes = "";
                $search_query->vk_id = null;
                $search_query->tw_user_id = $item;
                $search_query->task_id = $task_id;
                $search_query->email_reserved = 0;
                $search_query->email_sended = 0;
                $search_query->sk_recevied = 0;
                $search_query->sk_sended = 0;
                $search_query->save();
            }
        }

        //Записываем в таблицу шаблонов ok
        if (!empty($request->get('tw_text'))) {
            $tw = new TemplateDeliveryTW();
            $tw->text = $request->get('tw_text');
            $tw->task_id = $task_id;
            $tw->save();
        }
        //Записываем в таблицу шаблонов skypes

        return redirect()->route('parsing_tasks.index');
    }

    public function testingDeliveryFB() {
        return view("parsing_tasks.testingDeliveryFB");
    }

    public function storeTestingDeliveryFB(Request $request) {
        //Записываем в таблицу тасков
        $task = new Tasks();

        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->reserved = 0;
        $task->google_offset = 0;
        $task->need_send = 1;
        $task->tw_offset = "-1";
        $task->fb_complete = "1";
        $task->save();
        $task_id = $task->id;
        //Записываем в таблицу тасков

        $fbs_list = $request->get('fb_list');
        if (!empty($fbs_list)) {

            $fbs = explode("\r\n", $fbs_list);

            foreach ($fbs as $item) {
                $search_query = new SearchQueries;
                $search_query->link = "Тестовая рассылка";
                $search_query->mails = " ";
                $search_query->phones = " ";
                $search_query->skypes = "";
                $search_query->fb_id = $item;
                $search_query->task_id = $task_id;
                $search_query->email_reserved = 0;
                $search_query->email_sended = 0;
                $search_query->sk_recevied = 0;
                $search_query->sk_sended = 0;
                $search_query->save();
            }
        }

        //Записываем в таблицу шаблонов skypes
        if (!empty($request->get('fb_text'))) {
            $fb = new TemplateDeliveryFB();
            $fb->text = $request->get('fb_text');
            $fb->task_id = $task_id;
            $fb->save();
        }
        //Записываем в таблицу шаблонов skypes

        return redirect()->route('parsing_tasks.index');
    }

    public function testingDeliveryAndroidBots() {
        return view("parsing_tasks.testingDeliveryAndroidBots");
    }

    public function storeTestingDeliveryAndroidBots(Request $request) {
        //Записываем в таблицу тасков
        $task = new Tasks();

        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->reserved = 0;
        $task->google_offset = 0;
        $task->need_send = 1;
        $task->tw_offset = "-1";
        $task->fb_complete = "1";
        $task->save();
        $task_id = $task->id;
        //Записываем в таблицу тасков

        $phones_list = $request->get('phones_list');
        if (!empty($phones_list)) {
            $phones_list = str_replace(["+"],"",$phones_list);
            $phones_list = explode("\r\n", $phones_list);
            $phones_list = implode(",", $phones_list);
           
          //dd($phones_list);
                $search_query = new SearchQueries;
                $search_query->link = "Тестовая рассылка";
                $search_query->mails = " ";
                $search_query->phones = $phones_list;
                $search_query->skypes = "";
                $search_query->fb_name = "";
                $search_query->task_id = $task_id;
                $search_query->email_reserved = 0;
                $search_query->email_sended = 0;
                $search_query->sk_recevied = 0;
                $search_query->sk_sended = 0;
                $search_query->save();
            
        }

        //Записываем в таблицу шаблонов skypes
        $viber_text=$request->get('viber_text');
        if (!empty($viber_text)) {
            
                $viber_text = str_pad($viber_text,strlen($viber_text)+5*2,"\r\n", STR_PAD_RIGHT);
           
            //dd($viber_text);
            $viber = new TemplateDeliveryViber();
            $viber->text = $viber_text;
            $viber->task_id = $task_id;
            $viber->save();
        }
        if (!empty($request->get('whats_text'))) {
            $whatsapp = new TemplateDeliveryWhatsapp();
            $whatsapp->text = $request->get('whats_text');
            $whatsapp->task_id = $task_id;
            $whatsapp->save();
        }
        

        return redirect()->route('parsing_tasks.index');
    }
    
}

<?php

namespace App\Http\Controllers;

use App\Models\Contacts;
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
use Illuminate\Support\Facades\DB;
use Supervisor\Api;
use Illuminate\Support\Facades\Storage;
use Kamaln7\Toastr\Facades\Toastr;

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
        //$task->reserved = 0;
        //$task->google_offset = 0;
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
            if($this->checkSymbolsInText($request->get('mails_text'))) {
                $mails->save();
            }else{
                Toastr::error("В тексте Contacts найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }
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
            if($this->checkSymbolsInText($request->get('skype_text'))) {
                $skype->save();
            }else{
                Toastr::error("В тексте Skype найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }

        }
        if (!empty($request->get('vk_text'))) {
            $vk = new TemplateDeliveryVK();
            $vk->text = $request->get('vk_text');
            $vk->task_id = $task->id;
            if($this->checkSymbolsInText($request->get('vk_text'))) {
                $vk->save();
            }else{
                Toastr::error("В тексте Вконтакте найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }
        }
        if (!empty($request->get('ok_text'))) {
            $ok = new TemplateDeliveryOK();
            $ok->text = $request->get('ok_text');
            $ok->task_id = $task->id;
            if($this->checkSymbolsInText($request->get('ok_text'))) {
                $ok->save();
            }else{
                Toastr::error("В тексте Одноклассники найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }
        }
        if (!empty($request->get('tw_text'))) {
            $tw = new TemplateDeliveryTw();
            $tw->text = $request->get('tw_text');
            $tw->task_id = $task->id;
            if($this->checkSymbolsInText($request->get('tw_text'))) {
                $tw->save();
            }
        }
        if (!empty($request->get('fb_text'))) {
            $fb = new TemplateDeliveryFB();
            $fb->text = $request->get('fb_text');
            $fb->task_id = $task->id;
            if($this->checkSymbolsInText($request->get('fb_text'))) {
                $fb->save();
            }else{
                Toastr::error("В тексте Facebook найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }

        }
        if (!empty($request->get('viber_text'))) {
            $viber = new TemplateDeliveryViber();
            $viber->text = $request->get('viber_text');
            $viber->task_id = $task->id;
            if($this->checkSymbolsInText($request->get('viber_text'))) {
                $viber->save();
            }else{
                Toastr::error("В тексте Viber найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }
        }
        if (!empty($request->get('whats_text'))) {
            $whatsapp = new TemplateDeliveryWhatsapp();
            $whatsapp->text = $request->get('whats_text');
            $whatsapp->task_id = $task->id;
            if($this->checkSymbolsInText($request->get('whats_text'))) {
                $whatsapp->save();
            }else{
                Toastr::error("В тексте WhatsApp найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }
        }
        //Записываем в таблицу шаблонов skypes

        return redirect()->route('parsing_tasks.index');
    }

    public function show($id) {
        $task = Tasks::whereId($id)->first();
        $mails = $task->getMail()->first();
        $templateFile = (isset($mails)) ? TemplateDeliveryMailsFiles::whereMailId($mails->id)->first() : null;
        $mails_file = (isset($templateFile)) ? $templateFile->path : "";
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
            'mails_file' => $mails_file,
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
       // $task->reserved = 0;
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
        $vk_text = $request->get("vk_text");
        //$tw_text = $request->get("tw_text");
        $fb_text = $request->get("fb_text");
        $viber_text = $request->get("viber_text");
        $whats_text = $request->get("whats_text");
        $mailsFile = $request->hasFile('mails_file');
        $mail_id = $request->get('mail_id');


        if (isset($skype_text)) {
            $skype = TemplateDeliverySkypes::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($skype)) {
                $skype = new TemplateDeliverySkypes;
                $skype->task_id = $request->get("delivery_id");
            }

            if($this->checkSymbolsInText($skype_text)){
                $skype->text = $skype_text;
                $skype->save();
            }else{
                Toastr::error("В тексте Skype найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }

        }

        if (isset($ok_text)) {
            $ok = TemplateDeliveryOK::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($ok)) {
                $ok = new TemplateDeliveryOK;
                $ok->task_id = $request->get("delivery_id");
            }
            if($this->checkSymbolsInText($ok_text)){
                $ok->text = $ok_text;
                $ok->save();
            }else{
                Toastr::error("В тексте Одноклассники найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }
        }

        if (isset($vk_text)) {
            $vk = TemplateDeliveryVK::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($vk)) {
                $vk = new TemplateDeliveryVK;
                $vk->task_id = $request->get("delivery_id");
            }
            if($this->checkSymbolsInText($vk_text)){
                $vk->text = $vk_text;
                $vk->save();
            }else{
                Toastr::error("В тексте Вконтакте найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }
        }

        /*if (isset($tw_text)) {
            $tw = TemplateDeliveryTw::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($tw)) {
                $tw = new TemplateDeliveryTw();
                $tw->task_id = $request->get("delivery_id");
            }
            $tw->text = $tw_text;
            $tw->save();
        }*/
        if (isset($fb_text)) {
            $fb = TemplateDeliveryFB::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($fb)) {
                $fb = new TemplateDeliveryFB();
                $fb->task_id = $request->get("delivery_id");
            }
            if($this->checkSymbolsInText($fb_text)){
                $fb->text = $fb_text;
                $fb->save();
            }else{
                Toastr::error("В тексте Facebook найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }
        }

        if (isset($mail_subj)) {
            $mail = TemplateDeliveryMails::where("task_id", "=", $request->get("delivery_id"))->first();

            if (empty($mail)) {
                $mail = new TemplateDeliveryMails;
                $mail->task_id = $request->get("delivery_id");
            }

            $mail->subject = $mail_subj;

            if (isset($mail_text)) {
                if($this->checkSymbolsInText($mail_text)){
                    $mail->text = $mail_text;
                }else{
                    Toastr::error("В тексте Contacts найдены недопустимые символы!", $title = "Ошибка!", $options = []);
                }

            }

            $mail->save();

        }

        if($mailsFile){

            if(!$mail_id){
                $mail_id = $mail->id;
            }

            $isExistFile = TemplateDeliveryMailsFiles::whereMailId($mail_id)->first();
            $file = $request->file('mails_file');

            if(isset($isExistFile)){
                Storage::delete($isExistFile->path);
                $filename = uniqid('mail_' . $mail_id, true) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('mail_files', $filename);
                $isExistFile->name = $filename;
                $isExistFile->path = 'mail_files/' . $filename;
                $isExistFile->save();

            }else{
                $filename = uniqid('mail_' . $mail_id, true) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('mail_files', $filename);
                $file_model = new TemplateDeliveryMailsFiles;
                $file_model->mail_id = $mail_id;
                $file_model->name = $filename;
                $file_model->path = 'mail_files/' . $filename;
                $file_model->save();
            }

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
            if($this->checkSymbolsInText($viber_text)){
                $viber->text = $viber_text;
                $viber->save();
            }else{
                Toastr::error("В тексте Viber найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }
        }
        if (isset($whats_text)) {
            $whatsapp = TemplateDeliveryWhatsapp::where("task_id", "=", $request->get("delivery_id"))->first();
            if (empty($whatsapp)) {
                $whatsapp = new TemplateDeliveryWhatsapp();
                $whatsapp->task_id = $request->get("delivery_id");
            }
            if($this->checkSymbolsInText($whats_text)){
                $whatsapp->text = $whats_text;
                $whatsapp->save();
            }else{
                Toastr::error("В тексте WhatsApp найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            }
        }
        return redirect()->back();
    }

    private function checkSymbolsInText($text)
    {
        $f = substr_count($text, "{");
        $s = substr_count($text, "}");

        if($f == $s){
            return true;
        }else{
            return false;
        }
    }

    public function getCsv($id) {
set_time_limit(0);
        $table = DB::select( DB::raw('SELECT search_queries.link, search_queries.city, search_queries.name,
                                    (SELECT GROUP_CONCAT(value SEPARATOR ", ") FROM contacts where search_queries_id=search_queries.id AND type=1) as mails,
                                    (SELECT GROUP_CONCAT(value SEPARATOR ", ") FROM contacts where search_queries_id=search_queries.id AND type=2) as phones,
                                    (SELECT GROUP_CONCAT(value SEPARATOR ", ") FROM contacts where search_queries_id=search_queries.id AND type=3) as skypes 
                                    FROM search_queries where task_id='.$id));

        $cols = [];
        $res = [];
        $i = 0;

        if (count($table) > 0) {
            $file = fopen("parse_result_".$id.".csv", 'w');

            foreach ($table[0] as $key => $row) {
                $cols[] = $key;
            }

            fputcsv($file, $cols, ";");
            foreach ($table as $row) {
                $res = [];
                foreach ($row as $item) {
                    $res[] = $item === null ? null : "=\"" .iconv("UTF-8", "Windows-1251//IGNORE", $item). "\"";
                }
                $i++;
                fputcsv($file, $res, ';');
            }
            fclose($file);

            return response()->download('parse_result_'.$id.'.csv');
        } else {
            return redirect()->back();
        }
    }

    public function getFromCsv(Request $request) {
        if ($request->hasFile('myfile')) {
            $fe = explode(".",$request->myfile->getClientOriginalName());
            if($fe[1] != "csv"){
                return redirect()->back();
            }
        }else{return redirect()->back();
        }

        if (( $file = fopen($request->myfile->getRealPath(), 'r')) == FALSE) {
            return redirect()->back();
        }

        $task_id = $request->get('task_id');

        $row = 1;
        $res = [];

        $cols = [];

        $contacts = [];
        $noCols = [];

        foreach (fgetcsv($file, 1000, ";") as $key=>$item){
            if($item == "mails"){
                $noCols["mails"] = $key;
                continue;
            }
            if($item == "phones"){
                $noCols["phones"] = $key;
                continue;
            }
            if($item == "skypes"){
                $noCols["skypes"] = $key;
                continue;
            }
            $cols[] = $item;
        }

        while (($data = fgetcsv($file, 1000, ";")) !== FALSE) {

            $num = count($data);
            $res = [];
            $contacts = [];

            for ($c=0; $c < $num; $c++) {
                if($c != $noCols["mails"] && $c != $noCols["phones"] && $c != $noCols["skypes"]){
                    $res[$cols[$c]] = ($data[$c] == '') ? NULL : str_replace(["\"", "="], "", $data[$c]);
                    $res["task_id"] = $task_id;
                }
            }

            $ins = SearchQueries::create($res);

            // mails
            $tmp_res = trim(str_replace(["\"", "="], "", $data[$noCols["mails"]]));
            if($tmp_res != ''){
                $tmp = explode(",", $tmp_res);
                if(count($tmp) > 1){
                    foreach ($tmp as $i){
                        $contacts[] = ["value" => $i, "type" => 1, "search_queries_id" => $ins->id];
                    }
                }else{
                    $contacts[] = ["value" => $tmp[0], "type" => 1, "search_queries_id" => $ins->id];
                }
            }
            //mails

            //phones
            $tmp_res = trim(str_replace(["\"", "="], "", $data[$noCols["phones"]]));
            if($tmp_res != '') {
                $tmp = explode(",", $tmp_res);
                if (count($tmp) > 1) {
                    foreach ($tmp as $i) {
                        $contacts[] = ["value" => $i, "type" => 2, "search_queries_id" => $ins->id];
                    }
                } else {
                    $contacts[] = ["value" => $tmp[0], "type" => 2, "search_queries_id" => $ins->id];
                }
            }
            //phones
            //
            $tmp_res = trim(str_replace(["\"", "="], "", $data[$noCols["skypes"]]));
            if($tmp_res != '') {
                $tmp = explode(",", $tmp_res);
                if (count($tmp) > 1) {
                    foreach ($tmp as $i) {
                        $contacts[] = ["value" => $i, "type" => 3, "search_queries_id" => $ins->id];
                    }
                } else {
                    $contacts[] = ["value" => $tmp[0], "type" => 3, "search_queries_id" => $ins->id];
                }
            }
            //

            Contacts::insert($contacts);
            $row++;

        }

        return redirect()->back();
    }

    public function testingDeliveryMails() {
        $user_id = Auth::user()->id;
        $email_templates = EmailTemplates::where(['user_id'=>$user_id])->get();
        //dd($email_templates);
        return view("parsing_tasks.testingDeliveryMails", ["data" => $email_templates]);
    }

    public function storeTestingDeliveryMails(Request $request) {
        if(!$this->checkSymbolsInText($request->get('mails_text'))) {
            Toastr::error("В тексте Contacts найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            return back();
        }
        //Записываем в таблицу тасков
        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->google_ru = 0;
        $task->google_ru_offset = 0;
        $task->tw_offset = "-1";
        $task->need_send = 1;
        $task->save();
        $task_id = $task->id;
        //Записываем в таблицу тасков
        //Обрабатываем список сайтов, если есть
        $mails_list = $request->get('mails_list');
        if (!empty($mails_list)) {

            $mails = explode("\r\n", $mails_list);
            $contacts = [];

            $search_query = new SearchQueries;
            $search_query->link = "Тестовая рассылка";
            $search_query->task_id = $task_id;
            $search_query->email_reserved = 0;
            $search_query->email_sended = 0;
            $search_query->sk_recevied = 0;
            $search_query->sk_sended = 0;
            $search_query->save();

            foreach ($mails as $mailItem) {
                $contacts[] = ["value" => $mailItem, "type" => 1, "search_queries_id" => $search_query->id];
            }

            Contacts::insert($contacts);
            $contacts = [];
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
        if(!$this->checkSymbolsInText($request->get('skypes_text'))) {
            Toastr::error("В тексте Skype найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            return back();
        }
        //Записываем в таблицу тасков
        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->google_ru = 0;
        $task->google_ru_offset = 0;
        $task->need_send = 1;
        $task->tw_offset = "-1";
        $task->fb_complete = "1";
        $task->save();
        $task_id = $task->id;
        //Записываем в таблицу тасков

        $skypes_list = $request->get('skypes_list');
        if (!empty($skypes_list)) {

            $skypes = explode("\r\n", $skypes_list);

            $search_query = new SearchQueries;
            $search_query->link = "Тестовая рассылка";
            $search_query->task_id = $task_id;
            $search_query->email_reserved = 0;
            $search_query->email_sended = 0;
            $search_query->sk_recevied = 0;
            $search_query->sk_sended = 0;
            $search_query->save();

            $contacts = [];

            foreach ($skypes as $item) {
                $contacts[] = ["value" => $item, "type" => 3, "search_queries_id" => $search_query->id];
            }

            Contacts::insert($contacts);
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
        if(!$this->checkSymbolsInText($request->get('vk_text'))) {
            Toastr::error("В тексте Вконтакте найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            return back();
        }
        //Записываем в таблицу тасков
        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->google_ru = 0;
        $task->google_ru_offset = 0;
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
        if(!$this->checkSymbolsInText($request->get('ok_text'))) {
            Toastr::error("В тексте Одноклассники найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            return back();
        }
        //Записываем в таблицу тасков
        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        $task->google_ru = 0;
        $task->google_ru_offset = 0;
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
        $task->google_ru = 0;
        $task->google_ru_offset = 0;
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
        if(!$this->checkSymbolsInText($request->get('fb_text'))) {
            Toastr::error("В тексте Facebook найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            return back();
        }
        //Записываем в таблицу тасков
        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        //$task->reserved = 0;
        //$task->google_offset = 0;
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
        if(!$this->checkSymbolsInText($request->get('viber_text'))) {
            Toastr::error("В тексте Viber найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            return back();
        }
        if(!$this->checkSymbolsInText($request->get('whats_text'))) {
            Toastr::error("В тексте WhatsApp найдены недопустимые символы!", $title = "Ошибка!", $options = []);
            return back();
        }
        //Записываем в таблицу тасков
        $task = new Tasks();

        $task->task_type_id = 3;
        $task->task_query = "Тестовая рассылка";
        $task->active_type = 1;
        //$task->reserved = 0;
       // $task->google_offset = 0;
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
            $contacts = [];
           
          //dd($phones_list);
                $search_query = new SearchQueries;
                $search_query->link = "Тестовая рассылка";
                $search_query->task_id = $task_id;
                $search_query->email_reserved = 0;
                $search_query->email_sended = 0;
                $search_query->sk_recevied = 0;
                $search_query->sk_sended = 0;
                $search_query->save();

            foreach($phones_list as $ph){
                $contacts[] = ["type" => 2, "value" => $ph, "search_queries_id" => $search_query->id];
            }

            Contacts::insert($contacts);

            
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

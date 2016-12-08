<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TasksType;
use App\Models\Tasks;
use App\Models\TemplateDeliveryMails;
use App\Models\TemplateDeliverySkypes;
use App\Models\TemplateDeliveryMailsFiles;
use App\Models\Parser\SiteLinks;
use App\Models\SearchQueries;
use Supervisor\Api;

class ParsingTasksController extends Controller
{
    public function index()
    {
        $data = Tasks::paginate(config('config.accountsdatapaginate'));
        return view('parsing_tasks.index', ['data' => $data]);
    }

    public function create()
    {
        $types = TasksType::all();
        return view('parsing_tasks.create', ['types' => $types]);
    }

    public function store(Request $request)
    {
        //Записываем в таблицу тасков
        $task = new Tasks();
            $task->task_type_id = $request->get('task_type_id');
            $task->task_query = $request->get('task_query');
            $task->active_type = 1;
            $task->reserved = 0;
            $task->google_offset = 0;
            $task->need_send = $request->get('send_directly') != null;
        $task->save();
        //Записываем в таблицу тасков

        //Обрабатываем список сайтов, если есть
        $site_list = $request->get('site_list');
        if($task->task_type_id == 2 && !empty($site_list)){
            $sites = explode("\r\n", $site_list);
            foreach ($sites as $item){
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
        if(!empty($request->get('subject')) && !empty($request->get('mails_text'))){
            $mails = new TemplateDeliveryMails;
                $mails->subject = $request->get('subject');
                $mails->text = $request->get('mails_text');
                $mails->task_id = $task->id;
            $mails->save();
        }
        //Записываем в таблицу шаблонов mails

        //Записываем в таблицу шаблонов вложений для mails
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file){
                $filename = uniqid('mail_'.$mails->id, true) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('mail_files', $filename);

                $file_model = new TemplateDeliveryMailsFiles;
                    $file_model->mail_id = $mails->id;
                    $file_model->name = $filename;
                    $file_model->path = 'mail_files/'.$filename;
                $file_model->save();

                unset($file_model);
            }
        }
        //Записываем в таблицу шаблонов вложений для mails

        //Записываем в таблицу шаблонов skypes
        if(!empty($request->get('skype_text'))){
            $skype = new TemplateDeliverySkypes();
                $skype->text = $request->get('skype_text');
                $skype->task_id = $task->id;
            $skype->save();
        }
        //Записываем в таблицу шаблонов skypes

        return redirect()->route('parsing_tasks.index');
    }

    public function show($id)
    {
        $task = Tasks::whereId($id)->first();
        $mails = $task->getMail()->first();
        $skype = $task->getSkype()->first();
        $search_queries = SearchQueries::where(['task_id' => $id])->orderBy('id', 'desc')->paginate(10);

        $active_type = "";

        //$api = new Api('127.0.0.1', , '', '' );
        //$task_info = $api->getProcessInfo('myworker');
        switch ($task->active_type){
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
            'data'              => $task,
            'active_type'       => $active_type,
            'mails'             => $mails,
            'skype'             => $skype,
            'search_queries'    => $search_queries
        ]);
    }

    public function start($id)
    {
        $task = Tasks::whereId($id)->first();
        $task->active_type = 1;
        $task->save();

        return redirect()->back();
    }

    public function stop($id)
    {
        $task = Tasks::whereId($id)->first();
        $task->active_type = 2;
        $task->save();

        return redirect()->back();
    }

    public function reserved($id)
    {
        $task = Tasks::whereId($id)->first();
        $task->reserved = 0;
        $task->save();

        return redirect()->back();
    }

    public function startDelivery($id)
    {
        $task = Tasks::whereId($id)->first();
        $task->need_send = 1;
        $task->save();

        return redirect()->back();
    }

    public function stopDelivery($id)
    {
        $task = Tasks::whereId($id)->first();
        $task->need_send = 0;
        $task->save();

        return redirect()->back();
    }

    public function changeDeliveryInfo(Request $request)
    {
        $skype_text = $request->get("skype_text");
        $mail_subj = $request->get("mail_subject");
        $mail_text = $request->get("mail_text");

        if(isset($skype_text)){
            $skype = TemplateDeliverySkypes::where("task_id", "=", $request->get("delivery_id"))->first();
            if(empty($skype)){
                $skype = new TemplateDeliverySkypes;
                $skype->task_id = $request->get("delivery_id");
            }
            $skype->text = $skype_text;
            $skype->save();
        }

        if(isset($mail_subj)){
            $mail = TemplateDeliveryMails::where("task_id", "=", $request->get("delivery_id"))->first();

            if(empty($mail)){
                $mail = new TemplateDeliveryMails;
                $mail->task_id = $request->get("delivery_id");
            }

            $mail->subject = $mail_subj;

            if(isset($mail_text)){
                $mail->text = $mail_text;
            }

            $mail->save();
        }

        return redirect()->back();
    }

    public function getCsv($id)
    {

            $table = SearchQueries::where('task_id', '=', $id)->get()->toArray();

            if(count($table) > 0){
                $file = fopen('search_queries_result.csv', 'w');
                foreach ($table as $row) {
                    $tmp = array_shift($row);
                    fputcsv($file, $row);
                }
                fclose($file);

                return response()->download('search_queries_result.csv');
            }else{
                return redirect()->back();
            }

    }

    public function testingDeliveryMails()
    {
        return view("parsing_tasks.testingDeliveryMails");
    }

    public function storeTestingDeliveryMails(Request $request)
    {
        //Записываем в таблицу тасков
        $task = new Tasks();
            $task->task_type_id = 3;
            $task->task_query = "Тестовая рассылка";
            $task->active_type = 1;
            $task->reserved = 0;
            $task->google_offset = 0;
            $task->need_send = 1;
        $task->save();
        $task_id = $task->id;
        //Записываем в таблицу тасков

        //Обрабатываем список сайтов, если есть
        $mails_list = $request->get('mails_list');
        if(!empty($mails_list)){

            $mails = explode("\r\n", $mails_list);
            $mails_number = count($mails);
            $tmp_mail = [];

            if($mails_number > 3){
                foreach ($mails as $key => $value){
                    ++$key;
                    $tmp_mail[] = $value;

                    if($key % 3 == 0){
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
                    if($key == $mails_number){
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
            }else{
                foreach ($mails as $item){
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
        if(!empty($request->get('subject')) && !empty($request->get('mails_text'))){
            $mails = new TemplateDeliveryMails;
            $mails->subject = $request->get('subject');
            $mails->text = $request->get('mails_text');
            $mails->task_id = $task_id;
            $mails->save();
        }
        //Записываем в таблицу шаблонов mails

        //Записываем в таблицу шаблонов вложений для mails
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file){
                $filename = uniqid('mail_'.$mails->id, true) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('mail_files', $filename);

                $file_model = new TemplateDeliveryMailsFiles;
                $file_model->mail_id = $mails->id;
                $file_model->name = $filename;
                $file_model->path = 'mail_files/'.$filename;
                $file_model->save();

                unset($file_model);
            }
        }
        //Записываем в таблицу шаблонов вложений для mails

        return redirect()->route('parsing_tasks.index');
    }


}

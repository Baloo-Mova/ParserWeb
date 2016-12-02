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
        $data = Tasks::all();
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
                    $site_links->reserved = 1;
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

        //Записываем в таблицу шаблонов mais
        if(!empty($request->get('subject'))){
            $skype = new TemplateDeliverySkypes();
                $skype->text = $request->get('skype_text');
                $skype->task_id = $task->id;
            $skype->save();
        }
        //Записываем в таблицу шаблонов mais

        return redirect()->route('parsing_tasks.index');
    }

    public function show($id)
    {
        $task = Tasks::whereId($id)->first();
        $mails = $task->getMail()->first();
        $skype = $task->getSkype()->first();
        $search_queries = SearchQueries::where(['task_id' => $id])->orderBy('id', 'desc')->get();

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
        //$api = new Api('127.0.0.1', port, 'user', 'pass' );
        //$api->startProcess('myworker');
        $task = Tasks::whereId($id)->first();
        $task->active_type = 1;
        $task->save();

        return redirect()->back();
    }

    public function stop($id)
    {
        //$api = new Api('127.0.0.1', port, 'user', 'pass' );
        //$api->stopProcess('myworker');
        $task = Tasks::whereId($id)->first();
        $task->active_type = 2;
        $task->save();

        return redirect()->back();
    }
}

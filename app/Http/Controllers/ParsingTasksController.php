<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TasksType;
use App\Models\Tasks;
use App\Models\TemplateDeliveryMails;
use App\Models\TemplateDeliverySkypes;
use App\Models\TemplateDeliveryMailsFiles;
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
        $task->save();
        //Записываем в таблицу тасков

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

        //$api = new Api('127.0.0.1', , '', '' );
        //$task_info = $api->getProcessInfo('myworker');

        return view('parsing_tasks.show', [
            'data' => $task,
            'task_info' => "STOPPED",
            'mails' => $mails,
            'skype' => $skype
        ]);
    }

    public function start()
    {
        $api = new Api('127.0.0.1', port, 'user', 'pass' );
        $api->startProcess('myworker');

        return redirect()->back();
    }

    public function stop()
    {
        $api = new Api('127.0.0.1', port, 'user', 'pass' );
        $api->stopProcess('myworker');

        return redirect()->back();
    }
}

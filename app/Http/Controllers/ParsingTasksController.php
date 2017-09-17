<?php

namespace App\Http\Controllers;

use App\Models\Contacts;
use App\Models\DeliveryData;
use App\Models\TaskGroups;
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

class ParsingTasksController extends Controller
{

    public function index()
    {
        $data = TaskGroups::orderBy('id', 'desc')->paginate(config('config.accountsdatapaginate'));

        return view('parsing_tasks.index', ['data' => $data]);
    }

    public function create()
    {
        $types = TasksType::all();

        return view('parsing_tasks.create', ['types' => $types]);
    }

    public function store(Request $request)
    {
        $taskTypeId = $request->get('task_type_id');
        $tasksQueries = $request->get('task_query');
        $site_list = $request->get('site_list');

        if ($taskTypeId == 1 && empty($tasksQueries)) {
            Toastr::error("Вы не указали поисковые запросы!");
            return back();
        }

        if ($taskTypeId == 2 && empty($site_list)) {
            Toastr::error("Вы не указали список сайтов!");
            return back();
        }

        $taskGroup = new TaskGroups();
        $taskGroup->name = "";
        $taskGroup->need_send = $request->get('send_directly') != null;
        $taskGroup->active_type = 2;

        if ($taskTypeId == 1) {
            $tasksQueries = explode("\r\n", $tasksQueries);
            $taskGroup->name = $tasksQueries[0];
            $taskGroup->save();
            $tasks = [];
            foreach ($tasksQueries as $task) {

                if (empty($task)) {
                    continue;
                }

                $tasks[] = [
                    'task_type_id' => TasksType::WORD,
                    'task_query' => $task,
                    'task_group_id' => $taskGroup->id
                ];

                if (count($tasks) > 999) {
                    Tasks::insert($tasks);
                    $tasks = [];
                }
            }

            if (count($tasks) > 0) {
                Tasks::insert($tasks);
                $tasks = [];
            }
        } else {
            $taskGroup->name = "Список сайтов";
            $taskGroup->save();

            $task = new Tasks();
            $task->task_query = "*";
            $task->task_type_id = TasksType::SITES;
            $task->task_group_id = $taskGroup->id;
            $task->save();

            $sites = explode("\r\n", $site_list);
            foreach ($sites as $item) {
                $site_links = new SiteLinks;
                $site_links->task_id = $task->id;
                $site_links->task_group_id = $taskGroup->id;
                $site_links->link = $item;
                $site_links->reserved = 0;
                $site_links->save();
            }
        }

        $sendData = new DeliveryData();
        $sendData->task_group_id = $taskGroup->id;
        $sendData->payload = json_encode($request->get('data'));
        $sendData->save();

        return redirect()->route('parsing_tasks.index');
    }

    private function checkSymbolsInText($text)
    {
        $f = substr_count($text, "{");
        $s = substr_count($text, "}");

        if ($f == $s) {
            return true;
        } else {
            return false;
        }
    }

    public function show($id)
    {
        $task = TaskGroups::with(['deliveryData', 'getTenTasks'])->find($id);
        if (!isset($task)) {
            abort(404);
        }


        $tasks = $task->getTenTasks->toArray();
        $sendInfo = Contacts::selectRaw('sum(sended = 1) as count_sended, sum(sended = 0) as count_need_send, type')->groupBy('type')->where('task_group_id', '=', $task->id)->get();
        $taskInfo = $task->tasks;
        return view('parsing_tasks.show', [
            'data' => $task,
            'send' => json_decode($task->deliveryData->payload, true),
            'taskInfo' => $taskInfo,
            'sendInfo' => $sendInfo
        ]);
    }

    public function start($id)
    {
        $task = TaskGroups::whereId($id)->first();
        $task->active_type = 1;
        $task->save();

        return redirect()->back();
    }

    public function stop($id)
    {

        $task_group = TaskGroups::find($id);
        $task_group->active_type = 2;
        $task_group->save();


        return redirect()->back();
    }

    public function reserved($id)
    {
        $task = Tasks::whereId($id)->first();
        // $task->reserved = 0;
        $task->save();

        return redirect()->back();
    }

    public function startDelivery($id)
    {
        $task = TaskGroups::whereId($id)->first();
        $task->need_send = 1;
        $task->save();

        return redirect()->back();
    }

    public function stopDelivery($id)
    {
        $task = TaskGroups::whereId($id)->first();
        $task->need_send = 0;
        $task->save();

        return redirect()->back();
    }

    public function changeDeliveryInfo(Request $request)
    {
        $deliveryData = DeliveryData::where('task_group_id', '=', $request->get('task_group_id'))->first();
        if (isset($deliveryData)) {
            $deliveryData->payload = json_encode($request->get('data'));
            $deliveryData->save();
        }

        return redirect()->back();
    }

    public function getCsv($id)
    {
        set_time_limit(0);
        $table = SearchQueries::where(['task_group_id' => $id])->get();

        $cols = [];
        $res = [];
        $i = 0;

        if (count($table) > 0) {
            if (!file_exists(storage_path('app/csv/'))) {
                mkdir(storage_path('app/csv/'));
            }

            $file = fopen(storage_path('app/csv/') . "parse_result_" . $id . ".csv", 'w');

            $cols[0] = "link";
            $cols[1] = "name";
            $cols[2] = "city";
            $cols[3] = "phones";
            $cols[4] = "skypes";
            $cols[5] = "emails";
            $cols[6] = "vk_id";
            $cols[7] = "ok_id";

            fputcsv($file, $cols, ";");
            foreach ($table as $row) {
                $res = [];

                $res[0] = $row->link === null ? "" : $this->icv($row->link);
                $res[1] = $row->name === null ? "" : $this->icv($row->name);
                $res[2] = $row->city === null ? "" : $this->icv($row->city);

                $cdata = json_decode($row->contact_data);

                if (isset($cdata->phones) && count($cdata->phones) > 0) {
                    $res[3] = implode(",", $cdata->phones);
                } else {
                    $res[3] = "";
                }

                if (isset($cdata->skypes) && count($cdata->skypes) > 0) {
                    $res[4] = $this->icv(implode(",", $cdata->skypes));
                } else {
                    $res[4] = "";
                }

                if (isset($cdata->emails) && count($cdata->emails) > 0) {
                    $res[5] = implode(",", $cdata->emails);
                } else {
                    $res[5] = "";
                }

                $res[6] = !isset($cdata->vk_id) ? "" : $this->icv($cdata->vk_id);
                $res[7] = !isset($cdata->ok_id) ? "" : $this->icv($cdata->ok_id);

                fputcsv($file, $res, ';');
            }
            fclose($file);

            return response()->download(storage_path('app/csv/') . 'parse_result_' . $id . '.csv');
        } else {
            return redirect()->back();
        }
    }

    private function icv($str)
    {
        $res = "=\"" . iconv("UTF-8", "Windows-1251//IGNORE", $str) . "\"";
        return $res;
    }

    public function getFromCsv(Request $request)
    {
        if ($request->hasFile('myfile')) {
            $fe = explode(".", $request->myfile->getClientOriginalName());
            if ($fe[1] != "csv") {
                return redirect()->back();
            }
        } else {
            return redirect()->back();
        }

        if (($file = fopen($request->myfile->getRealPath(), 'r')) == false) {
            return redirect()->back();
        }

        $task_id = $request->get('task_id');

        $row = 1;
        $res = [];

        $cols = [];

        $contacts = [];
        $noCols = [];

        foreach (fgetcsv($file, 1000, ";") as $key => $item) {
            if ($item == "mails") {
                $noCols["mails"] = $key;
                continue;
            }
            if ($item == "phones") {
                $noCols["phones"] = $key;
                continue;
            }
            if ($item == "skypes") {
                $noCols["skypes"] = $key;
                continue;
            }
            $cols[] = $item;
        }

        while (($data = fgetcsv($file, 1000, ";")) !== false) {

            $num = count($data);
            $res = [];
            $contacts = [];

            for ($c = 0; $c < $num; $c++) {
                if ($c != $noCols["mails"] && $c != $noCols["phones"] && $c != $noCols["skypes"]) {
                    $res[$cols[$c]] = ($data[$c] == '') ? null : str_replace(["\"", "="], "", $data[$c]);
                    $res["task_id"] = $task_id;
                }
            }

            $ins = SearchQueries::create($res);

            // mails
            $tmp_res = trim(str_replace(["\"", "="], "", $data[$noCols["mails"]]));
            if ($tmp_res != '') {
                $tmp = explode(",", $tmp_res);
                if (count($tmp) > 1) {
                    foreach ($tmp as $i) {
                        $contacts[] = ["value" => $i, "type" => 1, "search_queries_id" => $ins->id];
                    }
                } else {
                    $contacts[] = ["value" => $tmp[0], "type" => 1, "search_queries_id" => $ins->id];
                }
            }
            //mails

            //phones
            $tmp_res = trim(str_replace(["\"", "="], "", $data[$noCols["phones"]]));
            if ($tmp_res != '') {
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
            if ($tmp_res != '') {
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

    public function testingDeliveryMails()
    {
        $user_id = Auth::user()->id;

        return view("parsing_tasks.testingDeliveryMails");
    }

    public function storeTestingDeliveryMails(Request $request)
    {
        if (!$this->checkSymbolsInText($request->get('mails_text'))) {
            Toastr::error("В тексте Contacts найдены недопустимые символы!", $title = "Ошибка!", $options = []);

            return back();
        }


        $taskGroup = new TaskGroups();
        $taskGroup->name = "Тестовая рассылка Mails";
        $taskGroup->active_type = 2;
        $taskGroup->need_send = 1;
        $taskGroup->save();

        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "*";
        $task->task_group_id = $taskGroup->id;
        $task->save();

        $mails_list = $request->get('mails_list');
        if (!empty($mails_list)) {
            $mails = explode("\r\n", $mails_list);
            $contacts = [];

            $search_query = new SearchQueries;
            $search_query->link = "Тестовая рассылка Email";
            $search_query->contact_from = "Добавлен в ручную";
            $search_query->task_id = $task->id;
            $search_query->task_group_id = $taskGroup->id;
            $search_query->contact_data = json_encode(['emails' => $mails]);
            $search_query->save();

            foreach ($mails as $mailItem) {
                $contacts[] = ["value" => $mailItem, "type" => Contacts::MAILS, "task_id" => $task->id, 'task_group_id' => $taskGroup->id];
            }

            Contacts::insert($contacts);
            $contacts = [];
        }

        DeliveryData::insert([
            'payload' => json_encode([
                'skype' => [
                    'text' => ""
                ],
                'ok' => [
                    'text' => ''
                ],
                'vk' => [
                    'text' => '',
                    'media' => ''
                ],
                'mail' => [
                    'text' => $request->get('mails_text'),
                    'subject' => ''
                ], 'viber' => [
                    'text' => ''
                ], 'whatsapp' => [
                    'text' => ''
                ],
            ]),
            'task_group_id' => $taskGroup->id
        ]);

        return redirect()->route('parsing_tasks.index');
    }

    public function testingDeliverySkypes()
    {
        return view("parsing_tasks.testingDeliverySkypes");
    }

    public function storeTestingDeliverySkypes(Request $request)
    {
        if (!$this->checkSymbolsInText($request->get('skypes_text'))) {
            Toastr::error("В тексте Skype найдены недопустимые символы!", $title = "Ошибка!", $options = []);

            return back();
        }

        $taskGroup = new TaskGroups();
        $taskGroup->name = "Тестовая рассылка Skype";
        $taskGroup->active_type = 2;
        $taskGroup->need_send = 1;
        $taskGroup->save();

        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "*";
        $task->task_group_id = $taskGroup->id;
        $task->save();

        $skypes_list = $request->get('skypes_list');
        if (!empty($skypes_list)) {
            $skypes = explode("\r\n", $skypes_list);

            $search_query = new SearchQueries;
            $search_query->link = "Тестовая рассылка SKYPE";
            $search_query->task_id = $task->id;
            $search_query->task_group_id = $taskGroup->id;
            $search_query->contact_from = "Добавлен в ручную";
            $search_query->contact_data = json_encode(['skypes' => $skypes]);
            $search_query->save();
            $contacts = [];

            foreach ($skypes as $item) {
                $contacts[] = ["value" => $item, "type" => Contacts::SKYPES, "task_id" => $task->id, 'task_group_id' => $taskGroup->id];
            }

            Contacts::insert($contacts);
        }

        if (!empty($request->get('skypes_text'))) {
            DeliveryData::insert([
                'payload' => json_encode([
                    'skype' => [
                        'text' => $request->get('skypes_text')
                    ],
                    'ok' => [
                        'text' => ''
                    ],
                    'vk' => [
                        'text' => '',
                        'media' => ''
                    ],
                    'mail' => [
                        'text' => '',
                        'subject' => ''
                    ], 'viber' => [
                        'text' => ''
                    ], 'whatsapp' => [
                        'text' => ''
                    ],
                ]),
                'task_group_id' => $taskGroup->id
            ]);
        }

        return redirect()->route('parsing_tasks.index');
    }

    public function testingDeliveryVK()
    {
        return view("parsing_tasks.testingDeliveryVK");
    }

    public function storeTestingDeliveryVK(Request $request)
    {
        if (!$this->checkSymbolsInText($request->get('vk_text'))) {
            Toastr::error("В тексте Skype найдены недопустимые символы!", $title = "Ошибка!", $options = []);

            return back();
        }

        $taskGroup = new TaskGroups();
        $taskGroup->name = "Тестовая рассылка VK";
        $taskGroup->active_type = 2;
        $taskGroup->need_send = 1;
        $taskGroup->save();

        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "*";
        $task->task_group_id = $taskGroup->id;
        $task->save();

        $skypes_list = $request->get('vk_list');
        if (!empty($skypes_list)) {
            $skypes = explode("\r\n", $skypes_list);
            $contacts = [];

            foreach ($skypes as $item) {
                $search_query = new SearchQueries;
                $search_query->link = "Тестовая рассылка VK";
                $search_query->task_id = $task->id;
                $search_query->task_group_id = $taskGroup->id;
                $search_query->contact_from = "Добавлен в ручную";
                $search_query->contact_data = json_encode(['vk_id' => $item]);
                $search_query->save();

                $contacts[] = ["value" => $item, "type" => Contacts::VK, "task_id" => $task->id, 'task_group_id' => $taskGroup->id];
            }

            Contacts::insert($contacts);
        }

        if (!empty($request->get('vk_text'))) {
            DeliveryData::insert([
                'payload' => json_encode([
                    'skype' => [
                        'text' => ""
                    ],
                    'ok' => [
                        'text' => ''
                    ],
                    'vk' => [
                        'text' => $request->get('vk_text'),
                        'media' => ''
                    ],
                    'mail' => [
                        'text' => '',
                        'subject' => ''
                    ], 'viber' => [
                        'text' => ''
                    ], 'whatsapp' => [
                        'text' => ''
                    ],
                ]),
                'task_group_id' => $taskGroup->id
            ]);
        }


        return redirect()->route('parsing_tasks.index');
    }

    public function testingDeliveryOK()
    {
        return view("parsing_tasks.testingDeliveryOK");
    }

    public function storeTestingDeliveryOK(Request $request)
    {
        if (!$this->checkSymbolsInText($request->get('ok_text'))) {
            Toastr::error("В тексте Одноклассники найдены недопустимые символы!", $title = "Ошибка!", $options = []);

            return back();
        }
        $taskGroup = new TaskGroups();
        $taskGroup->name = "Тестовая рассылка OK";
        $taskGroup->active_type = 2;
        $taskGroup->need_send = 1;
        $taskGroup->save();

        $task = new Tasks();
        $task->task_type_id = 3;
        $task->task_query = "*";
        $task->task_group_id = $taskGroup->id;
        $task->save();

        $skypes_list = $request->get('ok_list');
        if (!empty($skypes_list)) {
            $skypes = explode("\r\n", $skypes_list);
            $contacts = [];
            foreach ($skypes as $item) {
                $search_query = new SearchQueries;
                $search_query->link = "Тестовая рассылка OK";
                $search_query->task_id = $task->id;
                $search_query->task_group_id = $taskGroup->id;
                $search_query->contact_from = "Добавлен в ручную";
                $search_query->contact_data = json_encode(['ok_id' => $skypes]);
                $search_query->save();
                $contacts[] = ["value" => $item, "type" => Contacts::OK, "task_id" => $task->id, 'task_group_id' => $taskGroup->id];
            }

            Contacts::insert($contacts);
        }

        if (!empty($request->get('ok_text'))) {
            DeliveryData::insert([
                'payload' => json_encode([
                    'skype' => [
                        'text' => ""
                    ],
                    'ok' => [
                        'text' => $request->get('ok_text')
                    ],
                    'vk' => [
                        'text' => '',
                        'media' => ''
                    ],
                    'mail' => [
                        'text' => '',
                        'subject' => ''
                    ], 'viber' => [
                        'text' => ''
                    ], 'whatsapp' => [
                        'text' => ''
                    ],
                ]),
                'task_group_id' => $taskGroup->id
            ]);
        }

        return redirect()->route('parsing_tasks.index');
    }

    public function testingDeliveryTW()
    {
        return view("parsing_tasks.testingDeliveryTW");
    }

    public function storeTestingDeliveryTW(Request $request)
    {

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

    public function testingDeliveryFB()
    {
        return view("parsing_tasks.testingDeliveryFB");
    }

    public function storeTestingDeliveryFB(Request $request)
    {
        if (!$this->checkSymbolsInText($request->get('fb_text'))) {
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

    public function testingDeliveryAndroidBots()
    {
        return view("parsing_tasks.testingDeliveryAndroidBots");
    }

    public function storeTestingDeliveryAndroidBots(Request $request)
    {
        if (!$this->checkSymbolsInText($request->get('viber_text'))) {
            Toastr::error("В тексте Viber найдены недопустимые символы!", $title = "Ошибка!", $options = []);

            return back();
        }
        if (!$this->checkSymbolsInText($request->get('whats_text'))) {
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
            $phones_list = str_replace(["+"], "", $phones_list);
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

            foreach ($phones_list as $ph) {
                $contacts[] = ["type" => 2, "value" => $ph, "search_queries_id" => $search_query->id];
            }

            Contacts::insert($contacts);
        }

        //Записываем в таблицу шаблонов skypes
        $viber_text = $request->get('viber_text');
        if (!empty($viber_text)) {

            $viber_text = str_pad($viber_text, strlen($viber_text) + 5 * 2, "\r\n", STR_PAD_RIGHT);

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

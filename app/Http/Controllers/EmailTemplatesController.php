<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailTemplates;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AndroidBots;

class EmailTemplatesController extends Controller
{
    public function index()
    {
        $user_id = Auth::user()->id;
        $email_templates = EmailTemplates::where(['user_id'=>$user_id])->paginate(config('config.accountsdatapaginate'));

        return view("email_templates.index", ["data" => $email_templates]);
    }

    public function create()
    {
        return view("email_templates.create");
    }

    public function store(Request $request)
    {
       // dd($request);

        $email_templates = new EmailTemplates;
        $email_templates->fill($request->all());
        $email_templates->user_id = Auth::user()->id;
        //$skype->valid = 1;
        $email_templates->save();
        

        return redirect()->route('email_templates.index');
    }

    public function edit($id)
    {
        $data = EmailTemplates::whereId($id)->first();
        //dd(addcslashes($data->body,quot;));
       $tmp= explode("{{++}}",$data->body);
        $data->body=$tmp[0];
       //dd($tmp);
        return view("email_templates.edit", ["data" => $data, "glcolor"=>$tmp[1]]);
    }

    public function update(Request $request, $id)
    {
        $data = EmailTemplates::whereId($id)->first();
        $data->fill($request->all());
        $data->save();

        return redirect()->route('email_templates.index');
    }





    public function delete($id)
    {
        $data = EmailTemplates::whereId($id)->first();
        $data->delete();

        return redirect()->back();
    }

    public function destroyEmailsTemplates()
    {

        DB::table('email_templates')->delete();

        return redirect()->route('email_templates.index');
    }
}

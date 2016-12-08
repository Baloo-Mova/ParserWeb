<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SkypeLogins;
use App\MyFacades\SkypeClassFacade;

class SkypesAccountsController extends Controller
{
    public function index()
    {
        $skypes = SkypeLogins::paginate(config('config.accountsdatapaginate'));

        return view("skypes_accounts.index", ["data" => $skypes]);
    }

    public function create()
    {
        return view("skypes_accounts.create");
    }

    public function store(Request $request)
    {
        
        
        $skype = new SkypeLogins;
        $skype->fill($request->all());
        $skype->valid=1;
        $skype->save();
       SkypeClassFacade::index($request->get('login'),$request->get('password'));
      
       $skype = SkypeLogins::where(['login'=>$request->get('login')])->first();
       if($skype->valid==0){
          $skype->delete();
           }
        return redirect()->route('skypes_accounts.index');
    }

    public function edit($id)
    {
        $data = SkypeLogins::whereId($id)->first();
        return view("skypes_accounts.edit", ["data" => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = SkypeLogins::whereId($id)->first();
        $data->fill($request->all());
        $data->save();

        return redirect()->route('skypes_accounts.index');
    }

    public function massupload(Request $request)
    {
        if(!(empty($request->get('text')))){
            $accounts = explode("\r\n", $request->get('text'));
            $this->textParse(array_filter($accounts));
        }else{
            if ($request->hasFile('text_file')) {
                $filename = uniqid('skype_logins_file', true) . '.' . $request->file('text_file')->getClientOriginalExtension();
                $request->file('text_file')->storeAs('tmp_files', $filename);
                $file = file(storage_path(config('config.tmp_folder')).$filename);
                $this->textParse($file);
            }
        }

        return redirect()->route('skypes_accounts.index');
    }

    public function textParse($data)
    {
        foreach ($data as $line){
            $tmp = explode(":", $line);
                $skype = new SkypeLogins;
                $skype->login = $tmp[0];
                $skype->password = $tmp[1];
                $skype->valid = 1;
                $skype->save();
                SkypeClassFacade::index($skype->login,$skype->password);
      
       $skype2 = SkypeLogins::where(['login'=> $tmp[0]])->first();
       //dd($skype2);
       if($skype2->valid==0){
          $skype2->delete();
           }
            unset($tmp);
            
            
        }
    }

    public function delete($id)
    {
        $data = SkypeLogins::whereId($id)->first();
        $data->delete();

        return redirect()->back();
    }
}

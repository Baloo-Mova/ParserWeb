<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SkypeLogins;

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
        $skype->save();
        return redirect()->route('skypes_accounts.index');
    }

    public function massupload(Request $request)
    {
        if(!(empty($request->get('text')))){
            $accounts = explode("\r\n", $request->get('text'));

            
            $this->mailsParse($accounts, $request->get('user_id'));
        }else{
            if ($request->hasFile('text_file')) {
                $filename = uniqid('smtp_file', true) . '.' . $request->file('text_file')->getClientOriginalExtension();
                $request->file('text_file')->storeAs('tmp_files', $filename);
                $file = file(storage_path(config('config.tmp_folder')).$filename);
                $this->mailsParse($file, $request->get('user_id'));
            }
        }

        return redirect()->route('accounts_data.emails');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccountsData;

class AccountsDataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = AccountsData::paginate(config('config.accountsdatapaginate'));
        return view('accounts_data.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('accounts_data.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = new AccountsData;
        $data->fill($request->all());
        if(!(empty($request->get("smtp_port")))){
            $data->smtp_port = $request->get("smtp_port");
        }
        if(!(empty($request->get("smtp_address")))){
            $data->smtp_address = $request->get("smtp_address");
        }
        $data->save();

        return redirect()->route('accounts_data.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = AccountsData::whereId($id)->first();
        $data->fill($request->all());
        if(!(empty($request->get("smtp_port")))){
            $data->smtp_port = $request->get("smtp_port");
        }
        if(!(empty($request->get("smtp_address")))){
            $data->smtp_address = $request->get("smtp_address");
        }
        $data->save();

        return redirect()->route('accounts_data.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $data = AccountsData::whereId($id)->first();
        $data->delete();

        return redirect()->route('accounts_data.index');
    }
}

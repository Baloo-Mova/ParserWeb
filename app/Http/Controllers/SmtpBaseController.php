<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SmtpBase;

class SmtpBaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = SmtpBase::paginate(config('config.smtpbasepaginate'));
        return view('smtpbase.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('smtpbase.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $smtpbase = new SmtpBase();
        $smtpbase->fill($request->all());
        $smtpbase->save();

        return redirect()->route('smtpbase.index');
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
        $data = SmtpBase::whereId($id)->first();
        return view('smtpbase.edit', ['data' => $data]);
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
        $smtpbase = SmtpBase::whereId($id)->first();
        $smtpbase->fill($request->all());
        $smtpbase->save();

        return redirect()->route('smtpbase.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $smtpbase = SmtpBase::whereId($id)->first();
        $smtpbase->delete();

        return redirect()->route('smtpbase.index');
    }
}

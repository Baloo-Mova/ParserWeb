<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Settings;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Settings::whereId(1)->first();

        return view("settings.index", ["data" => $settings]);
    }

    public function store(Request $request)
    {
        $settings = Settings::whereId(1)->first();
        if(empty($settings)){
            $settings = new Settings;
        }
        $settings->fill($request->all());
        $settings->save();

        return redirect()->route('settings.index');
    }

}

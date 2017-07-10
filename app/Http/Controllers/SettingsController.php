<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Settings;
//for create Programms configs 
use App\Models\ProcessConfigs;
use App\Models\Processes;
use Mockery\Exception;
use Supervisor\Configuration\Configuration;
//use Supervisor\Configuration\Section\Supervisord;
use Supervisor\Configuration\Section\Program;
use Supervisor\Configuration\Writer;
use Supervisor\Configuration\Exception\WriterException;
use Indigo\Ini\Renderer;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Supervisor\Api;

class SettingsController extends Controller {

    public function index() {
        $settings = Settings::whereId(1)->first();
        $configs = ProcessConfigs::orderBy('name', 'desc')->get();
		
       //$processes = Processes::orderBy('id', 'desc')->get();
        //for supervisors
        try {
            $supervisor = new \Supervisor\Api('127.0.0.1', 9001, 'admin', 'admin');
            $processestmp = $supervisor->getAllProcessInfo();
            Processes::truncate();
            foreach ($processestmp as $proc) {
                //dd($proc);
                $dbproc = Processes::where(["name" => $proc["name"]])->orderBy('id', 'desc')->first();

                if ($dbproc == null) {
                    $dbproc = new Processes();

                    //
                }
                $dbproc->fill($proc);
                $dbproc->groupname = $proc["group"];
                $dbproc->errorlog = $proc["stderr_logfile"];
                $dbproc->outlog = $proc["stdout_logfile"];
                //$dbproc->created_at = date("Y-m-d H:i:s", $proc["start"]);
                $dbproc->updated_at = date("Y-m-d H:i:s", $proc["now"]);
                //dd($dbproc);
                $dbproc->save();

            }
            // dd($processes);
            $processes = Processes::where(['statename' => 'RUNNING'])->orwhere(['statename' => 'STARTING'])->orderBy('id', 'desc')->get();


            $windows = false;
        }catch(\Exception $ex){
            $processes = [];
            $windows = true;
        }

        $counters = array();
        foreach ($configs as $conf) {
            $counters += [$conf->name => Processes::where(['groupname' => $conf->name])->where(['statename' => 'RUNNING'])->groupBy('groupname')->count()];

        }
		
        return view("settings.index", [
            "data" => $settings,
            "windows" => $windows,
            "configs" => $configs,
            "processes" => $processes,
            "counters" =>$counters
        ]);
    }

    public function store(Request $request) {
        $settings = Settings::whereId(1)->first();
        if (empty($settings)) {
            $settings = new Settings;
        }
        $settings->fill($request->all());
        $settings->save();

        return redirect()->route('settings.index');
    }

    public function config_store(Request $conf_req) {
        //for write to DB
        //  dd($conf_req);
        $configDB = new ProcessConfigs();
        $configDB->name = $conf_req->get('name_proc');
        $configDB->description = $conf_req->get('description_config');
        $configDB->numprocs = $conf_req->get('numprocs');
        $configDB->save();
        $configDB->path_config = storage_path('app/') . "supervisor/" . $configDB->id . $configDB->name . ".ini";
        $configDB->save();

        $ini_array = parse_ini_string("[program:" . $configDB->name . "]\r\n" . $configDB->description); //parse_ini_string("[program:".$configDB->name."]\r\n".$configDB->description);
        //for write to file
        $config = new Configuration;
        $renderer = new Renderer;
//
////$section = new Supervisord(['identifier' => 'supervisor']);
////$config->addSection($section);
//
        $section = new Program('test', $ini_array);
        $config->addSection($section);
        $section->setProperty('numprocs', intval($conf_req->get('numprocs')));
        $section->setProperty('autostart', false);
        $section->setProperty('autorestart', true);




        $config->addSection($section);


        $adapter = new Local(storage_path('app/') . 'supervisor/');
        $filesystem = new Filesystem($adapter);

        $writer = new \League\Flysystem\File($filesystem, $configDB->id . $configDB->name . ".ini");

        if ($writer->exists()) {
            $writer->update($renderer->render($config->toArray()));
        } else {
            $writer->write($renderer->render($config->toArray()));
        }



        return redirect()->route('settings.index');
    }
    
    public function config_edit(Request $request, ProcessConfigs $config) {
   
        //dd($request);
        $config->numprocs = $request->get('numprocs');
        $config->save();
        $supervisor = new \Supervisor\Api('127.0.0.1', 9001, 'admin', 'admin');
         
		 $ini_array = parse_ini_string("[program:" . $config->name . "]\r\n" . $config->description); //parse_ini_string("[program:".$configDB->name."]\r\n".$configDB->description);
        //for write to file
        $configFile = new Configuration;
        $renderer = new Renderer;
//
////$section = new Supervisord(['identifier' => 'supervisor']);
////$config->addSection($section);
//
         try{
		$supervisor->stopAllProcesses();	 
	    }catch(\Supervisor\ApiException $e){ }        
        $section = new Program($config->name, $ini_array);
        $configFile->addSection($section);
        $section->setProperty('numprocs', intval($config->numprocs));
        $section->setProperty('autostart', false);
        $section->setProperty('autorestart', true);
        $configFile->addSection($section);
        $adapter = new Local(storage_path('app/') . 'supervisor/');
        $filesystem = new Filesystem($adapter);
        $writer = new \League\Flysystem\File($filesystem, $config->id . $config->name . ".ini");

        if ($writer->exists()) {
            $writer->update($renderer->render($configFile->toArray()));
        } else {
            $writer->write($renderer->render($configFile->toArray()));
        }

		 
		 try {
        $supervisor->restart();
        //$supervisor->startAllProcesses($wait=true);
        
        }
       catch (\Supervisor\ApiException $e){
       }        
       //$processtmp = $supervisor->getProcessInfo($process->name);
        
        return redirect()->route('settings.index');
    }

    public function proc_start(ProcessConfigs $process) {
    //dd($process);
        $supervisor = new \Supervisor\Api('127.0.0.1', 9001, 'admin', 'admin');
         try {
        $supervisor->startProcessGroup($process->name, $wait=true);
        }
       catch (\Supervisor\ApiException $e){
		   //dd($e);
       }        
       //$processtmp = $supervisor->getProcessInfo($process->name);
        

        //$process->statename = "RUNNING";
        //$process->save();
        return redirect()->route('settings.index');
    }

    public function proc_stop(ProcessConfigs $process) {
   $supervisor = new \Supervisor\Api('127.0.0.1', 9001, 'admin', 'admin');
         try {
        $supervisor->stopProcessGroup($process->name, $wait=true);
        }
       catch (\Supervisor\ApiException $e){
       }        
       //$processtmp = $supervisor->getProcessInfo($process->name);
        //$process->statename = "STOPPED";
        //$process->save();
        return redirect()->route('settings.index');
    }
    
    public function proc_startall() {
   $supervisor = new \Supervisor\Api('127.0.0.1', 9001, 'admin', 'admin');
        try {
       $supervisor->startAllProcesses($wait=true);
       }
       catch (\Supervisor\ApiException $e){
       }        
       //$processtmp = $supervisor->getProcessInfo($process->name);
        
        return redirect()->route('settings.index');
    } 
    public function proc_stopall() {
   $supervisor = new \Supervisor\Api('127.0.0.1', 9001, 'admin', 'admin');
         try {
        $supervisor->stopAllProcesses($wait=true); //stopProcessGroup("google",$wait=true);
        }
       catch (\Supervisor\ApiException $e){
		   dd($e);
       }        
       //$processtmp = $supervisor->getProcessInfo($process->name);
       
       return redirect()->route('settings.index');
    } 

     public function proc_restart() {
   //$supervisor = new \Supervisor\Api('127.0.0.1', 9001, 'admin', 'admin');
        // try {
        //$supervisor->restart();
        //$supervisor->startAllProcess($wait=true);
        //
        //}
       //catch (\Supervisor\ApiException $e){
       //}        
       //$processtmp = $supervisor->getProcessInfo($process->name);
        //$process->statename = "STOPPED";
        return redirect()->route('settings.index');
    } 
}

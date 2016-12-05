<?php

namespace App\Console\Commands;

use App\Models\SearchQueries;
use Illuminate\Console\Command;

class Tester extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        while(true){
          $q = new SearchQueries();
            $q->link = "Test link";
            $q->task_id = 1;
            $q->save();
            sleep(1);
        }
    }
}

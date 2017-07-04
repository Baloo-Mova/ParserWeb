<?php

namespace App\Console\Commands\Cleaner;

use Illuminate\Console\Command;
use App\Models\AccountsData;
class RefreshSenders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:senders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'refresh count_request for senders accounts';

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
        //
        AccountsData::where(['is_sender'=>1])->update(['count_request'=>0]);
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColsToSkypeloginsRenameColAccdata extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*skype_logins add login column*/
        Schema::table('skype_logins', function(Blueprint $table){
            $table->string('skype_id',300)->after('password')->nullable();

        });
        /*rename column process_id to reserved*/
        Schema::table('accounts_data', function(Blueprint $table){
            $table->renameColumn('process_id','reserved');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('skype_logins', function(Blueprint $table){
            $table->dropColumn('skype_id');
        });
        Schema::table('accounts_data', function(Blueprint $table){
            $table->renameColumn('reserved','process_id');

        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameColProxylogins extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('skype_logins', function(Blueprint $table){
            $table->renameColumn('process_id','reserved');
            $table->integer('count_request')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts_data', function(Blueprint $table){
            $table->renameColumn('process_id','reserved');
            $table->dropColumn('count_request');

        });
    }
}

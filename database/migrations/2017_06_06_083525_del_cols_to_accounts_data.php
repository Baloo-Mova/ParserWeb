<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DelColsToAccountsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function down()
    {

        /*skype_logins add login column*/
        Schema::table('accounts_data', function (Blueprint $table) {
            $table->integer('count_sended_messages')->after('instagram_reserved')->default(0);
            //$table->integer('email')->after('instagram_reserved')->default(0);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('accounts_data', function (Blueprint $table) {
            $table->dropColumn('count_sended_messages');
            //$table->dropColumn('email');


        });
    }
}

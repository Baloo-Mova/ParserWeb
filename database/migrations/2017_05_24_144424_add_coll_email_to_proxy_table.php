<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCollEmailToProxyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        /*skype_logins add login column*/
        Schema::table('proxy', function (Blueprint $table) {
            $table->integer('email_reserved')->after('instagram_reserved')->default(0);
            $table->integer('email')->after('instagram_reserved')->default(0);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('proxy', function (Blueprint $table) {
            $table->dropColumn('email_reserved');
            $table->dropColumn('email');


        });
    }
}

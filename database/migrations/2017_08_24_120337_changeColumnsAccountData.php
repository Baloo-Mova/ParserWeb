<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnsAccountData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts_data', function (Blueprint $table) {
            $table->dropColumn('ok_user_gwt');
            $table->dropColumn('ok_user_tkn');
            $table->dropColumn('vk_cookie');
            $table->dropColumn('ok_cookie');
            $table->dropColumn('tw_cookie');
            $table->dropColumn('tw_tkn');
            $table->dropColumn('fb_user_id');
            $table->dropColumn('fb_access_token');
            $table->dropColumn('fb_cookie');
            $table->dropColumn('ins_cookie');
            $table->dropColumn('ins_tkn');
            $table->dropColumn('api_key');

            $table->text('payload')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

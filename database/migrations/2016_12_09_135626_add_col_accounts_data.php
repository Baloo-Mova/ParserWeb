<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColAccountsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
        public function up()
    {
        Schema::table('accounts_data', function ($table) {
     $table->integer('count_sended_messages')->default(0);
     
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('accounts_data', function ($table) {
    $table->dropColumn('count_sended_messages');
    
});
    }
}

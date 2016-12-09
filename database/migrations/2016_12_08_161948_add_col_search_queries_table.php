<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColSearchQueriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('search_queries', function ($table) {
     $table->integer('sk_recevied')->default(0);
     $table->integer('sk_sended')->default(0);
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('search_queries', function ($table) {
    $table->dropColumn('sk_recevied');
    $table->dropColumn('sk_sended');
});
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteOldColsSearchQueries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('search_queries', function (Blueprint $table) {
            $table->dropColumn('mails');
            $table->dropColumn('phones');
            $table->dropColumn('skypes');
            $table->dropColumn('ins_user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('search_queries', function (Blueprint $table) {
            $table->text('mails')->nullable();
            $table->text('phones')->nullable();
            $table->text('skypes')->nullable();
            $table->string('ins_user_id', 255)->nullable();
        });
    }
}
